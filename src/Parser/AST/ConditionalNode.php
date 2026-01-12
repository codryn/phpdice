<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser\AST;

/**
 * Represents a conditional (if-then-else) expression.
 * Evaluates condition and returns either trueBranch or falseBranch result.
 */
class ConditionalNode extends Node
{
    public function __construct(
        private readonly Node $condition,
        private readonly Node $trueBranch,
        private readonly Node $falseBranch
    ) {
    }

    public function evaluate(): int|float
    {
        $conditionValue = $this->condition->evaluate();

        // Evaluate and return appropriate branch
        // Treat 0 as false, any non-zero value as true
        if ($conditionValue != 0) {
            return $this->trueBranch->evaluate();
        } else {
            return $this->falseBranch->evaluate();
        }
    }

    public function getCondition(): Node
    {
        return $this->condition;
    }

    public function getTrueBranch(): Node
    {
        return $this->trueBranch;
    }

    public function getFalseBranch(): Node
    {
        return $this->falseBranch;
    }
}
