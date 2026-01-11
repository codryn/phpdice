<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for Roll Groups feature.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Parser\Lexer
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 */
#[CoversClass(PHPDice::class)]
final class RollGroupsTest extends BaseTestCase
{
    /**
     * Test parsing expression with a single group.
     */
    public function testParseSingleGroup(): void
    {
        $expression = '{ 1d6+6 }';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->astRoot);
        $this->assertSame($expression, $expr->originalExpression);
    }

    /**
     * Test parsing expression with group and comment.
     */
    public function testParseGroupWithComment(): void
    {
        $expression = '{ 1d6+6 # fire damage }';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->astRoot);
    }

    /**
     * Test parsing expression with multiple groups.
     */
    public function testParseMultipleGroups(): void
    {
        $expression = '{ 1d6+6 # fire } + { 2d6 # water }';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->astRoot);
    }

    /**
     * Test rolling expression with groups populates groups field.
     */
    public function testRollWithGroupsPopulatesField(): void
    {
        $expression = '{ 1d6+6 # fire } + { 2d6 # water }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertIsArray($result->groups);
        $this->assertCount(2, $result->groups);
    }

    /**
     * Test group comments are preserved.
     */
    public function testGroupCommentsPreserved(): void
    {
        $expression = '{ 1d6+6 # fire } + { 2d6 # water }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertSame('fire', $result->groups[0]->comment);
        $this->assertSame('water', $result->groups[1]->comment);
    }

    /**
     * Test overall expression comment and group comments coexist.
     */
    public function testOverallAndGroupComments(): void
    {
        $expression = '{ 1d6+6 # fire } + { 2d6 # water } # total damage';
        $result = $this->phpdice->roll($expression);

        $this->assertSame('total damage', $result->comment);
        $this->assertNotNull($result->groups);
        $this->assertSame('fire', $result->groups[0]->comment);
        $this->assertSame('water', $result->groups[1]->comment);
    }

    /**
     * Test nested groups throw exception.
     */
    public function testNestedGroupsThrowException(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->expectExceptionMessage('Groups cannot be nested');

        $expression = '{ 1d6 + { 2d6 } }';
        $this->phpdice->parse($expression);
    }

    /**
     * Test empty group throws exception.
     */
    public function testEmptyGroupThrowsException(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);

        $expression = '{ }';
        $this->phpdice->parse($expression);
    }

    /**
     * Test groups must be combined with arithmetic operators.
     */
    public function testGroupsMustBeCombinedWithOperators(): void
    {
        // Groups need to be combined with operators like + or -
        $expression = '{ 1d6 } + { 2d6 }';
        $expr = $this->phpdice->parse($expression);

        $this->assertNotNull($expr->astRoot);
    }

    /**
     * Test group with placeholder in comment.
     */
    public function testGroupWithPlaceholderInComment(): void
    {
        $expression = '{ 1d6+$bonus$ # fire damage ($bonus$ bonus) }';
        $variables = ['bonus' => 3];

        $result = $this->phpdice->roll($expression, $variables);

        $this->assertNotNull($result->groups);
        $this->assertSame('fire damage (3 bonus)', $result->groups[0]->comment);
    }

    /**
     * Test groups with subtraction operator.
     */
    public function testGroupsWithSubtraction(): void
    {
        $expression = '{ 3d6 # damage } - { 1d4 # resistance }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertCount(2, $result->groups);
    }

    /**
     * Test groups with multiplication operator.
     */
    public function testGroupsWithMultiplication(): void
    {
        $expression = '{ 2d6 # base } * { 1d4 # multiplier }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertCount(2, $result->groups);
    }

    /**
     * Test single group without other operators.
     */
    public function testSingleGroupAlone(): void
    {
        $expression = '{ 1d20 + 5 # attack roll }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertCount(1, $result->groups);
        $this->assertSame('attack roll', $result->groups[0]->comment);
    }

    /**
     * Test group without comment.
     */
    public function testGroupWithoutComment(): void
    {
        $expression = '{ 1d6 } + { 2d6 }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertNull($result->groups[0]->comment);
        $this->assertNull($result->groups[1]->comment);
    }

    /**
     * Test group results have correct totals.
     */
    public function testGroupResultsHaveCorrectTotals(): void
    {
        $expression = '{ 1d6+6 } + { 2d6 }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);

        // Group 1 should be between 7 and 12 (1d6+6)
        $this->assertGreaterThanOrEqual(7, $result->groups[0]->total);
        $this->assertLessThanOrEqual(12, $result->groups[0]->total);

        // Group 2 should be between 2 and 12 (2d6)
        $this->assertGreaterThanOrEqual(2, $result->groups[1]->total);
        $this->assertLessThanOrEqual(12, $result->groups[1]->total);
    }
}
