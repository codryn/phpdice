<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ParseException;

/**
 * Integration tests for placeholder variable support (US7).
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Roller\DiceRoller
 */
final class PlaceholdersTest extends BaseTestCase
{
    /**
     * AC1: Parse with variables, verify placeholder resolution.
     *
     * Given an expression "1d20+%str%+%luck%" with variable values provided (str=3, luck=2)
     * When parsed
     * Then the structure resolves "%str%" and "%luck%" placeholders to their numeric values
     */
    public function testParsePlaceholdersResolvesVariables(): void
    {
        $expression = '1d20+%str%+%luck%';
        $variables = ['str' => 3, 'luck' => 2];

        $expr = $this->phpdice->parse($expression, $variables);

        // Statistics should reflect the resolved values (1d20 + 3 + 2)
        $this->assertSame(6, $expr->statistics->minimum); // 1 + 3 + 2
        $this->assertSame(25, $expr->statistics->maximum); // 20 + 3 + 2
        $this->assertSame(15.5, $expr->statistics->expected); // 10.5 + 3 + 2
    }

    /**
     * AC1: Test placeholder resolution with complex expression.
     */
    public function testPlaceholdersInComplexExpression(): void
    {
        $expression = '2d6+%strength%+%proficiency%-1';
        $variables = ['strength' => 4, 'proficiency' => 2];

        $expr = $this->phpdice->parse($expression, $variables);

        // Statistics: 2d6 + 4 + 2 - 1
        $this->assertSame(7, $expr->statistics->minimum); // 2 + 4 + 2 - 1
        $this->assertSame(17, $expr->statistics->maximum); // 12 + 4 + 2 - 1
        $this->assertSame(12.0, $expr->statistics->expected); // 7 + 4 + 2 - 1
    }

    /**
     * AC1: Test single placeholder.
     */
    public function testSinglePlaceholder(): void
    {
        $expression = '1d20+%bonus%';
        $variables = ['bonus' => 5];

        $expr = $this->phpdice->parse($expression, $variables);

        // Statistics: 1d20 + 5
        $this->assertSame(6, $expr->statistics->minimum); // 1 + 5
        $this->assertSame(25, $expr->statistics->maximum); // 20 + 5
        $this->assertSame(15.5, $expr->statistics->expected); // 10.5 + 5
    }

    /**
     * AC2: Roll with resolved placeholders.
     *
     * Given a parsed expression with resolved placeholders
     * When rolled
     * Then the roll evaluates correctly using the bound values
     */
    public function testRollWithPlaceholders(): void
    {
        $expression = '1d20+%str%+%proficiency%';
        $variables = ['str' => 3, 'proficiency' => 2];

        $result = $this->phpdice->roll($expression, $variables);

        // Total should be within valid range (1d20 + 3 + 2)
        $this->assertGreaterThanOrEqual(6, $result->total); // minimum: 1 + 3 + 2
        $this->assertLessThanOrEqual(25, $result->total); // maximum: 20 + 3 + 2

        // Individual die should be within 1-20
        $this->assertNotNull($result->diceValues);
        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(1, $result->diceValues[0]);
        $this->assertLessThanOrEqual(20, $result->diceValues[0]);
    }

    /**
     * AC2: Multiple rolls with same placeholders produce different dice results.
     */
    public function testMultipleRollsWithPlaceholdersVaryDiceResults(): void
    {
        $expression = '1d20+%bonus%';
        $variables = ['bonus' => 5];

        $results = [];
        for ($i = 0; $i < 50; $i++) {
            $result = $this->phpdice->roll($expression, $variables);
            $results[] = $result->total;
        }

        // We should see variation in results (very unlikely to roll same number 50 times)
        $uniqueResults = array_unique($results);
        $this->assertGreaterThan(1, count($uniqueResults));

        // All results should be in valid range (1d20 + 5)
        foreach ($results as $total) {
            $this->assertGreaterThanOrEqual(6, $total);
            $this->assertLessThanOrEqual(25, $total);
        }
    }

    /**
     * AC3: Parser rejects unbound placeholders.
     *
     * Given an expression with unbound placeholders
     * When parsed without providing values
     * Then the parser MUST reject the expression with a clear error message listing the missing variable names
     */
    public function testUnboundPlaceholderThrowsError(): void
    {
        $expression = '1d20+%str%';

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Unbound placeholder variable '%str%'");

        $this->phpdice->parse($expression, []);
    }

    /**
     * AC3: Parser rejects when some variables are missing.
     */
    public function testPartiallyBoundPlaceholdersThrowsError(): void
    {
        $expression = '1d20+%str%+%proficiency%';
        $variables = ['str' => 3]; // proficiency is missing

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Unbound placeholder variable '%proficiency%'");

        $this->phpdice->parse($expression, $variables);
    }

    /**
     * AC3: Error message is helpful and identifies the missing variable.
     */
    public function testUnboundPlaceholderErrorIsHelpful(): void
    {
        $expression = '1d20+%dexterity%';

        try {
            $this->phpdice->parse($expression, []);
            $this->fail('Expected ParseException to be thrown');
        } catch (ParseException $e) {
            // Error should mention the variable name
            $this->assertStringContainsString('dexterity', $e->getMessage());
            // Error should indicate it's unbound
            $this->assertStringContainsString('Unbound', $e->getMessage());
            // Error should be actionable
            $this->assertStringContainsString('provide a value', $e->getMessage());
        }
    }

