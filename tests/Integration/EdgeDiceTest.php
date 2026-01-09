<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ValidationException;

/**
 * Integration tests for Shadowrun Edge (Rule of Six) mechanic.
 *
 * Tests the edge mechanic where dice that meet the threshold
 * trigger additional dice to be added to the pool (not summed).
 * This is different from explode (which sums) and reroll (which replaces).
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Roller\DiceRoller
 * @covers \PHPDice\Model\DiceExpression
 * @covers \PHPDice\Model\RollResult
 */
class EdgeDiceTest extends BaseTestCaseMock
{
    /**
     * Test basic edge with default limit (100) and default threshold (max value)
     * Acceptance: "3d6 edge" rolls 3d6, edge triggers on 6, adds additional dice.
     */
    public function testEdgeWithDefaultLimitAndThreshold(): void
    {
        // First die: 4 (no edge)
        // Second die: 6 (triggers edge) -> edge die: 3 (no edge)
        // Third die: 2 (no edge)
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 6, 2, 3);

        $result = $this->phpdice->roll('3d6 edge');

        // Should have 4 dice total (3 original + 1 edge)
        $this->assertCount(4, $result->diceValues);
        $this->assertEquals([4, 6, 2, 3], $result->diceValues);

        // Total should be sum of all 4 dice
        $this->assertEquals(15, $result->total);

