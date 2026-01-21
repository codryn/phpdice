<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

/**
 * Integration tests for counting even/odd rolls.
 *
 * Tests dice pool mechanics where dice are counted if they are even or odd.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 * @covers \Codryn\PHPDice\Model\DiceExpression
 * @covers \Codryn\PHPDice\Model\RollResult
 */
class EvenOddCountingTest extends BaseTestCase
{
    /**
     * @test
     * Test "count even" syntax
     */
    public function testCountEvenSyntax(): void
    {
        $result = $this->phpdice->roll('6d6 count even');

        // Should roll 6 dice
        $this->assertCount(6, $result->diceValues);

        // Total should be success count, not sum
        $this->assertNotEquals(\array_sum($result->diceValues), $result->total);

        // Success count should match manual count
        $manualCount = 0;
        foreach ($result->diceValues as $value) {
            if ($value % 2 === 0) {
                $manualCount++;
            }
        }
        $this->assertEquals($manualCount, $result->successCount);
        $this->assertEquals($manualCount, $result->total);

        // Success count should be between 0 and 6
        $this->assertGreaterThanOrEqual(0, $result->successCount);
        $this->assertLessThanOrEqual(6, $result->successCount);
    }

    /**
     * @test
     * Test "count odd" syntax
     */
    public function testCountOddSyntax(): void
    {
        $result = $this->phpdice->roll('12d4 count odd');

        // Should roll 12 dice
        $this->assertCount(12, $result->diceValues);

        // Total should be success count, not sum
        $this->assertNotEquals(\array_sum($result->diceValues), $result->total);

        // Success count should match manual count
        $manualCount = 0;
        foreach ($result->diceValues as $value) {
            if ($value % 2 !== 0) {
                $manualCount++;
            }
        }
        $this->assertEquals($manualCount, $result->successCount);
        $this->assertEquals($manualCount, $result->total);

        // Success count should be between 0 and 12
        $this->assertGreaterThanOrEqual(0, $result->successCount);
        $this->assertLessThanOrEqual(12, $result->successCount);
    }

    /**
     * @test
     * Test statistics for "count even" on d6
     */
    public function testCountEvenStatistics(): void
    {
        $expression = $this->phpdice->parse('6d6 count even');
        $stats = $expression->statistics;

        // Min: 0 (all dice odd)
        $this->assertEquals(0, $stats->minimum);

        // Max: 6 (all dice even)
        $this->assertEquals(6, $stats->maximum);

        // Expected: 6 dice * 3/6 probability = 3.0
        // (values 2, 4, 6 are even on d6)
        $this->assertEquals(3.0, $stats->expected);
    }

    /**
     * @test
     * Test statistics for "count odd" on d10
     */
    public function testCountOddStatistics(): void
    {
        $expression = $this->phpdice->parse('10d10 count odd');
        $stats = $expression->statistics;

        // Min: 0 (all dice even)
        $this->assertEquals(0, $stats->minimum);

        // Max: 10 (all dice odd)
        $this->assertEquals(10, $stats->maximum);

        // Expected: 10 dice * 5/10 probability = 5.0
        // (values 1, 3, 5, 7, 9 are odd on d10)
        $this->assertEquals(5.0, $stats->expected);
    }

    /**
     * @test
     * Test statistics for "count even" on d4
     */
    public function testCountEvenD4Statistics(): void
    {
        $expression = $this->phpdice->parse('8d4 count even');
        $stats = $expression->statistics;

        // Min: 0
        $this->assertEquals(0, $stats->minimum);

        // Max: 8
        $this->assertEquals(8, $stats->maximum);

        // Expected: 8 dice * 2/4 probability = 4.0
        // (values 2, 4 are even on d4)
        $this->assertEquals(4.0, $stats->expected);
    }

    /**
     * @test
     * Test statistics for "count odd" on d20
     */
    public function testCountOddD20Statistics(): void
    {
        $expression = $this->phpdice->parse('4d20 count odd');
        $stats = $expression->statistics;

        // Min: 0
        $this->assertEquals(0, $stats->minimum);

        // Max: 4
        $this->assertEquals(4, $stats->maximum);

        // Expected: 4 dice * 10/20 probability = 2.0
        // (values 1, 3, 5, 7, 9, 11, 13, 15, 17, 19 are odd on d20)
        $this->assertEquals(2.0, $stats->expected);
    }

    /**
     * @test
     * Verify result contains individual dice values
     */
    public function testIndividualDiceValuesAvailable(): void
    {
        $result = $this->phpdice->roll('8d10 count even');

        // Should have all 8 dice values
        $this->assertCount(8, $result->diceValues);

        // All values should be between 1 and 10
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(10, $value);
        }
    }

    /**
     * @test
     * Edge case: All d6 values are even (2, 4, 6)
     */
    public function testD6EvenProbability(): void
    {
        // On a d6, values 2, 4, 6 are even (50% probability)
        // Run multiple times to ensure reasonable distribution
        $totalEven = 0;
        $iterations = 100;
        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->phpdice->roll('6d6 count even');
            $totalEven += $result->successCount;
        }
        $averageEven = $totalEven / $iterations;

        // Average should be close to 3.0 (6 dice * 0.5 probability)
        // Allow some variance (between 2.5 and 3.5)
        $this->assertGreaterThan(2.5, $averageEven);
        $this->assertLessThan(3.5, $averageEven);
    }

    /**
     * @test
     * Edge case: All d4 values have equal even/odd distribution
     */
    public function testD4OddProbability(): void
    {
        // On a d4, values 1, 3 are odd (50% probability)
        // Run multiple times to ensure reasonable distribution
        $totalOdd = 0;
        $iterations = 100;
        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->phpdice->roll('10d4 count odd');
            $totalOdd += $result->successCount;
        }
        $averageOdd = $totalOdd / $iterations;

        // Average should be close to 5.0 (10 dice * 0.5 probability)
        // Allow some variance (between 4.5 and 5.5)
        $this->assertGreaterThan(4.5, $averageOdd);
        $this->assertLessThan(5.5, $averageOdd);
    }

    /**
     * @test
     * Backward compatibility: Ensure comparison operators still work
     */
    public function testBackwardCompatibilityWithComparison(): void
    {
        $result = $this->phpdice->roll('5d6 count >= 4');

        // Count dice >= 4
        $expected = 0;
        foreach ($result->diceValues as $value) {
            if ($value >= 4) {
                $expected++;
            }
        }

        $this->assertEquals($expected, $result->successCount);
        $this->assertEquals($expected, $result->total);
    }

    /**
     * @test
     * Test even/odd counting with keep mechanics
     */
    public function testEvenCountingWithKeepHighest(): void
    {
        $result = $this->phpdice->roll('6d6 keep 4 highest count even');

        // Should roll 6 dice
        $this->assertCount(6, $result->diceValues);

        // Should keep 4
        $this->assertCount(4, $result->keptDice ?? []);
        $this->assertCount(2, $result->discardedDice ?? []);

        // Success count should be based on kept dice only
        $keptValues = [];
        foreach ($result->keptDice as $index) {
            $keptValues[] = $result->diceValues[$index];
        }

        $expectedSuccesses = 0;
        foreach ($keptValues as $value) {
            if ($value % 2 === 0) {
                $expectedSuccesses++;
            }
        }

        $this->assertEquals($expectedSuccesses, $result->successCount);
    }
}
