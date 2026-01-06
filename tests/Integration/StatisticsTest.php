<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Model\StatisticalData;

/**
 * Integration tests for User Story 10: Statistical Analysis.
 *
 * Tests that statistics (min, max, expected) are correctly calculated
 * for all expression types without rolling dice.
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Model\DiceExpression
 * @covers \PHPDice\Model\StatisticalCalculator
 * @covers \PHPDice\Model\StatisticalData
 */
class StatisticsTest extends BaseTestCase
{
    /**
     * AC1: Basic dice statistics.
     *
     * @test
     */
    public function testBasicDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('3d6');
        $stats = $expression->getStatistics();

        $this->assertSame(3, $stats->minimum);
        $this->assertSame(18, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    /**
     * AC1: Single die statistics.
     *
     * @test
     */
    public function testSingleDieStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20');
        $stats = $expression->getStatistics();

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    /**
     * AC2: Arithmetic modifier statistics.
     *
     * @test
     */
    public function testArithmeticModifierStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20+5');
        $stats = $expression->getStatistics();

        $this->assertSame(6, $stats->minimum);
        $this->assertSame(25, $stats->maximum);
        $this->assertSame(15.5, $stats->expected);
    }

    /**
     * AC2: Complex arithmetic statistics.
     *
     * @test
     */
    public function testComplexArithmeticStatistics(): void
    {
        $expression = $this->phpdice->parse('(2d6+3)*2');
        $stats = $expression->getStatistics();

        $this->assertSame(10, $stats->minimum); // (2+3)*2
        $this->assertSame(30, $stats->maximum); // (12+3)*2
        $this->assertSame(20.0, $stats->expected); // (7+3)*2
    }

