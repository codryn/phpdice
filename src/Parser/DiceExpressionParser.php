<?php

declare(strict_types=1);

namespace PHPDice\Parser;

use PHPDice\Exception\ParseException;
use PHPDice\Model\DiceExpression;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\StatisticalCalculator;
use PHPDice\Parser\AST\BinaryOpNode;
use PHPDice\Parser\AST\DiceNode;
use PHPDice\Parser\AST\FunctionNode;
use PHPDice\Parser\AST\Node;
use PHPDice\Parser\AST\NumberNode;

/**
 * Parses dice expressions into structured DiceExpression objects.
 */
class DiceExpressionParser
{
    /** @var array<int, Token> */
    private array $tokens = [];
    private int $current = 0;
    private ?Node $astRoot = null;
    /** @var array<string, int> Placeholder values */
    private array $variables = [];
    /** @var array<string, int> Track which variables were actually used */
    private array $usedVariables = [];

    public function __construct(
        private readonly Validator $validator = new Validator(),
        private readonly StatisticalCalculator $calculator = new StatisticalCalculator()
    ) {
    }

    /**
     * Parse a dice expression.
     *
     * @param string $expression Dice expression to parse (e.g., "3d6", "1d20+5")
     * @param array<string, int> $variables Optional placeholder variables
     * @return DiceExpression Parsed expression
     * @throws ParseException If parsing fails
     */
    public function parse(string $expression, array $variables = []): DiceExpression
    {
        // Store variables for placeholder substitution
        $this->variables = $variables;
        $this->usedVariables = [];

        // Tokenize
        $lexer = new Lexer($expression);
        $this->tokens = $lexer->tokenize();
        $this->current = 0;

        // Parse initial dice notation to get base AST
        $this->astRoot = $this->parseTerm(); // Start with term to get just the dice

        // Extract dice specification from AST
        $diceNode = $this->findDiceNode($this->astRoot);
        if ($diceNode === null) {
            // Fallback: try simple validation for backward compatibility
            $this->validator->validateExpression($expression);
            throw new ParseException('No dice notation found in expression', 0);
        }

        // Create dice specification
        $spec = new DiceSpecification(
            count: $diceNode->getCount(),
            sides: $diceNode->getSides(),
            type: $diceNode->getType()
        );

        // Validate specification
        $this->validator->validateDiceSpecification($spec);

        // Parse modifiers (advantage, disadvantage, keep) - these consume KEYWORD tokens
        $modifiers = $this->parseModifiers($spec);

        // Validate modifiers for conflicts
        $this->validator->validateModifiers($modifiers);

        // Continue parsing the rest of the expression (arithmetic operators)
        // At this point, current token might be +, -, *, /, or EOF
        while ($this->match(Token::TYPE_OPERATOR, ['+', '-'])) {
            $operator = $this->previous()->value;
            $right = $this->parseTerm();
            $this->astRoot = new BinaryOpNode($this->astRoot, (string)$operator, $right);
        }

        // Parse comparison operator and threshold for success rolls (US8)
        // Requires 'dc' keyword before comparison (e.g., "1d20+5 dc >= 15")
        $comparisonOperator = null;
        $comparisonThreshold = null;

        // Check for 'dc' keyword before comparison
        if ($this->match(Token::TYPE_KEYWORD, ['dc'])) {
            // After 'dc', comparison operator is required
            if (!$this->match(Token::TYPE_COMPARISON)) {
                throw new ParseException(
                    "Expected comparison operator after 'dc' keyword",
                    $this->peek()->position
                );
            }
            $comparisonOperator = (string)$this->previous()->value;

            // Next token must be the threshold number or placeholder
            if ($this->check(Token::TYPE_NUMBER)) {
                $comparisonThreshold = (int)$this->advance()->value;
            } elseif ($this->check(Token::TYPE_PLACEHOLDER)) {
                // Handle placeholder for comparison threshold
                $this->advance();
                $variableName = (string)$this->previous()->value;

                if (!array_key_exists($variableName, $this->variables)) {
                    throw new ParseException(
                        "Unbound placeholder variable '\${$variableName}\$'. Please provide a value for this variable.",
                        $this->previous()->position
                    );
                }

                // Track variable usage
                $this->usedVariables[$variableName] = $this->variables[$variableName];
                $comparisonThreshold = $this->variables[$variableName];
            } else {
                throw new ParseException(
                    "Expected number or placeholder after comparison operator '{$comparisonOperator}'",
                    $this->getCurrentPosition()
                );
            }
        }

        // Ensure all tokens are consumed
        if (!$this->isAtEnd()) {
            $remaining = $this->peek();
            // Check if it's a duplicate modifier keyword
            if ($remaining->type === Token::TYPE_KEYWORD) {
                throw new \PHPDice\Exception\ValidationException(
                    'Modifier conflict: cannot specify multiple or conflicting keep modifiers',
                    'modifiers'
                );
            }
            throw new ParseException(
                "Unexpected token: {$remaining->type} '{$remaining->value}'",
                $this->getCurrentPosition()
            );
        }

        // Update modifiers with resolved variables (parsed during expression evaluation)
        if (!empty($this->usedVariables)) {
            $modifiers = new RollModifiers(
                advantageCount: $modifiers->advantageCount,
                keepHighest: $modifiers->keepHighest,
                keepLowest: $modifiers->keepLowest,
                successThreshold: $modifiers->successThreshold,
                successOperator: $modifiers->successOperator,
                explosionThreshold: $modifiers->explosionThreshold,
                explosionOperator: $modifiers->explosionOperator,
                explosionLimit: $modifiers->explosionLimit,
                rerollThreshold: $modifiers->rerollThreshold,
                rerollOperator: $modifiers->rerollOperator,
                rerollLimit: $modifiers->rerollLimit,
                resolvedVariables: $this->usedVariables
            );
        }

        // Calculate statistics from AST
        $statistics = $this->calculator->calculate($spec, $modifiers, $this->astRoot);

        // Build expression
        return new DiceExpression(
            specification: $spec,
            modifiers: $modifiers,
            statistics: $statistics,
            originalExpression: $expression,
            astRoot: $this->astRoot,
            comparisonOperator: $comparisonOperator,
            comparisonThreshold: $comparisonThreshold
        );
    }

