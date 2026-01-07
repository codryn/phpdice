<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Parser;

use PHPDice\Parser\Lexer;
use PHPDice\Parser\Token;
use PHPDice\Tests\Unit\BaseTestCase;

/**
 * Unit tests for Lexer.
 *
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Parser\Token
 */
class LexerTest extends BaseTestCase
{
    /**
     * Test tokenizing basic dice notation.
     */
    public function testTokenizeBasicDiceNotation(): void
    {
        $lexer = new Lexer('3d6');
        $tokens = $lexer->tokenize();

        $this->assertCount(4, $tokens); // 3, d, 6, EOF

        $this->assertSame(Token::TYPE_NUMBER, $tokens[0]->type);
        $this->assertSame(3, $tokens[0]->value);

        $this->assertSame(Token::TYPE_DICE, $tokens[1]->type);
        $this->assertSame('d', $tokens[1]->value);

        $this->assertSame(Token::TYPE_NUMBER, $tokens[2]->type);
        $this->assertSame(6, $tokens[2]->value);

        $this->assertSame(Token::TYPE_EOF, $tokens[3]->type);
    }

    /**
     * Test tokenizing with whitespace.
     */
    public function testTokenizeWithWhitespace(): void
    {
        $lexer = new Lexer('  3  d  6  ');
        $tokens = $lexer->tokenize();

        $this->assertCount(4, $tokens); // Whitespace should be ignored

        $this->assertSame(Token::TYPE_NUMBER, $tokens[0]->type);
        $this->assertSame(3, $tokens[0]->value);

        $this->assertSame(Token::TYPE_DICE, $tokens[1]->type);

        $this->assertSame(Token::TYPE_NUMBER, $tokens[2]->type);
        $this->assertSame(6, $tokens[2]->value);
    }

    /**
     * Test tokenizing 1d20.
     */
    public function testTokenize1d20(): void
    {
        $lexer = new Lexer('1d20');
        $tokens = $lexer->tokenize();

        $this->assertCount(4, $tokens);

        $this->assertSame(1, $tokens[0]->value);
        $this->assertSame('d', $tokens[1]->value);
        $this->assertSame(20, $tokens[2]->value);
    }

    /**
     * Test tokenizing large numbers.
     */
    public function testTokenizeLargeNumbers(): void
    {
        $lexer = new Lexer('100d100');
        $tokens = $lexer->tokenize();

        $this->assertSame(100, $tokens[0]->value);
        $this->assertSame(100, $tokens[2]->value);
    }

    /**
     * Test tokenizing uppercase D.
     */
    public function testTokenizeUppercaseD(): void
    {
        $lexer = new Lexer('3D6');
        $tokens = $lexer->tokenize();

        $this->assertSame(Token::TYPE_DICE, $tokens[1]->type);
        $this->assertSame('d', $tokens[1]->value);
    }

    /**
     * Test tokenizing operators (for future use).
     */
    public function testTokenizeOperators(): void
    {
        $lexer = new Lexer('3d6+5');
        $tokens = $lexer->tokenize();

        $this->assertCount(6, $tokens); // 3, d, 6, +, 5, EOF

        $this->assertSame(Token::TYPE_OPERATOR, $tokens[3]->type);
        $this->assertSame('+', $tokens[3]->value);

        $this->assertSame(Token::TYPE_NUMBER, $tokens[4]->type);
        $this->assertSame(5, $tokens[4]->value);
    }

    /**
     * Test tokenizing parentheses.
     */
    public function testTokenizeParentheses(): void
    {
        $lexer = new Lexer('(3d6)');
        $tokens = $lexer->tokenize();

        $this->assertSame(Token::TYPE_LPAREN, $tokens[0]->type);
        $this->assertSame(Token::TYPE_NUMBER, $tokens[1]->type);
        $this->assertSame(Token::TYPE_DICE, $tokens[2]->type);
        $this->assertSame(Token::TYPE_NUMBER, $tokens[3]->type);
        $this->assertSame(Token::TYPE_RPAREN, $tokens[4]->type);
    }

    /**
     * Test tokenizing with placeholder.
     */
    public function testTokenizePlaceholder(): void
    {
        $lexer = new Lexer('$a$ + 3');
        $tokens = $lexer->tokenize();

        $this->assertSame(Token::TYPE_PLACEHOLDER, $tokens[0]->type);
        $this->assertSame(Token::TYPE_OPERATOR, $tokens[1]->type);
        $this->assertSame(Token::TYPE_NUMBER, $tokens[2]->type);
    }

