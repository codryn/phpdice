<?php

declare(strict_types=1);

namespace PHPDice\Roller;

/**
 * Random number generator abstraction using PHP's random_int().
 */
class RandomNumberGenerator
{
    /**
     * Generate a random integer between min and max (inclusive).
     *
     * @param int $min Minimum value (inclusive)
     * @param int $max Maximum value (inclusive)
     * @return int Random value between min and max
     */
    public function generate(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
