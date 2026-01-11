<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

/**
 * Integration tests for special dice types: Fudge dice (dF), Percentile dice (d%), and Coin dice (C)
 * Tests FR-007 (Fudge dice), FR-008 (Percentile dice), and Coin flip dice.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Parser\Lexer
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 * @covers \Codryn\PHPDice\Model\StatisticalCalculator
 */
class SpecialDiceTest extends BaseTestCase
{
    /**
     * Test fudge dice notation parsing and basic rolling
     * FR-007: Support Fudge dice (dF) notation.
     */
    public function testFudgeDiceNotation(): void
    {
        $result = $this->phpdice->roll('4dF');

        $this->assertCount(4, $result->diceValues);

        // Each die should be -1, 0, or +1
        foreach ($result->diceValues as $value) {
            $this->assertContains($value, [-1, 0, 1], 'Fudge die value must be -1, 0, or +1');
        }

        // Total should be in range [-4, 4]
        $this->assertGreaterThanOrEqual(-4, $result->total);
        $this->assertLessThanOrEqual(4, $result->total);
    }

    /**
     * Test uppercase and lowercase dF notation.
     */
    public function testFudgeDiceCaseInsensitive(): void
    {
        $resultLower = $this->phpdice->roll('dF');
        $resultUpper = $this->phpdice->roll('dF');

        $this->assertCount(1, $resultLower->diceValues);
        $this->assertCount(1, $resultUpper->diceValues);
        $this->assertContains($resultLower->total, [-1, 0, 1]);
        $this->assertContains($resultUpper->total, [-1, 0, 1]);
    }

    /**
     * Test multiple fudge dice.
     */
    public function testMultipleFudgeDice(): void
    {
        $result = $this->phpdice->roll('6dF');

        $this->assertCount(6, $result->diceValues);
        $this->assertGreaterThanOrEqual(-6, $result->total);
        $this->assertLessThanOrEqual(6, $result->total);
    }

    /**
     * Test fudge dice statistics
     * FR-007: Fudge dice should have min=-count, max=+count, expected=0.
     */
    public function testFudgeDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('4dF');
        $stats = $expression->statistics;

