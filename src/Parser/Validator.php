<?php

declare(strict_types=1);

namespace PHPDice\Parser;

use PHPDice\Exception\ValidationException;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\RollModifiers;

/**
 * Validates dice expressions and their components.
 */
class Validator
{
    /**
     * Validate a dice specification.
     *
     * @param DiceSpecification $spec Dice specification to validate
     * @throws ValidationException If validation fails
     */
    public function validateDiceSpecification(DiceSpecification $spec): void
    {
        // FR-027: Dice count must be >= 1
        if ($spec->count < 1) {
            throw new ValidationException(
                "Dice count must be at least 1, got {$spec->count}",
                'count'
            );
        }

        // FR-028: Sides must be >= 2 (updated from original spec)
        if ($spec->sides < 2) {
            throw new ValidationException(
                "Dice must have at least 2 sides, got {$spec->sides}",
                'sides'
            );
        }

        // FR-029: Maximum 100 dice total
        if ($spec->count > 100) {
            throw new ValidationException(
                "Cannot roll more than 100 dice, got {$spec->count}",
                'count'
            );
        }

        // FR-030: Maximum 100 sides per die
        if ($spec->sides > 100) {
            throw new ValidationException(
                "Dice cannot have more than 100 sides, got {$spec->sides}",
                'sides'
            );
        }
    }

    /**
     * Validate that an expression is not malformed.
     *
     * @param string $expression Expression to validate
     * @throws ValidationException If expression is invalid
     */
    public function validateExpression(string $expression): void
    {
        // FR-026: Reject invalid syntax
        $trimmed = trim($expression);

        if (empty($trimmed)) {
            throw new ValidationException('Expression cannot be empty', 'expression');
        }
    }

    /**
     * Validate parenthesis matching (FR-033).
     *
     * @param string $expression Expression to validate
     * @throws ValidationException If parentheses don't match
     */
    public function validateParentheses(string $expression): void
    {
        $count = 0;

        for ($i = 0; $i < strlen($expression); $i++) {
            if ($expression[$i] === '(') {
                $count++;
            } elseif ($expression[$i] === ')') {
                $count--;
                if ($count < 0) {
                    throw new ValidationException('Unmatched closing parenthesis', 'parentheses');
                }
            }
        }

        if ($count > 0) {
            throw new ValidationException('Unmatched opening parenthesis', 'parentheses');
        }
    }

    /**
     * Validate modifiers don't conflict (FR-034).
     *
     * @param RollModifiers $modifiers Modifiers to validate
     * @throws ValidationException If modifiers conflict
     */
    public function validateModifiers(RollModifiers $modifiers): void
    {
        // FR-034: Cannot have both keepHighest and keepLowest
        if ($modifiers->keepHighest !== null && $modifiers->keepLowest !== null) {
            throw new ValidationException(
                'Cannot have both keep highest and keep lowest',
                'modifiers'
            );
        }
    }

    /**
     * Validate reroll range doesn't cover entire die (FR-005b).
     *
     * @param DiceSpecification $spec Dice specification
     * @param int $threshold Reroll threshold
     * @param string $operator Reroll operator
     * @throws ValidationException If reroll would always trigger
     */
    public function validateRerollRange(DiceSpecification $spec, int $threshold, string $operator): void
    {
        $minValue = 1;
        $maxValue = $spec->sides;

        // Check if reroll condition covers all possible values
        $coversAll = false;

        switch ($operator) {
            case '<=':
                $coversAll = $threshold >= $maxValue;
                break;
            case '<':
                $coversAll = $threshold > $maxValue;
                break;
            case '>=':
                $coversAll = $threshold <= $minValue;
                break;
            case '>':
                $coversAll = $threshold < $minValue;
                break;
            case '==':
                // Equality never covers all values (at most one value)
                $coversAll = false;
                break;
        }

        if ($coversAll) {
            throw new ValidationException(
                "Reroll condition '{$operator} {$threshold}' would trigger on all possible die values (1-{$maxValue}), preventing termination",
                'reroll'
            );
        }
    }

    /**
     * Validate explosion range doesn't cover entire die (FR-038c).
     *
     * @param DiceSpecification $spec Dice specification
     * @param int $threshold Explosion threshold
     * @param string $operator Explosion operator
     * @throws ValidationException If explosion would always trigger
     */
    public function validateExplosionRange(DiceSpecification $spec, int $threshold, string $operator): void
    {
        $minValue = 1;
        $maxValue = $spec->sides;

        if ($threshold < 0) {
            throw new ValidationException(
                "Explosion treshold '{$threshold}' is negative",
                'explode'
            );
        }

        // Check if explosion condition covers all possible values
        $coversAll = false;
        switch ($operator) {
            case '>':
                $coversAll = $threshold < $minValue;
                break;
            case '>=':
                $coversAll = $threshold <= $minValue;
                break;
            case '<=':
                $coversAll = $threshold >= $maxValue;
                break;
            case '<':
                $coversAll = $threshold > $maxValue;
                break;
            case '==':
                // can never be true, dice has at least 2 distinct values
                $coversAll = false;
                break;
        }

        if ($coversAll) {
            throw new ValidationException(
                "Explosion condition '{$operator} {$threshold}' would trigger on all possible die values (1-{$maxValue}), preventing termination",
                'explode'
            );
        }
    }

    /**
     * Validate critical threshold is allowed and within die range (FR-035, FR-036).
     *
     * @param DiceSpecification $spec Dice specification
     * @param int $threshold Critical threshold value
     * @param string $type Type of critical ('success' or 'failure')
     * @throws ValidationException If threshold is outside die range
     */
    public function validateCriticalThreshold(DiceSpecification $spec, int $threshold, string $type): void
    {
        $minValue = 1;
        $maxValue = $spec->sides;

        if ($threshold < $minValue || $threshold > $maxValue) {
            $label = $type === 'success' ? 'Critical success' : 'Critical failure';
            throw new ValidationException(
                "{$label} threshold {$threshold} is outside die range ({$minValue}-{$maxValue})",
                'critical'
            );
        }
    }
}
