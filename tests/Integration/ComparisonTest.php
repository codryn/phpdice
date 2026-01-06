<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

/**
 * Integration tests for success rolls and comparisons (US8).
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Roller\DiceRoller
 */
final class ComparisonTest extends BaseTestCaseMock
{
    /**
     * AC1: Expression includes both die value and success/failure flag.
     *
     * Given an expression "1d20 >= 15"
     * When rolled
     * Then the result includes both the die value and a success/failure flag
     */
    public function testComparisonIncludesValueAndFlag(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(15);

        $result = $this->phpdice->roll('1d20 >= 15');

        // Should have a die value
        $this->assertNotNull($result->diceValues);
        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([15], $result->diceValues);

        // Should have isSuccess flag (boolean, not null)
        $this->assertIsBool($result->isSuccess);
        $this->assertTrue($result->isSuccess);

        // Total should equal the die value
        $this->assertEquals(15, $result->total);
    }

    /**
     * AC2: Success flag is true when roll meets threshold.
     *
     * Given a comparison expression
     * When the roll meets the threshold
     * Then the success flag is true
     */
    public function testSuccessFlagTrueWhenMeetsThreshold(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(10);

        // Use a guaranteed success: 1d20+20 >= 1 (minimum 21, threshold 1)
        $result = $this->phpdice->roll('1d20+20 >= 1');

        $this->assertTrue($result->isSuccess, 'Expected success for 1d20+20 >= 1 (always succeeds)');
        $this->assertEquals(30, $result->total);
    }

    /**
     * AC2: Test >= operator.
     */
    public function testGreaterThanOrEqualOperator(): void
    {
        // Test success case
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 5);

        $result = $this->phpdice->roll('1d20 >= 10');
        $this->assertTrue($result->isSuccess, 'Expected success when total (10) >= 10');
        $this->assertEquals(10, $result->total);

