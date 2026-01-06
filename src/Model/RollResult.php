<?php

declare(strict_types=1);

namespace PHPDice\Model;

/**
 * Represents the complete result of a dice roll.
 */
class RollResult
{
    /**
     * Create a new roll result.
     *
     * @param DiceExpression $expression The original parsed expression
     * @param int|float $total Final calculated total
     * @param array<int> $diceValues Individual die values rolled
     * @param array<int>|null $keptDice Indices of dice that were kept (for advantage/disadvantage)
     * @param array<int>|null $discardedDice Indices of dice that were discarded
     * @param int|null $successCount Number of successes (for success counting mode)
     * @param bool $isCriticalSuccess Whether this roll is a critical success
     * @param bool $isCriticalFailure Whether this roll is a critical failure
     * @param bool|null $isSuccess Whether comparison check succeeded (for success rolls)
     * @param array<int, array{rolls: array<int, int>, count: int, limitReached: bool}>|null $rerollHistory History of rerolls per die
     * @param array<int, array{rolls: array<int, int>, count: int, cumulativeTotal: int, limitReached: bool}>|null $explosionHistory History of explosions per die
     */
    public function __construct(
        public readonly DiceExpression $expression,
        public readonly int|float $total,
        public readonly array $diceValues,
        public readonly ?array $keptDice = null,
        public readonly ?array $discardedDice = null,
        public readonly ?int $successCount = null,
        public readonly bool $isCriticalSuccess = false,
        public readonly bool $isCriticalFailure = false,
        public readonly ?bool $isSuccess = null,
        public readonly ?array $rerollHistory = null,
        public readonly ?array $explosionHistory = null
    ) {
    }
}
