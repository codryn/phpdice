<?php

declare(strict_types=1);

namespace Codryn\PHPDice;

use Codryn\PHPDice\Exception\ParseException;
use Codryn\PHPDice\Model\DiceExpression;
use Codryn\PHPDice\Model\RollResult;
use Codryn\PHPDice\Parser\AST\BinaryOpNode;
use Codryn\PHPDice\Parser\AST\ComparisonNode;
use Codryn\PHPDice\Parser\AST\ConditionalNode;
use Codryn\PHPDice\Parser\AST\DiceNode;
use Codryn\PHPDice\Parser\AST\FunctionNode;
use Codryn\PHPDice\Parser\AST\GroupNode;
use Codryn\PHPDice\Parser\AST\Node;
use Codryn\PHPDice\Parser\AST\NumberNode;
use Codryn\PHPDice\Parser\DiceExpressionParser;
use Codryn\PHPDice\Roller\DiceRoller;
use Codryn\PHPDice\Roller\RandomNumberGenerator;

/**
 * Main facade for PHPDice library.
 */
class PHPDice
{
    private readonly DiceExpressionParser $parser;
    private readonly DiceRoller $roller;

    public function __construct(RandomNumberGenerator $rng = new RandomNumberGenerator())
    {
        $this->parser = new DiceExpressionParser();
        $this->roller = new DiceRoller($rng);
    }

    /**
     * Parse a dice expression.
     *
     * @param string $expression Dice expression (e.g., "3d6", "1d20+5")
     * @param array<string, int> $variables Optional placeholder variables
     * @return DiceExpression Parsed expression with statistics
     */
    public function parse(string $expression, array $variables = []): DiceExpression
    {
        return $this->parser->parse($expression, $variables);
    }

    /**
     * Roll dice based on an expression string.
     *
     * @param string $expression Dice expression (e.g., "3d6", "1d20+5")
     * @param array<string, int> $variables Optional placeholder variables
     * @return RollResult Roll result with total and individual values
     */
    public function roll(string $expression, array $variables = []): RollResult
    {
        $parsed = $this->parse($expression, $variables);
        return $this->roller->roll($parsed, $parsed->astRoot);
    }

    /**
     * Roll dice based on a parsed DiceExpression.
     *
     * @param DiceExpression $expression Parsed dice expression
     * @return RollResult Roll result with total and individual values
     */
    public function rollExpression(DiceExpression $expression): RollResult
    {
        return $this->roller->roll($expression, $expression->astRoot);
    }

    /**
     * Evaluate an expression and return string with placeholders replaced.
     * Resolves conditions that can be evaluated and removes them from output.
     *
     * @param string $expression Dice expression with placeholders
     * @param array<string, int> $variables Placeholder variable values
     * @param bool $partial If true, allow missing placeholders (default: false)
     * @return string Evaluated expression string with placeholders replaced
     * @throws ParseException If expression is invalid or placeholders are missing (when partial=false)
     */
    public function eval(string $expression, array $variables, bool $partial = false): string
    {
        if ($partial) {
            // For partial evaluation, extract all placeholders and substitute missing ones
            return $this->evalPartial($expression, $variables);
        }

        // Parse the expression normally (will throw on missing variables)
        $parsed = $this->parser->parse($expression, $variables);

        // Convert AST back to string representation with conditions resolved
        return $this->nodeToString($parsed->astRoot, $variables, false);
    }

    /**
     * Perform partial evaluation, allowing missing placeholders.
     *
     * @param string $expression Expression to evaluate
     * @param array<string, int> $variables Available variables
     * @return string Evaluated expression with missing placeholders preserved
     */
    private function evalPartial(string $expression, array $variables): string
    {
        // Extract all placeholders from the expression
        preg_match_all('/\$([a-zA-Z0-9_.]+)\$/', $expression, $matches);
        $placeholders = array_unique($matches[1]);

        // Create a complete variable set with dummy values for missing vars
        $completeVars = $variables;
        $missingVars = [];
        foreach ($placeholders as $placeholder) {
            if (!array_key_exists($placeholder, $variables)) {
                // Use a unique placeholder value (we'll use a specific pattern)
                $completeVars[$placeholder] = PHP_INT_MAX - array_search($placeholder, $placeholders, true);
                $missingVars[$placeholder] = $completeVars[$placeholder];
            }
        }

        // Parse with complete variable set
        $parsed = $this->parser->parse($expression, $completeVars);

        // Convert AST back to string, preserving missing placeholders
        return $this->nodeToString($parsed->astRoot, $completeVars, true, $missingVars);
    }

