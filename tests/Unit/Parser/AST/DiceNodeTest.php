<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Parser\AST;

use PHPDice\Model\DiceType;
use PHPDice\Parser\AST\DiceNode;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DiceNode.
 *
 * @covers \PHPDice\Parser\AST\DiceNode
 * @covers \PHPDice\Parser\AST\Node
 */
class DiceNodeTest extends TestCase
{
    public function testConstructorWithStandardDice(): void
    {
        $node = new DiceNode(3, 6, DiceType::STANDARD);

        $this->assertSame(3, $node->getCount());
        $this->assertSame(6, $node->getSides());
        $this->assertSame(DiceType::STANDARD, $node->getType());
    }

    public function testConstructorDefaultType(): void
    {
        $node = new DiceNode(2, 10);

        $this->assertSame(DiceType::STANDARD, $node->getType());
    }

    public function testEvaluateBeforeRoll(): void
    {
        $node = new DiceNode(3, 6);

        $this->assertSame(0, $node->evaluate());
    }

    public function testSetRollResultAndEvaluate(): void
    {
        $node = new DiceNode(3, 6);
        $node->setRollResult(12);

        $this->assertSame(12, $node->evaluate());
    }

    public function testSetRollResultWithFloat(): void
    {
        $node = new DiceNode(2, 6);
        $node->setRollResult(7.5);

        $this->assertSame(7.5, $node->evaluate());
    }

    public function testMultipleSetRollResults(): void
    {
        $node = new DiceNode(1, 20);
        
        $node->setRollResult(10);
        $this->assertSame(10, $node->evaluate());
        
        $node->setRollResult(15);
        $this->assertSame(15, $node->evaluate());
    }

    public function testFudgeDiceType(): void
    {
        $node = new DiceNode(4, 3, DiceType::FUDGE);

        $this->assertSame(DiceType::FUDGE, $node->getType());
    }

    public function testPercentileDiceType(): void
    {
        $node = new DiceNode(1, 100, DiceType::PERCENTILE);

        $this->assertSame(DiceType::PERCENTILE, $node->getType());
    }
}
