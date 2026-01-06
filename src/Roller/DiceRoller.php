<?php

declare(strict_types=1);

namespace PHPDice\Roller;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\RollResult;
use PHPDice\Parser\AST\BinaryOpNode;
use PHPDice\Parser\AST\DiceNode;
use PHPDice\Parser\AST\FunctionNode;
use PHPDice\Parser\AST\Node;

/**
 * Rolls dice based on parsed expressions.
 */
class DiceRoller
{
    public function __construct(
        private readonly RandomNumberGenerator $rng = new RandomNumberGenerator()
    ) {
    }

    /**
     * Roll dice based on an expression.
     *
     * @param DiceExpression $expression Parsed dice expression
     * @param Node|null $ast Optional AST for complex expressions
     * @return RollResult Roll result with values and total
     */
    public function roll(DiceExpression $expression, ?Node $ast = null): RollResult
    {
        // If we have an AST with multiple dice groups, handle them separately
        if ($ast !== null && $this->hasMultipleDiceGroups($ast)) {
            return $this->rollMultipleDiceGroups($ast, $expression);
        }

        $spec = $expression->specification;
        $modifiers = $expression->modifiers;
        $diceValues = [];

        // Determine total dice to roll (base + advantage)
        $totalDiceToRoll = $spec->count;
        if ($modifiers->advantageCount !== null) {
            $totalDiceToRoll += $modifiers->advantageCount;
        }

        // Roll each die
        $rerollHistory = null;
        $explosionHistory = null;
        $originalDiceValues = []; // Track original values before explosions for critical detection

        for ($i = 0; $i < $totalDiceToRoll; $i++) {
            // Generate raw roll based on dice type
            $rawRoll = $this->rng->generate(1, $spec->sides);

            // Convert for special dice types
            $initialRoll = $this->convertDiceValue($rawRoll, $spec->type);
            $diceValues[] = $initialRoll;

            // Handle rerolls if configured (rerolls happen first, then explosions)
            if ($modifiers->rerollThreshold !== null && $modifiers->rerollOperator !== null) {
                $rerollCount = 0;
                $currentValue = $initialRoll;
                $history = [$initialRoll];

                while ($this->shouldReroll($currentValue, $modifiers->rerollThreshold, $modifiers->rerollOperator)
                       && $rerollCount < $modifiers->rerollLimit) {
                    $rawReroll = $this->rng->generate(1, $spec->sides);
                    $currentValue = $this->convertDiceValue($rawReroll, $spec->type);
                    $history[] = $currentValue;
                    $rerollCount++;
                }

                // Update the die value to the final result
                $diceValues[$i] = $currentValue;

                // Track reroll history if any rerolls occurred
                if ($rerollCount > 0) {
                    if ($rerollHistory === null) {
                        $rerollHistory = [];
                    }
                    $rerollHistory[$i] = [
                        'rolls' => $history,
                        'count' => $rerollCount,
                        'limitReached' => $rerollCount >= $modifiers->rerollLimit,
                    ];
                }
            }

            // Store the original value before explosions (for critical detection)
            $originalDiceValues[$i] = $diceValues[$i];

            // Handle explosions if configured (FR-039: reroll and add when threshold met)
            if ($modifiers->explosionThreshold !== null && $modifiers->explosionOperator !== null) {
                $explosionCount = 0;
                $currentValue = $diceValues[$i];
                $cumulativeTotal = $currentValue;
                $explosions = [$currentValue];

                // Keep exploding while threshold is met and limit not reached
                while ($this->shouldExplode($currentValue, $modifiers->explosionThreshold, $modifiers->explosionOperator)
                       && $explosionCount < $modifiers->explosionLimit) {
                    $rawExplosion = $this->rng->generate(1, $spec->sides);
                    $currentValue = $this->convertDiceValue($rawExplosion, $spec->type);
                    $explosions[] = $currentValue;
                    $cumulativeTotal += $currentValue;
                    $explosionCount++;
                }

                // Update the die value to cumulative total
                $diceValues[$i] = $cumulativeTotal;

                // Track explosion history if any explosions occurred (FR-040)
                if ($explosionCount > 0) {
                    if ($explosionHistory === null) {
                        $explosionHistory = [];
                    }
                    $explosionHistory[$i] = [
                        'rolls' => $explosions,
                        'count' => $explosionCount,
                        'cumulativeTotal' => $cumulativeTotal,
                        'limitReached' => $explosionCount >= $modifiers->explosionLimit,
                    ];
                }
            }
        }

        // Handle keep highest/lowest
        $keptIndices = null;
        $discardedIndices = null;
        $finalValues = $diceValues;

        if ($modifiers->keepHighest !== null) {
            [$finalValues, $keptIndices, $discardedIndices] = $this->keepHighest($diceValues, $modifiers->keepHighest);
        } elseif ($modifiers->keepLowest !== null) {
            [$finalValues, $keptIndices, $discardedIndices] = $this->keepLowest($diceValues, $modifiers->keepLowest);
        }

        // Handle success counting mode
        $successCount = null;
        if ($modifiers->successThreshold !== null && $modifiers->successOperator !== null) {
            $successCount = $this->countSuccesses($finalValues, $modifiers->successThreshold, $modifiers->successOperator);
        }

        // Calculate total
        if ($modifiers->successThreshold !== null) {
            // In success counting mode, total = success count
            $total = $successCount ?? 0;
        } elseif ($ast !== null) {
            // Evaluate AST with dice results
            $this->setDiceResults($ast, array_sum($finalValues));
            $total = $ast->evaluate();
        } else {
            $total = array_sum($finalValues) + $modifiers->arithmeticModifier;
        }

        // Evaluate expression-level comparison for success rolls (US8)
        $isSuccess = null;
        if ($expression->comparisonOperator !== null && $expression->comparisonThreshold !== null) {
            $isSuccess = $this->evaluateComparison(
                $total,
                $expression->comparisonThreshold,
                $expression->comparisonOperator
            );
        }

        // Check for critical success/failure (US9)
        // Criticals are based on ORIGINAL die values (before explosions, after rerolls)
        // Exploded dice DO count as criticals - explosion is a separate mechanic
        $isCriticalSuccess = false;
        $isCriticalFailure = false;

        if ($modifiers->criticalSuccess !== null) {
            // Check if ANY die rolled the critical success value (using original values)
            foreach ($originalDiceValues as $i => $value) {
                if ($value >= $modifiers->criticalSuccess) {
                    // If there's a comparison threshold, critical only counts if the roll would hit
                    // Exception: natural max (e.g., 20 on d20) always hits regardless of threshold
                    if ($expression->comparisonOperator !== null && $expression->comparisonThreshold !== null) {
                        $isNaturalMax = ($value === $spec->sides);
                        if ($isNaturalMax) {
                            // Natural max always hits and is always critical
                            $isCriticalSuccess = true;
                            // Override isSuccess for natural max
                            $isSuccess = true;
                        } elseif ($isSuccess === true) {
                            // Roll would hit the threshold, so it's a critical
                            $isCriticalSuccess = true;
                        }
                        // Otherwise, roll is in crit range but doesn't hit - not a critical
                    } else {
                        // No threshold, any value in crit range is critical
                        $isCriticalSuccess = true;
                    }
                    break;
                }
            }
        }

        if ($modifiers->criticalFailure !== null) {
            // Check if ANY die rolled the critical failure value (using original values)
            foreach ($originalDiceValues as $i => $value) {
                if ($value <= $modifiers->criticalFailure) {
                    $isCriticalFailure = true;
                    break;
                }
            }
        }

        return new RollResult(
            expression: $expression,
            total: $total,
            diceValues: $diceValues,
            keptDice: $keptIndices,
            discardedDice: $discardedIndices,
            successCount: $successCount,
            isCriticalSuccess: $isCriticalSuccess,
            isCriticalFailure: $isCriticalFailure,
            isSuccess: $isSuccess,
            rerollHistory: $rerollHistory,
            explosionHistory: $explosionHistory
        );
    }

