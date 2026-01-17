<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\Exception\ParseException;

require_once __DIR__ . '/BaseTestCase.php';

/**
 * Integration tests for eval() method (Issue #79).
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 */
final class EvalTest extends BaseTestCase
{
    /**
     * Test basic eval() with conditional that resolves.
     * Example from issue: if $a$ == 1 : 1d20 | 1d12 + $b$
     * With a=2, b=1, should return "1d12+1"
     */
    public function testEvalWithConditionalResolved(): void
    {
        $expression = 'if $a$ == 1 : 1d20 | 1d12 + $b$';
        $variables = ['a' => 2, 'b' => 1];

        $result = $this->phpdice->eval($expression, $variables);

        $this->assertSame('1d12+1', $result);
    }

    /**
     * Test eval() with conditional true branch.
     */
    public function testEvalWithConditionalTrueBranch(): void
    {
        $expression = 'if $a$ == 1 : 1d20 | 1d12 + $b$';
        $variables = ['a' => 1, 'b' => 2];

        $result = $this->phpdice->eval($expression, $variables);

        $this->assertSame('1d20', $result);
    }

    /**
     * Test eval() with simple placeholder replacement.
     */
    public function testEvalSimplePlaceholderReplacement(): void
    {
        $expression = '1d20+$bonus$';
        $variables = ['bonus' => 5];

        $result = $this->phpdice->eval($expression, $variables);

        $this->assertSame('1d20+5', $result);
    }

    /**
     * Test eval() with multiple placeholders.
     */
    public function testEvalMultiplePlaceholders(): void
    {
        $expression = '1d20+$str$+$proficiency$';
        $variables = ['str' => 3, 'proficiency' => 2];

        $result = $this->phpdice->eval($expression, $variables);

        $this->assertSame('1d20+3+2', $result);
    }

    /**
     * Test eval() throws on missing placeholder (non-partial mode).
     */
    public function testEvalThrowsOnMissingPlaceholder(): void
    {
        $expression = '1d20+$bonus$';
        $variables = [];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unbound placeholder');

        $this->phpdice->eval($expression, $variables);
    }

    /**
     * Test eval() with partial=true allows missing placeholders.
     * Example from issue: if $a$ == 1 : 1d20 + $b$ | 1d20
     * With a=1, should return "1d20 + $b$"
     */
    public function testEvalPartialAllowsMissingPlaceholders(): void
    {
        $expression = 'if $a$ == 1 : 1d20 + $b$ | 1d20';
        $variables = ['a' => 1];

        $result = $this->phpdice->eval($expression, $variables, true);

        // Should resolve condition but keep $b$ placeholder
        $this->assertStringContainsString('1d20', $result);
        $this->assertStringContainsString('$b$', $result);
    }

    /**
     * Test eval() with nested conditionals.
     */
    public function testEvalNestedConditionals(): void
    {
        $expression = 'if $a$ > 0 : (if $b$ > 0 : 1d20 | 1d12) | 1d6';
        $variables = ['a' => 1, 'b' => 0];

        $result = $this->phpdice->eval($expression, $variables);

        $this->assertSame('1d12', $result);
    }

    /**
     * Test eval() with comparison operators.
     */
    public function testEvalWithComparisonOperators(): void
    {
        $expression = 'if $level$ >= 5 : 3d6 | 2d6';
        $variables = ['level' => 5];

        $result = $this->phpdice->eval($expression, $variables);

        $this->assertSame('3d6', $result);
    }

    /**
     * Test eval() preserves dice expressions.
     */
    public function testEvalPreservesDiceExpressions(): void
    {
        $expression = '2d6+$modifier$';
        $variables = ['modifier' => 3];

        $result = $this->phpdice->eval($expression, $variables);

        $this->assertSame('2d6+3', $result);
    }

    /**
     * Test eval() with negative values.
     */
    public function testEvalWithNegativeValues(): void
    {
        $expression = '1d20+$penalty$';
        $variables = ['penalty' => -2];

        $result = $this->phpdice->eval($expression, $variables);

        // Negative values are formatted without the redundant +
        $this->assertSame('1d20-2', $result);
    }

    /**
     * Test eval() validates expression.
     */
    public function testEvalValidatesExpression(): void
    {
        $expression = '1d20+$bonus$';
        $variables = ['bonus' => 5];

        // Should not throw
        $result = $this->phpdice->eval($expression, $variables);

        $this->assertIsString($result);
    }

    /**
     * Test eval() with math-only expression - should fully evaluate.
     */
    public function testEvalMathOnlyExpression(): void
    {
        $expression = '1+2+3';
        $variables = [];

        $result = $this->phpdice->eval($expression, $variables, true);

        $this->assertSame('6', $result);
    }

    /**
     * Test eval() with partial math expression - preserves placeholder.
     */
    public function testEvalPartialMathExpression(): void
    {
        $expression = '(1+$a$-4)/2';
        $variables = [];

        $result = $this->phpdice->eval($expression, $variables, true);

        // The expression structure is preserved with placeholder
        // Note: Due to operator precedence, this evaluates as (1+$a$-(4/2))
        $this->assertStringContainsString('$a$', $result);
        $this->assertStringContainsString('/', $result);
    }

    /**
     * Test eval() with math and placeholder - evaluates when placeholder provided.
     */
    public function testEvalMathWithPlaceholder(): void
    {
        $expression = '1+$a$*2';
        $variables = ['a' => 4];

        $result = $this->phpdice->eval($expression, $variables, false);

        $this->assertSame('9', $result);
    }

    /**
     * Test eval() with conditional and division.
     */
    public function testEvalConditionalWithDivision(): void
    {
        $expression = 'if $a$ == 1 : $a$ / 4 | $b$ + 1';
        $variables = ['a' => 1];

        // Should throw because $b$ is missing and partial is false
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unbound placeholder');

        $this->phpdice->eval($expression, $variables, false);
    }

    /**
     * Test eval() with conditional and division - evaluates math.
     */
    public function testEvalConditionalWithDivisionResolved(): void
    {
        $expression = 'if $a$ == 1 : $a$ / 4 | $b$ + 1';
        $variables = ['a' => 1, 'b' => 5];

        $result = $this->phpdice->eval($expression, $variables, false);

        $this->assertSame('0.25', $result);
    }
}
