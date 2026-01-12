<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Model;

use Codryn\PHPDice\Parser\AST\BinaryOpNode;
use Codryn\PHPDice\Parser\AST\ConditionalNode;
use Codryn\PHPDice\Parser\AST\DiceExpressionNode;
use Codryn\PHPDice\Parser\AST\DiceNode;
use Codryn\PHPDice\Parser\AST\FunctionNode;
use Codryn\PHPDice\Parser\AST\GroupNode;
use Codryn\PHPDice\Parser\AST\Node;
use Codryn\PHPDice\Parser\AST\NumberNode;

/**
 * Calculates statistical properties of dice expressions.
 */
class StatisticalCalculator
{
    /**
     * Calculate statistics for a dice specification.
     *
     * @param DiceSpecification $spec Dice specification
     * @param RollModifiers $modifiers Roll modifiers
     * @param Node|null $ast Optional AST for complex expressions
     * @return StatisticalData Statistical data
     */
    public function calculate(DiceSpecification $spec, RollModifiers $modifiers, ?Node $ast = null): StatisticalData
    {
        // Handle success counting mode
        if ($modifiers->successOperator !== null) {
            return $this->calculateSuccessCount($spec, $modifiers);
        }

        // Handle explosion mechanics (must check before edge and rerolls)
        if ($modifiers->explosionThreshold !== null && $modifiers->explosionOperator !== null) {
            return $this->calculateWithExplosions($spec, $modifiers, $ast);
        }

        // Handle edge mechanics (must check before rerolls)
        if ($modifiers->edgeThreshold !== null && $modifiers->edgeOperator !== null) {
            return $this->calculateWithEdge($spec, $modifiers, $ast);
        }

        // Handle reroll mechanics
        if ($modifiers->rerollThreshold !== null && $modifiers->rerollOperator !== null) {
            return $this->calculateWithRerolls($spec, $modifiers, $ast);
        }

        // Calculate base statistics
        $baseStats = $ast !== null
            ? $this->calculateFromAstWithSpec($ast, $spec, $modifiers)
            : $this->calculateBasicDice($spec);

        // Apply keep modifiers if present
        if ($modifiers->keepHighest !== null || $modifiers->keepLowest !== null) {
            // Determine total dice to roll
            $totalDice = $spec->count;
            if ($modifiers->advantageCount !== null) {
                $totalDice += $modifiers->advantageCount;
            }
            $sides = $spec->sides;

            if ($modifiers->keepHighest !== null) {
                $minimum = $modifiers->keepHighest * 1;
                $maximum = $modifiers->keepHighest * $sides;
                $expected = $this->calculateKeepHighestExpected($sides, $totalDice, $modifiers->keepHighest);
            } else { // keepLowest
                assert($modifiers->keepLowest !== null, 'keepLowest must not be null when keepHighest is null');
                $minimum = $modifiers->keepLowest * 1;
                $maximum = $modifiers->keepLowest * $sides;
                $expected = $this->calculateKeepLowestExpected($sides, $totalDice, $modifiers->keepLowest);
            }

            // If there's an AST, we need to adjust for the arithmetic operations
            if ($ast !== null) {
                // For expressions like "1d20 advantage + 5", we need to apply the arithmetic to the keep stats
                $keepStats = new StatisticalData($minimum, $maximum, round($expected, 3), null, null);
                return $this->applyAstOperations($ast, $keepStats);
            }

            return new StatisticalData($minimum, $maximum, round($expected, 3), null, null);
        }

        // Apply arithmetic modifier if no AST
        if ($ast === null) {
            $minimum = $baseStats->minimum + $modifiers->arithmeticModifier;
            $maximum = $baseStats->maximum + $modifiers->arithmeticModifier;
            $expected = $baseStats->expected + $modifiers->arithmeticModifier;
            // Variance and standard deviation are unaffected by adding a constant
            return new StatisticalData($minimum, $maximum, round($expected, 3), $baseStats->variance, $baseStats->standardDeviation);
        }

        return $baseStats;
    }

    /**
     * Calculate statistics from AST for math-only expressions (no dice).
     *
     * @param Node $ast AST node
     * @return StatisticalData Statistical data
     */
    public function calculateFromAst(Node $ast): StatisticalData
    {
        return $this->calculateFromAstInternal($ast);
    }

