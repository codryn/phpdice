<?php

declare(strict_types=1);

namespace PHPDice\Roller;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\RollResult;
use PHPDice\Model\StatisticalCalculator;
use PHPDice\Parser\AST\BinaryOpNode;
use PHPDice\Parser\AST\DiceExpressionNode;
use PHPDice\Parser\AST\DiceNode;
use PHPDice\Parser\AST\FunctionNode;
use PHPDice\Parser\AST\GroupNode;
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
        // Handle math-only expressions (no dice)
        if ($expression->specification === null) {
            return $this->rollMathOnly($ast, $expression);
        }

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
        $edgeHistory = null;
        $originalDiceValues = []; // Track original values before explosions for critical detection
        $edgeTriggers = []; // Track which dice triggered edge (for deferred processing)

        for ($i = 0; $i < $totalDiceToRoll; $i++) {
            // Generate raw roll based on dice type
            $rawRoll = $this->rng->generate(1, $spec->sides);

            // Convert for special dice types
            $initialRoll = $this->convertDiceValue($rawRoll, $spec->type);
            $diceValues[] = $initialRoll;

            // Handle rerolls if configured (rerolls happen first, then explosions/edge)
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

            // Handle edge if configured (Shadowrun Rule of Six: add additional dice when threshold met)
            // We defer actual edge rolling until after all original dice are rolled
            if ($modifiers->edgeThreshold !== null && $modifiers->edgeOperator !== null) {
                if ($this->shouldEdge($diceValues[$i], $modifiers->edgeThreshold, $modifiers->edgeOperator)) {
                    $edgeTriggers[] = $i;
                }
            }
        }

        // Process edge triggers after all original dice are rolled
        if (!empty($edgeTriggers) && $modifiers->edgeThreshold !== null && $modifiers->edgeOperator !== null) {
            foreach ($edgeTriggers as $triggerIndex) {
                $edgeCount = 0;
                $currentValue = $diceValues[$triggerIndex];
                $edgeDice = [];

                // Keep rolling edge dice while threshold is met and limit not reached
                while ($this->shouldEdge($currentValue, $modifiers->edgeThreshold, $modifiers->edgeOperator)
                       && $edgeCount < $modifiers->edgeLimit) {
                    // Roll a new die and add it to the dice pool
                    $rawEdge = $this->rng->generate(1, $spec->sides);
                    $edgeDieValue = $this->convertDiceValue($rawEdge, $spec->type);
                    $edgeDice[] = $edgeDieValue;

                    // Add the new die to the dice values array
                    $diceValues[] = $edgeDieValue;
                    $originalDiceValues[] = $edgeDieValue; // Track for critical detection

                    // Check if the new die also triggers edge (cascading)
                    $currentValue = $edgeDieValue;
                    $edgeCount++;
                }

                // Track edge history if any edge dice were added
                if ($edgeCount > 0) {
                    if ($edgeHistory === null) {
                        $edgeHistory = [];
                    }
                    $edgeHistory[$triggerIndex] = [
                        'rolls' => $edgeDice,
                        'count' => $edgeCount,
                        'limitReached' => $edgeCount >= $modifiers->edgeLimit,
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
        if ($modifiers->successOperator !== null) {
            $successCount = $this->countSuccesses($finalValues, $modifiers->successThreshold, $modifiers->successOperator);
        }

        // Calculate total
        if ($modifiers->successOperator !== null) {
            // In success counting mode, total = success count
            $total = $successCount;
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

        // Check for auto success/failure first (before critical checks)
        // Auto success/failure means the roll is a success/failure regardless of DC
        $hasAutoSuccess = false;
        $hasAutoFailure = false;

        if ($modifiers->autoSuccess !== null) {
            foreach ($originalDiceValues as $i => $value) {
                // Auto success uses >= for high values (e.g., auto 20 on d20)
                // and <= for low values (e.g., auto 1 on d20 for inverted logic)
                // We determine which to use based on whether the threshold is in the upper or lower half
                $midpoint = ($spec->sides + 1) / 2;
                $isAutoSuccess = ($modifiers->autoSuccess >= $midpoint)
                    ? ($value >= $modifiers->autoSuccess)
                    : ($value <= $modifiers->autoSuccess);

                if ($isAutoSuccess) {
                    $hasAutoSuccess = true;
                    // Override isSuccess if we have a DC comparison
                    if ($expression->comparisonOperator !== null && $expression->comparisonThreshold !== null) {
                        $isSuccess = true;
                    }
                    break;
                }
            }
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
                    // OR if the roll has auto success
                    if ($expression->comparisonOperator !== null && $expression->comparisonThreshold !== null) {
                        if ($hasAutoSuccess || $isSuccess === true) {
                            // Roll would hit the threshold (or has auto success), so it's a critical
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
            // Glitch uses <= for low values (e.g., glitch 1 on d20)
            // and >= for high values (e.g., glitch 20 on d20 for inverted logic)
            foreach ($originalDiceValues as $i => $value) {
                $midpoint = ($spec->sides + 1) / 2;
                $isCritFailure = ($modifiers->criticalFailure >= $midpoint)
                    ? ($value >= $modifiers->criticalFailure)
                    : ($value <= $modifiers->criticalFailure);

                if ($isCritFailure) {
                    $isCriticalFailure = true;
                    break;
                }
            }
        }

        // Handle groups if present in AST
        $groups = null;
        if ($ast !== null) {
            $groups = $this->extractAndEvaluateGroups($ast, $expression, $diceValues);
        }

        // Main expression keeps its own tags (don't add group tags to main)
        $tags = $expression->tags;

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
            explosionHistory: $explosionHistory,
            edgeHistory: $edgeHistory,
            comment: $expression->comment,
            groups: $groups,
            tags: $tags
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
     * Check if a die value should trigger edge (add additional dice).
     *
     * @param int $value Die value
     * @param int $threshold Edge threshold
     * @param string $operator Comparison operator (>= or <=)
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
     * Count successes based on threshold and operator.
     *
     * @param array<int> $diceValues Dice values to check
     * @param int|null $threshold Success threshold (null for even/odd)
     * @param string $operator Comparison operator (>=, >, <=, <, ==, 'even', 'odd')
     * @return int Number of successful dice
     */
    private function countSuccesses(array $diceValues, ?int $threshold, string $operator): int
    {
        $count = 0;
        foreach ($diceValues as $value) {
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
        } elseif ($node instanceof DiceExpressionNode) {
            // DiceExpressionNode will be rolled separately, don't set here
            return;
        } elseif ($node instanceof GroupNode) {
            // Set results on the group's expression
            $this->setDiceResults($node->getExpression(), $result);
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
        } elseif ($node instanceof DiceExpressionNode) {
            // Count as a single dice group (even though it may have modifiers)
            $count++;
        } elseif ($node instanceof BinaryOpNode) {
            $this->countDiceNodes($node->getLeft(), $count);
            $this->countDiceNodes($node->getRight(), $count);
        } elseif ($node instanceof FunctionNode) {
            // Count dice in all arguments
            foreach ($node->getArguments() as $argument) {
                $this->countDiceNodes($argument, $count);
            }
        } elseif ($node instanceof GroupNode) {
            // Count dice inside the group
            $this->countDiceNodes($node->getExpression(), $count);
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

        // Handle groups if present
        $groups = $this->extractAndEvaluateGroups($ast, $expression, $allDiceValues);

        // Main expression keeps its own tags (don't add group tags to main)
        $tags = $expression->tags;

        return new RollResult(
            expression: $expression,
            total: $total,
            diceValues: $allDiceValues,
            comment: $expression->comment,
            groups: $groups,
            tags: $tags
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
        if ($node instanceof DiceExpressionNode) {
            // This is a complete dice expression with modifiers - roll it properly
            $spec = $node->getSpecification();
            $modifiers = $node->getModifiers();

            // Create a temporary DiceExpression for rolling
            // We need statistics even if we don't use them for the final result
            $calculator = new StatisticalCalculator();
            $stats = $calculator->calculate($spec, $modifiers, $node->getDiceNode());

            $tempExpression = new DiceExpression(
                specification: $spec,
                modifiers: $modifiers,
                statistics: $stats,
                originalExpression: '', // Not needed for this context
                astRoot: $node->getDiceNode()
            );

            // Roll the dice expression with all modifiers, passing the AST
            $result = $this->roll($tempExpression, $node->getDiceNode());

            // For success counting, use the success count as the result
            // Otherwise, use the total
            if ($modifiers->successOperator !== null) {
                $node->setRollResult($result->successCount ?? 0);
            } else {
                $node->setRollResult($result->total);
            }

            // Add individual dice values to the collection
            foreach ($result->diceValues as $value) {
                $allDiceValues[] = $value;
            }
        } elseif ($node instanceof DiceNode) {
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
        } elseif ($node instanceof GroupNode) {
            // Roll dice inside the group
            $this->rollDiceNode($node->getExpression(), $allDiceValues);
        } elseif ($node instanceof FunctionNode) {
            // Roll dice in all arguments
            foreach ($node->getArguments() as $argument) {
                $this->rollDiceNode($argument, $allDiceValues);
            }
        }
    }

    /**
     * Roll a math-only expression (no top-level dice, but may contain dice in sub-expressions).
     *
     * @param Node|null $ast AST to evaluate
     * @param DiceExpression $expression Original expression
     * @return RollResult Result with dice values and computed total
     */
    private function rollMathOnly(?Node $ast, DiceExpression $expression): RollResult
    {
        if ($ast === null) {
            throw new \PHPDice\Exception\ValidationException('Math-only expression must have an AST', 'expression');
        }

        // Roll any dice nodes in the expression (e.g., inside function arguments)
        $allDiceValues = [];
        $this->rollDiceNode($ast, $allDiceValues);

        // Evaluate the AST to get the total
        $total = $ast->evaluate();

        // Handle groups if present
        $groups = $this->extractAndEvaluateGroups($ast, $expression, $allDiceValues);

        // Main expression keeps its own tags (don't add group tags to main)
        $tags = $expression->tags;

        // Return result with collected dice values
        return new RollResult(
            expression: $expression,
            total: $total,
            diceValues: $allDiceValues,
            comment: $expression->comment,
            groups: $groups,
            tags: $tags
        );
    }

    /**
     * Extract and evaluate all groups from the AST.
     *
     * @param Node $ast The full AST
     * @param DiceExpression $expression Original expression
     * @param array<int> $diceValues Already rolled dice values
     * @return array<RollResult>|null Array of group results or null if no groups
     */
    private function extractAndEvaluateGroups(Node $ast, DiceExpression $expression, array $diceValues): ?array
    {
        $groups = [];
        $this->findGroups($ast, $groups);

        if (empty($groups)) {
            return null;
        }

        $results = [];
        $diceOffset = 0;

        foreach ($groups as $groupNode) {
            /** @var GroupNode $groupNode */
            $groupExpression = $groupNode->getExpression();
            $groupComment = $groupNode->getComment();
            $groupTags = $groupNode->getTags();

            // Count how many dice this group needs
            $diceCount = 0;
            $this->countDiceNodes($groupExpression, $diceCount);

            // Extract the dice values for this group
            $groupDiceValues = array_slice($diceValues, $diceOffset, $diceCount);
            $diceOffset += $diceCount;

            // Evaluate the group expression to get its total
            // Note: dice have already been rolled and their results set in the nodes
            $groupTotal = $groupExpression->evaluate();

            // Create a RollResult for this group
            $results[] = new RollResult(
                expression: $expression,
                total: $groupTotal,
                diceValues: $groupDiceValues,
                comment: $groupComment,
                tags: $groupTags
            );
        }

        return $results;
    }

    /**
     * Find all GroupNodes in the AST.
     *
     * @param Node $node Node to traverse
     * @param array<GroupNode> $groups Array to collect groups
     */
    private function findGroups(Node $node, array &$groups): void
    {
        if ($node instanceof GroupNode) {
            $groups[] = $node;
        } elseif ($node instanceof BinaryOpNode) {
            $this->findGroups($node->getLeft(), $groups);
            $this->findGroups($node->getRight(), $groups);
        } elseif ($node instanceof FunctionNode) {
            foreach ($node->getArguments() as $argument) {
                $this->findGroups($argument, $groups);
            }
        }
    }

}
