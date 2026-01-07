<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for decimal number modifiers.
 */
#[CoversClass(PHPDice::class)]
class DecimalModifiersTest extends BaseTestCaseMock
{
    /**
     * Test rolling with decimal multiplier and round function.
     */
    public function testRoundWithDecimalMultiplier(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(10);

        $result = $this->phpdice->roll('round(1d20 * 1.4)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([10], $result->diceValues);
        $this->assertEquals(14, $result->total); // round(10 * 1.4) = round(14.0) = 14
    }

    /**
     * Test rolling with decimal addition.
     */
    public function testDecimalAddition(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(5);

        $result = $this->phpdice->roll('1d6 + 2.5');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([5], $result->diceValues);
        $this->assertEquals(7.5, $result->total); // 5 + 2.5 = 7.5
    }

    /**
     * Test rolling with decimal multiplication.
     */
    public function testDecimalMultiplication(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(8);

        $result = $this->phpdice->roll('1d20 * 0.5');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([8], $result->diceValues);
        $this->assertEquals(4.0, $result->total); // 8 * 0.5 = 4.0
    }

    /**
     * Test floor function with decimal multiplication.
     */
    public function testFloorWithDecimalMultiplication(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(15);

        $result = $this->phpdice->roll('floor(1d20 * 0.5)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([15], $result->diceValues);
        $this->assertEquals(7.0, $result->total); // floor(15 * 0.5) = floor(7.5) = 7.0
    }

    /**
     * Test ceil function with decimal multiplication.
     */
    public function testCeilWithDecimalMultiplication(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(5);

        $result = $this->phpdice->roll('ceil(1d8 * 1.25)');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([5], $result->diceValues);
        $this->assertEquals(7.0, $result->total); // ceil(5 * 1.25) = ceil(6.25) = 7.0
    }

    /**
     * Test multiple dice with decimal multiplication.
     */
    public function testMultipleDiceWithDecimalMultiplication(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 5, 3);

        $result = $this->phpdice->roll('3d6 * 0.75');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([4, 5, 3], $result->diceValues);
        $this->assertEquals(9.0, $result->total); // (4 + 5 + 3) * 0.75 = 12 * 0.75 = 9.0
    }

    /**
     * Test parsing decimal numbers in expressions.
     */
    public function testParseDecimalNumbers(): void
    {
        $expression = $this->phpdice->parse('1d20 * 1.5');

        $this->assertSame('1d20 * 1.5', $expression->originalExpression);
        $this->assertSame(1, $expression->specification->count);
        $this->assertSame(20, $expression->specification->sides);
    }

    /**
     * Test that integer operations still work correctly.
     */
    public function testIntegerOperationsStillWork(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(12);

        $result = $this->phpdice->roll('1d20 + 3');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([12], $result->diceValues);
        $this->assertEquals(15, $result->total); // 12 + 3 = 15
        $this->assertIsInt($result->total);
    }
}