    /**
     * Calculate success count statistics.
     *
     * @param DiceSpecification $spec Dice specification
     * @param RollModifiers $modifiers Roll modifiers with success threshold
     * @return StatisticalData Success count statistics
     */
    private function calculateSuccessCount(DiceSpecification $spec, RollModifiers $modifiers): StatisticalData
    {
        $threshold = $modifiers->successThreshold;
        $operator = $modifiers->successOperator;
        $count = $spec->count;

        // Determine value range based on dice type
        if ($spec->type === DiceType::FUDGE) {
            // Fudge dice have values: -1, 0, +1
            $minValue = -1;
            $maxValue = 1;
            $totalValues = 3;
        } elseif ($spec->type === DiceType::COIN) {
            // Coin dice have values: 0, 1
            $minValue = 0;
            $maxValue = 1;
            $totalValues = 2;
        } else {
            // Standard and percentile dice: 1 to sides
            $minValue = 1;
            $maxValue = $spec->sides;
            $totalValues = $spec->sides;
        }

        // Calculate probability of success for a single die
        $successValues = 0;
        for ($value = $minValue; $value <= $maxValue; $value++) {
            $matches = match ($operator) {
                'even' => $value % 2 === 0,
                'odd' => $value % 2 !== 0,
                '>=' => $threshold !== null && $value >= $threshold,
                '>' => $threshold !== null && $value > $threshold,
                '<=' => $threshold !== null && $value <= $threshold,
                '<' => $threshold !== null && $value < $threshold,
                '==' => $threshold !== null && $value === $threshold,
                default => false,
            };
            if ($matches) {
                $successValues++;
            }
        }

        $probabilityPerDie = $successValues / $totalValues;

        // Minimum successes: 0 (all dice fail)
        $minimum = 0;

        // Maximum successes: all dice succeed
        $maximum = $count;

        // Expected successes: count * probability
        $expected = $count * $probabilityPerDie;

        // Variance for binomial distribution: n * p * (1 - p)
        $variance = $count * $probabilityPerDie * (1 - $probabilityPerDie);
        $standardDeviation = sqrt($variance);

        return new StatisticalData($minimum, $maximum, round($expected, 3), round($variance, 3), round($standardDeviation, 3));
    }

    /**
     * Calculate statistics for basic dice without modifiers.
     *
     * @param DiceSpecification $spec Dice specification
     * @return StatisticalData Statistical data
     */
    private function calculateBasicDice(DiceSpecification $spec): StatisticalData
    {
        // Handle fudge dice (dF) - values are -1, 0, +1 (FR-007)
        if ($spec->type === DiceType::FUDGE) {
            $minPerDie = -1;
            $maxPerDie = 1;
            $expectedPerDie = 0; // Equal probability of -1, 0, +1

            $minimum = $spec->count * $minPerDie;
            $maximum = $spec->count * $maxPerDie;
            $expected = $spec->count * $expectedPerDie;

            // Variance for fudge dice: E[X^2] - E[X]^2 = ((-1)^2 + 0^2 + 1^2)/3 - 0 = 2/3
            $variancePerDie = 2.0 / 3.0;
            $variance = $spec->count * $variancePerDie;
            $standardDeviation = sqrt($variance);

            return new StatisticalData($minimum, $maximum, round($expected, 3), round($variance, 3), round($standardDeviation, 3));
        }

        // Handle coin dice (C) - values are 0, 1
        if ($spec->type === DiceType::COIN) {
            $minPerDie = 0;
            $maxPerDie = 1;
            $expectedPerDie = 0.5; // Equal probability of 0, 1

            $minimum = $spec->count * $minPerDie;
            $maximum = $spec->count * $maxPerDie;
            $expected = $spec->count * $expectedPerDie;

            // Variance for coin flip: E[X^2] - E[X]^2 = (0^2 + 1^2)/2 - 0.5^2 = 0.5 - 0.25 = 0.25
            $variancePerDie = 0.25;
            $variance = $spec->count * $variancePerDie;
            $standardDeviation = sqrt($variance);

            return new StatisticalData($minimum, $maximum, round($expected, 3), round($variance, 3), round($standardDeviation, 3));
        }

        // Standard and percentile dice work the same way for statistics
        $minPerDie = 1;
        $maxPerDie = $spec->sides;
        $expectedPerDie = ($minPerDie + $maxPerDie) / 2;

        $minimum = $spec->count * $minPerDie;
        $maximum = $spec->count * $maxPerDie;
        $expected = $spec->count * $expectedPerDie;

        // Variance for uniform distribution: (n^2 - 1) / 12 where n is the number of sides
        $variancePerDie = ($spec->sides * $spec->sides - 1) / 12.0;
        $variance = $spec->count * $variancePerDie;
        $standardDeviation = sqrt($variance);

        return new StatisticalData($minimum, $maximum, round($expected, 3), round($variance, 3), round($standardDeviation, 3));
    }

