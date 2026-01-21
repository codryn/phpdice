<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\Exception\ValidationException;

/**
 * Integration tests for User Story 5a: Success Counting.
 *
 * Tests dice pool mechanics where dice are counted as successes
 * if they meet a threshold (Shadowrun-style).
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 * @covers \Codryn\PHPDice\Model\DiceExpression
 * @covers \Codryn\PHPDice\Model\RollResult
 */
class SuccessCountingTest extends BaseTestCase
{
    /**
     * @test
     * AC4.1: Parse "5d6 success threshold 4" and count successes
     */
    public function testSuccessThresholdSyntax(): void
    {
        $result = $this->phpdice->roll('5d6 success threshold 4');

        // Should roll 5 dice
        $this->assertCount(5, $result->diceValues);

        // Total should be success count, not sum
        $this->assertNotEquals(\array_sum($result->diceValues), $result->total);

        // Success count should match manual count
        $manualCount = 0;
        foreach ($result->diceValues as $value) {
            if ($value >= 4) {
                $manualCount++;
            }
        }
        $this->assertEquals($manualCount, $result->successCount);
        $this->assertEquals($manualCount, $result->total);

        // Success count should be between 0 and 5
        $this->assertGreaterThanOrEqual(0, $result->successCount);
        $this->assertLessThanOrEqual(5, $result->successCount);
    }

    /**
     * @test
     * AC4.2: Parse "10d10 >= 7" shorthand syntax
     */
    public function testComparisonOperatorSyntax(): void
    {
        $result = $this->phpdice->roll('10d10 count >= 7');

        // Should roll 10 dice
        $this->assertCount(10, $result->diceValues);

        // Count dice >= 7
        $expected = 0;
        foreach ($result->diceValues as $value) {
            if ($value >= 7) {
                $expected++;
            }
        }

        $this->assertEquals($expected, $result->successCount);
        $this->assertEquals($expected, $result->total);
    }

    /**
     * @test
     * AC4.3: Test > operator (strict greater than)
     */
    public function testStrictGreaterThanOperator(): void
    {
        $result = $this->phpdice->roll('5d6 count > 3');

        // Count dice > 3 (i.e., 4, 5, 6)
        $expected = 0;
        foreach ($result->diceValues as $value) {
            if ($value > 3) {
                $expected++;
            }
        }

        $this->assertEquals($expected, $result->successCount);
    }

    /**
     * @test
     * AC4.4: Test "threshold N" shorthand (without "success" keyword)
     */
    public function testThresholdShorthand(): void
    {
        $result = $this->phpdice->roll('8d8 threshold 5');

        // Should use >= by default
        $expected = 0;
        foreach ($result->diceValues as $value) {
            if ($value >= 5) {
                $expected++;
            }
        }

        $this->assertEquals($expected, $result->successCount);
    }

    /**
     * @test
     * AC4.5: Statistics show expected success count
     */
    public function testSuccessCountStatistics(): void
    {
        $expression = $this->phpdice->parse('6d6 count >= 4');
        $stats = $expression->statistics;

        // Min: 0 (all dice fail)
        $this->assertEquals(0, $stats->minimum);

        // Max: 6 (all dice succeed)
        $this->assertEquals(6, $stats->maximum);

        // Expected: 6 dice * 3/6 probability = 3.0
        // (values 4, 5, 6 succeed on d6)
        $this->assertEquals(3.0, $stats->expected);
    }

    /**
     * @test
     * AC4.6: Statistics for d10 >= 7
     */
    public function testD10SuccessStatistics(): void
    {
        $expression = $this->phpdice->parse('10d10 count >= 7');
        $stats = $expression->statistics;

        // Min: 0
        $this->assertEquals(0, $stats->minimum);

        // Max: 10
        $this->assertEquals(10, $stats->maximum);

        // Expected: 10 dice * 4/10 probability = 4.0
        // (values 7, 8, 9, 10 succeed on d10)
        $this->assertEquals(4.0, $stats->expected);
    }

    /**
     * @test
     * AC4.7: Statistics for strict > operator
     */
    public function testStrictGreaterThanStatistics(): void
    {
        $expression = $this->phpdice->parse('5d6 count > 4');
        $stats = $expression->statistics;

        // Min: 0
        $this->assertEquals(0, $stats->minimum);

        // Max: 5
        $this->assertEquals(5, $stats->maximum);

        // Expected: 5 dice * 2/6 probability = 1.667
        // (only values 5, 6 succeed on d6 with > 4)
        $this->assertEquals(1.667, $stats->expected);
    }

    /**
     * @test
     * Test < operator (less than)
     */
    public function testLessThanOperator(): void
    {
        $result = $this->phpdice->roll('5d6 count < 4');

        // Count dice < 4 (i.e., 1, 2, 3)
        $expected = 0;
        foreach ($result->diceValues as $value) {
            if ($value < 4) {
                $expected++;
            }
        }

        $this->assertEquals($expected, $result->successCount);
    }

    /**
     * @test
     * Test <= operator (less than or equal)
     */
    public function testLessThanOrEqualOperator(): void
    {
        $result = $this->phpdice->roll('5d6 count <= 3');

        // Count dice <= 3 (i.e., 1, 2, 3)
        $expected = 0;
        foreach ($result->diceValues as $value) {
            if ($value <= 3) {
                $expected++;
            }
        }

        $this->assertEquals($expected, $result->successCount);
    }

    /**
     * @test
     * Test == operator (equals)
     */
    public function testEqualsOperator(): void
    {
        $result = $this->phpdice->roll('5d6 count == 4');

        // Count dice == 4
        $expected = 0;
        foreach ($result->diceValues as $value) {
            if ($value === 4) {
                $expected++;
            }
        }

        $this->assertEquals($expected, $result->successCount);
    }

    /**
     * @test
     * Verify invalid operators throw exception
     */
    public function testInvalidOperatorThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid success operator');

        $this->phpdice->parse('5d6 count != 3');
    }

    /**
     * @test
     * Edge case: All dice succeed
     */
    public function testAllDiceSucceed(): void
    {
        // Use a low threshold where all values succeed
        $result = $this->phpdice->roll('4d6 count >= 1');

        // All 4 dice should succeed (every d6 value is >= 1)
        $this->assertEquals(4, $result->successCount);
        $this->assertEquals(4, $result->total);
    }

    /**
     * @test
     * Edge case: No dice succeed
     */
    public function testNoDiceSucceed(): void
    {
        // Use an impossible threshold
        $result = $this->phpdice->roll('4d6 count > 6');

        // No dice should succeed (d6 max is 6, > 6 is impossible)
        $this->assertEquals(0, $result->successCount);
        $this->assertEquals(0, $result->total);
    }

    /**
     * @test
     * Verify result contains individual dice values
     */
    public function testIndividualDiceValuesAvailable(): void
    {
        $result = $this->phpdice->roll('8d10 count >= 6');

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
     * Success counting with keep mechanics (Shadowrun-style: roll extra, keep highest, count successes)
     */
    public function testSuccessCountingWithKeepHighest(): void
    {
        $result = $this->phpdice->roll('6d6 keep 4 highest count >= 5');

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
            if ($value >= 5) {
                $expectedSuccesses++;
            }
        }

        $this->assertEquals($expectedSuccesses, $result->successCount);
    }
}
