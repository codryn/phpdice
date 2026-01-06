<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Parser\AST;

use PHPDice\Exception\ValidationException;
use PHPDice\Parser\AST\BinaryOpNode;
use PHPDice\Parser\AST\NumberNode;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BinaryOpNode.
 *
 * @covers \PHPDice\Parser\AST\BinaryOpNode
 * @covers \PHPDice\Parser\AST\Node
 */
class BinaryOpNodeTest extends TestCase
{
    public function testAddition(): void
    {
        $left = new NumberNode(5);
        $right = new NumberNode(3);
        $node = new BinaryOpNode($left, '+', $right);

        $this->assertSame(8, $node->evaluate());
        $this->assertSame('+', $node->getOperator());
    }

    public function testSubtraction(): void
    {
        $left = new NumberNode(10);
        $right = new NumberNode(4);
        $node = new BinaryOpNode($left, '-', $right);

        $this->assertSame(6, $node->evaluate());
    }

    public function testMultiplication(): void
    {
        $left = new NumberNode(6);
        $right = new NumberNode(7);
        $node = new BinaryOpNode($left, '*', $right);

        $this->assertSame(42, $node->evaluate());
    }

    public function testDivision(): void
    {
        $left = new NumberNode(15);
        $right = new NumberNode(3);
        $node = new BinaryOpNode($left, '/', $right);

        $this->assertEquals(5.0, $node->evaluate());
    }

    public function testDivisionByZero(): void
    {
        $left = new NumberNode(10);
        $right = new NumberNode(0);
        $node = new BinaryOpNode($left, '/', $right);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Division by zero');
        $node->evaluate();
    }

    public function testModulo(): void
    {
        $left = new NumberNode(17);
        $right = new NumberNode(5);
        $node = new BinaryOpNode($left, '~', $right);

        $this->assertSame(2, $node->evaluate());
    }

    public function testModuloByZero(): void
    {
        $left = new NumberNode(10);
        $right = new NumberNode(0);
        $node = new BinaryOpNode($left, '~', $right);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Modulo by zero');
        $node->evaluate();
    }

    public function testExponentiation(): void
    {
        $left = new NumberNode(2);
        $right = new NumberNode(3);
        $node = new BinaryOpNode($left, '^', $right);

        $this->assertEquals(8.0, $node->evaluate());
    }

    public function testNestedOperations(): void
    {
        // (5 + 3) * 2 = 16
        $left = new BinaryOpNode(new NumberNode(5), '+', new NumberNode(3));
        $right = new NumberNode(2);
        $node = new BinaryOpNode($left, '*', $right);

        $this->assertSame(16, $node->evaluate());
    }

    public function testGetLeftAndRight(): void
    {
        $left = new NumberNode(10);
        $right = new NumberNode(5);
        $node = new BinaryOpNode($left, '+', $right);

        $this->assertSame($left, $node->getLeft());
        $this->assertSame($right, $node->getRight());
    }

    public function testUnknownOperator(): void
    {
        $left = new NumberNode(5);
        $right = new NumberNode(3);
        $node = new BinaryOpNode($left, '&', $right);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown operator: &');
        $node->evaluate();
    }
}
