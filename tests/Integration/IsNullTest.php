<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

require_once __DIR__ . '/BaseTestCaseMock.php';

/**
 * Test "is null" check in conditional expressions.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 * @covers \Codryn\PHPDice\Model\DiceExpression
 * @covers \Codryn\PHPDice\Model\RollResult
 * @covers \Codryn\PHPDice\Parser\AST\ConditionalNode
 * @covers \Codryn\PHPDice\Parser\AST\IsNullNode
 */
class IsNullTest extends BaseTestCaseMock
{
    /**
     * Test basic "is null" check with missing variable.
     */
    public function testIsNullWithMissingVariable(): void
    {
        // if $var$ is null : 10 | 20
        $expression = 'if $var$ is null : 10 | 20';

        // When $var$ is not provided, should return 10 (true branch)
        $result = $this->phpdice->roll($expression, []);
        $this->assertEquals(10, $result->total);
    }

    /**
     * Test "is null" check with null value.
     */
    public function testIsNullWithNullValue(): void
    {
        // if $var$ is null : 10 | 20
        $expression = 'if $var$ is null : 10 | 20';

        // When $var$ is null, should return 10 (true branch)
        $result = $this->phpdice->roll($expression, ['var' => null]);
        $this->assertEquals(10, $result->total);
    }

    /**
     * Test "is null" check with non-null value.
     */
    public function testIsNullWithNonNullValue(): void
    {
        // if $var$ is null : 10 | 20
        $expression = 'if $var$ is null : 10 | 20';

        // When $var$ has a value, should return 20 (false branch)
        $result = $this->phpdice->roll($expression, ['var' => 5]);
        $this->assertEquals(20, $result->total);
    }

    /**
     * Test "is null" check with zero value (zero should not be treated as null).
     */
    public function testIsNullWithZeroValue(): void
    {
        // if $var$ is null : 10 | 20
        $expression = 'if $var$ is null : 10 | 20';

        // When $var$ is 0, should return 20 (false branch) - zero is not null
        $result = $this->phpdice->roll($expression, ['var' => 0]);
        $this->assertEquals(20, $result->total);
    }

    /**
     * Test lazy evaluation with "is null" - true branch with dice.
     */
    public function testIsNullLazyEvaluationTrueBranch(): void
    {
        // if $var$ is null : 1d20-1 | 1d20+$var$
        $expression = 'if $var$ is null : 1d20-1 | 1d20+$var$';

        // When $var$ is null, should roll 1d20-1
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(15);

        $result = $this->phpdice->roll($expression, []);
        $this->assertEquals(14, $result->total); // 15-1
    }

    /**
     * Test lazy evaluation with "is null" - false branch with dice and variable.
     */
    public function testIsNullLazyEvaluationFalseBranch(): void
    {
        // if $var$ is null : 1d20-1 | 1d20+$var$
        $expression = 'if $var$ is null : 1d20-1 | 1d20+$var$';

        // When $var$ is 5, should roll 1d20+5
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(12);

        $result = $this->phpdice->roll($expression, ['var' => 5]);
        $this->assertEquals(17, $result->total); // 12+5
    }

    /**
     * Test "is null" in nested conditionals.
     */
    public function testIsNullInNestedConditionals(): void
    {
        // if $a$ is null : 10 | (if $b$ is null : 20 | 30)
        $expression = 'if $a$ is null : 10 | (if $b$ is null : 20 | 30)';

        // When $a$ is null, should return 10
        $result = $this->phpdice->roll($expression, []);
        $this->assertEquals(10, $result->total);

        // When $a$ is not null but $b$ is null, should return 20
        $result = $this->phpdice->roll($expression, ['a' => 1]);
        $this->assertEquals(20, $result->total);

        // When both $a$ and $b$ are not null, should return 30
        $result = $this->phpdice->roll($expression, ['a' => 1, 'b' => 2]);
        $this->assertEquals(30, $result->total);
    }

    /**
     * Test "is null" with arithmetic expressions.
     */
    public function testIsNullInArithmeticExpressions(): void
    {
        // 1d20 + (if $bonus$ is null : 0 | $bonus$)
        $expression = '1d20 + (if $bonus$ is null : 0 | $bonus$)';

        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(10, 10);

        // When $bonus$ is null, should add 0
        $result = $this->phpdice->roll($expression, []);
        $this->assertEquals(10, $result->total); // 10+0

        // When $bonus$ is 5, should add 5
        $result = $this->phpdice->roll($expression, ['bonus' => 5]);
        $this->assertEquals(15, $result->total); // 10+5
    }

    /**
     * Test multiple "is null" checks in same expression.
     */
    public function testMultipleIsNullChecks(): void
    {
        // (if $a$ is null : 1 | 0) + (if $b$ is null : 1 | 0)
        $expression = '(if $a$ is null : 1 | 0) + (if $b$ is null : 1 | 0)';

        // Both null: should return 2
        $result = $this->phpdice->roll($expression, []);
        $this->assertEquals(2, $result->total);

        // Only $a$ null: should return 1
        $result = $this->phpdice->roll($expression, ['b' => 5]);
        $this->assertEquals(1, $result->total);

        // Only $b$ null: should return 1
        $result = $this->phpdice->roll($expression, ['a' => 5]);
        $this->assertEquals(1, $result->total);

        // Neither null: should return 0
        $result = $this->phpdice->roll($expression, ['a' => 5, 'b' => 3]);
        $this->assertEquals(0, $result->total);
    }
}
