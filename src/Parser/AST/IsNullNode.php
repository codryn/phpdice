<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser\AST;

/**
 * Represents an "is null" check for a variable.
 * Returns 1 if the variable is null or not set, 0 otherwise.
 */
class IsNullNode extends Node
{
    public function __construct(
        private readonly int|float|null $value,
        private readonly bool $isSet
    ) {
    }

    public function evaluate(): int|float
    {
        // Return 1 (true) if variable is not set or is null
        // Return 0 (false) otherwise
        if (!$this->isSet || $this->value === null) {
            return 1;
        }

        return 0;
    }

    public function getValue(): int|float|null
    {
        return $this->value;
    }

    public function isVariableSet(): bool
    {
        return $this->isSet;
    }
}
