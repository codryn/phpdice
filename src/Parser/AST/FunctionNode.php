<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

use PHPDice\Exception\ValidationException;

/**
 * Represents a function call (e.g., floor, ceil, round).
 */
class FunctionNode extends Node
{
    public function __construct(
        private readonly string $name,
        private readonly Node $argument
    ) {
    }

    public function evaluate(): int|float
    {
        $value = $this->argument->evaluate();

        return match (strtolower($this->name)) {
            'floor' => floor($value),
            'ceil' => ceil($value),
            'round' => round($value),
            'abs' => (int)abs($value),
            default => throw new ValidationException("Unknown function: {$this->name}", 'function'),
        };
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArgument(): Node
    {
        return $this->argument;
    }
}