    /**
     * Get the AST root for evaluation.
     *
     * @return Node|null AST root node
     */
    public function getAstRoot(): ?Node
    {
        return $this->astRoot;
    }

    /**
     * Parse an expression (handles +, -).
     *
     * @return Node Expression node
     */
    private function parseExpression(): Node
    {
        $node = $this->parseTerm();

        while ($this->match(Token::TYPE_OPERATOR, ['+', '-'])) {
            $operator = $this->previous()->value;
            $right = $this->parseTerm();
            $node = new BinaryOpNode($node, (string)$operator, $right);
        }

        return $node;
    }

    /**
     * Parse a term (handles *, /).
     *
     * @return Node Term node
     */
    private function parseTerm(): Node
    {
        $node = $this->parseFactor();

        while ($this->match(Token::TYPE_OPERATOR, ['*', '/', '~', '^'])) {
            $operator = $this->previous()->value;
            $right = $this->parseFactor();
            $node = new BinaryOpNode($node, (string)$operator, $right);
        }

        return $node;
    }

    /**
     * Parse a factor (handles numbers, dice, parentheses, functions).
     *
     * @return Node Factor node
     */
    private function parseFactor(): Node
    {
        // Function call
        if ($this->match(Token::TYPE_FUNCTION)) {
            return $this->parseFunction();
        }

        // Parenthesized expression
        if ($this->match(Token::TYPE_LPAREN)) {
            $expr = $this->parseExpression();
            $this->consume(Token::TYPE_RPAREN, 'Expected closing parenthesis');
            return $expr;
        }

        // Dice notation (XdY) - DON'T consume modifiers here, they're parsed separately
        if ($this->check(Token::TYPE_NUMBER) && $this->checkNext(Token::TYPE_DICE)) {
            $count = $this->consumeNumber();
            $diceToken = $this->advance();
            $diceValue = (string)$diceToken->value;

            // Check for special dice types
            if ($diceValue === 'dF') {
                // Fudge dice: count is specified, sides is always 3 (representing -1, 0, +1)
                return new DiceNode($count, 3, \PHPDice\Model\DiceType::FUDGE);
            } elseif ($diceValue === 'd%') {
                // Percentile dice: count is specified, sides is always 100
                return new DiceNode($count, 100, \PHPDice\Model\DiceType::PERCENTILE);
            } else {
                // Standard dice: get the sides
                $sides = $this->consumeNumber();
                return new DiceNode($count, $sides);
            }
        }

        // Standalone d% or dF (equivalent to 1d% or 1dF)
        if ($this->check(Token::TYPE_DICE)) {
            $diceToken = $this->peek();
            $diceValue = (string)$diceToken->value;

            if ($diceValue === 'd%') {
                $this->advance(); // Consume d%
                return new DiceNode(1, 100, \PHPDice\Model\DiceType::PERCENTILE);
            } elseif ($diceValue === 'dF') {
                $this->advance(); // Consume dF
                return new DiceNode(1, 3, \PHPDice\Model\DiceType::FUDGE);
            }
        }

        // Placeholder ($name$)
        if ($this->match(Token::TYPE_PLACEHOLDER)) {
            $variableName = (string)$this->previous()->value;

            // Check if variable is provided
            if (!array_key_exists($variableName, $this->variables)) {
                throw new ParseException(
                    "Unbound placeholder variable '\${$variableName}\$'. Please provide a value for this variable.",
                    $this->previous()->position
                );
            }

            // Track that this variable was used
            $this->usedVariables[$variableName] = $this->variables[$variableName];

            // Return the numeric value
            return new NumberNode($this->variables[$variableName]);
        }

        // Plain number
        if ($this->match(Token::TYPE_NUMBER)) {
            $value = $this->previous()->value;
            // Keep the original type (int or float) from the token
            if (!is_int($value) && !is_float($value)) {
                throw new ParseException('Number token must have int or float value', $this->getCurrentPosition());
            }
            return new NumberNode($value);
        }

        throw new ParseException('Expected number, dice, or expression', $this->getCurrentPosition());
    }

