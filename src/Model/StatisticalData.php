<?php

declare(strict_types=1);

namespace PHPDice\Model;

/**
 * Provides pre-calculated probability information for a dice expression.
 */
class StatisticalData
{
    /**
     * Create a new statistical data instance.
     *
     * @param int|float $minimum Minimum possible result
     * @param int|float $maximum Maximum possible result
     * @param float $expected Expected value (mean)
     * @param float|null $variance Variance (optional)
     * @param float|null $standardDeviation Standard deviation (optional)
     */
    public function __construct(
        public readonly int|float $minimum,
        public readonly int|float $maximum,
        public readonly float $expected,
        public readonly ?float $variance = null,
        public readonly ?float $standardDeviation = null
    ) {
    }
}
