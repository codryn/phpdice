<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for math-only expressions (no dice notation).
 */
#[CoversClass(PHPDice::class)]
class MathOnlyTest extends BaseTestCaseMock
{
    /**
     * Test simple addition without dice.
     */
    public function testSimpleAddition(): void
    {
        $result = $this->phpdice->roll('1+1');

        $this->assertEquals(2, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test simple number constant.
     */
    public function testSimpleNumber(): void
    {
        $result = $this->phpdice->roll('42');

        $this->assertEquals(42, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test max function.
     */
    public function testMaxFunction(): void
    {
        $result = $this->phpdice->roll('max(1,2)');

        $this->assertEquals(2, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test max function with three arguments.
     */
    public function testMaxFunctionThreeArgs(): void
    {
        $result = $this->phpdice->roll('max(5,2,8)');

        $this->assertEquals(8, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test min function.
     */
    public function testMinFunction(): void
    {
        $result = $this->phpdice->roll('min(1,2)');

        $this->assertEquals(1, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test min function with three arguments.
     */
    public function testMinFunctionThreeArgs(): void
    {
        $result = $this->phpdice->roll('min(5,2,8)');

        $this->assertEquals(2, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test round function with addition.
     */
    public function testRoundFunctionWithAddition(): void
    {
        $result = $this->phpdice->roll('round(1.6)+1');

        $this->assertEquals(3, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test multiplication.
     */
    public function testMultiplication(): void
    {
        $result = $this->phpdice->roll('2*3');

        $this->assertEquals(6, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test division.
     */
    public function testDivision(): void
    {
        $result = $this->phpdice->roll('10/2');

        $this->assertEquals(5, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test subtraction.
     */
    public function testSubtraction(): void
    {
        $result = $this->phpdice->roll('10-3');

        $this->assertEquals(7, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test floor function.
     */
    public function testFloorFunction(): void
    {
        $result = $this->phpdice->roll('floor(1.9)');

        $this->assertEquals(1, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test ceil function.
     */
    public function testCeilFunction(): void
    {
        $result = $this->phpdice->roll('ceil(1.1)');

        $this->assertEquals(2, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test complex expression with parentheses.
     */
    public function testComplexExpressionWithParentheses(): void
    {
        $result = $this->phpdice->roll('(2+3)*4');

        $this->assertEquals(20, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test power operator.
     */
    public function testPowerOperator(): void
    {
        $result = $this->phpdice->roll('2^3');

        $this->assertEquals(8, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test empty string throws error.
     */
    public function testEmptyStringThrowsError(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->phpdice->roll('');
    }

    /**
     * Test whitespace-only string throws error.
     */
    public function testWhitespaceOnlyThrowsError(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->phpdice->roll('   ');
    }

    /**
     * Test that math-only expression can be parsed.
     */
    public function testMathOnlyParsing(): void
    {
        $expression = $this->phpdice->parse('1+1');

        $this->assertNull($expression->specification);
        $this->assertEquals('1+1', $expression->originalExpression);
        $this->assertNotNull($expression->astRoot);
    }

    /**
     * Test that math-only expression has correct statistics.
     */
    public function testMathOnlyStatistics(): void
    {
        $expression = $this->phpdice->parse('5+3');

        $this->assertEquals(8, $expression->statistics->minimum);
        $this->assertEquals(8, $expression->statistics->maximum);
        $this->assertEquals(8.0, $expression->statistics->expected);
    }

    /**
     * Test that math-only expression with variables works.
     */
    public function testMathOnlyWithVariables(): void
    {
        $result = $this->phpdice->roll('10+$bonus$', ['bonus' => 5]);

        $this->assertEquals(15, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test that dice expressions still work after adding math-only support.
     */
    public function testDiceExpressionsStillWork(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 4, 5);

        $result = $this->phpdice->roll('3d6');

        $this->assertEquals(12, $result->total);
        $this->assertCount(3, $result->diceValues);
        $this->assertNotNull($result->expression->specification);
    }

    /**
     * Test that dice with modifiers still work.
     */
    public function testDiceWithModifiersStillWork(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 5);

        $result = $this->phpdice->roll('2d6+3');

        $this->assertEquals(12, $result->total);
        $this->assertCount(2, $result->diceValues);
        $this->assertNotNull($result->expression->specification);
    }
}