    /**
     * Parse a function call.
     *
     * @return FunctionNode Function node
     */
    private function parseFunction(): FunctionNode
    {
        $functionName = (string)$this->previous()->value;

        $this->consume(Token::TYPE_LPAREN, 'Expected opening parenthesis after function name');
        $argument = $this->parseExpression();
        $this->consume(Token::TYPE_RPAREN, 'Expected closing parenthesis after function argument');

        return new FunctionNode($functionName, $argument);
    }

    /**
     * Parse modifiers like advantage, disadvantage, keep.
     *
     * @param DiceSpecification $spec Dice specification
     * @return RollModifiers Roll modifiers
     */
    private function parseModifiers(DiceSpecification $spec): RollModifiers
    {
        $advantageCount = null;
        $keepHighest = null;
        $keepLowest = null;
        $successThreshold = null;
        $successOperator = null;

        // Check for advantage keyword
        if ($this->match(Token::TYPE_KEYWORD, ['advantage'])) {
            // Roll spec->count extra dice, keep spec->count highest
            $advantageCount = $spec->count;
            $keepHighest = $spec->count;
        }

        // Check for disadvantage keyword
        if ($this->match(Token::TYPE_KEYWORD, ['disadvantage'])) {
            if ($advantageCount !== null) {
                throw new \PHPDice\Exception\ValidationException(
                    'Cannot have both advantage and disadvantage',
                    'modifiers'
                );
            }
            // Roll spec->count extra dice, keep spec->count lowest
            $advantageCount = $spec->count;
            $keepLowest = $spec->count;
        }

        // Check for keep X highest/lowest
        if ($this->match(Token::TYPE_KEYWORD, ['keep'])) {
            $count = $this->consumeNumber();

            if ($this->match(Token::TYPE_KEYWORD, ['highest'])) {
                if ($keepHighest !== null || $keepLowest !== null) {
                    throw new \PHPDice\Exception\ValidationException(
                        'Cannot specify keep multiple times',
                        'modifiers'
                    );
                }
                $keepHighest = $count;
            } elseif ($this->match(Token::TYPE_KEYWORD, ['lowest'])) {
                if ($keepHighest !== null || $keepLowest !== null) {
                    throw new \PHPDice\Exception\ValidationException(
                        'Cannot specify keep multiple times',
                        'modifiers'
                    );
                }
                $keepLowest = $count;
            } else {
                throw new ParseException('Expected "highest" or "lowest" after keep count', $this->getCurrentPosition());
            }

            // Calculate total dice to roll (base + advantage)
            $totalDiceToRoll = $spec->count;
            if ($advantageCount !== null) {
                $totalDiceToRoll += $advantageCount;
            }

            // Validate keep count doesn't exceed total dice
            if ($keepHighest !== null && $keepHighest > $totalDiceToRoll) {
                throw new \PHPDice\Exception\ValidationException(
                    "Cannot keep {$keepHighest} dice when only rolling {$totalDiceToRoll}",
                    'keep'
                );
            }
            if ($keepLowest !== null && $keepLowest > $totalDiceToRoll) {
                throw new \PHPDice\Exception\ValidationException(
                    "Cannot keep {$keepLowest} dice when only rolling {$totalDiceToRoll}",
                    'keep'
                );
            }
        }

        // Check for reroll FIRST: "reroll [limit] operator threshold"
        // Must parse before success counting to handle "reroll <= 2 >= 4" correctly
        $rerollThreshold = null;
        $rerollOperator = null;
        $rerollLimit = 100; // Default limit

        if ($this->match(Token::TYPE_KEYWORD, ['reroll'])) {
            // Check for optional limit number
            if ($this->check(Token::TYPE_NUMBER)) {
                $nextPos = $this->current + 1;
                // Peek ahead to see if the next token after the number is a comparison operator
                if ($nextPos < count($this->tokens) && $this->tokens[$nextPos]->type === Token::TYPE_COMPARISON) {
                    // This number is the limit
                    $rerollLimit = $this->consumeNumber();
                }
            }

            // Expect comparison operator
            if (!$this->check(Token::TYPE_COMPARISON)) {
                throw new ParseException('Expected comparison operator after "reroll"', $this->getCurrentPosition());
            }

            $comparison = $this->advance();
            $rerollOperator = (string)$comparison->value;

            // Validate operator (all comparison operators allowed for reroll)
            if (!in_array($rerollOperator, ['<=', '<', '>=', '>', '=='], true)) {
                throw new \PHPDice\Exception\ValidationException(
                    "Invalid reroll operator '{$rerollOperator}'",
                    'reroll'
                );
            }

            // Get threshold value
            $rerollThreshold = $this->consumeNumber();

            // Validate reroll range doesn't cover entire die (FR-005b)
            $this->validator->validateRerollRange($spec, $rerollThreshold, $rerollOperator);
        }

        // Check for success counting: "count >=N" or "count >N" or "success threshold N" (legacy)
        // Parsed after reroll to allow "reroll <= 2 count >= 4" syntax
        if ($this->match(Token::TYPE_KEYWORD, ['count'])) {
            // After 'count', comparison operator is required
            if (!$this->check(Token::TYPE_COMPARISON)) {
                throw new ParseException(
                    "Expected comparison operator after 'count' keyword",
                    $this->peek()->position
                );
            }

            $comparison = $this->advance();
            $operator = (string)$comparison->value;

            // Allow all comparison operators for success counting
            if (!in_array($operator, ['>=', '>', '<=', '<', '=='], true)) {
                throw new \PHPDice\Exception\ValidationException(
                    "Invalid success operator '{$operator}'. Only >=, >, <=, <, and == are supported for success counting.",
                    'success'
                );
            }

            $successOperator = $operator;
            $successThreshold = $this->consumeNumber();
        } elseif ($this->match(Token::TYPE_KEYWORD, ['success'])) {
            // Legacy syntax: "success threshold N"
            // Expect "threshold N"
            if (!$this->match(Token::TYPE_KEYWORD, ['threshold'])) {
                throw new ParseException('Expected "threshold" after "success"', $this->getCurrentPosition());
            }
            $successThreshold = $this->consumeNumber();
            $successOperator = '>='; // Default to >= for "success threshold N" syntax
        } elseif ($this->match(Token::TYPE_KEYWORD, ['threshold'])) {
            // Legacy syntax: just "threshold N" (shorthand for "success threshold N")
            $successThreshold = $this->consumeNumber();
            $successOperator = '>=';
        }

        // Check for explode: "explode [limit] [operator threshold]"
        // Parsed after keep but before reroll/success to allow "keep 3 highest explode >=5"
        $explosionThreshold = null;
        $explosionOperator = null;
        $explosionLimit = 100; // Default limit

        if ($this->match(Token::TYPE_KEYWORD, ['explode'])) {
            // Check for optional limit number
            if ($this->check(Token::TYPE_NUMBER)) {
                $nextPos = $this->current + 1;
                // Peek ahead to see if the next token after the number is a comparison operator or EOF
                $hasComparison = ($nextPos < count($this->tokens) && $this->tokens[$nextPos]->type === Token::TYPE_COMPARISON);

                if ($hasComparison) {
                    // This number is the limit
                    $explosionLimit = $this->consumeNumber();
                } else {
                    // This number might be the limit, check if we're at end or next is keyword
                    $nextIsEnd = ($nextPos >= count($this->tokens) || $this->tokens[$nextPos]->type === Token::TYPE_EOF);
                    $nextIsKeyword = (!$nextIsEnd && $this->tokens[$nextPos]->type === Token::TYPE_KEYWORD);

                    if ($nextIsEnd || $nextIsKeyword) {
                        // This number is the limit with no threshold
                        $explosionLimit = $this->consumeNumber();
                    }
                }
            }

            // Check for optional comparison operator and threshold
            if ($this->check(Token::TYPE_COMPARISON)) {
                $comparison = $this->advance();
                $explosionOperator = (string)$comparison->value;

                // Validate operator (only >= and <= allowed for explosions per spec)
                if (!in_array($explosionOperator, ['>=', '<='], true)) {
                    throw new \PHPDice\Exception\ValidationException(
                        "Invalid explosion operator '{$explosionOperator}'. Only >= and <= are supported for exploding dice.",
                        'explode'
                    );
                }

                // Get threshold value
                $explosionThreshold = $this->consumeNumber();

                // Validate explosion range doesn't cover entire die (FR-038c)
                $this->validator->validateExplosionRange($spec, $explosionThreshold, $explosionOperator);
            } else {
                // No threshold specified - default to maximum die value
                $explosionThreshold = $spec->sides;
                $explosionOperator = '>=';

                // Validate this doesn't create infinite loop (single-sided die)
                $this->validator->validateExplosionRange($spec, $explosionThreshold, $explosionOperator);
            }
        }

        // Check for critical success: "crit N"
        $criticalSuccess = null;
        if ($this->match(Token::TYPE_KEYWORD, ['crit'])) {
            $criticalSuccess = $this->consumeNumber();

            // Validate critical threshold is within die range (FR-035)
            $this->validator->validateCriticalThreshold($spec, $criticalSuccess, 'success');
        }

        // Check for critical failure: "glitch N"
        $criticalFailure = null;
        if ($this->match(Token::TYPE_KEYWORD, ['glitch'])) {
            $criticalFailure = $this->consumeNumber();

            // Validate critical threshold is within die range (FR-036)
            $this->validator->validateCriticalThreshold($spec, $criticalFailure, 'failure');
        }

        return new RollModifiers(
            advantageCount: $advantageCount,
            keepHighest: $keepHighest,
            keepLowest: $keepLowest,
            successThreshold: $successThreshold,
            successOperator: $successOperator,
            explosionThreshold: $explosionThreshold,
            explosionOperator: $explosionOperator,
            explosionLimit: $explosionLimit,
            rerollThreshold: $rerollThreshold,
            rerollOperator: $rerollOperator,
            rerollLimit: $rerollLimit,
            criticalSuccess: $criticalSuccess,
            criticalFailure: $criticalFailure,
            resolvedVariables: $this->usedVariables
        );
    }

