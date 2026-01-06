<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Model;

use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DiceSpecification.
 *
 * @covers \PHPDice\Model\DiceSpecification
 */
class DiceSpecificationTest extends TestCase
{
    public function testConstructorWithStandardDice(): void
    {
        $spec = new DiceSpecification(3, 6, DiceType::STANDARD);

        $this->assertSame(3, $spec->count);
        $this->assertSame(6, $spec->sides);
        $this->assertSame(DiceType::STANDARD, $spec->type);
    }

    public function testConstructorWithFudgeDice(): void
    {
        $spec = new DiceSpecification(4, 3, DiceType::FUDGE);

        $this->assertSame(4, $spec->count);
        $this->assertSame(3, $spec->sides);
        $this->assertSame(DiceType::FUDGE, $spec->type);
    }

    public function testConstructorWithPercentileDice(): void
    {
        $spec = new DiceSpecification(1, 100, DiceType::PERCENTILE);

        $this->assertSame(1, $spec->count);
        $this->assertSame(100, $spec->sides);
        $this->assertSame(DiceType::PERCENTILE, $spec->type);
    }

    public function testConstructorDefaultType(): void
    {
        $spec = new DiceSpecification(2, 10);

        $this->assertSame(DiceType::STANDARD, $spec->type);
    }
}
