<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for modifiers and arithmetic (User Story 2).
 */
#[CoversClass(PHPDice::class)]
class ModifiersTest extends BaseTestCaseMock
{
    /**
     * Test simple addition modifier.
     */
    public function testAddition(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(10);

        $result = $this->phpdice->roll('1d20+5');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(10 + 5, $result->total);
    }

    /**
     * Test simple subtraction modifier.
     */
    public function testSubtraction(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 5, 3);

        $result = $this->phpdice->roll('3d6-2');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals(1 + 5 + 3 - 2, $result->total);
    }

    /**
     * Test multiplication.
     */
    public function testMultiplication(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 5);

        $result = $this->phpdice->roll('2d6*2');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals((1 + 5) * 2, $result->total);
    }

    /**
     * Test division.
     */
    public function testDivision(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(11);

        $result = $this->phpdice->roll('1d20/2');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(5.5, $result->total);
    }

    /**
     * Test modulo.
     */
    public function testModulo(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(11);

        $result = $this->phpdice->roll('1d20~2');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(1, $result->total);
    }

    /**
     * Test power.
     */
    public function testPower(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(11);

        $result = $this->phpdice->roll('1d20^2');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(121, $result->total);
    }

    /**
     * Test parentheses for order of operations.
     */
    public function testParentheses(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 2);

        $result = $this->phpdice->roll('(2d6+3)*2');

        // $this->assertEquals(2.0, $result->diceValues);
        $this->assertEquals(16, $result->total);
    }

    /**
     * Test floor function.
     */
    public function testFloorFunction(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(11);

        $result = $this->phpdice->roll('floor(1d20/2)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test ceil function.
     */
    public function testCeilFunction(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(11);

        $result = $this->phpdice->roll('ceil(1d20/2)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(6, $result->total);
    }

    /**
     * Test round function.
     */
    public function testRoundFunction(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(8);

        $result = $this->phpdice->roll('round(1d20/3)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(3, $result->total);
    }

    /**
     * Test abs function.
     */
    public function testAbsFunction(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(11);

        $result = $this->phpdice->roll('abs(1d20/2)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test complex expression.
     */
    public function testComplexExpression(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 4);

        $result = $this->phpdice->roll('(2d6+3)*2-5');

        // $this->assertEquals(2, $result->diceValues);
        $this->assertEquals(13, $result->total);
    }

    /**
     * Test statistics for addition.
     */
    public function testStatisticsAddition(): void
    {
        $expression = $this->phpdice->parse('3d6+5');
        $stats = $expression->statistics;

        $this->assertEquals(8, $stats->minimum);
        $this->assertEquals(15.5, $stats->expected);
        $this->assertEquals(23, $stats->maximum);
        //$this->assertEquals(22, $stats->variance);
        //$this->assertEquals(22, $stats->standardDeviation);
    }

    /**
     * Test statistics for subtraction.
     */
    public function testStatisticsSubtraction(): void
    {
        $expression = $this->phpdice->parse('3d6-5');
        $stats = $expression->statistics;

        $this->assertEquals(-2, $stats->minimum);
        $this->assertEquals(5.5, $stats->expected);
        $this->assertEquals(13, $stats->maximum);
        //$this->assertEquals(22, $stats->variance);
        //$this->assertEquals(22, $stats->standardDeviation);
    }

    /**
     * Test statistics for multiplication.
     */
    public function testStatisticsMultiplication(): void
    {
        $expression = $this->phpdice->parse('2d6*2');
        $stats = $expression->statistics;

        $this->assertEquals(4, $stats->minimum);
        $this->assertEquals(14, $stats->expected);
        $this->assertEquals(24, $stats->maximum);
        //$this->assertEquals(22, $stats->variance);
        //$this->assertEquals(22, $stats->standardDeviation);
    }

    /**
     * Test statistics for division.
     */
    public function testStatisticsDivision(): void
    {
        $expression = $this->phpdice->parse('2d6/2');
        $stats = $expression->statistics;

        $this->assertEquals(1, $stats->minimum);
        $this->assertEquals(3.5, $stats->expected);
        $this->assertEquals(6, $stats->maximum);
        //$this->assertEquals(5.5, $stats->variance);
        //$this->assertEquals(2.3452078799117, $stats->standardDeviation);
    }

    /**
     * Test statistics for modulo.
     */
    public function testStatisticsModulo(): void
    {
        $expression = $this->phpdice->parse('2d6 ~ 2');
        $stats = $expression->statistics;

        $this->assertEquals(0, $stats->minimum);
        $this->assertEquals(0.5, $stats->expected);
        $this->assertEquals(1, $stats->maximum);
        //$this->assertEquals(5.5, $stats->variance);
        //$this->assertEquals(2.3452078799117, $stats->standardDeviation);
    }

    /**
     * Test statistics for power.
     */
    public function testStatisticsPower(): void
    {
        $expression = $this->phpdice->parse('2d6 ^ 2');
        $stats = $expression->statistics;

        $this->assertEquals(4, $stats->minimum);
        $this->assertEquals(49, $stats->expected);
        $this->assertEquals(144, $stats->maximum);
        //$this->assertEquals(5.5, $stats->variance);
        //$this->assertEquals(2.3452078799117, $stats->standardDeviation);
    }

    /**
     * Test division by zero validation.
     */
    public function testDivisionByZero(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Division by zero');

        // This will fail when we try to roll and evaluate
        //$expression = $this->phpdice->parse('1d20+0');
        $result = $this->phpdice->roll('1d20/0');
    }

    /**
     * Test modulo by zero validation.
     */
    public function testModuloByZero(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Modulo by zero');

        // This will fail when we try to roll and evaluate
        //$expression = $this->phpdice->parse('1d20+0');
        $result = $this->phpdice->roll('1d20~0');
    }

    /**
     * Test min function with two arguments.
     */
    public function testMinFunctionWithTwoArguments(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 5);

        $result = $this->phpdice->roll('min(1d20, 1d20)');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test min function with three arguments.
     */
    public function testMinFunctionWithThreeArguments(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 5, 12);

        $result = $this->phpdice->roll('min(1d20, 1d20, 1d20)');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test min function with dice and numbers.
     */
    public function testMinFunctionWithDiceAndNumbers(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15);

        $result = $this->phpdice->roll('min(1d20, 10)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(10, $result->total);
    }

    /**
     * Test max function with two arguments.
     */
    public function testMaxFunctionWithTwoArguments(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 5);

        $result = $this->phpdice->roll('max(1d20, 1d20)');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(10, $result->total);
    }

    /**
     * Test max function with three arguments.
     */
    public function testMaxFunctionWithThreeArguments(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 5, 12);

        $result = $this->phpdice->roll('max(1d20, 1d20, 1d20)');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals(12, $result->total);
    }

    /**
     * Test max function with dice and numbers.
     */
    public function testMaxFunctionWithDiceAndNumbers(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(8);

        $result = $this->phpdice->roll('max(1d20, 10)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals(10, $result->total);
    }

    /**
     * Test min function with expressions.
     */
    public function testMinFunctionWithExpressions(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 3);

        $result = $this->phpdice->roll('min(1d20+5, 1d6)');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(3, $result->total); // min(10+5, 3) = 3
    }

    /**
     * Test max function with expressions.
     */
    public function testMaxFunctionWithExpressions(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 3);

        $result = $this->phpdice->roll('max(1d20+5, 1d6)');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(15, $result->total); // max(10+5, 3) = 15
    }
}
