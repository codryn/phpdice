<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for dice expressions with modifiers as function arguments.
 */
#[CoversClass(PHPDice::class)]
class DiceInFunctionsTest extends BaseTestCaseMock
{
    /**
     * Test max function with dice expression with success counting (Shadowrun example).
     */
    public function testMaxWithSuccessCountingShadowrun(): void
    {
        // Roll 12d6, count results >4, max with 5
        // Dice: 2, 3, 2, 6, 1, 4, 5, 6, 2, 3, 6, 3
        // Success count (>4): 5, 6, 5, 6, 6 = 5 successes
        // max(5, 5) = 5
        $this->mockRng->expects($this->exactly(12))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 3, 2, 6, 1, 4, 5, 6, 2, 3, 6, 3);

        $result = $this->phpdice->roll('max(12d6 count >4, 5)');

        $this->assertCount(12, $result->diceValues);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test max with success counting where dice win.
     */
    public function testMaxWithSuccessCountingDiceWin(): void
    {
        // Roll 6d6, count results >=5
        // Dice: 6, 6, 5, 6, 5, 5
        // Success count (>=5): 6 successes
        // max(6, 3) = 6
        $this->mockRng->expects($this->exactly(6))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 6, 5, 6, 5, 5);

        $result = $this->phpdice->roll('max(6d6 count >=5, 3)');

        $this->assertCount(6, $result->diceValues);
        $this->assertEquals(6, $result->total);
    }

    /**
     * Test max with success counting where constant wins.
     */
    public function testMaxWithSuccessCountingConstantWins(): void
    {
        // Roll 6d6, count results >=5
        // Dice: 1, 2, 3, 1, 2, 4
        // Success count (>=5): 0 successes
        // max(0, 3) = 3
        $this->mockRng->expects($this->exactly(6))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 1, 2, 4);

        $result = $this->phpdice->roll('max(6d6 count >=5, 3)');

        $this->assertCount(6, $result->diceValues);
        $this->assertEquals(3, $result->total);
    }

    /**
     * Test min with success counting.
     */
    public function testMinWithSuccessCounting(): void
    {
        // Roll 6d6, count results >=5
        // Dice: 6, 6, 5, 6, 5, 5
        // Success count (>=5): 6 successes
        // min(6, 3) = 3
        $this->mockRng->expects($this->exactly(6))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 6, 5, 6, 5, 5);

        $result = $this->phpdice->roll('min(6d6 count >=5, 3)');

        $this->assertCount(6, $result->diceValues);
        $this->assertEquals(3, $result->total);
    }

    /**
     * Test max with exploding dice.
     */
    public function testMaxWithExplodingDice(): void
    {
        // Roll 2d6 explode (>=6), compare with 1d6
        // First dice group: 6 (explodes) -> 5 = 6+5=11, 3 = 11+3=14
        // Second dice group: 3
        // max(14, 3) = 14
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 5, 3, 3);

        $result = $this->phpdice->roll('max(2d6 explode, 1d6)');

        $this->assertEquals(14, $result->total);
    }

    /**
     * Test max with keep highest.
     */
    public function testMaxWithKeepHighest(): void
    {
        // Roll 4d6 keep 3 highest, compare with 10
        // Dice: 6, 3, 5, 2
        // Keep highest 3: 6, 5, 3 = 14
        // max(14, 10) = 14
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 3, 5, 2);

        $result = $this->phpdice->roll('max(4d6 keep 3 highest, 10)');

        $this->assertEquals(14, $result->total);
    }

    /**
     * Test min with reroll.
     */
    public function testMinWithReroll(): void
    {
        // Roll 3d6 reroll ==1, compare with 8
        // Dice: 1 (reroll) -> 5, 3, 2
        // Total: 5 + 3 + 2 = 10
        // min(10, 8) = 8
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 5, 3, 2);

        $result = $this->phpdice->roll('min(3d6 reroll ==1, 8)');

        $this->assertEquals(8, $result->total);
    }

    /**
     * Test max with three arguments including dice expressions.
     */
    public function testMaxWithThreeArgumentsIncludingDice(): void
    {
        // max(2d6 count >=5, 3d6 count >=5, 4)
        // First group: 6, 5 = 2 successes
        // Second group: 6, 4, 3 = 1 success
        // max(2, 1, 4) = 4
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 5, 6, 4, 3);

        $result = $this->phpdice->roll('max(2d6 count >=5, 3d6 count >=5, 4)');

        $this->assertEquals(4, $result->total);
    }

    /**
     * Test max with plain dice (no modifiers) still works.
     */
    public function testMaxWithPlainDice(): void
    {
        // max(2d6, 8)
        // Dice: 3, 4 = 7
        // max(7, 8) = 8
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 4);

        $result = $this->phpdice->roll('max(2d6, 8)');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(8, $result->total);
    }

    /**
     * Test min with plain dice (no modifiers) still works.
     */
    public function testMinWithPlainDice(): void
    {
        // min(2d6, 8)
        // Dice: 5, 6 = 11
        // min(11, 8) = 8
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 6);

        $result = $this->phpdice->roll('min(2d6, 8)');

        $this->assertCount(2, $result->diceValues);
        $this->assertEquals(8, $result->total);
    }

    /**
     * Test floor with dice expression.
     */
    public function testFloorWithDiceExpression(): void
    {
        // floor(3d6 / 2)
        // Dice: 5, 3, 2 = 10
        // floor(10 / 2) = floor(5) = 5
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 3, 2);

        $result = $this->phpdice->roll('floor(3d6 / 2)');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test max with advantage dice.
     */
    public function testMaxWithAdvantage(): void
    {
        // max(1d20 advantage, 10)
        // Roll 2d20, keep highest: 15, 8 -> keep 15
        // max(15, 10) = 15
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 8);

        $result = $this->phpdice->roll('max(1d20 advantage, 10)');

        $this->assertEquals(15, $result->total);
    }

    /**
     * Test that top-level modifiers still work (regression test).
     */
    public function testTopLevelModifiersStillWork(): void
    {
        // 12d6 count >4
        // Dice: 2, 3, 2, 6, 1, 4, 5, 6, 2, 3, 6, 3
        // Success count (>4): 6, 5, 6, 6 = 4 successes
        $this->mockRng->expects($this->exactly(12))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 3, 2, 6, 1, 4, 5, 6, 2, 3, 6, 3);

        $result = $this->phpdice->roll('12d6 count >4');

        $this->assertCount(12, $result->diceValues);
        $this->assertEquals(4, $result->successCount);
    }

    /**
     * Test expression with arithmetic around function call.
     */
    public function testArithmeticAroundFunctionCall(): void
    {
        // max(2d6, 5) + 3
        // Dice: 4, 3 = 7
        // max(7, 5) = 7
        // 7 + 3 = 10
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 3);

        $result = $this->phpdice->roll('max(2d6, 5) + 3');

        $this->assertEquals(10, $result->total);
    }
}