    /**
     * Check if a die value should be rerolled.
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
     * Check if a die value should explode.
     *
     * @param int $value Die value
     * @param int $threshold Explosion threshold
     * @param string $operator Comparison operator (>= or <=)
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
     * Count successes based on threshold and operator.
     *
     * @param array<int> $diceValues Dice values to check
     * @param int $threshold Success threshold
     * @param string $operator Comparison operator (>=, >, <=, <, ==)
     * @return int Number of successful dice
     */
    private function countSuccesses(array $diceValues, int $threshold, string $operator): int
    {
        $count = 0;
        foreach ($diceValues as $value) {
            $matches = match ($operator) {
                '>=' => $value >= $threshold,
                '>' => $value > $threshold,
                '<=' => $value <= $threshold,
                '<' => $value < $threshold,
                '==' => $value == $threshold,
                default => false,
            };
            if ($matches) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Evaluate comparison for success rolls (US8).
     *
     * @param int|float $total Roll total
     * @param int $threshold Comparison threshold
     * @param string $operator Comparison operator (>=, >, <=, <, ==)
     * @return bool True if comparison succeeds
     */
    private function evaluateComparison(int|float $total, int $threshold, string $operator): bool
    {
        return match ($operator) {
            '>=' => $total >= $threshold,
            '>' => $total > $threshold,
            '<=' => $total <= $threshold,
            '<' => $total < $threshold,
            '==' => $total == $threshold,
            default => false,
        };
    }

    /**
     * Keep the highest N dice.
     *
     * @param array<int> $diceValues All dice values
     * @param int $count Number to keep
     * @return array{0: array<int>, 1: array<int>, 2: array<int>} [kept values, kept indices, discarded indices]
     */
    private function keepHighest(array $diceValues, int $count): array
    {
        // Create array of [index => value]
        $indexed = [];
        foreach ($diceValues as $index => $value) {
            $indexed[$index] = $value;
        }

        // Sort by value descending, maintaining indices
        arsort($indexed);

        // Take top N
        $keptIndices = array_slice(array_keys($indexed), 0, $count, true);
        $discardedIndices = array_slice(array_keys($indexed), $count, null, true);

        $keptValues = [];
        foreach ($keptIndices as $index) {
            $keptValues[] = $diceValues[$index];
        }

        return [$keptValues, array_values($keptIndices), array_values($discardedIndices)];
    }

    /**
     * Keep the lowest N dice.
     *
     * @param array<int> $diceValues All dice values
     * @param int $count Number to keep
     * @return array{0: array<int>, 1: array<int>, 2: array<int>} [kept values, kept indices, discarded indices]
     */
    private function keepLowest(array $diceValues, int $count): array
    {
        // Create array of [index => value]
        $indexed = [];
        foreach ($diceValues as $index => $value) {
            $indexed[$index] = $value;
        }

        // Sort by value ascending, maintaining indices
        asort($indexed);

        // Take bottom N
        $keptIndices = array_slice(array_keys($indexed), 0, $count, true);
        $discardedIndices = array_slice(array_keys($indexed), $count, null, true);

        $keptValues = [];
        foreach ($keptIndices as $index) {
            $keptValues[] = $diceValues[$index];
        }

        return [$keptValues, array_values($keptIndices), array_values($discardedIndices)];
    }

    /**
     * Convert dice value based on dice type.
     *
     * @param int $rawValue Raw dice value (1 to sides)
     * @param \PHPDice\Model\DiceType $type Dice type
     * @return int Converted value
     */
    private function convertDiceValue(int $rawValue, \PHPDice\Model\DiceType $type): int
    {
        return match ($type) {
            \PHPDice\Model\DiceType::FUDGE => $rawValue - 2, // Convert 1,2,3 to -1,0,+1
            \PHPDice\Model\DiceType::STANDARD, \PHPDice\Model\DiceType::PERCENTILE => $rawValue,
        };
    }

    /**
     * Set dice roll results in the AST.
     *
     * @param Node $node Node to update
     * @param int|float $result Roll result
     */
    private function setDiceResults(Node $node, int|float $result): void
    {
        if ($node instanceof DiceNode) {
            $node->setRollResult($result);
        } elseif ($node instanceof BinaryOpNode) {
            $this->setDiceResults($node->getLeft(), $result);
            $this->setDiceResults($node->getRight(), $result);
        } elseif ($node instanceof FunctionNode) {
            $this->setDiceResults($node->getArgument(), $result);
        }
    }

    /**
     * Check if the AST contains multiple dice groups.
     *
     * @param Node $node Node to check
     * @return bool True if multiple dice groups exist
     */
    private function hasMultipleDiceGroups(Node $node): bool
    {
        $diceCount = 0;
        $this->countDiceNodes($node, $diceCount);
        return $diceCount > 1;
    }

    /**
     * Count dice nodes in the AST.
     *
     * @param Node $node Node to traverse
     * @param int $count Counter reference
     */
    private function countDiceNodes(Node $node, int &$count): void
    {
        if ($node instanceof DiceNode) {
            $count++;
        } elseif ($node instanceof BinaryOpNode) {
            $this->countDiceNodes($node->getLeft(), $count);
            $this->countDiceNodes($node->getRight(), $count);
        } elseif ($node instanceof FunctionNode) {
            $this->countDiceNodes($node->getArgument(), $count);
        }
    }

    /**
     * Roll multiple dice groups and combine results.
     *
     * @param Node $ast AST with multiple dice groups
     * @param DiceExpression $expression Original expression
     * @return RollResult Combined roll result
     */
    private function rollMultipleDiceGroups(Node $ast, DiceExpression $expression): RollResult
    {
        $allDiceValues = [];

        // Roll each dice group and collect all values
        $this->rollDiceNode($ast, $allDiceValues);

        // Evaluate the AST to get the total
        $total = (int) $ast->evaluate();

        return new RollResult(
            expression: $expression,
            total: $total,
            diceValues: $allDiceValues
        );
    }

    /**
     * Roll dice for a node and its children, collecting all dice values.
     *
     * @param Node $node Node to roll
     * @param array<int> $allDiceValues Collector for all dice values
     */
    private function rollDiceNode(Node $node, array &$allDiceValues): void
    {
        if ($node instanceof DiceNode) {
            // Roll this dice group
            $diceValues = [];
            for ($i = 0; $i < $node->getCount(); $i++) {
                $value = $this->rng->generate(1, $node->getSides());
                $diceValues[] = $this->convertDiceValue($value, $node->getType());
            }

            // Store the sum in the node for evaluation
            $node->setRollResult(array_sum($diceValues));

            // Add individual values to the collection
            foreach ($diceValues as $value) {
                $allDiceValues[] = $value;
            }
        } elseif ($node instanceof BinaryOpNode) {
            // Process left and right children
            $this->rollDiceNode($node->getLeft(), $allDiceValues);
            $this->rollDiceNode($node->getRight(), $allDiceValues);
        } elseif ($node instanceof FunctionNode) {
            $this->rollDiceNode($node->getArgument(), $allDiceValues);
        }
    }

}
