<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for roll comments feature.
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Roller\DiceRoller
 */
#[CoversClass(PHPDice::class)]
class RollCommentsTest extends BaseTestCase
{
    /**
     * Test parsing expression with basic comment.
     */
    public function testParseBasicComment(): void
    {
        $expression = '1d20 + 5 # Roll for initiative';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Roll for initiative', $expr->comment);
        $this->assertSame('1d20 + 5 # Roll for initiative', $expr->originalExpression);
    }

    /**
     * Test rolling with comment included in result.
     */
    public function testRollWithComment(): void
    {
        $expression = '1d20 + 5 # Attack roll';
        $result = $this->phpdice->roll($expression);

        $this->assertSame('Attack roll', $result->comment);
        $this->assertGreaterThanOrEqual(6, $result->total);
        $this->assertLessThanOrEqual(25, $result->total);
    }

    /**
     * Test comment with placeholders expansion.
     */
    public function testCommentWithPlaceholderExpansion(): void
    {
        $expression = '1d20 + $ini$ # Roll for initiative (bonus $ini$)!';
        $variables = ['ini' => 3];

        $expr = $this->phpdice->parse($expression, $variables);

        // Placeholders should be expanded in comment
        $this->assertSame('Roll for initiative (bonus 3)!', $expr->comment);
    }

    /**
     * Test rolling with placeholder in comment.
     */
    public function testRollWithPlaceholderInComment(): void
    {
        $expression = '1d20 + $str$ # Strength check (+$str$)';
        $variables = ['str' => 4];

        $result = $this->phpdice->roll($expression, $variables);

        $this->assertSame('Strength check (+4)', $result->comment);
        $this->assertGreaterThanOrEqual(5, $result->total);
        $this->assertLessThanOrEqual(24, $result->total);
    }

    /**
     * Test comment with multiple # characters (subsequent # are part of comment).
     */
    public function testCommentWithMultipleHashSigns(): void
    {
        $expression = '1d20 + 15 # Attack codryn/phpdice#1';
        $expr = $this->phpdice->parse($expression);

        // Everything after first # should be in comment
        $this->assertSame('Attack codryn/phpdice#1', $expr->comment);
    }

    /**
     * Test comment with GitHub issue reference.
     */
    public function testCommentWithGitHubIssue(): void
    {
        $expression = '1d20 + 5  # Attack codryn/phpdice#2 (-5 Penalty)';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Attack codryn/phpdice#2 (-5 Penalty)', $expr->comment);
    }

    /**
     * Test comment with leading/trailing whitespace trimmed.
     */
    public function testCommentWhitespaceTrimming(): void
    {
        $expression = '1d20 + 5 #   Some comment with spaces   ';
        $expr = $this->phpdice->parse($expression);

        // Leading and trailing whitespace should be trimmed
        $this->assertSame('Some comment with spaces', $expr->comment);
    }

    /**
     * Test expression without comment has null comment.
     */
    public function testExpressionWithoutComment(): void
    {
        $expression = '1d20 + 5';
        $expr = $this->phpdice->parse($expression);

        $this->assertNull($expr->comment);
    }

    /**
     * Test rolling without comment has null comment.
     */
    public function testRollWithoutComment(): void
    {
        $expression = '1d20 + 5';
        $result = $this->phpdice->roll($expression);

        $this->assertNull($result->comment);
    }

    /**
     * Test comment with multiple placeholders.
     */
    public function testCommentWithMultiplePlaceholders(): void
    {
        $expression = '1d20 + $str$ + $prof$ # Attack roll (STR: $str$, Prof: $prof$)';
        $variables = ['str' => 3, 'prof' => 2];

        $expr = $this->phpdice->parse($expression, $variables);

        $this->assertSame('Attack roll (STR: 3, Prof: 2)', $expr->comment);
    }

    /**
     * Test comment with modifiers.
     */
    public function testCommentWithDiceModifiers(): void
    {
        $expression = '2d20 keep 1 highest + 5 # Attack with advantage';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Attack with advantage', $expr->comment);
        $this->assertSame(1, $expr->modifiers->keepHighest);
    }

    /**
     * Test comment with advantage modifier.
     */
    public function testCommentWithAdvantage(): void
    {
        $expression = '1d20 advantage + 3 # Attack with advantage';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Attack with advantage', $expr->comment);
        $this->assertSame(1, $expr->modifiers->advantageCount);
    }

