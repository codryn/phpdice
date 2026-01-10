<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

/**
 * Represents a grouped expression { expression # comment }.
 */
class GroupNode extends Node
{
    public function __construct(
        private readonly Node $expression,
        private readonly ?string $comment = null
    ) {
    }

    public function evaluate(): int|float
    {
        return $this->expression->evaluate();
    }

    public function getExpression(): Node
    {
        return $this->expression;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
