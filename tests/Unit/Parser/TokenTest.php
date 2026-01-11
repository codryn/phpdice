<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Unit\Parser;

use Codryn\PHPDice\Parser\Token;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Token.
 *
 * @covers \Codryn\PHPDice\Parser\Token
 */
class TokenTest extends TestCase
{
    public function testConstructorWithNumberToken(): void
    {
        $token = new Token(Token::TYPE_NUMBER, 42, 0);

        $this->assertSame(Token::TYPE_NUMBER, $token->type);
        $this->assertSame(42, $token->value);
        $this->assertSame(0, $token->position);
    }

    public function testConstructorWithDiceToken(): void
    {
        $token = new Token(Token::TYPE_DICE, 'd', 2);

        $this->assertSame(Token::TYPE_DICE, $token->type);
        $this->assertSame('d', $token->value);
        $this->assertSame(2, $token->position);
    }

    public function testConstructorWithOperatorToken(): void
    {
        $token = new Token(Token::TYPE_OPERATOR, '+', 5);

        $this->assertSame(Token::TYPE_OPERATOR, $token->type);
        $this->assertSame('+', $token->value);
        $this->assertSame(5, $token->position);
    }

    public function testConstructorWithEOFToken(): void
    {
        $token = new Token(Token::TYPE_EOF, null, 10);

        $this->assertSame(Token::TYPE_EOF, $token->type);
        $this->assertNull($token->value);
        $this->assertSame(10, $token->position);
    }

    public function testConstructorWithKeywordToken(): void
    {
        $token = new Token(Token::TYPE_KEYWORD, 'advantage', 7);

        $this->assertSame(Token::TYPE_KEYWORD, $token->type);
        $this->assertSame('advantage', $token->value);
    }

    public function testConstructorWithFunctionToken(): void
    {
        $token = new Token(Token::TYPE_FUNCTION, 'floor', 0);

        $this->assertSame(Token::TYPE_FUNCTION, $token->type);
        $this->assertSame('floor', $token->value);
    }

    public function testConstructorWithParenTokens(): void
    {
        $lparen = new Token(Token::TYPE_LPAREN, '(', 3);
        $rparen = new Token(Token::TYPE_RPAREN, ')', 8);

        $this->assertSame(Token::TYPE_LPAREN, $lparen->type);
        $this->assertSame('(', $lparen->value);
        $this->assertSame(Token::TYPE_RPAREN, $rparen->type);
        $this->assertSame(')', $rparen->value);
    }

    public function testConstructorWithCommaToken(): void
    {
        $token = new Token(Token::TYPE_COMMA, ',', 5);

        $this->assertSame(Token::TYPE_COMMA, $token->type);
        $this->assertSame(',', $token->value);
    }

    public function testConstructorWithPercentToken(): void
    {
        $token = new Token(Token::TYPE_PERCENT, '%', 4);

        $this->assertSame(Token::TYPE_PERCENT, $token->type);
        $this->assertSame('%', $token->value);
    }

    public function testConstructorWithPlaceholderToken(): void
    {
        $token = new Token(Token::TYPE_PLACEHOLDER, 'bonus', 1);

        $this->assertSame(Token::TYPE_PLACEHOLDER, $token->type);
        $this->assertSame('bonus', $token->value);
    }

    public function testConstructorWithComparisonToken(): void
    {
        $token = new Token(Token::TYPE_COMPARISON, '>=', 6);

        $this->assertSame(Token::TYPE_COMPARISON, $token->type);
        $this->assertSame('>=', $token->value);
    }

    public function testConstructorWithFloatValue(): void
    {
        $token = new Token(Token::TYPE_NUMBER, 3.14, 0);

        $this->assertSame(3.14, $token->value);
    }

    public function testDefaultPosition(): void
    {
        $token = new Token(Token::TYPE_NUMBER, 5);

        $this->assertSame(0, $token->position);
    }

    public function testDefaultValue(): void
    {
        $token = new Token(Token::TYPE_EOF);

        $this->assertNull($token->value);
        $this->assertSame(0, $token->position);
    }

    public function testAllTokenTypeConstants(): void
    {
        $this->assertSame('NUMBER', Token::TYPE_NUMBER);
        $this->assertSame('DICE', Token::TYPE_DICE);
        $this->assertSame('EOF', Token::TYPE_EOF);
        $this->assertSame('OPERATOR', Token::TYPE_OPERATOR);
        $this->assertSame('LPAREN', Token::TYPE_LPAREN);
        $this->assertSame('RPAREN', Token::TYPE_RPAREN);
        $this->assertSame('KEYWORD', Token::TYPE_KEYWORD);
        $this->assertSame('FUNCTION', Token::TYPE_FUNCTION);
        $this->assertSame('COMMA', Token::TYPE_COMMA);
        $this->assertSame('PERCENT', Token::TYPE_PERCENT);
        $this->assertSame('PLACEHOLDER', Token::TYPE_PLACEHOLDER);
        $this->assertSame('COMPARISON', Token::TYPE_COMPARISON);
    }
}
