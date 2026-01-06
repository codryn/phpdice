<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for basic dice rolling (User Story 1).
 */
#[CoversClass(PHPDice::class)]
class BasicRollingTest extends BaseTestCaseMock
{
    /**
     * Test parsing basic dice notation.
     */
    public function testParseBasicDiceNotation(): void
    {
        $expression = $this->phpdice->parse('3d6');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
        $this->assertSame('3d6', $expression->originalExpression);
    }

    /**
     * Test rolling basic dice.
     */
    public function testRollBasicDice(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 4, 5);

        $result = $this->phpdice->roll('3d6');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([3, 4, 5], $result->diceValues);
        $this->assertEquals(12, $result->total);
    }

    /**
     * Test rolling 1d20 (common D&D roll).
     */
    public function testRoll1d20(): void
    {
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(15);

        $result = $this->phpdice->roll('1d20');

        $this->assertCount(1, $result->diceValues);
        $this->assertEquals([15], $result->diceValues);
        $this->assertEquals(15, $result->total);
    }

    /**
     * Test rolling 2d10.
     */
    public function testRoll2d10(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(8, 7);

        $result = $this->phpdice->roll('2d10');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals([8, 7], $result->diceValues);
        $this->assertEquals(15, $result->total);
    }

    /**
     * Test rolling multiple dice.
     */
    public function testRollMultipleDice(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 6, 3);

        $result = $this->phpdice->roll('1d10 + 2d8');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([2, 6, 3], $result->diceValues);
        $this->assertEquals(11, $result->total);
    }

    /**
     * Test statistical calculations.
     */
    public function testStatistics(): void
    {
        $expression = $this->phpdice->parse('3d6');
        $stats = $expression->statistics;

        $this->assertSame(3, $stats->minimum);
        $this->assertSame(18, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    /**
     * Test invalid expression - missing dice count.
     */
    public function testInvalidExpressionMissingCount(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->phpdice->parse('d6');
    }

    /**
     * Test invalid expression - missing sides.
     */
    public function testInvalidExpressionMissingSides(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->phpdice->parse('3d');
    }

    /**
     * Test invalid expression - non-numeric.
     */
    public function testInvalidExpressionNonNumeric(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->phpdice->parse('abc');
    }

    /**
     * Test zero dice count validation.
     */
    public function testZeroDiceCount(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Dice count must be at least 1');
        $this->phpdice->parse('0d6');
    }

    /**
     * Test single-sided die validation (must be at least 2 sides).
     */
    public function testSingleSidedDie(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Dice must have at least 2 sides');
        $this->phpdice->parse('3d1');
    }

    /**
     * Test maximum dice count validation (100 max).
     */
    public function testTooManyDice(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot roll more than 100 dice');
        $this->phpdice->parse('101d6');
    }

    /**
     * Test maximum sides validation (100 max).
     */
    public function testTooManySides(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Dice cannot have more than 100 sides');
        $this->phpdice->parse('3d101');
    }

    /**
     * Test rolling with pre-parsed DiceExpression.
     */
    public function testRollExpression(): void
    {
        // Parse once
        $expression = $this->phpdice->parse('2d6+3');

        // Roll multiple times with the same parsed expression
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 5, 2, 6);

        $result1 = $this->phpdice->rollExpression($expression);
        $this->assertEquals([4, 5], $result1->diceValues);
        $this->assertEquals(12, $result1->total); // 4 + 5 + 3

        $result2 = $this->phpdice->rollExpression($expression);
        $this->assertEquals([2, 6], $result2->diceValues);
        $this->assertEquals(11, $result2->total); // 2 + 6 + 3
    }
}
