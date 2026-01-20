<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\Exception\ParseException;
use Codryn\PHPDice\Exception\ValidationException;
use Codryn\PHPDice\Model\DiceType;
use Codryn\PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for math in dice expressions.
 * Ensures that expressions like (1+2)d(10-2) work correctly.
 */
#[CoversClass(PHPDice::class)]
class MathInDiceExpressionTest extends BaseTestCaseMock
{
    /**
     * Test parsing math in both count and sides.
     */
    public function testParseMathInCountAndSides(): void
    {
        $expression = $this->phpdice->parse('(1+2)d(10-2)');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(8, $expression->specification->sides);
        $this->assertSame(DiceType::STANDARD, $expression->specification->type);
    }

    /**
     * Test rolling dice with math expressions.
     */
    public function testRollMathInDiceExpression(): void
    {
        // (1+2)d(10-2) = 3d8
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 7, 8);

        $result = $this->phpdice->roll('(1+2)d(10-2)');

        $this->assertCount(3, $result->diceValues);
        $this->assertEquals([5, 7, 8], $result->diceValues);
        $this->assertEquals(20, $result->total);
    }

    /**
     * Test math in count only.
     */
    public function testMathInCountOnly(): void
    {
        $expression = $this->phpdice->parse('(2*2)d6');

        $this->assertSame(4, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    /**
     * Test math in sides only.
     */
    public function testMathInSidesOnly(): void
    {
        $expression = $this->phpdice->parse('3d(3+3)');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    /**
     * Test simple parentheses (no actual math).
     */
    public function testSimpleParentheses(): void
    {
        $expression = $this->phpdice->parse('(1)d(6)');

        $this->assertSame(1, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    /**
     * Test complex math expressions.
     */
    public function testComplexMathExpressions(): void
    {
        // (2+1)d(4*3) = 3d12
        $expression = $this->phpdice->parse('(2+1)d(4*3)');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(12, $expression->specification->sides);
    }

    /**
     * Test math with subtraction and division.
     */
    public function testMathWithVariousOperators(): void
    {
        // (10-2)d(12/2) = 8d6
        $expression = $this->phpdice->parse('(10-2)d(12/2)');

        $this->assertSame(8, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    /**
     * Test that math expressions work with modifiers.
     */
    public function testMathWithModifiers(): void
    {
        $expression = $this->phpdice->parse('(1+2)d(10-2) advantage');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(8, $expression->specification->sides);
        $this->assertSame(3, $expression->modifiers->advantageCount);
        $this->assertSame(3, $expression->modifiers->keepHighest);
    }

    /**
     * Test that math expressions work with arithmetic.
     */
    public function testMathWithArithmetic(): void
    {
        // (1+2)d(10-2) + 5 = 3d8 + 5
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 4, 5);

        $result = $this->phpdice->roll('(1+2)d(10-2) + 5');

        $this->assertEquals(17, $result->total); // 3+4+5+5 = 17
    }

    /**
     * Test math with fudge dice.
     */
    public function testMathWithFudgeDice(): void
    {
        $expression = $this->phpdice->parse('(2+2)dF');

        $this->assertSame(4, $expression->specification->count);
        $this->assertSame(3, $expression->specification->sides);
        $this->assertSame(DiceType::FUDGE, $expression->specification->type);
    }

    /**
     * Test math with percentile dice.
     */
    public function testMathWithPercentileDice(): void
    {
        $expression = $this->phpdice->parse('(1+0)d%');

        $this->assertSame(1, $expression->specification->count);
        $this->assertSame(100, $expression->specification->sides);
        $this->assertSame(DiceType::PERCENTILE, $expression->specification->type);
    }

    /**
     * Test that dice in count throws exception.
     */
    public function testDiceInCountThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Dice count cannot contain dice rolls');

        $this->phpdice->parse('(1d6)d6');
    }

    /**
     * Test that dice in sides throws exception.
     */
    public function testDiceInSidesThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Dice sides cannot contain dice rolls');

        $this->phpdice->parse('3d(1d6)');
    }

    /**
     * Test validation: count must be at least 1.
     */
    public function testValidationCountMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Dice count must be at least 1/');

        $this->phpdice->parse('(1-2)d6');
    }

    /**
     * Test validation: sides must be at least 2.
     */
    public function testValidationSidesMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Dice must have at least 2 sides/');

        $this->phpdice->parse('3d(2-1)');
    }

    /**
     * Test edge case: minimum valid values.
     */
    public function testMinimumValidValues(): void
    {
        $expression = $this->phpdice->parse('(1+0)d(2+0)');

        $this->assertSame(1, $expression->specification->count);
        $this->assertSame(2, $expression->specification->sides);
    }

    /**
     * Test nested parentheses.
     */
    public function testNestedParentheses(): void
    {
        // ((1+1)+1)d((5+1)+0) = 3d6
        $expression = $this->phpdice->parse('((1+1)+1)d((5+1)+0)');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    /**
     * Test that regular dice notation still works.
     */
    public function testRegularDiceNotationStillWorks(): void
    {
        $expression = $this->phpdice->parse('3d6');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    /**
     * Test mixed: math in count, literal in sides.
     */
    public function testMixedMathAndLiteral(): void
    {
        $expression = $this->phpdice->parse('(2+1)d20');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(20, $expression->specification->sides);
    }
}