    /**
     * Calculate statistics with reroll mechanics.
     *
     * @param DiceSpecification $spec Dice specification
     * @param RollModifiers $modifiers Roll modifiers with reroll settings
     * @param Node|null $ast Optional AST for arithmetic
     * @return StatisticalData Statistics adjusted for rerolls
     */
    private function calculateWithRerolls(DiceSpecification $spec, RollModifiers $modifiers, ?Node $ast): StatisticalData
    {
        $sides = $spec->sides;
        $threshold = $modifiers->rerollThreshold;
        $operator = $modifiers->rerollOperator;

        assert($threshold !== null && $operator !== null, 'Reroll threshold and operator must not be null');

        // Determine which values trigger reroll
        $rerollValues = [];
        for ($value = 1; $value <= $sides; $value++) {
            if ($this->shouldReroll($value, $threshold, $operator)) {
                $rerollValues[] = $value;
            }
        }

        // Calculate minimum die value (smallest non-reroll value)
        $minDieValue = $sides + 1; // Start with impossible value
        for ($value = 1; $value <= $sides; $value++) {
            if (!in_array($value, $rerollValues, true)) {
                $minDieValue = min($minDieValue, $value);
            }
        }

        // Calculate maximum die value (largest non-reroll value)
        $maxDieValue = 0;
        for ($value = 1; $value <= $sides; $value++) {
            if (!in_array($value, $rerollValues, true)) {
                $maxDieValue = max($maxDieValue, $value);
            }
        }

        // Expected value per die with rerolls (simplified approximation)
        // In reality this is complex, but we approximate based on non-reroll values
        $nonRerollCount = $sides - count($rerollValues);
        $nonRerollSum = 0;
        for ($value = 1; $value <= $sides; $value++) {
            if (!in_array($value, $rerollValues, true)) {
                $nonRerollSum += $value;
            }
        }
        $expectedPerDie = $nonRerollCount > 0 ? $nonRerollSum / $nonRerollCount : 0;

        $minimum = $spec->count * $minDieValue;
        $maximum = $spec->count * $maxDieValue;
        $expected = $spec->count * $expectedPerDie;

        // Apply arithmetic if AST exists
        if ($ast !== null) {
            $rerollStats = new StatisticalData($minimum, $maximum, round($expected, 3), null, null);
            return $this->applyAstOperations($ast, $rerollStats);
        }

        return new StatisticalData($minimum, $maximum, round($expected, 3), null, null);
    }

    /**
     * Check if a value should trigger reroll.
     *
     * @param int $value Die value
     * @param int $threshold Reroll threshold
     * @param string $operator Comparison operator
     * @return bool True if should reroll
     */
    private function shouldReroll(int $value, int $threshold, string $operator): bool
    {
        return match ($operator) {
            '<=' => $value <= $threshold,
            '<' => $value < $threshold,
            '>=' => $value >= $threshold,
            '>' => $value > $threshold,
            '==' => $value === $threshold,
            default => false,
        };
    }

