<?php

declare(strict_types=1);

namespace PHPDice\Model;

/**
 * Contains all modifiers and special mechanics for a dice roll.
 */
class RollModifiers
{
    /**
     * Create a new roll modifiers instance.
     *
     * @param string|null $arithmeticExpression Full arithmetic expression tree (AST or string)
     * @param int $arithmeticModifier Flat bonus/penalty (deprecated in favor of arithmeticExpression)
     * @param int|null $advantageCount Number of extra dice for advantage
     * @param int|null $keepHighest Keep N highest dice
     * @param int|null $keepLowest Keep N lowest dice
     * @param int|null $rerollThreshold Reroll if condition met
     * @param string|null $rerollOperator Reroll comparison operator (<=, <, >=, >, ==)
     * @param int $rerollLimit Max rerolls per die (default 100)
     * @param int|null $explosionThreshold Explode if condition met
     * @param string|null $explosionOperator Explosion comparison operator (>=, <=)
     * @param int $explosionLimit Max explosions per die (default 100)
     * @param int|null $successThreshold Count successes above this value
     * @param string|null $successOperator Success comparison operator (>=, >)
     * @param int|null $criticalSuccess Flag critical success on this value
     * @param int|null $criticalFailure Flag critical failure on this value
     * @param array<string, int> $resolvedVariables Placeholder values (name => value)
     */
    public function __construct(
        public readonly ?string $arithmeticExpression = null,
        public readonly int $arithmeticModifier = 0,
        public readonly ?int $advantageCount = null,
        public readonly ?int $keepHighest = null,
        public readonly ?int $keepLowest = null,
        public readonly ?int $rerollThreshold = null,
        public readonly ?string $rerollOperator = null,
        public readonly int $rerollLimit = 100,
        public readonly ?int $explosionThreshold = null,
        public readonly ?string $explosionOperator = null,
        public readonly int $explosionLimit = 100,
        public readonly ?int $successThreshold = null,
        public readonly ?string $successOperator = null,
        public readonly ?int $criticalSuccess = null,
        public readonly ?int $criticalFailure = null,
        public readonly array $resolvedVariables = []
    ) {
    }
}
