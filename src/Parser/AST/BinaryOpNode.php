<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

use PHPDice\Exception\ValidationException;

/**
 * Represents a binary operation (e.g., addition, multiplication).
 */
class BinaryOpNode extends Node
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

        return match ($this->operator) {
            '+' => $leftValue + $rightValue,
            '-' => $leftValue - $rightValue,
            '*' => $leftValue * $rightValue,
            '^' => pow($leftValue, $rightValue),
            '/' => $this->divide($leftValue, $rightValue),
            '~' => $this->modulo($leftValue, $rightValue),
            default => throw new ValidationException("Unknown operator: {$this->operator}", 'operator'),
        };
    }

    private function divide(int|float $left, int|float $right): int|float
    {
        if ($right == 0) {
            throw new ValidationException('Division by zero', 'arithmetic');
        }

        return $left / $right;
    }

    private function modulo(int|float $left, int|float $right): int
    {
        if ($right == 0) {
            throw new ValidationException('Modulo by zero', 'arithmetic');
        }

        return $left % $right;
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
