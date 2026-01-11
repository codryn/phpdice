<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Model;

use PHPDice\Model\DiceType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DiceType enum.
 *
 * @covers \PHPDice\Model\DiceType
 */
class DiceTypeTest extends TestCase
{
    public function testStandardDiceType(): void
    {
        $type = DiceType::STANDARD;

        $this->assertSame('standard', $type->value);
    }

    public function testFudgeDiceType(): void
    {
        $type = DiceType::FUDGE;

        $this->assertSame('fudge', $type->value);
    }

    public function testPercentileDiceType(): void
    {
        $type = DiceType::PERCENTILE;

        $this->assertSame('percentile', $type->value);
    }

    public function testCoinDiceType(): void
    {
        $type = DiceType::COIN;

        $this->assertSame('coin', $type->value);
    }

    public function testEnumCases(): void
    {
        $cases = DiceType::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(DiceType::STANDARD, $cases);
        $this->assertContains(DiceType::FUDGE, $cases);
        $this->assertContains(DiceType::PERCENTILE, $cases);
        $this->assertContains(DiceType::COIN, $cases);
    }

    public function testEnumFromValue(): void
    {
        $this->assertSame(DiceType::STANDARD, DiceType::from('standard'));
        $this->assertSame(DiceType::FUDGE, DiceType::from('fudge'));
        $this->assertSame(DiceType::PERCENTILE, DiceType::from('percentile'));
        $this->assertSame(DiceType::COIN, DiceType::from('coin'));
    }
}