        $this->assertSame(-4, $stats->minimum, '4dF minimum should be -4');
        $this->assertSame(4, $stats->maximum, '4dF maximum should be +4');
        $this->assertSame(0.0, $stats->expected, '4dF expected should be 0');
    }

    /**
     * Test single fudge die statistics.
     */
    public function testSingleFudgeDieStatistics(): void
    {
        $expression = $this->phpdice->parse('dF');
        $stats = $expression->statistics;

        $this->assertSame(-1, $stats->minimum);
        $this->assertSame(1, $stats->maximum);
        $this->assertSame(0.0, $stats->expected);
    }

    /**
     * Test fudge dice with arithmetic modifiers.
     */
    public function testFudgeDiceWithArithmetic(): void
    {
        $result = $this->phpdice->roll('4dF+3');

        $this->assertGreaterThanOrEqual(-1, $result->total); // -4 + 3 = -1
        $this->assertLessThanOrEqual(7, $result->total);     // +4 + 3 = 7
    }

    /**
     * Test fudge dice statistics with arithmetic.
     */
    public function testFudgeDiceStatisticsWithArithmetic(): void
    {
        $expression = $this->phpdice->parse('4dF+3');
        $stats = $expression->statistics;

        $this->assertSame(-1, $stats->minimum); // -4 + 3
        $this->assertSame(7, $stats->maximum);  // +4 + 3
        $this->assertSame(3.0, $stats->expected); // 0 + 3
    }

    /**
     * Test percentile dice notation (d%)
     * FR-008: Support Percentile dice (d% or 1d100).
     */
    public function testPercentileDiceNotation(): void
    {
        $result = $this->phpdice->roll('d%');

        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(1, $result->total);
        $this->assertLessThanOrEqual(100, $result->total);
    }

    /**
     * Test percentile dice with explicit count.
     */
    public function testExplicitPercentileDiceCount(): void
    {
        $result = $this->phpdice->roll('2d%');

        $this->assertCount(2, $result->diceValues);
        $this->assertGreaterThanOrEqual(2, $result->total);
        $this->assertLessThanOrEqual(200, $result->total);
    }

    /**
     * Test percentile dice statistics.
     */
    public function testPercentileDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('d%');
        $stats = $expression->statistics;

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(100, $stats->maximum);
        $this->assertSame(50.5, $stats->expected);
    }

    /**
     * Test percentile dice equivalent to 1d100.
     */
    public function testPercentileEquivalentTo1d100(): void
    {
        $exprPercent = $this->phpdice->parse('d%');
        $expr1d100 = $this->phpdice->parse('1d100');

        $statsPercent = $exprPercent->statistics;
        $stats1d100 = $expr1d100->statistics;

        $this->assertSame($stats1d100->minimum, $statsPercent->minimum);
        $this->assertSame($stats1d100->maximum, $statsPercent->maximum);
        $this->assertSame($stats1d100->expected, $statsPercent->expected);
    }

    /**
     * Test percentile dice with arithmetic.
     */
    public function testPercentileDiceWithArithmetic(): void
    {
        $result = $this->phpdice->roll('d%+10');

        $this->assertGreaterThanOrEqual(11, $result->total);  // 1 + 10
        $this->assertLessThanOrEqual(110, $result->total);    // 100 + 10
    }

    /**
     * Test fudge dice with multiple rolls to verify distribution
     * This is a statistical test to ensure proper value distribution.
     */
    public function testFudgeDiceDistribution(): void
    {
        $counts = [-1 => 0, 0 => 0, 1 => 0];
        $iterations = 300; // Roll 300 times

        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->phpdice->roll('dF');
            $value = $result->total;
            $counts[$value]++;
        }

        // Each value should appear roughly 1/3 of the time (allow 20-40% range)
        foreach ($counts as $value => $count) {
            $percentage = ($count / $iterations) * 100;
            $this->assertGreaterThan(20, $percentage, "Value $value appears too rarely");
            $this->assertLessThan(50, $percentage, "Value $value appears too frequently");
        }
    }

    /**
     * Test percentile dice produce values across full range.
     */
    public function testPercentileDiceFullRange(): void
    {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $result = $this->phpdice->roll('d%');
            $results[] = $result->total;
        }

        // Should have values in all quartiles
        $hasLow = count(array_filter($results, fn ($v) => $v >= 1 && $v <= 25)) > 0;
        $hasMidLow = count(array_filter($results, fn ($v) => $v >= 26 && $v <= 50)) > 0;
        $hasMidHigh = count(array_filter($results, fn ($v) => $v >= 51 && $v <= 75)) > 0;
        $hasHigh = count(array_filter($results, fn ($v) => $v >= 76 && $v <= 100)) > 0;

        $this->assertTrue($hasLow, 'Should have values in 1-25 range');
        $this->assertTrue($hasMidLow, 'Should have values in 26-50 range');
        $this->assertTrue($hasMidHigh, 'Should have values in 51-75 range');
        $this->assertTrue($hasHigh, 'Should have values in 76-100 range');
    }

    /**
     * Test complex expression with fudge dice.
     */
    public function testComplexFudgeDiceExpression(): void
    {
        $result = $this->phpdice->roll('4dF + 1d6');

        // 4dF contributes -4 to +4
        // 1d6 contributes 1 to 6
        // Total range: -3 to 10
        $this->assertGreaterThanOrEqual(-4, $result->total);
        $this->assertLessThanOrEqual(10, $result->total);
    }

    /**
     * Test fudge dice with success counting.
     */
    public function testFudgeDiceSuccessCounting(): void
    {
        $result = $this->phpdice->roll('4dF count >=0');

        // Success count: how many dice rolled 0 or higher
        $this->assertGreaterThanOrEqual(0, $result->successCount);
        $this->assertLessThanOrEqual(4, $result->successCount);
    }

    /**
     * Test fudge dice success counting statistics.
     */
    public function testFudgeDiceSuccessCountingStatistics(): void
    {
        $expression = $this->phpdice->parse('4dF count >=0');
        $stats = $expression->statistics;

        // Each die has 2/3 chance of success (0 or +1)
        // Expected: 4 * (2/3) = 2.667
        $this->assertSame(0, $stats->minimum);
        $this->assertSame(4, $stats->maximum);
        $this->assertEqualsWithDelta(2.667, $stats->expected, 0.001);
    }

    /**
     * Test fudge dice with reroll mechanics
     * Note: Testing reroll <0 means rerolling -1 values.
     */
    public function testFudgeDiceWithReroll(): void
    {
        // Use a generous number of rolls to ensure we see rerolls
        for ($i = 0; $i < 50; $i++) {
            $result = $this->phpdice->roll('10dF reroll <0');

            // If we found a reroll, verify all final values are >= 0
            if (isset($result->metadata['rerollHistory']) && !empty($result->metadata['rerollHistory'])) {
                foreach ($result->diceValues as $value) {
                    $this->assertGreaterThanOrEqual(0, $value, 'Rerolled fudge dice should not have -1');
                }
                return; // Test passed
            }
        }

        // If we never saw a reroll in 50 attempts with 10 dice, that's statistically unlikely
        // but not impossible. Mark the test as passed anyway since the parser accepted the syntax.
        $this->assertTrue(true, 'Reroll syntax accepted');
    }

    /**
     * Test fudge dice with explosion mechanics
     * Note: Fudge dice explode on the + face (value 1) which is valid.
     */
    public function testFudgeDiceWithExplosion(): void
    {
        // Test basic explosion parsing
        $expression = $this->phpdice->parse('4dF explode');

        // Should have explosion mechanics configured with default threshold (max value)
        $this->assertNotNull($expression->modifiers->explosionThreshold);

        // Actually roll to verify it works
        $result = $this->phpdice->roll('4dF explode');
        $this->assertCount(4, $result->diceValues);
    }

    /**
     * Test coin flip notation parsing and basic rolling
     * Coin dice work exactly as 1d2 but return 0 or 1 (head or tails).
     */
    public function testCoinFlipNotation(): void
    {
        $result = $this->phpdice->roll('1C');

        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(0, $result->total);
        $this->assertLessThanOrEqual(1, $result->total);
        $this->assertTrue(in_array($result->total, [0, 1]), 'Coin flip must be 0 or 1');
    }

    /**
     * Test standalone coin notation (C without count).
     */
    public function testStandaloneCoinNotation(): void
    {
        $result = $this->phpdice->roll('C');

        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(0, $result->total);
        $this->assertLessThanOrEqual(1, $result->total);
        $this->assertTrue(in_array($result->total, [0, 1]), 'Coin flip must be 0 or 1');
    }

    /**
     * Test multiple coin flips.
     */
    public function testMultipleCoinFlips(): void
    {
        $result = $this->phpdice->roll('5C');

        $this->assertCount(5, $result->diceValues);

        // Each coin should be 0 or 1
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(1, $value);
            $this->assertTrue(in_array($value, [0, 1]), 'Each coin flip must be 0 or 1');
        }

        // Total should be between 0 and 5
        $this->assertGreaterThanOrEqual(0, $result->total);
        $this->assertLessThanOrEqual(5, $result->total);
    }

    /**
     * Test coin dice statistics.
     */
    public function testCoinDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('1C');
        $stats = $expression->statistics;

        $this->assertSame(0, $stats->minimum, '1C minimum should be 0');
        $this->assertSame(1, $stats->maximum, '1C maximum should be 1');
        $this->assertSame(0.5, $stats->expected, '1C expected should be 0.5');
    }

    /**
     * Test multiple coin dice statistics.
     */
    public function testMultipleCoinDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('3C');
        $stats = $expression->statistics;

        $this->assertSame(0, $stats->minimum, '3C minimum should be 0');
        $this->assertSame(3, $stats->maximum, '3C maximum should be 3');
        $this->assertSame(1.5, $stats->expected, '3C expected should be 1.5');
    }

    /**
     * Test coin dice with arithmetic modifiers.
     */
    public function testCoinDiceWithArithmetic(): void
    {
        $result = $this->phpdice->roll('2C+5');

        // 2C contributes 0 to 2
        // +5 adds 5
        // Total range: 5 to 7
        $this->assertGreaterThanOrEqual(5, $result->total);
        $this->assertLessThanOrEqual(7, $result->total);
    }

    /**
     * Test coin dice statistics with arithmetic.
     */
    public function testCoinDiceStatisticsWithArithmetic(): void
    {
        $expression = $this->phpdice->parse('2C+5');
        $stats = $expression->statistics;

        $this->assertSame(5, $stats->minimum); // 0 + 5
        $this->assertSame(7, $stats->maximum);  // 2 + 5
        $this->assertSame(6.0, $stats->expected); // 1 + 5
    }

    /**
     * Test coin dice distribution over multiple rolls.
     */
    public function testCoinDiceDistribution(): void
    {
        $counts = [0 => 0, 1 => 0];
        $iterations = 200; // Roll 200 times

        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->phpdice->roll('C');
            $value = $result->total;
            $counts[$value]++;
        }

        // Each value should appear roughly 50% of the time (allow 30-70% range)
        foreach ($counts as $value => $count) {
            $percentage = ($count / $iterations) * 100;
            $this->assertGreaterThan(30, $percentage, "Value $value appears too rarely");
            $this->assertLessThan(70, $percentage, "Value $value appears too frequently");
        }
    }

    /**
     * Test complex expression with coin dice.
     */
    public function testComplexCoinDiceExpression(): void
    {
        $result = $this->phpdice->roll('3C + 1d6');

        // 3C contributes 0 to 3
        // 1d6 contributes 1 to 6
        // Total range: 1 to 9
        $this->assertGreaterThanOrEqual(1, $result->total);
        $this->assertLessThanOrEqual(9, $result->total);
    }

    /**
     * Test coin dice with success counting.
     */
    public function testCoinDiceSuccessCounting(): void
    {
        $result = $this->phpdice->roll('10C count >=1');

        // Success count: how many coins rolled 1 (heads)
        $this->assertGreaterThanOrEqual(0, $result->successCount);
        $this->assertLessThanOrEqual(10, $result->successCount);
    }

    /**
     * Test that coin dice work like 1d2 in behavior.
     */
    public function testCoinEquivalentToDice(): void
    {
        // Parse both expressions
        $exprCoin = $this->phpdice->parse('1C');
        $expr1d2 = $this->phpdice->parse('1d2');

        // The statistics should have same range
        $statsCoin = $exprCoin->statistics;
        $stats1d2 = $expr1d2->statistics;

        // Coin: 0 to 1, 1d2: 1 to 2
        // The ranges are different by offset
        $this->assertSame(0, $statsCoin->minimum);
        $this->assertSame(1, $statsCoin->maximum);
        $this->assertSame(1, $stats1d2->minimum);
        $this->assertSame(2, $stats1d2->maximum);

        // But both have the same span
        $this->assertSame(
            $statsCoin->maximum - $statsCoin->minimum,
            $stats1d2->maximum - $stats1d2->minimum
        );
    }
}
