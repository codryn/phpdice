<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ValidationException;

/**
 * Integration tests for User Story 4 & 4a: Advantage/Disadvantage and Keep Mechanics.
 *
 * Tests D&D 5e style advantage/disadvantage and general keep highest/lowest mechanics.
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Roller\DiceRoller
 * @covers \PHPDice\Model\DiceExpression
 * @covers \PHPDice\Model\RollResult
 */
class AdvantageTest extends BaseTestCaseMock
{
    /**
     * @test
     * AC3.1: Parse "1d20 advantage" as rolling 2d20 and keeping highest
     */
    public function testAdvantageRollsTwoDiceKeepsHighest(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(17, 18);

        $result = $this->phpdice->roll('1d20 advantage');

        // Should roll 2 dice
        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(18, $result->total);

        // Should keep 1 die (the highest)
        $this->assertCount(1, $result->keptDice ?? []);
        $this->assertEquals(1, $result->keptDice[0]);
        $this->assertCount(1, $result->discardedDice ?? []);
        $this->assertEquals(0, $result->discardedDice[0]);

        // Total should be the highest rolled value
        $highest = max($result->diceValues);
        $this->assertEquals($highest, $result->total);

        // Verify kept die is the highest
        $keptIndex = $result->keptDice[0];
        $this->assertEquals($highest, $result->diceValues[$keptIndex]);
    }

    /**
     * @test
     * AC3.2: Parse "1d20 disadvantage" as rolling 2d20 and keeping lowest
     */
    public function testDisadvantageRollsTwoDiceKeepsLowest(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(17, 18);

        $result = $this->phpdice->roll('1d20 disadvantage');

        // Should roll 2 dice
        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(17, $result->total);

        // Should keep 1 die (the lowest)
        $this->assertCount(1, $result->keptDice ?? []);
        $this->assertEquals(0, $result->keptDice[0]);
        $this->assertCount(1, $result->discardedDice ?? []);
        $this->assertEquals(1, $result->discardedDice[0]);

        // Total should be the lowest rolled value
        $lowest = min($result->diceValues);
        $this->assertEquals($lowest, $result->total);

        // Verify kept die is the lowest
        $keptIndex = $result->keptDice[0];
        $this->assertEquals($lowest, $result->diceValues[$keptIndex]);
    }

    /**
     * @test
     * AC3.3: Parse "4d6 keep 3 highest" for ability score generation
     */
    public function testKeepHighestForAbilityScores(): void
    {
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4);

        $result = $this->phpdice->roll('4d6 keep 3 highest');

        // Should roll 4 dice
        $this->assertCount(4, $result->diceValues);

        // Should keep 3 dice
        $this->assertCount(3, $result->keptDice ?? []);
        $this->assertCount(1, $result->discardedDice ?? []);

        // Total should be sum of 3 highest
        $sorted = $result->diceValues;
        rsort($sorted);
        $expectedTotal = $sorted[0] + $sorted[1] + $sorted[2];
        $this->assertEquals($expectedTotal, $result->total);

        // Verify kept dice are the highest 3
        $keptValues = [];
        foreach ($result->keptDice as $index) {
            $keptValues[] = $result->diceValues[$index];
        }
        rsort($keptValues);
        $this->assertEquals([$sorted[0], $sorted[1], $sorted[2]], $keptValues);
    }

    /**
     * @test
     * AC3.4: Parse "4d6 keep 1 lowest" (rarely used but valid)
     */
    public function testKeepLowest(): void
    {
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4);

        $result = $this->phpdice->roll('4d6 keep 1 lowest');

        // Should roll 4 dice
        $this->assertCount(4, $result->diceValues);

        // Should keep 1 die
        $this->assertCount(1, $result->keptDice ?? []);
        $this->assertCount(3, $result->discardedDice ?? []);

        // Total should be the lowest value
        $lowest = min($result->diceValues);
        $this->assertEquals($lowest, $result->total);
    }

    /**
     * @test
     * AC3.5: Statistics for advantage show correct min/max/expected
     */
    public function testAdvantageStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20 advantage');
        $stats = $expression->statistics;

        // Min: 1 (worst case both dice roll 1)
        $this->assertEquals(1, $stats->minimum);

        // Max: 20 (at least one die rolls 20)
        $this->assertEquals(20, $stats->maximum);

        // Expected for d20 advantage: ~13.825
        $this->assertGreaterThan(13.0, $stats->expected);
        $this->assertLessThan(14.5, $stats->expected);
    }

    /**
     * @test
     * AC3.6: Statistics for disadvantage show correct min/max/expected
     */
    public function testDisadvantageStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20 disadvantage');
        $stats = $expression->statistics;

        // Min: 1
        $this->assertEquals(1, $stats->minimum);

        // Max: 20 (best case both dice roll 20)
        $this->assertEquals(20, $stats->maximum);

        // Expected for d20 disadvantage: ~7.175
        $this->assertGreaterThan(6.5, $stats->expected);
        $this->assertLessThan(8.0, $stats->expected);
    }

    /**
     * @test
     * AC3.7: Validate keep count doesn't exceed total dice rolled
     */
    public function testKeepCountExceedsTotalDiceThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot keep');

        $this->phpdice->parse('2d6 keep 3 highest');
    }

    /**
     * @test
     * FR-034: Cannot have both keepHighest and keepLowest
     */
    public function testCannotHaveBothKeepHighestAndKeepLowest(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('conflict');

        $this->phpdice->parse('4d6 keep 2 highest keep 1 lowest');
    }

    /**
     * @test
     * FR-003a: Advantage can be combined with arithmetic
     */
    public function testAdvantageWithArithmetic(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(7, 8);

        $result = $this->phpdice->roll('1d20 advantage + 5');

        // Should roll 2 dice, keep highest, add 5
        $this->assertCount(2, $result->diceValues);
        $highest = max($result->diceValues);
        $this->assertEquals($highest + 5, $result->total);
    }

    /**
     * @test
     * FR-004a: Disadvantage can be combined with arithmetic
     */
    public function testDisadvantageWithArithmetic(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(7, 8);

        $result = $this->phpdice->roll('1d20 disadvantage - 2');

        // Should roll 2 dice, keep lowest, subtract 2
        $this->assertCount(2, $result->diceValues);
        $lowest = min($result->diceValues);
        $this->assertEquals($lowest - 2, $result->total);
    }

    /**
     * @test
     * Edge case: Multiple dice with advantage
     */
    public function testMultipleDiceWithAdvantage(): void
    {
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4);

        $result = $this->phpdice->roll('2d6 advantage');

        // Should roll 4 dice (2 base + 2 advantage)
        $this->assertCount(4, $result->diceValues);

        // Should keep 2 highest
        $this->assertCount(2, $result->keptDice ?? []);
        $this->assertCount(2, $result->discardedDice ?? []);
    }

    /**
     * @test
     * Edge case: Keep all dice (no-op)
     */
    public function testKeepAllDice(): void
    {
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4);

        $result = $this->phpdice->roll('4d6 keep 4 highest');

        // Should roll 4 dice
        $this->assertCount(4, $result->diceValues);

        // Should keep all 4
        $this->assertCount(4, $result->keptDice ?? []);
        $this->assertEmpty($result->discardedDice ?? []);

        // Total should be sum of all dice
        $this->assertEquals(array_sum($result->diceValues), $result->total);
    }
}
