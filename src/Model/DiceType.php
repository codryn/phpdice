<?php

declare(strict_types=1);

namespace PHPDice\Model;

/**
 * Enum representing different types of dice.
 */
enum DiceType: string
{
    /**
     * Standard polyhedral dice (e.g., d6, d20).
     */
    case STANDARD = 'standard';

    /**
     * FATE/Fudge dice (dF) with values -1, 0, +1.
     */
    case FUDGE = 'fudge';

    /**
     * Percentile dice (d% or d100) with values 1-100.
     */
    case PERCENTILE = 'percentile';
}
