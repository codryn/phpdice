<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

require_once __DIR__ . '/BaseTestCaseMock.php';

/**
 * Test conditional (boolean algebra) expressions.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 * @covers \Codryn\PHPDice\Model\DiceExpression
 * @covers \Codryn\PHPDice\Model\RollResult
 * @covers \Codryn\PHPDice\Parser\AST\ConditionalNode
 */
class ConditionalTest extends BaseTestCaseMock
{
    /**
     * Test basic conditional with placeholder comparison - false branch.
     */
    public function testBasicConditionalWithPlaceholderFalseBranch(): void
    {
        // if $crit$ > 0: 2d6+5 | 1d6+2
        $expression = 'if $crit$ > 0: 2d6+5 | 1d6+2';

        // When $crit$ = 0, should roll 1d6+2
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(4);

        $result = $this->phpdice->roll($expression, ['crit' => 0]);
        $this->assertEquals(6, $result->total); // 4+2
    }

    /**
     * Test basic conditional with placeholder comparison - true branch.
     */
    public function testBasicConditionalWithPlaceholderTrueBranch(): void
    {
        // if $crit$ > 0: 2d6+5 | 1d6+2
        $expression = 'if $crit$ > 0: 2d6+5 | 1d6+2';

        // When $crit$ = 1, should roll 2d6+5
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 5);