    /**
     * Convert an AST node to its string representation.
     *
     * @param Node $node AST node to convert
     * @param array<string, int> $variables Available variable values
     * @param bool $partial Whether to allow unresolved placeholders
     * @param array<string, int> $missingVars Missing variables (keys) with their dummy values
     * @return string String representation
     */
    private function nodeToString(Node $node, array $variables, bool $partial, array $missingVars = []): string
    {
        if ($node instanceof ConditionalNode) {
            // Evaluate the condition
            $condition = $node->getCondition();
            $conditionValue = $this->tryEvaluate($condition, $variables, $partial, $missingVars);

            if ($conditionValue !== null) {
                // Condition can be resolved, return the appropriate branch
                $branch = $conditionValue != 0 ? $node->getTrueBranch() : $node->getFalseBranch();
                return $this->nodeToString($branch, $variables, $partial, $missingVars);
            } else {
                // Condition cannot be fully resolved (missing variables in partial mode)
                // Return the full conditional expression
                $condStr = $this->nodeToString($condition, $variables, $partial, $missingVars);
                $trueStr = $this->nodeToString($node->getTrueBranch(), $variables, $partial, $missingVars);
                $falseStr = $this->nodeToString($node->getFalseBranch(), $variables, $partial, $missingVars);
                return "if {$condStr} : {$trueStr} | {$falseStr}";
            }
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->nodeToString($node->getLeft(), $variables, $partial, $missingVars);
            $right = $this->nodeToString($node->getRight(), $variables, $partial, $missingVars);
            $op = $node->getOperator();

            // Add spaces around certain operators for readability
            if (in_array($op, ['+', '-'], true)) {
                return "{$left}{$op}{$right}";
            }
            return "({$left}{$op}{$right})";
        }

        if ($node instanceof ComparisonNode) {
            $left = $this->nodeToString($node->getLeft(), $variables, $partial, $missingVars);
            $right = $this->nodeToString($node->getRight(), $variables, $partial, $missingVars);
            return "{$left} {$node->getOperator()} {$right}";
        }

        if ($node instanceof NumberNode) {
            $value = $node->getValue();

            // Check if this is a dummy value for a missing placeholder
            if ($partial && !empty($missingVars)) {
                foreach ($missingVars as $varName => $dummyValue) {
                    if ($value === $dummyValue) {
                        // Return the placeholder instead of the value
                        return "\${$varName}\$";
                    }
                }
            }

            // Format number appropriately
            return is_int($value) ? (string)$value : rtrim(rtrim((string)$value, '0'), '.');
        }

        if ($node instanceof DiceNode) {
            $count = $node->getCount();
            $sides = $node->getSides();
            return "{$count}d{$sides}";
        }

        if ($node instanceof FunctionNode) {
            $args = array_map(
                fn($arg) => $this->nodeToString($arg, $variables, $partial, $missingVars),
                $node->getArguments()
            );
            return $node->getName() . '(' . implode(', ', $args) . ')';
        }

        if ($node instanceof GroupNode) {
            return '(' . $this->nodeToString($node->getExpression(), $variables, $partial, $missingVars) . ')';
        }

        // Fallback: try to evaluate and return as number
        $value = $node->evaluate();
        return is_int($value) ? (string)$value : rtrim(rtrim((string)$value, '0'), '.');
    }

    /**
     * Try to evaluate a node, returning null if it cannot be evaluated (missing variables).
     *
     * @param Node $node Node to evaluate
     * @param array<string, int> $variables Available variables
     * @param bool $partial Whether partial evaluation is allowed
     * @param array<string, int> $missingVars Missing variables with their dummy values
     * @return int|float|null Evaluated value or null if cannot evaluate
     */
    private function tryEvaluate(Node $node, array $variables, bool $partial, array $missingVars = []): int|float|null
    {
        if (!$partial) {
            // In non-partial mode, always evaluate
            return $node->evaluate();
        }

        // In partial mode, check if the node depends on missing variables
        if ($this->nodeUsesMissingVars($node, $missingVars)) {
            return null;
        }

        // Try to evaluate
        try {
            return $node->evaluate();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a node uses any missing variables.
     *
     * @param Node $node Node to check
     * @param array<string, int> $missingVars Missing variables with their dummy values
     * @return bool True if node uses missing variables
     */
    private function nodeUsesMissingVars(Node $node, array $missingVars): bool
    {
        if (empty($missingVars)) {
            return false;
        }

        // Check if node is a NumberNode with a dummy value
        if ($node instanceof NumberNode) {
            $value = $node->getValue();
            foreach ($missingVars as $dummyValue) {
                if ($value === $dummyValue) {
                    return true;
                }
            }
            return false;
        }

        // For compound nodes, check recursively
        if ($node instanceof BinaryOpNode) {
            return $this->nodeUsesMissingVars($node->getLeft(), $missingVars)
                || $this->nodeUsesMissingVars($node->getRight(), $missingVars);
        }

        if ($node instanceof ComparisonNode) {
            return $this->nodeUsesMissingVars($node->getLeft(), $missingVars)
                || $this->nodeUsesMissingVars($node->getRight(), $missingVars);
        }

        if ($node instanceof ConditionalNode) {
            return $this->nodeUsesMissingVars($node->getCondition(), $missingVars)
                || $this->nodeUsesMissingVars($node->getTrueBranch(), $missingVars)
                || $this->nodeUsesMissingVars($node->getFalseBranch(), $missingVars);
        }

        if ($node instanceof GroupNode) {
            return $this->nodeUsesMissingVars($node->getExpression(), $missingVars);
        }

        if ($node instanceof FunctionNode) {
            foreach ($node->getArguments() as $arg) {
                if ($this->nodeUsesMissingVars($arg, $missingVars)) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }
}
