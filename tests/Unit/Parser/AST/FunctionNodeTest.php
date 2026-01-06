<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Parser\AST;

use PHPDice\Exception\ValidationException;
use PHPDice\Parser\AST\FunctionNode;
use PHPDice\Parser\AST\NumberNode;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FunctionNode.
 *
 * @covers \PHPDice\Parser\AST\FunctionNode
 * @covers \PHPDice\Parser\AST\Node
 */
class FunctionNodeTest extends TestCase
{
    public function testFloorFunction(): void
    {
        $argument = new NumberNode(3.7);
        $node = new FunctionNode('floor', $argument);

        $this->assertEquals(3.0, $node->evaluate());
        $this->assertSame('floor', $node->getName());
        $this->assertSame($argument, $node->getArgument());
    }

    public function testFloorFunctionCaseInsensitive(): void
    {
        $argument = new NumberNode(3.7);
        $node = new FunctionNode('FLOOR', $argument);

        $this->assertEquals(3.0, $node->evaluate());
    }

    public function testCeilFunction(): void
    {
        $argument = new NumberNode(3.2);
        $node = new FunctionNode('ceil', $argument);

        $this->assertEquals(4.0, $node->evaluate());
    }

    public function testRoundFunction(): void
    {
        $argument = new NumberNode(3.6);
        $node = new FunctionNode('round', $argument);

        $this->assertEquals(4.0, $node->evaluate());
    }

    public function testRoundFunctionDown(): void
    {
        $argument = new NumberNode(3.4);
        $node = new FunctionNode('round', $argument);

        $this->assertEquals(3.0, $node->evaluate());
    }

    public function testAbsFunction(): void
    {
        $argument = new NumberNode(-5);
        $node = new FunctionNode('abs', $argument);

        $this->assertSame(5, $node->evaluate());
    }

    public function testAbsFunctionWithPositive(): void
    {
        $argument = new NumberNode(7);
        $node = new FunctionNode('abs', $argument);

        $this->assertSame(7, $node->evaluate());
    }

    public function testUnknownFunction(): void
    {
        $argument = new NumberNode(5);
        $node = new FunctionNode('unknown', $argument);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown function: unknown');
        $node->evaluate();
    }

    public function testGetName(): void
    {
        $node = new FunctionNode('floor', new NumberNode(1));

        $this->assertSame('floor', $node->getName());
    }

    public function testGetArgument(): void
    {
        $argument = new NumberNode(42);
        $node = new FunctionNode('abs', $argument);

        $this->assertSame($argument, $node->getArgument());
    }
}