    /**
     * Calculate statistics for dice with explosion mechanics
     * Explosions add unpredictable values, so we use approximation.
     *
     * @param DiceSpecification $spec Dice specification
     * @param RollModifiers $modifiers Roll modifiers with explosion settings
     * @param Node|null $ast Optional AST for arithmetic
     * @return StatisticalData Statistics adjusted for explosions
     */
    private function calculateWithExplosions(DiceSpecification $spec, RollModifiers $modifiers, ?Node $ast): StatisticalData
    {
        $sides = $spec->sides;
        $threshold = $modifiers->explosionThreshold;
        $operator = $modifiers->explosionOperator;

        assert($threshold !== null && $operator !== null, 'Explosion threshold and operator must not be null');

        // Determine which values trigger explosion
        $explosionValues = [];
        for ($value = 1; $value <= $sides; $value++) {
            if ($this->shouldExplode($value, $threshold, $operator)) {
                $explosionValues[] = $value;
            }
        }

        // Probability of explosion
        $explosionProb = count($explosionValues) / $sides;

        // Expected number of explosions per die (geometric series)
        // E[explosions] = p / (1 - p) where p = probability of explosion
        // But capped at explosion limit
        $avgExplosionsPerDie = $explosionProb > 0 && $explosionProb < 1
            ? min($modifiers->explosionLimit, $explosionProb / (1 - $explosionProb))
            : 0;

        // Expected value per die with explosions
        // Base expected value + expected explosions * average roll value
        $baseExpected = ($sides + 1) / 2;
        $expectedPerDie = $baseExpected * (1 + $avgExplosionsPerDie);

        // Minimum: no explosions
        $minimum = $spec->count * 1;

        // Maximum: all dice explode to limit, all rolls are maximum
        $maxExplosionsPerDie = $modifiers->explosionLimit;
        $maximum = $spec->count * $sides * (1 + $maxExplosionsPerDie);

        $expected = $spec->count * $expectedPerDie;

        // Apply arithmetic if AST exists
        if ($ast !== null) {
            $explosionStats = new StatisticalData($minimum, $maximum, round($expected, 3), null, null);
            return $this->applyAstOperations($ast, $explosionStats);
        }

        return new StatisticalData($minimum, $maximum, round($expected, 3), null, null);
    }

    /**
     * Check if a value should trigger explosion.
     *
     * @param int $value Die value
     * @param int $threshold Explosion threshold
     * @param string $operator Comparison operator
     * @return bool True if should explode
     */
    private function shouldExplode(int $value, int $threshold, string $operator): bool
    {
        return match ($operator) {
            '>=' => $value >= $threshold,
            '<=' => $value <= $threshold,
            default => false,
        };
    }

    /**
     * Calculate statistics for dice with edge mechanics (Shadowrun Rule of Six).
     * Edge adds additional dice rather than summing into one die.
     *
     * @param DiceSpecification $spec Dice specification
     * @param RollModifiers $modifiers Roll modifiers with edge settings
     * @param Node|null $ast Optional AST for arithmetic
     * @return StatisticalData Statistics adjusted for edge
     */
    private function calculateWithEdge(DiceSpecification $spec, RollModifiers $modifiers, ?Node $ast): StatisticalData
    {
        $sides = $spec->sides;
        $threshold = $modifiers->edgeThreshold;
        $operator = $modifiers->edgeOperator;

        assert($threshold !== null && $operator !== null, 'Edge threshold and operator must not be null');

        // Determine which values trigger edge
        $edgeValues = [];
        for ($value = 1; $value <= $sides; $value++) {
            if ($this->shouldEdge($value, $threshold, $operator)) {
                $edgeValues[] = $value;
            }
        }

        // Probability of edge
        $edgeProb = count($edgeValues) / $sides;

        // Expected number of additional dice per die (geometric series)
        // E[edge_dice] = p + p^2 + p^3 + ... = p / (1 - p) where p = probability of edge
        // But capped at edge limit
        $avgEdgeDicePerDie = $edgeProb > 0 && $edgeProb < 1
            ? min($modifiers->edgeLimit, $edgeProb / (1 - $edgeProb))
            : 0;

        // Expected total dice = original dice + expected edge dice
        $expectedTotalDice = $spec->count * (1 + $avgEdgeDicePerDie);

        // Average value per die
        $avgValuePerDie = ($sides + 1) / 2;

        // Minimum: no edge triggers (just original dice, all roll 1)
        $minimum = $spec->count * 1;

        // Maximum: all dice trigger edge to limit, all rolls are maximum
        $maxEdgeDicePerDie = $modifiers->edgeLimit;
        $maxTotalDice = $spec->count * (1 + $maxEdgeDicePerDie);
        $maximum = $maxTotalDice * $sides;

        // Expected: expected total dice * average value per die
        $expected = $expectedTotalDice * $avgValuePerDie;

        // Apply arithmetic if AST exists
        if ($ast !== null) {
            $edgeStats = new StatisticalData($minimum, $maximum, round($expected, 3));
            return $this->applyAstOperations($ast, $edgeStats);
        }

        return new StatisticalData($minimum, $maximum, round($expected, 3));
    }

