<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Unit\Parser\AST;

use Codryn\PHPDice\Parser\AST\NumberNode;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NumberNode.
 *
 * @covers \Codryn\PHPDice\Parser\AST\NumberNode
 * @covers \Codryn\PHPDice\Parser\AST\Node
 */
class NumberNodeTest extends TestCase
{
    public function testConstructorAndEvaluateWithInteger(): void
    {
        $node = new NumberNode(42);

        $this->assertSame(42, $node->evaluate());
        $this->assertSame(42, $node->getValue());
    }

    public function testConstructorAndEvaluateWithFloat(): void
    {
        $node = new NumberNode(3.14);

        $this->assertSame(3.14, $node->evaluate());
        $this->assertSame(3.14, $node->getValue());
    }

    public function testConstructorWithZero(): void
    {
        $node = new NumberNode(0);

        $this->assertSame(0, $node->evaluate());
    }

    public function testConstructorWithNegativeNumber(): void
    {
        $node = new NumberNode(-5);

        $this->assertSame(-5, $node->evaluate());
    }
}
