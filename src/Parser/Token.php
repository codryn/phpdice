<?php

declare(strict_types=1);

namespace PHPDice\Parser;

/**
 * Represents a token in the dice expression.
 */
class Token
{
    public const TYPE_NUMBER = 'NUMBER';
    public const TYPE_DICE = 'DICE';
    public const TYPE_EOF = 'EOF';
    public const TYPE_OPERATOR = 'OPERATOR';
    public const TYPE_LPAREN = 'LPAREN';
    public const TYPE_RPAREN = 'RPAREN';
    public const TYPE_KEYWORD = 'KEYWORD';
    public const TYPE_FUNCTION = 'FUNCTION';
    public const TYPE_COMMA = 'COMMA';
    public const TYPE_PERCENT = 'PERCENT';
    public const TYPE_PLACEHOLDER = 'PLACEHOLDER';
    public const TYPE_COMPARISON = 'COMPARISON';

    /**
     * Create a new token.
     *
     * @param string $type Token type
     * @param string|int|float|null $value Token value
     * @param int $position Position in the expression (0-indexed)
     */
    public function __construct(
        public readonly string $type,
        public readonly string|int|float|null $value = null,
        public readonly int $position = 0
    ) {
    }
}