    /**
     * AC3: Advantage statistics (2d20 keep highest).
     *
     * @test
     */
    public function testAdvantageStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20 advantage');
        $stats = $expression->getStatistics();

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        // Expected value for d20 with advantage (approximation)
        $this->assertEqualsWithDelta(14.0, $stats->expected, 0.1);
    }

    /**
     * AC3: Disadvantage statistics (2d20 keep lowest).
     *
     * @test
     */
    public function testDisadvantageStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20 disadvantage');
        $stats = $expression->getStatistics();

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        // Expected value for d20 with disadvantage (approximation)
        $this->assertEqualsWithDelta(7.0, $stats->expected, 0.1);
    }

    /**
     * AC3: Keep highest statistics (4d6 keep 3 highest).
     *
     * @test
     */
    public function testKeepHighestStatistics(): void
    {
        $expression = $this->phpdice->parse('4d6 keep 3 highest');
        $stats = $expression->getStatistics();

        $this->assertSame(3, $stats->minimum); // 3 dice, all roll 1
        $this->assertSame(18, $stats->maximum); // 3 dice, all roll 6
        // Expected is higher than 3d6 (10.5) due to dropping lowest
        $this->assertEqualsWithDelta(12.6, $stats->expected, 0.1);
    }

    /**
     * AC4: Success counting statistics.
     *
     * @test
     */
    public function testSuccessCountingStatistics(): void
    {
        $expression = $this->phpdice->parse('5d6 >=4');
        $stats = $expression->getStatistics();

        $this->assertSame(0, $stats->minimum); // All dice fail
        $this->assertSame(5, $stats->maximum); // All dice succeed
        // Expected: 5 dice * P(>=4) = 5 * (3/6) = 2.5
        $this->assertSame(2.5, $stats->expected);
    }

    /**
     * AC4: Success counting with different operator.
     *
     * @test
     */
    public function testSuccessCountingWithGreaterThanStatistics(): void
    {
        $expression = $this->phpdice->parse('10d10 >7');
        $stats = $expression->getStatistics();

        $this->assertSame(0, $stats->minimum);
        $this->assertSame(10, $stats->maximum);
        // Expected: 10 dice * P(>7) = 10 * (3/10) = 3.0
        $this->assertSame(3.0, $stats->expected);
    }

    /**
     * AC5: Fudge dice statistics.
     *
     * @test
     */
    public function testFudgeDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('4dF');
        $stats = $expression->getStatistics();

        $this->assertSame(-4, $stats->minimum); // All -1
        $this->assertSame(4, $stats->maximum); // All +1
        $this->assertSame(0.0, $stats->expected); // Equal probability of -1, 0, +1
    }

    /**
     * AC5: Percentile dice statistics.
     *
     * @test
     */
    public function testPercentileDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('d%');
        $stats = $expression->getStatistics();

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(100, $stats->maximum);
        $this->assertSame(50.5, $stats->expected);
    }

    /**
     * AC6: Placeholder statistics.
     *
     * @test
     */
    public function testPlaceholderStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20+$str$+$proficiency$', ['str' => 3, 'proficiency' => 2]);
        $stats = $expression->getStatistics();

        $this->assertSame(6, $stats->minimum); // 1+3+2
        $this->assertSame(25, $stats->maximum); // 20+3+2
        $this->assertSame(15.5, $stats->expected); // 10.5+3+2
    }

    /**
     * AC7: Comparison expressions don't affect statistics
     * Success rolls evaluate after rolling, so stats show pre-comparison values.
     *
     * @test
     */
    public function testComparisonDoesNotAffectStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20+5 >= 15');
        $stats = $expression->getStatistics();

        // Statistics are for the rolled expression (1d20+5), not the comparison result
        $this->assertSame(6, $stats->minimum);
        $this->assertSame(25, $stats->maximum);
        $this->assertSame(15.5, $stats->expected);
    }

    /**
     * AC8: Critical thresholds don't affect statistics
     * Criticals are flags set after rolling.
     *
     * @test
     */
    public function testCriticalThresholdsDoNotAffectStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20 crit 20 glitch 1');
        $stats = $expression->getStatistics();

        // Statistics are for the rolled expression (1d20), not the critical flags
        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    /**
     * AC9: Reroll statistics adjust minimum/expected.
     *
     * @test
     */
    public function testRerollStatistics(): void
    {
        $expression = $this->phpdice->parse('4d6 reroll <=2');
        $stats = $expression->getStatistics();

        // Minimum: All dice roll 3 (lowest non-reroll value)
        $this->assertSame(12, $stats->minimum);
        // Maximum: All dice roll 6
        $this->assertSame(24, $stats->maximum);
        // Expected is higher than normal 4d6 due to rerolling low values
        $this->assertGreaterThan(14.0, $stats->expected); // Normal 4d6 = 14.0
    }

    /**
     * AC10: Exploding dice statistics show potential ranges.
     *
     * @test
     */
    public function testExplodingDiceStatistics(): void
    {
        $expression = $this->phpdice->parse('3d6 explode');
        $stats = $expression->getStatistics();

        // Minimum: No explosions
        $this->assertSame(3, $stats->minimum);
        // Maximum: All dice explode to limit (default 100)
        $this->assertGreaterThan(18, $stats->maximum);
        // Expected is higher than normal 3d6 due to explosions
        $this->assertGreaterThan(10.5, $stats->expected); // Normal 3d6 = 10.5
    }

    /**
     * AC11: Precision requirement - 3 decimal places.
     *
     * @test
     */
    public function testStatisticsPrecision(): void
    {
        $expression = $this->phpdice->parse('1d20');
        $stats = $expression->getStatistics();

        // Expected value should be rounded to 3 decimal places
        $expectedString = number_format($stats->expected, 3, '.', '');
        $this->assertSame('10.500', $expectedString);
    }

    /**
     * AC12: Complex expression with multiple mechanics.
     *
     * @test
     */
    public function testComplexExpressionStatistics(): void
    {
        $expression = $this->phpdice->parse('1d20 advantage + 5');
        $stats = $expression->getStatistics();

        $this->assertSame(6, $stats->minimum); // 1+5
        $this->assertSame(25, $stats->maximum); // 20+5
        // Expected: advantage (14.0 approx) + 5 = 19.0
        $this->assertEqualsWithDelta(19.0, $stats->expected, 0.1);
    }

    /**
     * AC13: Statistics available without rolling.
     *
     * @test
     */
    public function testStatisticsAvailableWithoutRolling(): void
    {
        $expression = $this->phpdice->parse('3d6+5');

        // Can get statistics without calling roll()
        $stats = $expression->getStatistics();

        $this->assertInstanceOf(StatisticalData::class, $stats);
        $this->assertSame(8, $stats->minimum);
        $this->assertSame(23, $stats->maximum);
        $this->assertSame(15.5, $stats->expected);
    }

    /**
     * AC14: Fudge dice with success counting.
     *
     * @test
     */
    public function testFudgeDiceSuccessCountingStatistics(): void
    {
        $expression = $this->phpdice->parse('6dF >=1');
        $stats = $expression->getStatistics();

        $this->assertSame(0, $stats->minimum); // All dice fail
        $this->assertSame(6, $stats->maximum); // All dice succeed (roll +1)
        // Expected: 6 dice * P(>=1) = 6 * (1/3) = 2.0
        $this->assertSame(2.0, $stats->expected);
    }

    /**
     * AC15: Mathematical functions in statistics.
     *
     * @test
     */
    public function testMathematicalFunctionStatistics(): void
    {
        $expression = $this->phpdice->parse('floor(1d20/2)');
        $stats = $expression->getStatistics();

        $this->assertSame(0.0, $stats->minimum); // floor(1/2) = 0.0 (float)
        $this->assertSame(10.0, $stats->maximum); // floor(20/2) = 10.0 (float)
        $this->assertSame(5.0, $stats->expected); // floor(10.5/2) = 5.0
    }
}