    /**
     * Find the first dice node in the AST.
     *
     * @param Node $node Node to search
     * @return DiceNode|null Dice node if found
     */
    private function findDiceNode(Node $node): ?DiceNode
    {
        if ($node instanceof DiceNode) {
            return $node;
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->findDiceNode($node->getLeft());
            if ($left !== null) {
                return $left;
            }
            return $this->findDiceNode($node->getRight());
        }

        if ($node instanceof FunctionNode) {
            return $this->findDiceNode($node->getArgument());
        }

        return null;
    }

    /**
     * Check if current token matches type and optional values.
     *
     * @param string $type Token type to match
     * @param array<string>|null $values Optional values to match
     * @return bool True if matches
     */
    private function match(string $type, ?array $values = null): bool
    {
        if (!$this->check($type)) {
            return false;
        }

        if ($values !== null) {
            $currentValue = $this->peek()->value;
            if (!in_array($currentValue, $values, true)) {
                return false;
            }
        }

        $this->advance();
        return true;
    }

    /**
     * Check if current token is of given type.
     *
     * @param string $type Token type
     * @return bool True if matches
     */
    private function check(string $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()->type === $type;
    }

    /**
     * Check if next token is of given type.
     *
     * @param string $type Token type
     * @return bool True if matches
     */
    private function checkNext(string $type): bool
    {
        if ($this->current + 1 >= count($this->tokens)) {
            return false;
        }

        return $this->tokens[$this->current + 1]->type === $type;
    }

