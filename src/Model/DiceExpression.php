<?php

declare(strict_types=1);

namespace PHPDice\Model;

use PHPDice\Parser\AST\Node;

/**
 * Represents a fully parsed and validated dice expression ready for rolling.
 */
class DiceExpression
{
    /**
     * Create a new dice expression.
     *
     * @param DiceSpecification|null $specification The base dice being rolled (null for math-only expressions)
     * @param RollModifiers $modifiers All modifiers and mechanics
     * @param StatisticalData $statistics Pre-calculated probability data
     * @param string $originalExpression Raw input string
     * @param Node|null $astRoot Abstract syntax tree for evaluation
     * @param string|null $comparisonOperator Operator for success rolls (>=, <=, etc.)
     * @param int|null $comparisonThreshold Target number for comparisons
     */
    public function __construct(
        public readonly ?DiceSpecification $specification,
        public readonly RollModifiers $modifiers,
        public readonly StatisticalData $statistics,
        public readonly string $originalExpression,
        public readonly ?Node $astRoot = null,
        public readonly ?string $comparisonOperator = null,
        public readonly ?int $comparisonThreshold = null
    ) {
    }

    /**
     * Get the statistics for this expression.
     *
     * @return StatisticalData Statistical information
     */
    public function getStatistics(): StatisticalData
    {
        return $this->statistics;
    }
}
