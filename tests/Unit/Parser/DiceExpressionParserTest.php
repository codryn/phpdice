<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Unit\Parser;

use Codryn\PHPDice\Exception\ParseException;
use Codryn\PHPDice\Model\DiceType;
use Codryn\PHPDice\Parser\DiceExpressionParser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DiceExpressionParser.
 *
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Parser\Validator
 * @covers \Codryn\PHPDice\Parser\Lexer
 * @covers \Codryn\PHPDice\Parser\Token
 * @covers \Codryn\PHPDice\Model\StatisticalCalculator
 * @covers \Codryn\PHPDice\Model\StatisticalData
 * @covers \Codryn\PHPDice\Model\DiceSpecification
 * @covers \Codryn\PHPDice\Model\DiceExpression
 * @covers \Codryn\PHPDice\Model\RollModifiers
 * @covers \Codryn\PHPDice\Model\DiceType
 * @covers \Codryn\PHPDice\Parser\AST\DiceNode
 * @covers \Codryn\PHPDice\Parser\AST\NumberNode
 * @covers \Codryn\PHPDice\Parser\AST\BinaryOpNode
 * @covers \Codryn\PHPDice\Parser\AST\Node
 */
class DiceExpressionParserTest extends TestCase
{
    private DiceExpressionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DiceExpressionParser();
    }

    public function testParseBasicDiceNotation(): void
    {
        $expression = $this->parser->parse('3d6');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
        $this->assertSame(DiceType::STANDARD, $expression->specification->type);
        $this->assertSame('3d6', $expression->originalExpression);
    }

    public function testParseSingleDie(): void
    {
        $expression = $this->parser->parse('1d20');

        $this->assertSame(1, $expression->specification->count);
        $this->assertSame(20, $expression->specification->sides);
    }

    public function testParseFudgeDice(): void
    {
        $expression = $this->parser->parse('4dF');

        $this->assertSame(4, $expression->specification->count);
        $this->assertSame(3, $expression->specification->sides);
        $this->assertSame(DiceType::FUDGE, $expression->specification->type);
    }

    public function testParsePercentileDice(): void
    {
        $expression = $this->parser->parse('d%');

        $this->assertSame(1, $expression->specification->count);
        $this->assertSame(100, $expression->specification->sides);
        $this->assertSame(DiceType::PERCENTILE, $expression->specification->type);
    }

    public function testParseWithAdvantage(): void
    {
        $expression = $this->parser->parse('1d20 advantage');

        $this->assertSame(1, $expression->modifiers->advantageCount);
        $this->assertSame(1, $expression->modifiers->keepHighest);
    }

    public function testParseWithDisadvantage(): void
    {
        $expression = $this->parser->parse('1d20 disadvantage');

        $this->assertSame(1, $expression->modifiers->advantageCount);
        $this->assertSame(1, $expression->modifiers->keepLowest);
    }

    public function testParseWithKeepHighest(): void
    {
        $expression = $this->parser->parse('4d6 keep 3 highest');

        $this->assertSame(3, $expression->modifiers->keepHighest);
        $this->assertNull($expression->modifiers->keepLowest);
    }

    public function testParseWithKeepLowest(): void
    {
        $expression = $this->parser->parse('4d6 keep 1 lowest');

        $this->assertNull($expression->modifiers->keepHighest);
        $this->assertSame(1, $expression->modifiers->keepLowest);
    }

    public function testParseInvalidExpression(): void
    {
        $this->expectException(ParseException::class);
        $this->parser->parse('invalid');
    }

    public function testParseWithSuccessCounting(): void
    {
        $expression = $this->parser->parse('5d6 count >= 4');

        $this->assertSame(4, $expression->modifiers->successThreshold);
        $this->assertSame('>=', $expression->modifiers->successOperator);
    }

    public function testGetAstRoot(): void
    {
        $this->parser->parse('3d6');
        $ast = $this->parser->getAstRoot();

        $this->assertNotNull($ast);
    }

    public function testParseWithPlaceholder(): void
    {
        $expression = $this->parser->parse('2d6 + $bonus$', ['bonus' => 3]);

        $this->assertArrayHasKey('bonus', $expression->modifiers->resolvedVariables);
        $this->assertSame(3, $expression->modifiers->resolvedVariables['bonus']);
    }

    public function testParseUnboundPlaceholder(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unbound placeholder');
        $this->parser->parse('2d6 + $bonus$');
    }

    public function testParseWithComparisonOperator(): void
    {
        $expression = $this->parser->parse('1d20 dc >= 15');

        $this->assertSame('>=', $expression->comparisonOperator);
        $this->assertSame(15, $expression->comparisonThreshold);
    }

    public function testParseMathInDiceExpression(): void
    {
        $expression = $this->parser->parse('(1+2)d(10-2)');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(8, $expression->specification->sides);
        $this->assertSame(DiceType::STANDARD, $expression->specification->type);
    }

    public function testParseMathInDiceCount(): void
    {
        $expression = $this->parser->parse('(2*2)d6');

        $this->assertSame(4, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    public function testParseMathInDiceSides(): void
    {
        $expression = $this->parser->parse('3d(3+3)');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    public function testParseSimpleParenthesesInDice(): void
    {
        $expression = $this->parser->parse('(1)d(6)');

        $this->assertSame(1, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
    }

    public function testParseComplexMathInDice(): void
    {
        $expression = $this->parser->parse('(2+1)d(4*3)');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(12, $expression->specification->sides);
    }

    public function testParseDiceInCountThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Dice count cannot contain dice rolls');
        $this->parser->parse('(1d6)d6');
    }

    public function testParseDiceInSidesThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Dice sides cannot contain dice rolls');
        $this->parser->parse('3d(1d6)');
    }

    public function testParseNonIntegerCountThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Dice count must be an integer');
        $this->parser->parse('(1.5+0)d6');
    }

    public function testParseNonIntegerSidesThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Dice sides must be an integer');
        $this->parser->parse('3d(2.5+0)');
    }
}
