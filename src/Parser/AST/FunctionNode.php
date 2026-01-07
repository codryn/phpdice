<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

use PHPDice\Exception\ValidationException;

/**
 * Represents a function call (e.g., floor, ceil, round, min, max).
 */
class FunctionNode extends Node
{
    /** @var array<Node> */
    private readonly array $arguments;

    /**
     * @param string $name Function name
     * @param Node|array<Node> $argument Single argument or array of arguments
     */
    public function __construct(
        private readonly string $name,
        Node|array $argument
    ) {
        // Support both single argument (for backward compatibility) and multiple arguments
        $this->arguments = is_array($argument) ? $argument : [$argument];
    }

    public function evaluate(): int|float
    {
        $lowerName = strtolower($this->name);

        // For single-argument functions
        if (in_array($lowerName, ['floor', 'ceil', 'round', 'abs'], true)) {
            if (count($this->arguments) !== 1) {
                throw new ValidationException("Function '{$this->name}' expects exactly 1 argument, got " . count($this->arguments), 'function');
            }
            $value = $this->arguments[0]->evaluate();

            return match ($lowerName) {
                'floor' => floor($value),
                'ceil' => ceil($value),
                'round' => round($value),
                'abs' => (int)abs($value),
            };
        }

        // For multi-argument functions (min, max)
        if (in_array($lowerName, ['min', 'max'], true)) {
            if (count($this->arguments) < 2) {
                throw new ValidationException("Function '{$this->name}' expects at least 2 arguments, got " . count($this->arguments), 'function');
            }

            $values = array_map(fn (Node $arg) => $arg->evaluate(), $this->arguments);

            return match ($lowerName) {
                'min' => min(...$values),
                'max' => max(...$values),
            };
        }

        throw new ValidationException("Unknown function: {$this->name}", 'function');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArgument(): Node
    {
        // For backward compatibility, return first argument
        return $this->arguments[0];
    }

    /**
     * Get all arguments.
     *
     * @return array<Node>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
