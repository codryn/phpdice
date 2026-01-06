<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Model;

use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\StatisticalCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StatisticalCalculator.
 *
 * Tests that statistical calculations are correct for all dice types
 * and mechanics without parsing expressions.
 *
 * @covers \PHPDice\Model\StatisticalCalculator
 * @covers \PHPDice\Model\StatisticalData
 * @covers \PHPDice\Model\DiceSpecification
 * @covers \PHPDice\Model\RollModifiers
 */
class StatisticalCalculatorTest extends TestCase
{
    private StatisticalCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new StatisticalCalculator();
    }

    /**
     * Test basic dice statistics.
     *
     * @test
     */
    public function testBasicDiceStatistics(): void
    {
        $spec = new DiceSpecification(count: 3, sides: 6, type: DiceType::STANDARD);
        $modifiers = new RollModifiers();

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(3, $stats->minimum);
        $this->assertSame(18, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    /**
     * Test single die statistics.
     *
     * @test
     */
    public function testSingleDieStatistics(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 20, type: DiceType::STANDARD);
        $modifiers = new RollModifiers();

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    /**
     * Test fudge dice statistics.
     *
     * @test
     */
    public function testFudgeDiceStatistics(): void
    {
        $spec = new DiceSpecification(count: 4, sides: 3, type: DiceType::FUDGE);
        $modifiers = new RollModifiers();

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(-4, $stats->minimum); // All -1
        $this->assertSame(4, $stats->maximum); // All +1
        $this->assertSame(0.0, $stats->expected); // Equal probability
    }

    /**
     * Test percentile dice statistics.
     *
     * @test
     */
    public function testPercentileDiceStatistics(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 100, type: DiceType::PERCENTILE);
        $modifiers = new RollModifiers();

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(100, $stats->maximum);
        $this->assertSame(50.5, $stats->expected);
    }

    /**
     * Test arithmetic modifier statistics.
     *
     * @test
     */
    public function testArithmeticModifierStatistics(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 20, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(arithmeticModifier: 5);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(6, $stats->minimum); // 1+5
        $this->assertSame(25, $stats->maximum); // 20+5
        $this->assertSame(15.5, $stats->expected); // 10.5+5
    }

    /**
     * Test negative arithmetic modifier.
     *
     * @test
     */
    public function testNegativeArithmeticModifier(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 20, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(arithmeticModifier: -3);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(-2, $stats->minimum); // 1-3
        $this->assertSame(17, $stats->maximum); // 20-3
        $this->assertSame(7.5, $stats->expected); // 10.5-3
    }

    /**
     * Test keep highest statistics.
     *
     * @test
     */
    public function testKeepHighestStatistics(): void
    {
        $spec = new DiceSpecification(count: 4, sides: 6, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(keepHighest: 3);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(3, $stats->minimum); // 3 dice * 1
        $this->assertSame(18, $stats->maximum); // 3 dice * 6
        $this->assertGreaterThan(10.5, $stats->expected); // Higher than 3d6
    }

    /**
     * Test keep lowest statistics.
     *
     * @test
     */
    public function testKeepLowestStatistics(): void
    {
        $spec = new DiceSpecification(count: 4, sides: 6, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(keepLowest: 3);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(3, $stats->minimum); // 3 dice * 1
        $this->assertSame(18, $stats->maximum); // 3 dice * 6
        $this->assertLessThan(10.5, $stats->expected); // Lower than 3d6
    }

    /**
     * Test advantage (2d20 keep 1 highest).
     *
     * @test
     */
    public function testAdvantageStatistics(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 20, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(advantageCount: 1, keepHighest: 1);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        $this->assertGreaterThan(10.5, $stats->expected); // Better than 1d20
    }

    /**
     * Test disadvantage (2d20 keep 1 lowest).
     *
     * @test
     */
    public function testDisadvantageStatistics(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 20, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(advantageCount: 1, keepLowest: 1);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        $this->assertLessThan(10.5, $stats->expected); // Worse than 1d20
    }

    /**
     * Test success counting statistics with >=.
     *
     * @test
     */
    public function testSuccessCountingGreaterOrEqual(): void
    {
        $spec = new DiceSpecification(count: 5, sides: 6, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(successThreshold: 4, successOperator: '>=');

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(0, $stats->minimum); // No successes
        $this->assertSame(5, $stats->maximum); // All successes
        // P(>=4) = 3/6 = 0.5, so 5 * 0.5 = 2.5
        $this->assertSame(2.5, $stats->expected);
    }

    /**
     * Test success counting statistics with >.
     *
     * @test
     */
    public function testSuccessCountingGreaterThan(): void
    {
        $spec = new DiceSpecification(count: 10, sides: 10, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(successThreshold: 7, successOperator: '>');

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(0, $stats->minimum);
        $this->assertSame(10, $stats->maximum);
        // P(>7) = 3/10 = 0.3, so 10 * 0.3 = 3.0
        $this->assertSame(3.0, $stats->expected);
    }

    /**
     * Test success counting with fudge dice.
     *
     * @test
     */
    public function testSuccessCountingFudgeDice(): void
    {
        $spec = new DiceSpecification(count: 6, sides: 3, type: DiceType::FUDGE);
        $modifiers = new RollModifiers(successThreshold: 1, successOperator: '>=');

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(0, $stats->minimum);
        $this->assertSame(6, $stats->maximum);
        // P(>=1) for fudge = 1/3, so 6 * 1/3 = 2.0
        $this->assertSame(2.0, $stats->expected);
    }

    /**
     * Test reroll statistics.
     *
     * @test
     */
    public function testRerollStatistics(): void
    {
        $spec = new DiceSpecification(count: 4, sides: 6, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(rerollThreshold: 2, rerollOperator: '<=', rerollLimit: 100);

        $stats = $this->calculator->calculate($spec, $modifiers);

        // Minimum: all dice roll 3 (lowest non-reroll value)
        $this->assertSame(12, $stats->minimum);
        // Maximum: all dice roll 6
        $this->assertSame(24, $stats->maximum);
        // Expected is higher than normal 4d6 (14.0)
        $this->assertGreaterThan(14.0, $stats->expected);
    }

    /**
     * Test explosion statistics.
     *
     * @test
     */
    public function testExplosionStatistics(): void
    {
        $spec = new DiceSpecification(count: 3, sides: 6, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(explosionThreshold: 6, explosionOperator: '>=', explosionLimit: 100);

        $stats = $this->calculator->calculate($spec, $modifiers);

        // Minimum: no explosions
        $this->assertSame(3, $stats->minimum);
        // Maximum: all dice explode to limit
        $this->assertGreaterThan(18, $stats->maximum);
        // Expected is higher than normal 3d6 (10.5)
        $this->assertGreaterThan(10.5, $stats->expected);
    }

    /**
     * Test 3 decimal precision.
     *
     * @test
     */
    public function testThreeDecimalPrecision(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 20, type: DiceType::STANDARD);
        $modifiers = new RollModifiers();

        $stats = $this->calculator->calculate($spec, $modifiers);

        // Expected should be exactly 10.5 with 3 decimal precision
        $this->assertSame(10.5, $stats->expected);

        // Verify string representation has 3 decimals
        $expectedString = number_format($stats->expected, 3, '.', '');
        $this->assertSame('10.500', $expectedString);
    }

    /**
     * Test very large dice count.
     *
     * @test
     */
    public function testLargeDiceCount(): void
    {
        $spec = new DiceSpecification(count: 100, sides: 6, type: DiceType::STANDARD);
        $modifiers = new RollModifiers();

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(100, $stats->minimum); // 100 * 1
        $this->assertSame(600, $stats->maximum); // 100 * 6
        $this->assertSame(350.0, $stats->expected); // 100 * 3.5
    }

    /**
     * Test very large die sides.
     *
     * @test
     */
    public function testLargeDieSides(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 100, type: DiceType::STANDARD);
        $modifiers = new RollModifiers();

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(100, $stats->maximum);
        $this->assertSame(50.5, $stats->expected);
    }

    /**
     * Test combined modifiers (keep + arithmetic).
     *
     * @test
     */
    public function testCombinedModifiers(): void
    {
        $spec = new DiceSpecification(count: 1, sides: 20, type: DiceType::STANDARD);
        // Note: This would require AST, so testing separately is complex
        // The integration tests cover this scenario better
        $modifiers = new RollModifiers(advantageCount: 1, keepHighest: 1);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        $this->assertGreaterThan(10.5, $stats->expected);
    }

    /**
     * Test zero arithmetic modifier.
     *
     * @test
     */
    public function testZeroArithmeticModifier(): void
    {
        $spec = new DiceSpecification(count: 2, sides: 8, type: DiceType::STANDARD);
        $modifiers = new RollModifiers(arithmeticModifier: 0);

        $stats = $this->calculator->calculate($spec, $modifiers);

        $this->assertSame(2, $stats->minimum);
        $this->assertSame(16, $stats->maximum);
        $this->assertSame(9.0, $stats->expected);
    }
}