        // Test failure case
        $result2 = $this->phpdice->roll('1d20 >= 10');
        $this->assertFalse($result2->isSuccess, 'Expected failure when total (5) < 10');
        $this->assertEquals(5, $result2->total);
    }

    /**
     * AC3: Success flag is false when roll fails threshold.
     *
     * Given a comparison expression
     * When the roll fails the threshold
     * Then the success flag is false
     */
    public function testSuccessFlagFalseWhenFailsThreshold(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(20);

        // Use a guaranteed failure: 1d20 >= 25 (maximum 20, threshold 25)
        $result = $this->phpdice->roll('1d20 >= 25');

        $this->assertFalse($result->isSuccess, 'Expected failure for 1d20 >= 25 (always fails)');
        $this->assertEquals(20, $result->total);
    }

    /**
     * AC4: Can inspect roll value, threshold, and success status.
     *
     * Given a success roll result
     * When inspected
     * Then I can see the actual roll value, the threshold, and the success status
     */
    public function testCanInspectComparisonDetails(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(12);

        $expression = '1d20+5 >= 15';
        $result = $this->phpdice->roll($expression);

        // Can see the actual roll value (total)
        $this->assertEquals(17, $result->total); // 12 + 5

        // Can see the threshold from the expression
        $expr = $this->phpdice->parse($expression);
        $this->assertSame(15, $expr->comparisonThreshold);
        $this->assertSame('>=', $expr->comparisonOperator);

        // Can see the success status
        $this->assertIsBool($result->isSuccess);
        $this->assertTrue($result->isSuccess); // 17 >= 15
    }

    /**
     * Test comparison with arithmetic expression.
     */
    public function testComparisonWithArithmetic(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(15);

        $expression = '1d20+3 >= 15';
        $result = $this->phpdice->roll($expression);

        // Total should include the +3 modifier
        $this->assertEquals(18, $result->total); // 15 + 3

        // Success should be based on total (including +3)
        $this->assertTrue($result->isSuccess); // 18 >= 15
    }

    /**
     * Test > operator (strict greater than).
     */
    public function testGreaterThanOperator(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 20);

        // Guaranteed success: 1d20+20 > 1 (minimum 21, threshold 1)
        $result = $this->phpdice->roll('1d20+20 > 1');
        $this->assertTrue($result->isSuccess);
        $this->assertEquals(35, $result->total); // 15 + 20

        // Guaranteed failure: 1d20 > 20 (maximum 20, threshold 20, needs > not >=)
        $result2 = $this->phpdice->roll('1d20 > 20');
        $this->assertFalse($result2->isSuccess);
        $this->assertEquals(20, $result2->total);
    }

    /**
     * Test <= operator.
     */
    public function testLessThanOrEqualOperator(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 1);

        // Guaranteed success: 1d20 <= 25 (maximum 20, threshold 25)
        $result = $this->phpdice->roll('1d20 <= 25');
        $this->assertTrue($result->isSuccess);
        $this->assertEquals(15, $result->total);

        // Guaranteed failure: 1d20 <= 0 (minimum 1, threshold 0)
        $result2 = $this->phpdice->roll('1d20 <= 0');
        $this->assertFalse($result2->isSuccess);
        $this->assertEquals(1, $result2->total);
    }

    /**
     * Test < operator.
     */
    public function testLessThanOperator(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 1);

        // Guaranteed success: 1d20 < 25 (maximum 20, threshold 25)
        $result = $this->phpdice->roll('1d20 < 25');
        $this->assertTrue($result->isSuccess);
        $this->assertEquals(15, $result->total);

        // Guaranteed failure: 1d20 < 1 (minimum 1, threshold 1, needs < not <=)
        $result2 = $this->phpdice->roll('1d20 < 1');
        $this->assertFalse($result2->isSuccess);
        $this->assertEquals(1, $result2->total);
    }

    /**
     * Test == operator.
     */
    public function testEqualityOperator(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 4);

        // Test match
        $result = $this->phpdice->roll('1d6 == 3');
        $this->assertTrue($result->isSuccess);
        $this->assertEquals(3, $result->total);

        // Test non-match
        $result2 = $this->phpdice->roll('1d6 == 3');
        $this->assertFalse($result2->isSuccess);
        $this->assertEquals(4, $result2->total);
    }

    /**
     * Test that expressions without comparisons don't have isSuccess.
     */
    public function testNoComparisonNoSuccessFlag(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(10);

        $result = $this->phpdice->roll('1d20+5');

        $this->assertNull($result->isSuccess, 'Expected null isSuccess for expression without comparison');
        $this->assertEquals(15, $result->total); // 10 + 5
    }

    /**
     * Test comparison with complex expression.
     */
    public function testComparisonWithComplexExpression(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 5);

        $expression = '2d6+3 >= 10';
        $result = $this->phpdice->roll($expression);

        // Total should be 2d6 + 3
        $this->assertEquals(12, $result->total); // 4 + 5 + 3

        // Success should match comparison
        $this->assertTrue($result->isSuccess); // 12 >= 10
    }

    /**
     * Test comparison with placeholders.
     */
    public function testComparisonWithPlaceholders(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(12);

        $expression = '1d20+%bonus% >= %dc%';
        $variables = ['bonus' => 5, 'dc' => 15];

        $result = $this->phpdice->roll($expression, $variables);

        // Total should be 1d20 + 5
        $this->assertEquals(17, $result->total); // 12 + 5

        // Success should be based on >= 15
        $this->assertTrue($result->isSuccess); // 17 >= 15
    }

    /**
     * Test that single-die comparisons are success rolls, not success counting.
     */
    public function testSingleDieComparisonIsSuccessRoll(): void
    {
        $expression = '1d20 >= 15';
        $expr = $this->phpdice->parse($expression);

        // Should be expression-level comparison, not success counting
        $this->assertSame('>=', $expr->comparisonOperator);
        $this->assertSame(15, $expr->comparisonThreshold);
        $this->assertNull($expr->modifiers->successThreshold);
        $this->assertNull($expr->modifiers->successOperator);
    }

    /**
     * Test that multi-die comparisons without arithmetic are success counting.
     */
    public function testMultiDieComparisonIsSuccessCounting(): void
    {
        $expression = '5d10 >= 7';
        $expr = $this->phpdice->parse($expression);

        // Should be success counting, not expression-level comparison
        $this->assertSame(7, $expr->modifiers->successThreshold);
        $this->assertSame('>=', $expr->modifiers->successOperator);
        $this->assertNull($expr->comparisonOperator);
        $this->assertNull($expr->comparisonThreshold);
    }

    /**
     * Test comparison with modifiers (advantage).
     */
    public function testComparisonWithAdvantage(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(12, 17);

        $expression = '1d20 advantage >= 15';
        $result = $this->phpdice->roll($expression);

        // Should roll 2 dice (advantage)
        $this->assertCount(2, $result->diceValues);
        $this->assertEquals([12, 17], $result->diceValues);

        // Total should be the higher of the two
        $this->assertEquals(17, $result->total);

        // Success should match comparison
        $this->assertTrue($result->isSuccess); // 17 >= 15
    }

    /**
     * Test comparison with keep mechanics.
     */
    public function testComparisonWithKeepHighest(): void
    {
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 4, 5, 3);

        $expression = '4d6 keep 3 highest +0 >= 12';
        $result = $this->phpdice->roll($expression);

        // Should roll 4 dice
        $this->assertCount(4, $result->diceValues);
        $this->assertEquals([2, 4, 5, 3], $result->diceValues);

        // Should keep 3
        $this->assertCount(3, $result->keptDice ?? []);

        // Total should be sum of 3 highest: 3+4+5 = 12
        $this->assertEquals(12, $result->total);

        // Success should be based on total of kept dice
        $this->assertTrue($result->isSuccess); // 12 >= 12
    }

    /**
     * Test that comparison doesn't interfere with success counting.
     */
    public function testSuccessCountingStillWorksIndependently(): void
    {
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 8, 9, 5, 10);

        $expression = '5d10 success threshold 7';
        $result = $this->phpdice->roll($expression);

        // Should have success count: 8, 9, 10 are >= 7 = 3 successes
        $this->assertIsInt($result->successCount);
        $this->assertEquals(3, $result->successCount);

        // Should NOT have isSuccess (no comparison)
        $this->assertNull($result->isSuccess);

        // Total should equal success count in success counting mode
        $this->assertEquals(3, $result->total);
    }

    /**
     * Test parsing comparison stores operator and threshold.
     */
    public function testParsingStoresComparisonDetails(): void
    {
        $testCases = [
            ['expression' => '1d20 >= 15', 'operator' => '>=', 'threshold' => 15],
            ['expression' => '1d20 > 10', 'operator' => '>', 'threshold' => 10],
            ['expression' => '1d20 <= 5', 'operator' => '<=', 'threshold' => 5],
            ['expression' => '1d20 < 20', 'operator' => '<', 'threshold' => 20],
            ['expression' => '1d20 == 15', 'operator' => '==', 'threshold' => 15],
        ];

        foreach ($testCases as $testCase) {
            $expr = $this->phpdice->parse($testCase['expression']);

            $this->assertSame(
                $testCase['operator'],
                $expr->comparisonOperator,
                "Expected operator {$testCase['operator']} for expression {$testCase['expression']}"
            );

            $this->assertSame(
                $testCase['threshold'],
                $expr->comparisonThreshold,
                "Expected threshold {$testCase['threshold']} for expression {$testCase['expression']}"
            );
        }
    }
}
