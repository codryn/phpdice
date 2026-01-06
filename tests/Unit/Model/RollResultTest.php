<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Model;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\RollResult;
use PHPDice\Model\StatisticalData;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RollResult.
 *
 * @covers \PHPDice\Model\RollResult
 */
class RollResultTest extends TestCase
{
    private DiceExpression $expression;

    protected function setUp(): void
    {
        parent::setUp();
        $this->expression = new DiceExpression(
            new DiceSpecification(3, 6, DiceType::STANDARD),
            new RollModifiers(),
            new StatisticalData(3, 18, 10.5),
            '3d6'
        );
    }

    public function testBasicRollResult(): void
    {
        $result = new RollResult(
            expression: $this->expression,
            total: 12,
            diceValues: [4, 5, 3]
        );

        $this->assertSame($this->expression, $result->expression);
        $this->assertSame(12, $result->total);
        $this->assertSame([4, 5, 3], $result->diceValues);
        $this->assertNull($result->keptDice);
        $this->assertNull($result->discardedDice);
        $this->assertNull($result->successCount);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);
        $this->assertNull($result->isSuccess);
        $this->assertNull($result->rerollHistory);
        $this->assertNull($result->explosionHistory);
    }

    public function testRollResultWithKeepHighest(): void
    {
        $result = new RollResult(
            expression: $this->expression,
            total: 11,
            diceValues: [6, 5, 2, 3],
            keptDice: [0, 1, 3],
            discardedDice: [2]
        );

        $this->assertSame([0, 1, 3], $result->keptDice);
        $this->assertSame([2], $result->discardedDice);
    }

    public function testRollResultWithSuccessCounting(): void
    {
        $result = new RollResult(
            expression: $this->expression,
            total: 2,
            diceValues: [5, 6, 3],
            successCount: 2
        );

        $this->assertSame(2, $result->successCount);
    }

    public function testRollResultWithCriticalSuccess(): void
    {
        $result = new RollResult(
            expression: $this->expression,
            total: 20,
            diceValues: [20],
            isCriticalSuccess: true
        );

        $this->assertTrue($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);
    }

    public function testRollResultWithCriticalFailure(): void
    {
        $result = new RollResult(
            expression: $this->expression,
            total: 1,
            diceValues: [1],
            isCriticalFailure: true
        );

        $this->assertFalse($result->isCriticalSuccess);
        $this->assertTrue($result->isCriticalFailure);
    }

    public function testRollResultWithSuccessComparison(): void
    {
        $result = new RollResult(
            expression: $this->expression,
            total: 15,
            diceValues: [15],
            isSuccess: true
        );

        $this->assertTrue($result->isSuccess);
    }

    public function testRollResultWithRerollHistory(): void
    {
        $rerollHistory = [
            0 => ['rolls' => [1, 4], 'count' => 1, 'limitReached' => false],
        ];

        $result = new RollResult(
            expression: $this->expression,
            total: 10,
            diceValues: [4, 3, 3],
            rerollHistory: $rerollHistory
        );

        $this->assertSame($rerollHistory, $result->rerollHistory);
    }

    public function testRollResultWithExplosionHistory(): void
    {
        $explosionHistory = [
            0 => [
                'rolls' => [6, 6, 3],
                'count' => 2,
                'cumulativeTotal' => 15,
                'limitReached' => false,
            ],
        ];

        $result = new RollResult(
            expression: $this->expression,
            total: 20,
            diceValues: [15, 2, 3],
            explosionHistory: $explosionHistory
        );

        $this->assertSame($explosionHistory, $result->explosionHistory);
    }
}
