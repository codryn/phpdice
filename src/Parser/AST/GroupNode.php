<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

/**
 * Represents a grouped expression { expression # comment [tag1, tag2] }.
 */
class GroupNode extends Node
{
    /**
     * @param Node $expression
     * @param string|null $comment
     * @param array<string>|null $tags
     */
    public function __construct(
        private readonly Node $expression,
        private readonly ?string $comment = null,
        private readonly ?array $tags = null
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

    /**
     * @return array<string>|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }
}