    /**
     * AC4: Inspect resolved placeholders.
     *
     * Given a parsed expression with resolved placeholders
     * When inspected
     * Then I can see which placeholders were used and their bound values
     */
    public function testResolvedVariablesAreInspectable(): void
    {
        $expression = '1d20+%str%+%proficiency%';
        $variables = ['str' => 3, 'proficiency' => 2];

        $expr = $this->phpdice->parse($expression, $variables);

        // resolvedVariables should contain the variables that were actually used
        $this->assertArrayHasKey('str', $expr->modifiers->resolvedVariables);
        $this->assertArrayHasKey('proficiency', $expr->modifiers->resolvedVariables);
        $this->assertSame(3, $expr->modifiers->resolvedVariables['str']);
        $this->assertSame(2, $expr->modifiers->resolvedVariables['proficiency']);
    }

    /**
     * AC4: Only used placeholders are tracked.
     */
    public function testOnlyUsedPlaceholdersAreTracked(): void
    {
        $expression = '1d20+%str%';
        $variables = ['str' => 3, 'luck' => 2, 'wisdom' => 1]; // extra variables

        $expr = $this->phpdice->parse($expression, $variables);

        // Only 'str' should be in resolvedVariables (not 'luck' or 'wisdom')
        $this->assertArrayHasKey('str', $expr->modifiers->resolvedVariables);
        $this->assertArrayNotHasKey('luck', $expr->modifiers->resolvedVariables);
        $this->assertArrayNotHasKey('wisdom', $expr->modifiers->resolvedVariables);
        $this->assertCount(1, $expr->modifiers->resolvedVariables);
    }

    /**
     * AC4: Expressions without placeholders have empty resolvedVariables.
     */
    public function testExpressionsWithoutPlaceholdersHaveEmptyResolvedVariables(): void
    {
        $expression = '1d20+5';

        $expr = $this->phpdice->parse($expression, []);

        // resolvedVariables should be empty
        $this->assertEmpty($expr->modifiers->resolvedVariables);
    }

    /**
     * Test placeholder with dice modifiers.
     */
    public function testPlaceholderWithModifiers(): void
    {
        $expression = '2d20 keep 1 highest +%bonus%';
        $variables = ['bonus' => 3];

        $expr = $this->phpdice->parse($expression, $variables);

        // Should parse successfully
        $this->assertSame(1, $expr->modifiers->keepHighest);
        $this->assertArrayHasKey('bonus', $expr->modifiers->resolvedVariables);
        $this->assertSame(3, $expr->modifiers->resolvedVariables['bonus']);

        // Verify parsing worked (exact statistics calculation is out of scope for placeholder test)
        $this->assertGreaterThan(0, $expr->statistics->minimum);
        $this->assertGreaterThan($expr->statistics->minimum, $expr->statistics->maximum);
    }

    /**
     * Test placeholder with advantage.
     */
    public function testPlaceholderWithAdvantage(): void
    {
        $expression = '1d20 advantage +%dex%';
        $variables = ['dex' => 4];

        $result = $this->phpdice->roll($expression, $variables);

        // Should roll 2d20 (advantage) and add 4
        $this->assertCount(2, $result->diceValues);
        $this->assertGreaterThanOrEqual(5, $result->total); // 1 + 4
        $this->assertLessThanOrEqual(24, $result->total); // 20 + 4
    }

    /**
     * Test placeholder values with zero.
     */
    public function testPlaceholderWithZeroValue(): void
    {
        $expression = '1d20+%bonus%';
        $variables = ['bonus' => 0];

        $expr = $this->phpdice->parse($expression, $variables);

        // Statistics should be same as 1d20+0
        $this->assertSame(1, $expr->statistics->minimum);
        $this->assertSame(20, $expr->statistics->maximum);
        $this->assertSame(10.5, $expr->statistics->expected);
        $this->assertArrayHasKey('bonus', $expr->modifiers->resolvedVariables);
        $this->assertSame(0, $expr->modifiers->resolvedVariables['bonus']);
    }

    /**
     * Test placeholder values with negative numbers.
     */
    public function testPlaceholderWithNegativeValue(): void
    {
        $expression = '1d20+%penalty%';
        $variables = ['penalty' => -2];

        $expr = $this->phpdice->parse($expression, $variables);

        // Statistics: 1d20 + (-2)
        $this->assertSame(-1, $expr->statistics->minimum); // 1 + (-2)
        $this->assertSame(18, $expr->statistics->maximum); // 20 + (-2)
        $this->assertSame(8.5, $expr->statistics->expected); // 10.5 + (-2)
        $this->assertArrayHasKey('penalty', $expr->modifiers->resolvedVariables);
        $this->assertSame(-2, $expr->modifiers->resolvedVariables['penalty']);
    }

    /**
     * Test placeholder with underscore in name.
     */
    public function testPlaceholderWithUnderscore(): void
    {
        $expression = '1d20+%spell_attack_bonus%';
        $variables = ['spell_attack_bonus' => 7];

        $expr = $this->phpdice->parse($expression, $variables);

        $this->assertArrayHasKey('spell_attack_bonus', $expr->modifiers->resolvedVariables);
        $this->assertSame(7, $expr->modifiers->resolvedVariables['spell_attack_bonus']);
    }

    /**
     * Test multiple uses of same placeholder.
     */
    public function testMultipleUsesOfSamePlaceholder(): void
    {
        $expression = '1d20+%bonus%+%bonus%';
        $variables = ['bonus' => 2];

        $expr = $this->phpdice->parse($expression, $variables);

        // Statistics: 1d20 + 2 + 2
        $this->assertSame(5, $expr->statistics->minimum); // 1 + 2 + 2
        $this->assertSame(24, $expr->statistics->maximum); // 20 + 2 + 2

        // Should only appear once in resolvedVariables
        $this->assertArrayHasKey('bonus', $expr->modifiers->resolvedVariables);
        $this->assertSame(2, $expr->modifiers->resolvedVariables['bonus']);
        $this->assertCount(1, $expr->modifiers->resolvedVariables);
    }
}
