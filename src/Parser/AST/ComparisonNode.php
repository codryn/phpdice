<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser\AST;

use Codryn\PHPDice\Exception\ValidationException;

/**
 * Represents a comparison operation (e.g., >, >=, <, <=, ==, !=).
 * Returns 1 for true, 0 for false to maintain numeric type.
 */
class ComparisonNode extends Node
{
    public function __construct(
        private readonly Node $left,
        private readonly string $operator,
        private readonly Node $right
    ) {
    }

    public function evaluate(): int|float
    {
        $leftValue = $this->left->evaluate();
        $rightValue = $this->right->evaluate();

        $result = match ($this->operator) {
            '>' => $leftValue > $rightValue,
            '>=' => $leftValue >= $rightValue,
            '<' => $leftValue < $rightValue,
            '<=' => $leftValue <= $rightValue,
            '==' => $leftValue == $rightValue,
            '!=' => $leftValue != $rightValue,
            default => throw new ValidationException("Unknown comparison operator: {$this->operator}", 'operator'),
        };

        // Return 1 for true, 0 for false
        return $result ? 1 : 0;
    }

    public function getLeft(): Node
    {
        return $this->left;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getRight(): Node
    {
        return $this->right;
    }
}
