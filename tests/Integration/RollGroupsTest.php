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
        $this->expectException(\Codryn\PHPDice\Exception\ParseException::class);
        $this->expectExceptionMessage('Groups cannot be nested');

        $expression = '{ 1d6 + { 2d6 } }';
        $this->phpdice->parse($expression);
    }

    /**
     * Test empty group throws exception.
     */
    public function testEmptyGroupThrowsException(): void
    {
        $this->expectException(\Codryn\PHPDice\Exception\ParseException::class);

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

    /**
     * Test that main expression has non-zero statistics with groups.
     */
    public function testMainExpressionHasStatisticsWithGroups(): void
    {
        $expression = '{ 1d20+4 } + { 1d4 }';
        $result = $this->phpdice->roll($expression);

        // Main expression should have valid statistics
        $stats = $result->expression->statistics;
        $this->assertNotNull($stats);
        $this->assertGreaterThan(0, $stats->expected, 'Expected value should be greater than 0');
        $this->assertGreaterThan(0, $stats->minimum, 'Minimum should be greater than 0');
        $this->assertGreaterThan(0, $stats->maximum, 'Maximum should be greater than 0');

        // Check that statistics are in expected ranges
        // 1d20+4 (min: 5, max: 24, expected: 14.5) + 1d4 (min: 1, max: 4, expected: 2.5)
        $this->assertSame(6, $stats->minimum);  // 5 + 1
        $this->assertSame(28, $stats->maximum); // 24 + 4
        $this->assertSame(17.0, $stats->expected); // 14.5 + 2.5
    }

    /**
     * Test that each group has its own statistics.
     */
    public function testGroupsHaveIndividualStatistics(): void
    {
        $expression = '{ 1d20+4 # skill } + { 1d4 # guidance }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);
        $this->assertCount(2, $result->groups);

        // Group 1 (1d20+4) should have its own statistics
        $group1Stats = $result->groups[0]->expression->statistics;
        $this->assertNotNull($group1Stats);
        $this->assertSame(5, $group1Stats->minimum);   // 1 + 4
        $this->assertSame(24, $group1Stats->maximum);  // 20 + 4
        $this->assertSame(14.5, $group1Stats->expected); // 10.5 + 4

        // Group 2 (1d4) should have its own statistics
        $group2Stats = $result->groups[1]->expression->statistics;
        $this->assertNotNull($group2Stats);
        $this->assertSame(1, $group2Stats->minimum);
        $this->assertSame(4, $group2Stats->maximum);
        $this->assertSame(2.5, $group2Stats->expected);

        // Groups should have different statistics (they're independent)
        $this->assertNotSame($group1Stats, $group2Stats);
        $this->assertNotEquals($group1Stats, $group2Stats);
    }

    /**
     * Test that statistics work with complex group expressions.
     */
    public function testStatisticsWithComplexGroupExpressions(): void
    {
        $expression = '{ 2d6+3 # damage } + { 1d4-1 # bonus }';
        $result = $this->phpdice->roll($expression);

        // Main expression statistics
        // Group 1: 2d6+3 (min: 5, max: 15)
        // Group 2: 1d4-1 (min: 0, max: 3)
        // Combined: min: 5+0=5, max: 15+3=18
        $mainStats = $result->expression->statistics;
        $this->assertSame(5, $mainStats->minimum);
        $this->assertSame(18, $mainStats->maximum);
        
        // Group 1 (2d6+3) statistics
        $group1Stats = $result->groups[0]->expression->statistics;
        $this->assertSame(5, $group1Stats->minimum);   // 2 + 3
        $this->assertSame(15, $group1Stats->maximum);  // 12 + 3
        $this->assertSame(10.0, $group1Stats->expected); // 7 + 3

        // Group 2 (1d4-1) statistics
        $group2Stats = $result->groups[1]->expression->statistics;
        $this->assertSame(0, $group2Stats->minimum);   // 1 - 1
        $this->assertSame(3, $group2Stats->maximum);   // 4 - 1
        $this->assertSame(1.5, $group2Stats->expected); // 2.5 - 1
    }

    /**
     * Test that standard deviation is calculated for groups.
     */
    public function testGroupsHaveStandardDeviation(): void
    {
        $expression = '{ 1d20 } + { 1d4 }';
        $result = $this->phpdice->roll($expression);

        $this->assertNotNull($result->groups);

        // Group 1 (1d20) should have standard deviation
        $group1Stats = $result->groups[0]->expression->statistics;
        $this->assertNotNull($group1Stats->standardDeviation);
        $this->assertGreaterThan(0, $group1Stats->standardDeviation);

        // Group 2 (1d4) should have standard deviation
        $group2Stats = $result->groups[1]->expression->statistics;
        $this->assertNotNull($group2Stats->standardDeviation);
        $this->assertGreaterThan(0, $group2Stats->standardDeviation);

        // Main expression should also have standard deviation
        $mainStats = $result->expression->statistics;
        $this->assertNotNull($mainStats->standardDeviation);
        $this->assertGreaterThan(0, $mainStats->standardDeviation);
    }
}