    /**
     * Test comment with success counting.
     */
    public function testCommentWithSuccessCounting(): void
    {
        $expression = '6d6 count >= 5 # Shadowrun test';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Shadowrun test', $expr->comment);
        $this->assertSame(5, $expr->modifiers->successThreshold);
    }

    /**
     * Test comment with DC comparison.
     */
    public function testCommentWithDCComparison(): void
    {
        $expression = '1d20 + 5 dc >= 15 # Saving throw';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Saving throw', $expr->comment);
        $this->assertSame('>=', $expr->comparisonOperator);
        $this->assertSame(15, $expr->comparisonThreshold);
    }

    /**
     * Test comment with exploding dice.
     */
    public function testCommentWithExplodingDice(): void
    {
        $expression = '1d6 explode # Savage Worlds damage';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Savage Worlds damage', $expr->comment);
        $this->assertSame(6, $expr->modifiers->explosionThreshold);
    }

    /**
     * Test math-only expression with comment.
     */
    public function testMathOnlyExpressionWithComment(): void
    {
        $expression = '5 + 3 * 2 # Math calculation';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Math calculation', $expr->comment);
        $this->assertNull($expr->specification);
    }

    /**
     * Test rolling math-only with comment.
     */
    public function testRollMathOnlyWithComment(): void
    {
        $expression = '10 + 5 # Simple addition';
        $result = $this->phpdice->roll($expression);

        $this->assertSame('Simple addition', $result->comment);
        $this->assertSame(15, $result->total);
    }

    /**
     * Test empty comment (just #).
     */
    public function testEmptyComment(): void
    {
        $expression = '1d20 + 5 #';
        $expr = $this->phpdice->parse($expression);

        // Empty comment should be empty string after trimming
        $this->assertSame('', $expr->comment);
    }

    /**
     * Test comment preserves special characters.
     */
    public function testCommentPreservesSpecialCharacters(): void
    {
        $expression = '1d20 + 5 # Attack! @target (50% chance)';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('Attack! @target (50% chance)', $expr->comment);
    }

    /**
     * Test comment with Unicode characters.
     */
    public function testCommentWithUnicode(): void
    {
        $expression = '1d20 + 5 # ⚔️ Attack roll';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame('⚔️ Attack roll', $expr->comment);
    }

    /**
     * Test unbound placeholder in comment doesn't cause error.
     */
    public function testUnboundPlaceholderInCommentIsPreserved(): void
    {
        $expression = '1d20 + 5 # Bonus: $missing$';
        $expr = $this->phpdice->parse($expression);

        // Unbound placeholders in comments should be left as-is
        $this->assertSame('Bonus: $missing$', $expr->comment);
    }

    /**
     * Test mixed bound and unbound placeholders in comment.
     */
    public function testMixedPlaceholdersInComment(): void
    {
        $expression = '1d20 + $str$ # STR: $str$, DEX: $dex$';
        $variables = ['str' => 3];

        $expr = $this->phpdice->parse($expression, $variables);

        // Bound placeholder expanded, unbound preserved
        $this->assertSame('STR: 3, DEX: $dex$', $expr->comment);
    }

    /**
     * Test placeholder with dots in name in comment.
     */
    public function testPlaceholderWithDotsInComment(): void
    {
        $expression = '1d20 + $ability.str$ # Strength: $ability.str$';
        $variables = ['ability.str' => 4];

        $expr = $this->phpdice->parse($expression, $variables);

        $this->assertSame('Strength: 4', $expr->comment);
    }

    /**
     * Test negative value placeholder in comment.
     */
    public function testNegativeValuePlaceholderInComment(): void
    {
        $expression = '1d20 + $penalty$ # Penalty: $penalty$';
        $variables = ['penalty' => -2];

        $expr = $this->phpdice->parse($expression, $variables);

        $this->assertSame('Penalty: -2', $expr->comment);
    }

    /**
     * Test zero value placeholder in comment.
     */
    public function testZeroValuePlaceholderInComment(): void
    {
        $expression = '1d20 + $bonus$ # Bonus: $bonus$';
        $variables = ['bonus' => 0];

        $expr = $this->phpdice->parse($expression, $variables);

        $this->assertSame('Bonus: 0', $expr->comment);
    }
}
