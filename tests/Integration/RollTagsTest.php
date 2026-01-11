<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for roll tags feature.
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Roller\DiceRoller
 */
#[CoversClass(PHPDice::class)]
final class RollTagsTest extends BaseTestCase
{
    /**
     * Test parsing expression with single tag.
     */
    public function testParseSingleTag(): void
    {
        $expression = '1d6+2 [magic]';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertCount(1, $expr->tags);
        $this->assertSame('magic', $expr->tags[0]);
    }

    /**
     * Test parsing expression with multiple tags.
     */
    public function testParseMultipleTags(): void
    {
        $expression = '1d6+2 [MAGIC, piercing, Silver]';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertCount(3, $expr->tags);
        $this->assertSame('magic', $expr->tags[0]);
        $this->assertSame('piercing', $expr->tags[1]);
        $this->assertSame('silver', $expr->tags[2]);
    }

    /**
     * Test tags are case-insensitive and normalized to lowercase.
     */
    public function testTagsAreLowercase(): void
    {
        $expression = '1d6+2 [MAGIC, Piercing, SiLvEr]';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertSame('magic', $expr->tags[0]);
        $this->assertSame('piercing', $expr->tags[1]);
        $this->assertSame('silver', $expr->tags[2]);
    }

    /**
     * Test tags with allowed special characters (., -, _).
     */
    public function testTagsWithSpecialCharacters(): void
    {
        $expression = '1d6+2 [magic-damage, weapon_bonus, damage.type]';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertCount(3, $expr->tags);
        $this->assertSame('magic-damage', $expr->tags[0]);
        $this->assertSame('weapon_bonus', $expr->tags[1]);
        $this->assertSame('damage.type', $expr->tags[2]);
    }

    /**
     * Test tags with numbers.
     */
    public function testTagsWithNumbers(): void
    {
        $expression = '1d6+2 [magic2, d20, level5]';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertCount(3, $expr->tags);
        $this->assertSame('magic2', $expr->tags[0]);
        $this->assertSame('d20', $expr->tags[1]);
        $this->assertSame('level5', $expr->tags[2]);
    }

    /**
     * Test tags with comment.
     */
    public function testTagsWithComment(): void
    {
        $expression = '1d6+2 [MAGIC, piercing, Silver] # roll damage';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertCount(3, $expr->tags);
        $this->assertSame('magic', $expr->tags[0]);
        $this->assertSame('piercing', $expr->tags[1]);
        $this->assertSame('silver', $expr->tags[2]);
        $this->assertSame('roll damage', $expr->comment);
    }

