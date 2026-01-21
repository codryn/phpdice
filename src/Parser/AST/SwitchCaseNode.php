<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser\AST;

/**
 * Represents a switch-case expression.
 * Evaluates the switch expression and returns the result of the matching case.
 */
class SwitchCaseNode extends Node
{
    /**
     * @param Node $switchExpression The expression to evaluate and match against cases
     * @param array<array{range: array<int>, expression: Node}> $cases Array of cases, each with a range and expression
     * @param Node|null $defaultExpression The default expression if no case matches (optional)
     */
    public function __construct(
        private readonly Node $switchExpression,
        private readonly array $cases,
        private readonly ?Node $defaultExpression = null,
    ) {
    }

    public function evaluate(): int|float
    {
        $switchValue = $this->switchExpression->evaluate();

        // Check each case to find a match
        foreach ($this->cases as $case) {
            foreach ($case['range'] as $value) {
                if ($switchValue == $value) {
                    return $case['expression']->evaluate();
                }
            }
        }

        // No case matched, use default if available
        if ($this->defaultExpression !== null) {
            return $this->defaultExpression->evaluate();
        }

        // No case matched and no default provided
        throw new \Codryn\PHPDice\Exception\ParseException(
            "Switch expression value {$switchValue} does not match any case and no default case is provided",
            0,
        );
    }

    public function getSwitchExpression(): Node
    {
        return $this->switchExpression;
    }

    /**
     * @return array<array{range: array<int>, expression: Node}>
     */
    public function getCases(): array
    {
        return $this->cases;
    }

    public function getDefaultExpression(): ?Node
    {
        return $this->defaultExpression;
    }
}