    /**
     * Check if a value should trigger edge.
     *
     * @param int $value Die value
     * @param int $threshold Edge threshold
     * @param string $operator Comparison operator
     * @return bool True if should trigger edge
     */
    private function shouldEdge(int $value, int $threshold, string $operator): bool
    {
        return match ($operator) {
            '>=' => $value >= $threshold,
            '<=' => $value <= $threshold,
            default => false,
        };
    }

    /**
     * Apply AST operations to keep statistics
     * For "1d20 advantage + 5", replaces the dice node value with keep stats.
     *
     * @param Node $node AST node
     * @param StatisticalData $diceStats Statistics for the dice after keep
     * @return StatisticalData Final statistics
     */
    private function applyAstOperations(Node $node, StatisticalData $diceStats): StatisticalData
    {
        if ($node instanceof DiceNode) {
            return $diceStats;
        }

        if ($node instanceof NumberNode) {
            $value = $node->getValue();
            // Constants have zero variance
            return new StatisticalData($value, $value, (float)$value, 0.0, 0.0);
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->applyAstOperations($node->getLeft(), $diceStats);
            $right = $this->applyAstOperations($node->getRight(), $diceStats);

            return match ($node->getOperator()) {
                '+' => new StatisticalData(
                    $left->minimum + $right->minimum,
                    $left->maximum + $right->maximum,
                    round($left->expected + $right->expected, 3),
                    // Var(X + Y) = Var(X) + Var(Y) for independent variables
                    ...($this->calculateCombinedVariance($left->variance, $right->variance))
                ),
                '-' => new StatisticalData(
                    $left->minimum - $right->maximum,
                    $left->maximum - $right->minimum,
                    round($left->expected - $right->expected, 3),
                    // Var(X - Y) = Var(X) + Var(Y) for independent variables
                    ...($this->calculateCombinedVariance($left->variance, $right->variance))
                ),
                '*' => new StatisticalData(
                    min(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    max(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    round($left->expected * $right->expected, 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                '/' => new StatisticalData(
                    $left->minimum / max($right->maximum, 1),
                    $left->maximum / max($right->minimum, 1),
                    round($left->expected / max($right->expected, 1), 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                default => new StatisticalData(0, 0, 0.0, null, null),
            };
        }

        if ($node instanceof FunctionNode) {
            $arg = $this->applyAstOperations($node->getArgument(), $diceStats);

            return match (strtolower($node->getName())) {
                'floor' => new StatisticalData(
                    floor($arg->minimum),
                    floor($arg->maximum),
                    round(floor($arg->expected), 3),
                    null, // Variance calculation complex for floor
                    null
                ),
                'ceil' => new StatisticalData(
                    ceil($arg->minimum),
                    ceil($arg->maximum),
                    round(ceil($arg->expected), 3),
                    null, // Variance calculation complex for ceil
                    null
                ),
                'round' => new StatisticalData(
                    round($arg->minimum),
                    round($arg->maximum),
                    round($arg->expected, 3),
                    null, // Variance calculation complex for round
                    null
                ),
                default => $arg,
            };
        }

        return $diceStats;
    }

    /**
     * Calculate expected value for keeping highest N dice from M rolls.
     *
     * @param int $sides Die sides
     * @param int $totalDice Total dice rolled
     * @param int $keepCount Number to keep
     * @return float Expected value
     */
    private function calculateKeepHighestExpected(int $sides, int $totalDice, int $keepCount): float
    {
        // For d20 advantage (2d20 keep 1 highest): expected ≈ 13.825
        // General formula uses order statistics, but we approximate
        // E[kth highest of n dice] ≈ (sides + 1) * (n - k + 1) / (n + 1)

        $expected = 0.0;
        for ($k = 1; $k <= $keepCount; $k++) {
            // E[kth highest] ≈ (sides + 1) * (totalDice - k + 1) / (totalDice + 1)
            $expected += ($sides + 1) * ($totalDice - $k + 1) / ($totalDice + 1);
        }

        return $expected;
    }

    /**
     * Calculate expected value for keeping lowest N dice from M rolls.
     *
     * @param int $sides Die sides
     * @param int $totalDice Total dice rolled
     * @param int $keepCount Number to keep
     * @return float Expected value
     */
    private function calculateKeepLowestExpected(int $sides, int $totalDice, int $keepCount): float
    {
        // For d20 disadvantage (2d20 keep 1 lowest): expected ≈ 7.175
        // E[kth lowest of n dice] ≈ (sides + 1) * k / (n + 1)

        $expected = 0.0;
        for ($k = 1; $k <= $keepCount; $k++) {
            $expected += ($sides + 1) * $k / ($totalDice + 1);
        }

        return $expected;
    }

    /**
     * Calculate statistics from AST (internal method with dice spec).
     *
     * @param Node $node AST node
     * @param DiceSpecification $spec Dice specification for dice nodes
     * @param RollModifiers $modifiers Roll modifiers
     * @return StatisticalData Statistical data
     */
    private function calculateFromAstWithSpec(Node $node, DiceSpecification $spec, RollModifiers $modifiers): StatisticalData
    {
        if ($node instanceof NumberNode) {
            $value = $node->getValue();
            return new StatisticalData($value, $value, (float)$value, 0.0, 0.0);
        }

        if ($node instanceof DiceNode) {
            // Check for special dice types (FR-007: Fudge dice)
            if ($node->getType() === DiceType::FUDGE) {
                $minPerDie = -1;
                $maxPerDie = 1;
                $expectedPerDie = 0;
                $variancePerDie = 2.0 / 3.0;
            } elseif ($node->getType() === DiceType::COIN) {
                // Coin dice have values: 0, 1
                $minPerDie = 0;
                $maxPerDie = 1;
                $expectedPerDie = 0.5;
                $variancePerDie = 0.25; // (0^2 + 1^2)/2 - 0.5^2 = 0.5 - 0.25 = 0.25
            } else {
                // Standard and percentile dice
                $minPerDie = 1;
                $maxPerDie = $node->getSides();
                $expectedPerDie = ($minPerDie + $maxPerDie) / 2;
                $variancePerDie = ($node->getSides() * $node->getSides() - 1) / 12.0;
            }

            $min = $node->getCount() * $minPerDie;
            $max = $node->getCount() * $maxPerDie;
            $expected = $node->getCount() * $expectedPerDie;
            $variance = $node->getCount() * $variancePerDie;
            $standardDeviation = sqrt($variance);

            return new StatisticalData($min, $max, round($expected, 3), round($variance, 3), round($standardDeviation, 3));
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->calculateFromAstWithSpec($node->getLeft(), $spec, $modifiers);
            $right = $this->calculateFromAstWithSpec($node->getRight(), $spec, $modifiers);

            return match ($node->getOperator()) {
                '+' => new StatisticalData(
                    $left->minimum + $right->minimum,
                    $left->maximum + $right->maximum,
                    round($left->expected + $right->expected, 3),
                    ...($this->calculateCombinedVariance($left->variance, $right->variance))
                ),
                '-' => new StatisticalData(
                    $left->minimum - $right->maximum,
                    $left->maximum - $right->minimum,
                    round($left->expected - $right->expected, 3),
                    ...($this->calculateCombinedVariance($left->variance, $right->variance))
                ),
                '*' => new StatisticalData(
                    min(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    max(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    round($left->expected * $right->expected, 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                '/' => new StatisticalData(
                    $left->minimum / max($right->maximum, 1),
                    $left->maximum / max($right->minimum, 1),
                    round($left->expected / max($right->expected, 1), 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                '%' => new StatisticalData(
                    // When both operands are constants (min == max), compute exact result
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum
                        ? $left->minimum % max($right->minimum, 1)
                        : 0,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum
                        ? $left->maximum % max($right->maximum, 1)
                        : max($right->maximum - 1, 0),
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum
                        ? (float)($left->expected % max($right->expected, 1))
                        : round(($right->maximum - 1) / 2, 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                '^' => new StatisticalData(
                    min(
                        pow($left->minimum, $right->minimum),
                        pow($left->minimum, $right->maximum),
                        pow($left->maximum, $right->minimum),
                        pow($left->maximum, $right->maximum)
                    ),
                    max(
                        pow($left->minimum, $right->minimum),
                        pow($left->minimum, $right->maximum),
                        pow($left->maximum, $right->minimum),
                        pow($left->maximum, $right->maximum)
                    ),
                    round(pow($left->expected, $right->expected), 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                default => new StatisticalData(0, 0, 0.0, null, null),
            };
        }

        if ($node instanceof FunctionNode) {
            $arg = $this->calculateFromAstWithSpec($node->getArgument(), $spec, $modifiers);

            return match (strtolower($node->getName())) {
                'floor' => new StatisticalData(
                    floor($arg->minimum),
                    floor($arg->maximum),
                    round(floor($arg->expected), 3),
                    // Variance: 0 for constants, null for variable cases
                    $arg->minimum === $arg->maximum ? 0.0 : null,
                    $arg->minimum === $arg->maximum ? 0.0 : null
                ),
                'ceil' => new StatisticalData(
                    ceil($arg->minimum),
                    ceil($arg->maximum),
                    round(ceil($arg->expected), 3),
                    null, // Complex to compute
                    null
                ),
                'round' => new StatisticalData(
                    round($arg->minimum),
                    round($arg->maximum),
                    round($arg->expected, 3),
                    null, // Complex to compute
                    null
                ),
                'abs' => new StatisticalData(
                    abs($arg->minimum),
                    abs($arg->maximum),
                    round(abs($arg->expected), 3),
                    null, // Complex to compute
                    null
                ),
                default => $arg,
            };
        }

        return new StatisticalData(0, 0, 0.0, null, null);
    }

    /**
     * Calculate statistics from AST (internal method for math-only expressions).
     *
     * @param Node $node AST node
     * @return StatisticalData Statistical data
     */
    private function calculateFromAstInternal(Node $node): StatisticalData
    {
        if ($node instanceof NumberNode) {
            $value = $node->getValue();
            return new StatisticalData($value, $value, (float)$value, 0.0, 0.0);
        }

        if ($node instanceof DiceNode) {
            // Handle dice nodes - calculate basic statistics
            $spec = new DiceSpecification(
                count: $node->getCount(),
                sides: $node->getSides(),
                type: $node->getType()
            );
            return $this->calculateBasicDice($spec);
        }

        if ($node instanceof DiceExpressionNode) {
            // Handle dice expression nodes (dice with modifiers)
            $spec = $node->getSpecification();
            $modifiers = $node->getModifiers();
            $diceNode = $node->getDiceNode();
            return $this->calculate($spec, $modifiers, $diceNode);
        }

        if ($node instanceof GroupNode) {
            // Handle group nodes - recursively calculate statistics for the group's expression
            return $this->calculateFromAstInternal($node->getExpression());
        }

        if ($node instanceof ConditionalNode) {
            // For conditionals, we need to calculate statistics for both branches
            // and return a range that covers both possibilities
            $trueBranchStats = $this->calculateFromAstInternal($node->getTrueBranch());
            $falseBranchStats = $this->calculateFromAstInternal($node->getFalseBranch());

            // The minimum is the smaller of the two minimums
            $minimum = min($trueBranchStats->minimum, $falseBranchStats->minimum);
            // The maximum is the larger of the two maximums
            $maximum = max($trueBranchStats->maximum, $falseBranchStats->maximum);

            // The expected value assumes 50/50 probability for the condition
            // This is a simplification since we don't evaluate the condition at parse time.
            // A more accurate calculation would require evaluating the condition with
            // the specific variable values, which is only available at roll time.
            $expected = ($trueBranchStats->expected + $falseBranchStats->expected) / 2;

            // Variance and standard deviation are complex to calculate for conditionals
            // as they depend on the condition probability, so we set them to null
            return new StatisticalData($minimum, $maximum, round($expected, 3), null, null);
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->calculateFromAstInternal($node->getLeft());
            $right = $this->calculateFromAstInternal($node->getRight());

            return match ($node->getOperator()) {
                '+' => new StatisticalData(
                    $left->minimum + $right->minimum,
                    $left->maximum + $right->maximum,
                    round($left->expected + $right->expected, 3),
                    ...($this->calculateCombinedVariance($left->variance, $right->variance))
                ),
                '-' => new StatisticalData(
                    $left->minimum - $right->maximum,
                    $left->maximum - $right->minimum,
                    round($left->expected - $right->expected, 3),
                    ...($this->calculateCombinedVariance($left->variance, $right->variance))
                ),
                '*' => new StatisticalData(
                    min(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    max(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    round($left->expected * $right->expected, 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                '/' => new StatisticalData(
                    $left->minimum / max($right->maximum, 1),
                    $left->maximum / max($right->minimum, 1),
                    round($left->expected / max($right->expected, 1), 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                '%' => new StatisticalData(
                    // When both operands are constants (min == max), compute exact result
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum
                        ? $left->minimum % max($right->minimum, 1)
                        : 0,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum
                        ? $left->maximum % max($right->maximum, 1)
                        : max($right->maximum - 1, 0),
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum
                        ? (float)($left->expected % max($right->expected, 1))
                        : round(($right->maximum - 1) / 2, 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                '^' => new StatisticalData(
                    min(
                        pow($left->minimum, $right->minimum),
                        pow($left->minimum, $right->maximum),
                        pow($left->maximum, $right->minimum),
                        pow($left->maximum, $right->maximum)
                    ),
                    max(
                        pow($left->minimum, $right->minimum),
                        pow($left->minimum, $right->maximum),
                        pow($left->maximum, $right->minimum),
                        pow($left->maximum, $right->maximum)
                    ),
                    round(pow($left->expected, $right->expected), 3),
                    // Variance: 0 for constants, null for variable cases
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null,
                    $left->minimum === $left->maximum && $right->minimum === $right->maximum ? 0.0 : null
                ),
                default => new StatisticalData(0, 0, 0.0, null, null),
            };
        }

        if ($node instanceof FunctionNode) {
            // Check if it's a multi-argument function
            $lowerName = strtolower($node->getName());
            if (in_array($lowerName, ['min', 'max'], true)) {
                // Handle multiple arguments
                $argStats = array_map(fn ($arg) => $this->calculateFromAstInternal($arg), $node->getArguments());

                if ($argStats === []) {
                    return new StatisticalData(0, 0, 0.0, null, null);
                }

                $minimums = array_map(fn ($s) => $s->minimum, $argStats);
                $maximums = array_map(fn ($s) => $s->maximum, $argStats);
                $expecteds = array_map(fn ($s) => $s->expected, $argStats);

                return match ($lowerName) {
                    'min' => new StatisticalData(
                        min($minimums),
                        min($maximums),
                        round(min($expecteds), 3),
                        null, // Complex to compute
                        null
                    ),
                    'max' => new StatisticalData(
                        max($minimums),
                        max($maximums),
                        round(max($expecteds), 3),
                        null, // Complex to compute
                        null
                    ),
                };
            }

            // Single argument functions
            $arg = $this->calculateFromAstInternal($node->getArgument());

            return match ($lowerName) {
                'floor' => new StatisticalData(
                    floor($arg->minimum),
                    floor($arg->maximum),
                    round(floor($arg->expected), 3),
                    null, // Complex to compute
                    null
                ),
                'ceil' => new StatisticalData(
                    ceil($arg->minimum),
                    ceil($arg->maximum),
                    round(ceil($arg->expected), 3),
                    null, // Complex to compute
                    null
                ),
                'round' => new StatisticalData(
                    round($arg->minimum),
                    round($arg->maximum),
                    round($arg->expected, 3),
                    null, // Complex to compute
                    null
                ),
                'abs' => new StatisticalData(
                    abs($arg->minimum),
                    abs($arg->maximum),
                    round(abs($arg->expected), 3),
                    null, // Complex to compute
                    null
                ),
                default => $arg,
            };
        }

        return new StatisticalData(0, 0, 0.0, null, null);
    }

    /**
     * Calculate combined variance and standard deviation for addition/subtraction.
     * For independent random variables: Var(X ± Y) = Var(X) + Var(Y).
     *
     * @param float|null $leftVariance Left operand variance
     * @param float|null $rightVariance Right operand variance
     * @return array{0: float|null, 1: float|null} [variance, standardDeviation]
     */
    private function calculateCombinedVariance(?float $leftVariance, ?float $rightVariance): array
    {
        if ($leftVariance !== null && $rightVariance !== null) {
            $variance = $leftVariance + $rightVariance;
            return [round($variance, 3), round(sqrt($variance), 3)];
        }
        return [null, null];
    }
}