    /**
     * Advance to next token.
     *
     * @return Token Previous token
     */
    private function advance(): Token
    {
        if (!$this->isAtEnd()) {
            $this->current++;
        }

        return $this->previous();
    }

    /**
     * Check if at end of tokens.
     *
     * @return bool True if at end
     */
    private function isAtEnd(): bool
    {
        return $this->peek()->type === Token::TYPE_EOF;
    }

    /**
     * Get current token.
     *
     * @return Token Current token
     */
    private function peek(): Token
    {
        return $this->tokens[$this->current];
    }

    /**
     * Get previous token.
     *
     * @return Token Previous token
     */
    private function previous(): Token
    {
        return $this->tokens[$this->current - 1];
    }

    /**
     * Consume a number token.
     *
     * @return int Number value
     * @throws ParseException If current token is not a number
     */
    private function consumeNumber(): int
    {
        if (!$this->match(Token::TYPE_NUMBER)) {
            throw new ParseException('Expected number', $this->getCurrentPosition());
        }

        return (int)$this->previous()->value;
    }

    /**
     * Consume a specific token type.
     *
     * @param string $type Expected token type
     * @param string|null $message Optional error message
     * @throws ParseException If current token doesn't match expected type
     */
    private function consume(string $type, ?string $message = null): void
    {
        if (!$this->match($type)) {
            $msg = $message ?? "Expected {$type}";
            throw new ParseException($msg, $this->getCurrentPosition());
        }
    }

    /**
     * Get current position in the expression.
     *
     * @return int Position
     */
    private function getCurrentPosition(): int
    {
        return $this->peek()->position ?? 0;
    }
}
