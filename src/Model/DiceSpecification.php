<?php

declare(strict_types=1);

namespace PHPDice\Model;

/**
 * Represents the basic dice pool specification (e.g., 3d6).
 */
class DiceSpecification
{
    /**
     * Create a new dice specification.
     *
     * @param int $count Number of dice to roll (must be > 0)
     * @param int $sides Number of sides per die (must be > 0)
     * @param DiceType $type Type of dice
     */
    public function __construct(
        public readonly int $count,
        public readonly int $sides,
        public readonly DiceType $type = DiceType::STANDARD
    ) {
    }
}