    /**
     * Test rolling with tags.
     */
    public function testRollWithTags(): void
    {
        $expression = '1d6+2 [magic, piercing]';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->tags);
        $this->assertCount(2, $result->tags);
        $this->assertSame('magic', $result->tags[0]);
        $this->assertSame('piercing', $result->tags[1]);
        $this->assertGreaterThanOrEqual(3, $result->total);
        $this->assertLessThanOrEqual(8, $result->total);
    }

    /**
     * Test group with tags.
     */
    public function testGroupWithTags(): void
    {
        $expression = '{1d6+6 [Piercing]}';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertCount(1, $result->groups);

        $group = $result->groups[0];
        $this->assertNotNull($group->tags);
        $this->assertCount(1, $group->tags);
        $this->assertSame('piercing', $group->tags[0]);
    }

    /**
     * Test multiple groups with different tags.
     */
    public function testMultipleGroupsWithTags(): void
    {
        $expression = '{1d6+6 [Piercing]} + {2d6 [Fire,magic]}';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertCount(2, $result->groups);

        // First group
        $group1 = $result->groups[0];
        $this->assertNotNull($group1->tags);
        $this->assertCount(1, $group1->tags);
        $this->assertSame('piercing', $group1->tags[0]);

        // Second group
        $group2 = $result->groups[1];
        $this->assertNotNull($group2->tags);
        $this->assertCount(2, $group2->tags);
        $this->assertSame('fire', $group2->tags[0]);
        $this->assertSame('magic', $group2->tags[1]);
    }

    /**
     * Test main result has no tags when only groups have tags.
     */
    public function testMainResultHasNoTagsWhenOnlyGroupsHaveTags(): void
    {
        $expression = '{1d6+6 [Piercing]} + {2d6 [Fire,magic]} # roll damage';
        $result = $this->phpdice->roll($expression);

        // Main result should not have tags (no tags on main expression)
        $this->assertNull($result->tags);

        // But comment should still be in main result
        $this->assertSame('roll damage', $result->comment);

        // Groups should have tags
        $this->assertNotNull($result->groups);
        $this->assertCount(2, $result->groups);
        $this->assertNotNull($result->groups[0]->tags);
        $this->assertNotNull($result->groups[1]->tags);
    }

    /**
     * Test main result keeps its own tags when groups also have tags.
     */
    public function testMainResultKeepsTagsWhenGroupsAlsoHaveTags(): void
    {
        $expression = '{1d6 [Piercing]} + {2d6 [Fire]} [slashing, magic] # total damage';
        $result = $this->phpdice->roll($expression);

        // Main result should keep its own tags
        $this->assertNotNull($result->tags);
        $this->assertCount(2, $result->tags);
        $this->assertSame('slashing', $result->tags[0]);
        $this->assertSame('magic', $result->tags[1]);

        // Groups should keep their own tags
        $this->assertNotNull($result->groups);
        $this->assertCount(2, $result->groups);
        $this->assertSame('piercing', $result->groups[0]->tags[0]);
        $this->assertSame('fire', $result->groups[1]->tags[0]);
    }

    /**
     * Test group with tags and comment.
     */
    public function testGroupWithTagsAndComment(): void
    {
        $expression = '{1d6+6 [Piercing] # physical damage}';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertCount(1, $result->groups);

        $group = $result->groups[0];
        $this->assertNotNull($group->tags);
        $this->assertSame('piercing', $group->tags[0]);
        $this->assertSame('physical damage', $group->comment);
    }

    /**
     * Test expression without tags.
     */
    public function testExpressionWithoutTags(): void
    {
        $expression = '1d6+2 # roll damage';
        $expr = $this->phpdice->parse($expression);

        $this->assertNull($expr->tags);
    }

    /**
     * Test rolling without tags.
     */
    public function testRollWithoutTags(): void
    {
        $expression = '1d20 + 5';
        $result = $this->phpdice->roll($expression);

        $this->assertNull($result->tags);
    }

    /**
     * Test tags work with modifiers.
     */
    public function testTagsWithModifiers(): void
    {
        $expression = '2d20 keep 1 highest [critical] # crit roll';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->tags);
        $this->assertSame('critical', $result->tags[0]);
        $this->assertSame('crit roll', $result->comment);
        $this->assertSame(1, $result->expression->modifiers->keepHighest);
    }

    /**
     * Test tags work with DC comparison.
     */
    public function testTagsWithDCComparison(): void
    {
        $expression = '1d20 + 5 dc >= 15 [saving-throw] # wisdom save';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->tags);
        $this->assertSame('saving-throw', $result->tags[0]);
        $this->assertSame('wisdom save', $result->comment);
        $this->assertSame('>=', $result->expression->comparisonOperator);
        $this->assertSame(15, $result->expression->comparisonThreshold);
    }

    /**
     * Test tags work with success counting.
     */
    public function testTagsWithSuccessCounting(): void
    {
        $expression = '6d6 count >= 5 [shadowrun] # skill check';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->tags);
        $this->assertSame('shadowrun', $result->tags[0]);
        $this->assertSame('skill check', $result->comment);
        $this->assertNotNull($result->successCount);
    }

    /**
     * Test tags work with exploding dice.
     */
    public function testTagsWithExplodingDice(): void
    {
        $expression = '1d6 explode [savage-worlds] # damage roll';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->tags);
        $this->assertSame('savage-worlds', $result->tags[0]);
        $this->assertSame('damage roll', $result->comment);
        $this->assertSame(6, $result->expression->modifiers->explosionThreshold);
    }

    /**
     * Test math-only expression with tags.
     */
    public function testMathOnlyWithTags(): void
    {
        $expression = '5 + 3 * 2 [calculation] # math result';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->tags);
        $this->assertSame('calculation', $result->tags[0]);
        $this->assertSame('math result', $result->comment);
        $this->assertSame(11, $result->total);
    }

    /**
     * Test whitespace handling in tags.
     */
    public function testTagWhitespaceHandling(): void
    {
        $expression = '1d6+2 [ magic , piercing , silver ]';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertCount(3, $expr->tags);
        $this->assertSame('magic', $expr->tags[0]);
        $this->assertSame('piercing', $expr->tags[1]);
        $this->assertSame('silver', $expr->tags[2]);
    }

    /**
     * Test empty tag list.
     */
    public function testEmptyTagList(): void
    {
        $expression = '1d6+2 []';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->tags);
        $this->assertCount(0, $expr->tags);
    }

    /**
     * Test multiple tag sections throw error.
     */
    public function testMultipleTagSectionsThrowError(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->expectExceptionMessage('Multiple tag sections are not allowed');

        $expression = '1d20 [fire] [magic]';
        $this->phpdice->parse($expression);
    }

    /**
     * Test multiple tag sections in group throw error.
     */
    public function testMultipleTagSectionsInGroupThrowError(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->expectExceptionMessage('Multiple tag sections are not allowed in a group');

        $expression = '{1d6 [fire] [magic]}';
        $this->phpdice->parse($expression);
    }
}