        // Check edge history
        $this->assertNotNull($result->edgeHistory);
        $this->assertArrayHasKey(1, $result->edgeHistory); // Second die (index 1) triggered edge
        $this->assertEquals([3], $result->edgeHistory[1]['rolls']); // Edge die rolled 3
        $this->assertEquals(1, $result->edgeHistory[1]['count']); // One edge die added
        $this->assertFalse($result->edgeHistory[1]['limitReached']); // Limit not reached
    }

    /**
     * Test edge with cascading (edge die also triggers edge)
     * Acceptance: Edge die that meets threshold should also trigger edge.
     */
    public function testEdgeWithCascading(): void
    {
        // First die: 6 (triggers edge) -> edge die: 6 (triggers edge) -> edge die: 4 (no edge)
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 6, 4);

        $result = $this->phpdice->roll('1d6 edge');

        // Should have 3 dice total (1 original + 2 edge)
        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([6, 6, 4], $result->diceValues);

        // Total should be 16
        $this->assertEquals(16, $result->total);

        // Check edge history
        $this->assertNotNull($result->edgeHistory);
        $this->assertEquals([6, 4], $result->edgeHistory[0]['rolls']); // Two edge dice
        $this->assertEquals(2, $result->edgeHistory[0]['count']); // Two edge dice added
    }

    /**
     * Test edge with explicit limit
     * Acceptance: "3d6 edge 2" allows at most 2 edge dice per original die.
     */
    public function testEdgeWithExplicitLimit(): void
    {
        // First die: 6 (triggers edge) -> edge die: 6 (triggers edge) -> edge die: 6 (limit reached)
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 6, 6);

        $result = $this->phpdice->roll('1d6 edge 2');

        // Should have 3 dice total (1 original + 2 edge, third 6 doesn't trigger due to limit)
        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([6, 6, 6], $result->diceValues);

        // Check edge history shows limit reached
        $this->assertNotNull($result->edgeHistory);
        $this->assertEquals(2, $result->edgeHistory[0]['count']); // Two edge dice (limit)
        $this->assertTrue($result->edgeHistory[0]['limitReached']); // Limit was reached
    }

    /**
     * Test edge with custom threshold
     * Acceptance: "3d6 edge 3 >=5" triggers edge on 5 or 6.
     */
    public function testEdgeWithCustomThreshold(): void
    {
        // First die: 5 (triggers edge) -> edge die: 4 (no edge)
        // Second die: 4 (no edge)
        // Third die: 6 (triggers edge) -> edge die: 3 (no edge)
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 4, 6, 4, 3);

        $result = $this->phpdice->roll('3d6 edge 3 >=5');

        // Should have 5 dice total (3 original + 2 edge)
        $this->assertCount(5, $result->diceValues);
        $this->assertEquals([5, 4, 6, 4, 3], $result->diceValues);

        // Check edge history
        $this->assertNotNull($result->edgeHistory);
        $this->assertArrayHasKey(0, $result->edgeHistory); // First die triggered edge
        $this->assertArrayHasKey(2, $result->edgeHistory); // Third die triggered edge
    }

    /**
     * Test edge with <= operator
     * Acceptance: "3d6 edge <=2" triggers edge on 1 or 2.
     */
    public function testEdgeWithLessThanOperator(): void
    {
        // First die: 1 (triggers edge) -> edge die: 3 (no edge)
        // Second die: 5 (no edge)
        // Third die: 2 (triggers edge) -> edge die: 4 (no edge)
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 5, 2, 3, 4);

        $result = $this->phpdice->roll('3d6 edge <=2');

        // Should have 5 dice total
        $this->assertCount(5, $result->diceValues);
        $this->assertEquals([1, 5, 2, 3, 4], $result->diceValues);

        // Check edge history
        $this->assertNotNull($result->edgeHistory);
        $this->assertArrayHasKey(0, $result->edgeHistory); // First die
        $this->assertArrayHasKey(2, $result->edgeHistory); // Third die
    }

    /**
     * Test edge with success counting (Shadowrun use case)
     * Acceptance: Edge dice count toward total success count.
     */
    public function testEdgeWithSuccessCounting(): void
    {
        // 2d6 edge count >=5
        // First die: 6 (success, triggers edge) -> edge die: 5 (success)
        // Second die: 4 (no success)
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 4, 5);

        $result = $this->phpdice->roll('2d6 edge count >=5');

        // Should have 3 dice total
        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([6, 4, 5], $result->diceValues);

        // Should have 2 successes (6 and 5, not 4)
        $this->assertEquals(2, $result->successCount);

        // In success counting mode, total equals success count
        $this->assertEquals(2, $result->total);
    }

    /**
     * Test edge doesn't trigger when threshold not met
     * Acceptance: Edge only triggers when threshold condition is met.
     */
    public function testEdgeDoesntTriggerWhenBelowThreshold(): void
    {
        // All dice below threshold
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 4, 5);

        $result = $this->phpdice->roll('3d6 edge >=6');

        // Should have only 3 dice (no edge triggered)
        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([3, 4, 5], $result->diceValues);

        // No edge history
        $this->assertNull($result->edgeHistory);
    }

    /**
     * Test edge covering entire range throws exception
     * Acceptance: "1d6 edge >=1" should reject (all values trigger edge - infinite loop).
     */
    public function testEdgeCoveringEntireRangeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('would trigger on all possible die values');

        $this->phpdice->roll('1d6 edge >=1');
    }

    /**
     * Test other invalid edge ranges.
     */
    public function testOtherInvalidEdgeRanges(): void
    {
        // "1d20 edge <=20" would trigger on all values
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('would trigger on all possible die values');

        $this->phpdice->roll('1d20 edge <=20');
    }

    /**
     * Test valid edge edge cases.
     */
    public function testValidEdgeEdgeCases(): void
    {
        // "1d6 edge >=6" - only 6 triggers edge (valid)
        $result1 = $this->phpdice->roll('1d6 edge >=6');
        $this->assertIsInt($result1->total);

        // "1d6 edge <=1" - only 1 triggers edge (valid)
        $result2 = $this->phpdice->roll('1d6 edge <=1');
        $this->assertIsInt($result2->total);

        // "1d6 edge >=2" - 2-6 trigger edge (valid, 5 out of 6 values)
        $result3 = $this->phpdice->roll('1d6 edge >=2');
        $this->assertIsInt($result3->total);
    }

    /**
     * Test edge cannot be combined with explode
     * Acceptance: Cannot use both edge and explode on same dice.
     */
    public function testEdgeCannotBeCombinedWithExplode(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot combine edge and explode');

        $this->phpdice->roll('3d6 explode edge');
    }

    /**
     * Test edge cannot be combined with reroll
     * Acceptance: Cannot use both edge and reroll on same dice.
     */
    public function testEdgeCannotBeCombinedWithReroll(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot combine edge and reroll');

        $this->phpdice->roll('3d6 reroll ==1 edge');
    }

    /**
     * Test edge with keep mechanics
     * Parser expects order: edge, keep, count, dc.
     */
    public function testEdgeWithKeepMechanics(): void
    {
        // 6d6 edge >=5 keep 4 highest
        // Dice: 6 (edge->4), 5 (edge->3), 4, 3, 2, 1 = [6, 5, 4, 3, 2, 1, 4, 3]
        // Keep 4 highest: 6, 5, 4, 4
        $this->mockRng->expects($this->exactly(8))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 5, 4, 3, 2, 1, 4, 3);

        $result = $this->phpdice->roll('6d6 edge >=5 keep 4 highest');

        // Should roll 8 dice total (6 original + 2 edge)
        $this->assertCount(8, $result->diceValues);

        // Should keep 4 highest
        $this->assertCount(4, $result->keptDice ?? []);

        // Total should be sum of kept dice
        $keptValues = [];
        foreach ($result->keptDice as $index) {
            $keptValues[] = $result->diceValues[$index];
        }
        $this->assertEquals(array_sum($keptValues), $result->total);
    }

    /**
     * Test edge with invalid operator.
     */
    public function testEdgeWithInvalidOperator(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only >= and <= are supported');

        $this->phpdice->roll('3d6 edge >5');
    }

    /**
     * Test parsing validates correct order
     * Acceptance: Edge should work when properly ordered with other modifiers
     */
    public function testEdgeParsingOrder(): void
    {
        // Should parse successfully with correct order: edge, keep, count
        $expression = $this->phpdice->parse('5d6 edge keep 3 highest count >=4');
        
        $this->assertEquals(6, $expression->modifiers->edgeThreshold);
        $this->assertEquals('>=', $expression->modifiers->edgeOperator);
        $this->assertEquals(3, $expression->modifiers->keepHighest);
        $this->assertEquals(4, $expression->modifiers->successThreshold);
    }

    /**
     * Test edge statistics calculation.
     * Note: Edge statistics are complex as they depend on cascading probability.
     */
    public function testEdgeStatistics(): void
    {
        $expression = $this->phpdice->parse('3d6 edge');

        // With edge, expected should be higher than base dice
        $baseExpected = 3 * 3.5; // 3d6 average
        
        // Edge adds dice, so minimum stays the same but maximum and expected increase
        $this->assertEquals(3, $expression->statistics->minimum); // 3 dice minimum (1+1+1)
        $this->assertGreaterThan(18, $expression->statistics->maximum); // More than 3d6 max due to edge
        $this->assertGreaterThan($baseExpected, $expression->statistics->expected);
    }
}