    /**
     * Test tokenizing with math function.
     */
    public function testTokenizeMathFunction(): void
    {
        $lexer = new Lexer('max(1,3)');
        $tokens = $lexer->tokenize();

        $this->assertSame(Token::TYPE_KEYWORD, $tokens[0]->type);
        $this->assertSame(Token::TYPE_LPAREN, $tokens[1]->type);
        $this->assertSame(Token::TYPE_NUMBER, $tokens[2]->type);
        $this->assertSame(Token::TYPE_COMMA, $tokens[3]->type);
        $this->assertSame(Token::TYPE_NUMBER, $tokens[4]->type);
        $this->assertSame(Token::TYPE_RPAREN, $tokens[5]->type);
    }

    /**
     * Test tokenizing with formula.
     */
    public function testTokenizeFormula(): void
    {
        $lexer = new Lexer('2 * $strength$ + 5');
        $tokens = $lexer->tokenize();

        $this->assertSame(Token::TYPE_NUMBER, $tokens[0]->type);
        $this->assertSame(Token::TYPE_OPERATOR, $tokens[1]->type);
        $this->assertSame(Token::TYPE_PLACEHOLDER, $tokens[2]->type);
        $this->assertSame(Token::TYPE_OPERATOR, $tokens[3]->type);
        $this->assertSame(Token::TYPE_NUMBER, $tokens[4]->type);
    }

    /**
     * Test invalid character throws exception.
     */
    public function testInvalidCharacter(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->expectExceptionMessage("Unexpected character '#'");

        $lexer = new Lexer('3#6');
        $lexer->tokenize();
    }

    /**
     * Test tokenizing decimal numbers.
     */
    public function testTokenizeDecimalNumbers(): void
    {
        $lexer = new Lexer('1.5 + 2.75');
        $tokens = $lexer->tokenize();

        $this->assertCount(4, $tokens); // 1.5, +, 2.75, EOF

        $this->assertSame(Token::TYPE_NUMBER, $tokens[0]->type);
        $this->assertSame(1.5, $tokens[0]->value);

        $this->assertSame(Token::TYPE_OPERATOR, $tokens[1]->type);
        $this->assertSame('+', $tokens[1]->value);

        $this->assertSame(Token::TYPE_NUMBER, $tokens[2]->type);
        $this->assertSame(2.75, $tokens[2]->value);

        $this->assertSame(Token::TYPE_EOF, $tokens[3]->type);
    }

    /**
     * Test tokenizing decimal in dice expression.
     */
    public function testTokenizeDecimalInDiceExpression(): void
    {
        $lexer = new Lexer('1d20 * 1.4');
        $tokens = $lexer->tokenize();

        $this->assertCount(6, $tokens); // 1, d, 20, *, 1.4, EOF

        $this->assertSame(Token::TYPE_NUMBER, $tokens[0]->type);
        $this->assertSame(1, $tokens[0]->value);

        $this->assertSame(Token::TYPE_DICE, $tokens[1]->type);
        $this->assertSame('d', $tokens[1]->value);

        $this->assertSame(Token::TYPE_NUMBER, $tokens[2]->type);
        $this->assertSame(20, $tokens[2]->value);

        $this->assertSame(Token::TYPE_OPERATOR, $tokens[3]->type);
        $this->assertSame('*', $tokens[3]->value);

        $this->assertSame(Token::TYPE_NUMBER, $tokens[4]->type);
        $this->assertSame(1.4, $tokens[4]->value);

        $this->assertSame(Token::TYPE_EOF, $tokens[5]->type);
    }

    /**
     * Test decimal point without following digits is not treated as decimal.
     */
    public function testDecimalPointWithoutDigitsNotTreatedAsDecimal(): void
    {
        // A decimal point followed by a non-digit should leave the decimal point
        // to be handled as an unexpected character
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->expectExceptionMessage("Unexpected character '.'");

        $lexer = new Lexer('5.d6');
        $lexer->tokenize();
    }

    /**
     * Test integer numbers still work correctly.
     */
    public function testIntegerNumbersStillWork(): void
    {
        $lexer = new Lexer('42');
        $tokens = $lexer->tokenize();

        $this->assertCount(2, $tokens); // 42, EOF

        $this->assertSame(Token::TYPE_NUMBER, $tokens[0]->type);
        $this->assertSame(42, $tokens[0]->value);
        $this->assertIsInt($tokens[0]->value);
    }
}
