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

    public function testMinFunctionWithTwoArguments(): void
    {
        $arg1 = new NumberNode(5);
        $arg2 = new NumberNode(3);
        $node = new FunctionNode('min', [$arg1, $arg2]);

        $this->assertSame(3, $node->evaluate());
    }

    public function testMinFunctionWithMultipleArguments(): void
    {
        $arg1 = new NumberNode(10);
        $arg2 = new NumberNode(3);
        $arg3 = new NumberNode(7);
        $arg4 = new NumberNode(1);
        $node = new FunctionNode('min', [$arg1, $arg2, $arg3, $arg4]);

        $this->assertSame(1, $node->evaluate());
    }

    public function testMinFunctionWithFloats(): void
    {
        $arg1 = new NumberNode(3.5);
        $arg2 = new NumberNode(2.1);
        $arg3 = new NumberNode(4.8);
        $node = new FunctionNode('min', [$arg1, $arg2, $arg3]);

        $this->assertEquals(2.1, $node->evaluate());
    }

    public function testMinFunctionCaseInsensitive(): void
    {
        $arg1 = new NumberNode(5);
        $arg2 = new NumberNode(3);
        $node = new FunctionNode('MIN', [$arg1, $arg2]);

        $this->assertSame(3, $node->evaluate());
    }

    public function testMaxFunctionWithTwoArguments(): void
    {
        $arg1 = new NumberNode(5);
        $arg2 = new NumberNode(3);
        $node = new FunctionNode('max', [$arg1, $arg2]);

        $this->assertSame(5, $node->evaluate());
    }

    public function testMaxFunctionWithMultipleArguments(): void
    {
        $arg1 = new NumberNode(10);
        $arg2 = new NumberNode(3);
        $arg3 = new NumberNode(7);
        $arg4 = new NumberNode(15);
        $node = new FunctionNode('max', [$arg1, $arg2, $arg3, $arg4]);

        $this->assertSame(15, $node->evaluate());
    }

    public function testMaxFunctionWithFloats(): void
    {
        $arg1 = new NumberNode(3.5);
        $arg2 = new NumberNode(2.1);
        $arg3 = new NumberNode(4.8);
        $node = new FunctionNode('max', [$arg1, $arg2, $arg3]);

        $this->assertEquals(4.8, $node->evaluate());
    }

    public function testMaxFunctionCaseInsensitive(): void
    {
        $arg1 = new NumberNode(5);
        $arg2 = new NumberNode(3);
        $node = new FunctionNode('MAX', [$arg1, $arg2]);

        $this->assertSame(5, $node->evaluate());
    }

    public function testMinFunctionWithSingleArgumentThrowsException(): void
    {
        $arg = new NumberNode(5);
        $node = new FunctionNode('min', [$arg]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Function 'min' expects at least 2 arguments, got 1");
        $node->evaluate();
    }

    public function testMaxFunctionWithSingleArgumentThrowsException(): void
    {
        $arg = new NumberNode(5);
        $node = new FunctionNode('max', [$arg]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Function 'max' expects at least 2 arguments, got 1");
        $node->evaluate();
    }

    public function testGetArguments(): void
    {
        $arg1 = new NumberNode(5);
        $arg2 = new NumberNode(3);
        $node = new FunctionNode('max', [$arg1, $arg2]);

        $arguments = $node->getArguments();
        $this->assertCount(2, $arguments);
        $this->assertSame($arg1, $arguments[0]);
        $this->assertSame($arg2, $arguments[1]);
    }
}
