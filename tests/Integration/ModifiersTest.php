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
    public function testSimpleAddition(): void
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
    public function testSimpleSubtraction(): void
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
     * Test parentheses for order of operations.
     */
    public function testParentheses(): void
    {
        $expression = $this->phpdice->parse('(2d6+3)*2');
        $stats = $expression->statistics;

        // (2+3)*2 = 10 minimum
        // (12+3)*2 = 30 maximum
        $this->assertEquals(10, $stats->minimum);
        $this->assertEquals(30, $stats->maximum);
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
     * Test ceiling function.
     */
    public function testCeilingFunction(): void
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
     * Test round function.
     */
    public function testAbsFunction(): void
    {
        $result = $this->dice->roll('abs(1d20/2)');

        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(0, $result->total);
        $this->assertLessThanOrEqual(10, $result->total);
    }    

    /**
     * Test complex expression.
     */
    public function testComplexExpression(): void
    {
        $expression = $this->phpdice->parse('(2d6+3)*2-5');
        $stats = $expression->statistics;

        // (2+3)*2-5 = 5 minimum
        // (12+3)*2-5 = 25 maximum
        $this->assertEquals(5, $stats->minimum);
        $this->assertEquals(25, $stats->maximum);
    }

    /**
     * Test statistics for addition.
     */
    public function testStatisticsAddition(): void
    {
        $expression = $this->phpdice->parse('3d6+5');
        $stats = $expression->statistics;

        $this->assertEquals(8, $stats->minimum);   // 3+5
        $this->assertEquals(23, $stats->maximum);  // 18+5
        $this->assertEquals(15.5, $stats->expected); // 10.5+5
    }

    /**
     * Test statistics for multiplication.
     */
    public function testStatisticsMultiplication(): void
    {
        $expression = $this->phpdice->parse('2d6*2');
        $stats = $expression->statistics;

        $this->assertEquals(4, $stats->minimum);   // 2*2
        $this->assertEquals(24, $stats->maximum);  // 12*2
        $this->assertEquals(14.0, $stats->expected); // 7*2
    }

    /**
     * Test division by zero validation.
     */
    public function testDivisionByZero(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Division by zero');

        // This will fail when we try to roll and evaluate
        $expression = $this->phpdice->parse('1d20+0');
        $result = $this->phpdice->roll('1d20/0');
    }
}
