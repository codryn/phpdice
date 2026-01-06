<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

/**
 * Represents a numeric literal in the AST.
 */
class NumberNode extends Node
{
    public function __construct(private readonly int|float $value)
    {
    }

    public function evaluate(): int|float
    {
        return $this->value;
    }

    public function getValue(): int|float
    {
        return $this->value;
    }
}
