<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

use PHPDice\Exception\ValidationException;

/**
 * Represents a function call (e.g., floor, ceil, round, max, min).
 */
class FunctionNode extends Node
{
    /**
     * @param string $name Function name
     * @param array<Node> $arguments Function arguments
     */
    public function __construct(
        private readonly string $name,
        private readonly array $arguments
    ) {
    }

    public function evaluate(): int|float
    {
        $funcName = strtolower($this->name);
        
        // Single-argument functions
        if (in_array($funcName, ['floor', 'ceil', 'round', 'abs'], true)) {
            if (count($this->arguments) !== 1) {
                throw new ValidationException(
                    "Function '{$this->name}' expects 1 argument, got " . count($this->arguments),
                    'function'
                );
            }
            $value = $this->arguments[0]->evaluate();
            
            return match ($funcName) {
                'floor' => floor($value),
                'ceil' => ceil($value),
                'round' => round($value),
                'abs' => (int)abs($value),
            };
        }
        
        // Multi-argument functions
        if (in_array($funcName, ['max', 'min'], true)) {
            if (count($this->arguments) < 2) {
                throw new ValidationException(
                    "Function '{$this->name}' expects at least 2 arguments, got " . count($this->arguments),
                    'function'
                );
            }
            $values = array_map(fn($arg) => $arg->evaluate(), $this->arguments);
            
            return match ($funcName) {
                'max' => max($values),
                'min' => min($values),
            };
        }
        
        throw new ValidationException("Unknown function: {$this->name}", 'function');
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<Node>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
    
    /**
     * Get the first argument (for backward compatibility).
     */
    public function getArgument(): Node
    {
        if (empty($this->arguments)) {
            throw new \RuntimeException('Function has no arguments');
        }
        return $this->arguments[0];
    }
}