        $result = $this->phpdice->roll($expression, ['crit' => 1]);
        $this->assertEquals(13, $result->total); // 3+5+5
    }

    /**
     * Test conditional with == comparison.
     */
    public function testConditionalWithEquality(): void
    {
        $expression = 'if $rank$ == 10: 4 | 2';

        // When $rank$ = 10, should return 4
        $result = $this->phpdice->roll($expression, ['rank' => 10]);
        $this->assertEquals(4, $result->total);

        // When $rank$ != 10, should return 2
        $result = $this->phpdice->roll($expression, ['rank' => 5]);
        $this->assertEquals(2, $result->total);
    }

    /**
     * Test conditional with != comparison.
     */
    public function testConditionalWithNotEqual(): void
    {
        $expression = 'if $crit$ != 0: 10 | 5';

        // When $crit$ != 0, should return 10
        $result = $this->phpdice->roll($expression, ['crit' => 1]);
        $this->assertEquals(10, $result->total);

        // When $crit$ == 0, should return 5
        $result = $this->phpdice->roll($expression, ['crit' => 0]);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test conditional with >= comparison.
     */
    public function testConditionalWithGreaterOrEqual(): void
    {
        $expression = 'if $rank$ >= 10: 4 | 2';

        // When $rank$ >= 10, should return 4
        $result = $this->phpdice->roll($expression, ['rank' => 10]);
        $this->assertEquals(4, $result->total);

        $result = $this->phpdice->roll($expression, ['rank' => 15]);
        $this->assertEquals(4, $result->total);

        // When $rank$ < 10, should return 2
        $result = $this->phpdice->roll($expression, ['rank' => 9]);
        $this->assertEquals(2, $result->total);
    }

    /**
     * Test conditional with < comparison.
     */
    public function testConditionalWithLessThan(): void
    {
        $expression = 'if $value$ < 5: 1 | 0';

        // When $value$ < 5, should return 1
        $result = $this->phpdice->roll($expression, ['value' => 4]);
        $this->assertEquals(1, $result->total);

        // When $value$ >= 5, should return 0
        $result = $this->phpdice->roll($expression, ['value' => 5]);
        $this->assertEquals(0, $result->total);
    }

    /**
     * Test conditional with <= comparison.
     */
    public function testConditionalWithLessOrEqual(): void
    {
        $expression = 'if $value$ <= 5: 1 | 0';

        // When $value$ <= 5, should return 1
        $result = $this->phpdice->roll($expression, ['value' => 5]);
        $this->assertEquals(1, $result->total);

        // When $value$ > 5, should return 0
        $result = $this->phpdice->roll($expression, ['value' => 6]);
        $this->assertEquals(0, $result->total);
    }

    /**
     * Test conditional used in arithmetic expression - true branch.
     */
    public function testConditionalInArithmeticTrueBranch(): void
    {
        // 1d20 + (if $rank$ >= 10: 4 | 2)
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2)';

        // When $rank$ >= 10, should add 4
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(12);

        $result = $this->phpdice->roll($expression, ['rank' => 10]);
        $this->assertEquals(16, $result->total); // 12+4
    }

    /**
     * Test conditional used in arithmetic expression - false branch.
     */
    public function testConditionalInArithmeticFalseBranch(): void
    {
        // 1d20 + (if $rank$ >= 10: 4 | 2)
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2)';

        // When $rank$ < 10, should add 2
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(15);

        $result = $this->phpdice->roll($expression, ['rank' => 9]);
        $this->assertEquals(17, $result->total); // 15+2
    }

    /**
     * Test conditional with dice roll in condition - true branch.
     */
    public function testConditionalWithDiceInConditionTrueBranch(): void
    {
        // if 1d6 > 3: 10 | 5
        $expression = 'if 1d6 > 3: 10 | 5';

        // Test when 1d6 rolls 5 (> 3), should return 10
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(5);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(10, $result->total);
    }

    /**
     * Test conditional with dice roll in condition - false branch.
     */
    public function testConditionalWithDiceInConditionFalseBranch(): void
    {
        // if 1d6 > 3: 10 | 5
        $expression = 'if 1d6 > 3: 10 | 5';

        // Test when 1d6 rolls 2 (<= 3), should return 5
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(2);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test all comparison operators.
     */
    public function testAllComparisonOperators(): void
    {
        $operators = [
            ['>', 6, 5, true],  // 6 > 5 = true
            ['>', 5, 6, false], // 5 > 6 = false
            ['>=', 5, 5, true], // 5 >= 5 = true
            ['>=', 4, 5, false], // 4 >= 5 = false
            ['<', 4, 5, true],  // 4 < 5 = true
            ['<', 6, 5, false], // 6 < 5 = false
            ['<=', 5, 5, true], // 5 <= 5 = true
            ['<=', 6, 5, false], // 6 <= 5 = false
            ['==', 5, 5, true], // 5 == 5 = true
            ['==', 5, 6, false], // 5 == 6 = false
            ['!=', 5, 6, true], // 5 != 6 = true
            ['!=', 5, 5, false], // 5 != 5 = false
        ];

        foreach ($operators as [$op, $left, $right, $expected]) {
            $expression = "if \$a\$ {$op} \$b\$: 1 | 0";
            $result = $this->phpdice->roll($expression, ['a' => $left, 'b' => $right]);
            $this->assertEquals(
                $expected ? 1 : 0,
                $result->total,
                "Failed for: {$left} {$op} {$right}"
            );
        }
    }

    /**
     * Test that conditional expressions have non-zero statistics.
     */
    public function testConditionalHasStatistics(): void
    {
        $expression = 'if $crit$ > 0: 2d6+5 | 1d6+2';
        $result = $this->phpdice->roll($expression, ['crit' => 0]);

        // Main expression should have valid statistics covering both branches
        $stats = $result->expression->statistics;
        $this->assertNotNull($stats);
        $this->assertGreaterThan(0, $stats->expected, 'Expected value should be greater than 0');
        $this->assertGreaterThan(0, $stats->minimum, 'Minimum should be greater than 0');
        $this->assertGreaterThan(0, $stats->maximum, 'Maximum should be greater than 0');

        // Statistics should cover the range of both branches
        // True branch: 2d6+5 (min: 7, max: 17, expected: 12)
        // False branch: 1d6+2 (min: 3, max: 8, expected: 5.5)
        // Combined: min: 3, max: 17, expected: (12 + 5.5) / 2 = 8.75
        $this->assertSame(3, $stats->minimum);
        $this->assertSame(17, $stats->maximum);
        $this->assertSame(8.75, $stats->expected);
    }

    /**
     * Test conditional statistics with different value ranges.
     */
    public function testConditionalStatisticsWithDifferentRanges(): void
    {
        $expression = 'if $check$ >= 10: 3d6 | 1d4';

        // Parse to get statistics
        $expr = $this->phpdice->parse($expression, ['check' => 5]);

        // Statistics should cover both branches
        // True branch: 3d6 (min: 3, max: 18, expected: 10.5)
        // False branch: 1d4 (min: 1, max: 4, expected: 2.5)
        // Combined: min: 1, max: 18, expected: (10.5 + 2.5) / 2 = 6.5
        $stats = $expr->statistics;
        $this->assertSame(1, $stats->minimum);
        $this->assertSame(18, $stats->maximum);
        $this->assertSame(6.5, $stats->expected);
    }

    /**
     * Test nested conditional expressions have statistics.
     */
    public function testNestedConditionalHasStatistics(): void
    {
        $expression = 'if $a$ > 0: (if $b$ > 0: 2d6 | 1d6) | 1d4';

        // Parse to get statistics
        $expr = $this->phpdice->parse($expression, ['a' => 0, 'b' => 0]);

        // Statistics should cover all three possible outcomes
        $stats = $expr->statistics;
        $this->assertNotNull($stats);
        $this->assertGreaterThan(0, $stats->minimum);
        $this->assertGreaterThan(0, $stats->maximum);
        $this->assertGreaterThan(0, $stats->expected);

        // The minimum should be at least 1 (from 1d4 or 1d6)
        $this->assertGreaterThanOrEqual(1, $stats->minimum);
        // The maximum should be at least 12 (from 2d6)
        $this->assertGreaterThanOrEqual(12, $stats->maximum);
    }
}
