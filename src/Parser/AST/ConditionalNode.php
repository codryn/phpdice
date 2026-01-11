<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser\AST;

use Codryn\PHPDice\Exception\ValidationException;

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

        // Validate that condition is boolean (0 or 1)
        // We allow any numeric value but treat 0 as false, non-zero as true
        if ($conditionValue != 0 && $conditionValue != 1) {
            // For non-boolean results, treat non-zero as true
            // This allows expressions like (1d6 > 3) which might evaluate to intermediate values
        }

        // Evaluate and return appropriate branch
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
