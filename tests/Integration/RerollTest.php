<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\Exception\ValidationException;
use Codryn\PHPDice\PHPDice;
use Codryn\PHPDice\Roller\RandomNumberGenerator;

/**
 * Integration tests for reroll mechanics (US5).
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 */
class RerollTest extends BaseTestCaseMock
{
    /**
     * @test
     * AC5.1: Parse and roll "4d6 reroll <= 2" with default limit of 100
     */
    public function testRerollWithDefaultLimit(): void
    {
        $expression = $this->phpdice->parse('4d6 reroll <= 2');

        // Check parsed modifiers
        $this->assertEquals(2, $expression->modifiers->rerollThreshold);
        $this->assertEquals('<=', $expression->modifiers->rerollOperator);
        $this->assertEquals(100, $expression->modifiers->rerollLimit);

        // Mock a roll with one die needing reroll
        // Die 1: 2 (reroll) -> 5
        // Die 2: 3
        // Die 3: 4
        // Die 4: 6
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 5, 3, 4, 6);

        $result = $this->phpdice->roll('4d6 reroll <= 2');

        // All final values should be > 2
        $this->assertEquals([5, 3, 4, 6], $result->diceValues);
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThan(2, $value, 'Final die value should be > 2 after rerolling <= 2');
        }

        // Should have reroll history for die 0
        $this->assertNotNull($result->rerollHistory);
        $this->assertArrayHasKey(0, $result->rerollHistory);
    }

    /**
     * @test
     * AC5.2: Parse and roll "4d6 reroll 1 <= 2" with explicit limit of 1
     */
    public function testRerollWithExplicitLimit(): void
    {
        $expression = $this->phpdice->parse('4d6 reroll 1 <= 2');

        // Check parsed limit
        $this->assertEquals(1, $expression->modifiers->rerollLimit);

        $result = $this->phpdice->roll('4d6 reroll 1 <= 2');

        // Verify if rerolls occurred, each die rerolled at most once
        if ($result->rerollHistory !== null) {
            foreach ($result->rerollHistory as $dieIndex => $history) {
                $this->assertLessThanOrEqual(1, $history['count'], 'Should reroll at most once per die');
                $this->assertCount(2, $history['rolls'], 'Should have original + 1 reroll = 2 rolls total');
            }
        }
    }

    /**
     * @test
     * AC5.3: Test "3d6 reroll 5 <= 3" with multiple rerolls allowed
     */
    public function testRerollWithMultipleRerollsAllowed(): void
    {
        $expression = $this->phpdice->parse('3d6 reroll 5 <= 3');

        $this->assertEquals(3, $expression->modifiers->rerollThreshold);
        $this->assertEquals('<=', $expression->modifiers->rerollOperator);
        $this->assertEquals(5, $expression->modifiers->rerollLimit);

        // Mock rolls: die 1 needs 2 rerolls, die 2 needs no reroll, die 3 needs 1 reroll
        // Die 1: 2 (reroll) -> 3 (reroll) -> 5 (keep)
        // Die 2: 6 (keep)
        // Die 3: 1 (reroll) -> 4 (keep)
        $this->mockRng->expects($this->exactly(6))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 3, 5, 6, 1, 4);

        $result = $this->phpdice->roll('3d6 reroll 5 <= 3');

        // Verify final values are all > 3
        $this->assertEquals([5, 6, 4], $result->diceValues);
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThan(3, $value, 'Final values should all be > 3');
        }
    }

    /**
     * @test
     * AC5.4: Verify reroll history tracking
     */
    public function testRerollHistoryTracking(): void
    {
        // Force rerolls with a high threshold
        $foundHistory = false;

        for ($i = 0; $i < 20; $i++) {
            $result = $this->phpdice->roll('6d6 reroll <= 3');

            if ($result->rerollHistory !== null && count($result->rerollHistory) > 0) {
                $foundHistory = true;

                foreach ($result->rerollHistory as $dieIndex => $history) {
                    // Verify history structure
                    $this->assertArrayHasKey('rolls', $history);
                    $this->assertArrayHasKey('count', $history);
                    $this->assertArrayHasKey('limitReached', $history);

                    // First roll in history should have triggered reroll
                    $this->assertLessThanOrEqual(3, $history['rolls'][0]);

                    // Last roll should be the final value
                    $lastRoll = end($history['rolls']);
                    $this->assertEquals($result->diceValues[$dieIndex], $lastRoll);

                    // Count should match array size - 1
                    $this->assertEquals(count($history['rolls']) - 1, $history['count']);
                }
                break;
            }
        }

        $this->assertTrue($foundHistory, 'Should have found reroll history in 20 attempts');
    }

    /**
     * @test
     * AC5.5: Test different comparison operators
     */
    public function testDifferentComparisonOperators(): void
    {
        // Test < : reroll if value < 3, so reroll 1,2
        // Rolls: 2 (reroll) -> 4, 3, 5, 6
        $this->mockRng = $this->createMock(RandomNumberGenerator::class);
        $this->phpdice = new \PHPDice\PHPDice($this->mockRng);
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 4, 3, 5, 6);
        $result1 = $this->phpdice->roll('4d6 reroll < 3');
        $this->assertEquals([4, 3, 5, 6], $result1->diceValues);
        foreach ($result1->diceValues as $value) {
            $this->assertGreaterThanOrEqual(3, $value);
        }

        // Test >= : reroll if value >= 5, so reroll 5,6
        // Rolls: 6 (reroll) -> 3, 4, 2, 1
        $this->mockRng = $this->createMock(RandomNumberGenerator::class);
        $this->phpdice = new \PHPDice\PHPDice($this->mockRng);
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 3, 4, 2, 1);
        $result2 = $this->phpdice->roll('4d6 reroll >= 5');
        $this->assertEquals([3, 4, 2, 1], $result2->diceValues);
        foreach ($result2->diceValues as $value) {
            $this->assertLessThan(5, $value);
        }

        // Test > : reroll if value > 4, so reroll 5,6
        // Rolls: 5 (reroll) -> 3, 4, 2, 1
        $this->mockRng = $this->createMock(RandomNumberGenerator::class);
        $this->phpdice = new \PHPDice\PHPDice($this->mockRng);
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 3, 4, 2, 1);
        $result3 = $this->phpdice->roll('4d6 reroll > 4');
        $this->assertEquals([3, 4, 2, 1], $result3->diceValues);
        foreach ($result3->diceValues as $value) {
            $this->assertLessThanOrEqual(4, $value);
        }

        // Test == : reroll if value == 1
        // Rolls: 1 (reroll) -> 3, 4, 5, 6, 2, 6
        $this->mockRng = $this->createMock(RandomNumberGenerator::class);
        $this->phpdice = new PHPDice($this->mockRng);
        $this->mockRng->expects($this->exactly(7))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 3, 4, 5, 6, 2, 6);
        $result4 = $this->phpdice->roll('6d6 reroll == 1');
        $this->assertEquals([3, 4, 5, 6, 2, 6], $result4->diceValues);
        foreach ($result4->diceValues as $value) {
            $this->assertNotEquals(1, $value);
        }
    }

    /**
     * @test
     * AC5.6: Test reroll with success counting
     */
    public function testRerollWithSuccessCounting(): void
    {
        // Mock rolls: 2 (reroll) -> 5, 1 (reroll) -> 4, 6, 3, 5
        // Final values: [5, 4, 6, 3, 5]
        // Successes (>= 4): 5, 4, 6, 5 = 4 successes
        $this->mockRng->expects($this->exactly(7))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 5, 1, 4, 6, 3, 5);

        $result = $this->phpdice->roll('5d6 reroll <= 2 count >= 4');

        // All dice should be > 2 (rerolled)
        $this->assertEquals([5, 4, 6, 3, 5], $result->diceValues);
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThan(2, $value);
        }

        // Count successes (>= 4): 5, 4, 6, 5 = 4
        $this->assertEquals(4, $result->successCount);
    }

    /**
     * @test
     * AC5.7: Reject reroll condition covering entire die range
     */
    public function testRerollCoveringEntireRangeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('preventing termination');

        $this->phpdice->parse('1d6 reroll <= 6');
    }

    /**
     * @test
     * Test other invalid reroll ranges
     */
    public function testOtherInvalidRerollRanges(): void
    {
        // Test >= 1 on d6 (all values >= 1)
        try {
            $this->phpdice->parse('1d6 reroll >= 1');
            $this->fail('Should throw ValidationException for >= 1 on d6');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('preventing termination', $e->getMessage());
        }

        // Test < 7 on d6 (all values < 7)
        try {
            $this->phpdice->parse('1d6 reroll < 7');
            $this->fail('Should throw ValidationException for < 7 on d6');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('preventing termination', $e->getMessage());
        }
    }

    /**
     * @test
     * Test valid edge cases
     */
    public function testValidRerollEdgeCases(): void
    {
        // These should NOT throw - they don't cover the entire range
        $this->phpdice->parse('1d6 reroll <= 5');  // Allows 6
        $this->phpdice->parse('1d6 reroll >= 2');  // Allows 1
        $this->phpdice->parse('1d6 reroll < 6');   // Allows 6
        $this->phpdice->parse('1d6 reroll > 1');   // Allows 1
        $this->phpdice->parse('1d6 reroll == 3');  // Allows all except 3

        $this->assertTrue(true, 'All valid edge cases parsed successfully');
    }

    /**
     * @test
     * Test reroll limit enforcement
     */
    public function testRerollLimitEnforcement(): void
    {
        // Set a very low limit to test enforcement
        $result = $this->phpdice->roll('10d6 reroll 0 <= 2');

        // With limit 0, no rerolls should occur even if initial roll is <= 2
        $this->assertNull($result->rerollHistory, 'No rerolls should occur with limit 0');
    }

    /**
     * @test
     * Test reroll with keep mechanics
     */
    public function testRerollWithKeepMechanics(): void
    {
        // Parser expects modifiers in order: reroll, keep, count, dc
        // Mock rolls: 2 (reroll) -> 5, 1 (reroll) -> 6, 4, 3, 5, 6
        // Final values: [5, 6, 4, 3, 5, 6]
        // Keep 4 highest: [6, 6, 5, 5] (indices 1, 5, 0, 4)
        $this->mockRng->expects($this->exactly(8))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 5, 1, 6, 4, 3, 5, 6);

        $result = $this->phpdice->roll('6d6 reroll <= 2 keep 4 highest');

        // Should roll 6 dice
        $this->assertCount(6, $result->diceValues);
        $this->assertEquals([5, 6, 4, 3, 5, 6], $result->diceValues);

        // All should be > 2 (rerolled)
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThan(2, $value);
        }

        // Should keep 4 highest
        $this->assertCount(4, $result->keptDice ?? []);
    }

    /**
     * @test
     * Test statistics with rerolls (approximate)
     */
    public function testRerollStatistics(): void
    {
        $expression = $this->phpdice->parse('4d6 reroll <= 2');
        $stats = $expression->statistics;

        // With reroll <= 2, minimum should be 3 (first non-rerolled value)
        $this->assertGreaterThanOrEqual(3, $stats->minimum);

        // Maximum should still be 24 (4 * 6)
        $this->assertEquals(24, $stats->maximum);

        // Expected should be higher than normal 4d6 due to rerolling low values
        // Normal 4d6 expected: 4 * 3.5 = 14
        // With reroll <= 2, expected should be higher
        $this->assertGreaterThan(14, $stats->expected);
    }
}
