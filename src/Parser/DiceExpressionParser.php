<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser;

use Codryn\PHPDice\Exception\ParseException;
use Codryn\PHPDice\Model\DiceExpression;
use Codryn\PHPDice\Model\DiceSpecification;
use Codryn\PHPDice\Model\RollModifiers;
use Codryn\PHPDice\Model\StatisticalCalculator;
use Codryn\PHPDice\Parser\AST\BinaryOpNode;
use Codryn\PHPDice\Parser\AST\DiceExpressionNode;
use Codryn\PHPDice\Parser\AST\DiceNode;
use Codryn\PHPDice\Parser\AST\FunctionNode;
use Codryn\PHPDice\Parser\AST\GroupNode;
use Codryn\PHPDice\Parser\AST\Node;
use Codryn\PHPDice\Parser\AST\NumberNode;

/**
 * Parses dice expressions into structured DiceExpression objects.
 */
class DiceExpressionParser
{
    /** @var array<string> Dice modifier keywords that can appear after dice notation */
    private const MODIFIER_KEYWORDS = ['advantage', 'disadvantage', 'keep', 'explode', 'reroll', 'edge', 'count', 'success', 'threshold', 'crit', 'glitch', 'auto'];

    /** @var array<int, Token> */
    private array $tokens = [];
    private int $current = 0;
    private ?Node $astRoot = null;
    /** @var array<string, int> Placeholder values */
    private array $variables = [];
    /** @var array<string, int> Track which variables were actually used */
    private array $usedVariables = [];
    /** @var int Group nesting depth (0 = not in group, 1 = in group) */
    private int $groupDepth = 0;

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
        $this->groupDepth = 0; // Reset group depth for each parse

        // Tokenize
        $lexer = new Lexer($expression);
        $this->tokens = $lexer->tokenize();
        $this->current = 0;

        // Parse initial expression to get base AST
        $this->astRoot = $this->parseConditional(); // Start with conditional to support if-then-else

        // Extract dice specification from AST
        $diceNode = $this->findDiceNode($this->astRoot);

        // Handle math-only expressions (no dice notation)
        if ($diceNode === null) {
            // Validate expression is not empty
            $this->validator->validateExpression($expression);

            // For math-only expressions, we don't need dice specification or modifiers
            return $this->createMathOnlyExpression($expression);
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
            $right = $this->parseConditional(); // Support conditionals in dice expressions
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

        // Parse tags if present (must come after DC and before comment)
        $tags = null;
        if ($this->match(Token::TYPE_TAGS)) {
            $tags = $this->parseTags((string)$this->previous()->value);
        }

        // Parse comment if present (must come after all other tokens)
        $comment = null;
        if ($this->match(Token::TYPE_COMMENT)) {
            $comment = $this->expandPlaceholdersInComment((string)$this->previous()->value);
        }

        // Ensure all tokens are consumed
        if (!$this->isAtEnd()) {
            $remaining = $this->peek();
            // Check if it's a duplicate tag section
            if ($remaining->type === Token::TYPE_TAGS) {
                throw new ParseException(
                    'Multiple tag sections are not allowed. Use a single tag section with comma-separated tags: [tag1, tag2, ...]',
                    $remaining->position
                );
            }
            // Check if DC keyword comes after tags (wrong order)
            if ($remaining->type === Token::TYPE_KEYWORD && $remaining->value === 'dc' && $tags !== null) {
                throw new ParseException(
                    'Tags must come after DC comparison. Correct order: expression dc >= N [tags] # comment',
                    $remaining->position
                );
            }
            // Check if it's a duplicate modifier keyword
            if ($remaining->type === Token::TYPE_KEYWORD) {
                throw new \Codryn\PHPDice\Exception\ValidationException(
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
                criticalSuccess: $modifiers->criticalSuccess,
                criticalFailure: $modifiers->criticalFailure,
                autoSuccess: $modifiers->autoSuccess,
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
            comparisonThreshold: $comparisonThreshold,
            comment: $comment,
            tags: $tags
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
     * Create a DiceExpression for math-only expressions (no dice).
     *
     * @param string $expression Original expression
     * @return DiceExpression Expression for pure math
     */
    private function createMathOnlyExpression(string $expression): DiceExpression
    {
        // Continue parsing the rest of the expression (arithmetic operators)
        // astRoot already contains the parsed conditional/comparison/expression
        while ($this->match(Token::TYPE_OPERATOR, ['+', '-'])) {
            $operator = $this->previous()->value;
            $right = $this->parseConditional(); // Support conditionals in math expressions
            if ($this->astRoot === null) {
                throw new ParseException(
                    'Invalid expression: missing left operand',
                    $this->getCurrentPosition()
                );
            }
            $this->astRoot = new BinaryOpNode($this->astRoot, (string)$operator, $right);
        }

        // Parse tags if present (must come before comment)
        $tags = null;
        if ($this->match(Token::TYPE_TAGS)) {
            $tags = $this->parseTags((string)$this->previous()->value);
        }

        // Parse comment if present (must come after all other tokens)
        $comment = null;
        if ($this->match(Token::TYPE_COMMENT)) {
            $comment = $this->expandPlaceholdersInComment((string)$this->previous()->value);
        }

        // Ensure all tokens are consumed
        if (!$this->isAtEnd()) {
            $remaining = $this->peek();
            throw new ParseException(
                "Unexpected token: {$remaining->type} '{$remaining->value}'",
                $this->getCurrentPosition()
            );
        }

        if ($this->astRoot === null) {
            throw new ParseException(
                'Invalid expression: empty or incomplete expression',
                0
            );
        }

        // Calculate statistics from AST (for math-only expressions)
        $statistics = $this->calculator->calculateFromAst($this->astRoot);

        // Create empty modifiers (no dice-specific modifiers for math-only expressions)
        $modifiers = new RollModifiers();

        // Build expression with null specification (math-only)
        return new DiceExpression(
            specification: null,
            modifiers: $modifiers,
            statistics: $statistics,
            originalExpression: $expression,
            astRoot: $this->astRoot,
            comparisonOperator: null,
            comparisonThreshold: null,
            comment: $comment,
            tags: $tags
        );
    }

    /**
     * Parse a conditional expression (if condition: trueBranch | falseBranch).
     * This is the highest precedence level.
     *
     * @return Node Conditional or comparison node
     */
    private function parseConditional(): Node
    {
        // Check for 'if' keyword
        if ($this->match(Token::TYPE_KEYWORD, ['if'])) {
            // Parse the condition (comparison expression)
            $condition = $this->parseComparison();

            // Expect colon
            if (!$this->match(Token::TYPE_COLON)) {
                throw new ParseException(
                    "Expected ':' after if condition",
                    $this->getCurrentPosition()
                );
            }

            // Parse true branch
            $trueBranch = $this->parseComparison();

            // Expect pipe
            if (!$this->match(Token::TYPE_PIPE)) {
                throw new ParseException(
                    "Expected '|' to separate true and false branches in if expression",
                    $this->getCurrentPosition()
                );
            }

            // Parse false branch
            $falseBranch = $this->parseComparison();

            return new \Codryn\PHPDice\Parser\AST\ConditionalNode($condition, $trueBranch, $falseBranch);
        }

        // Not a conditional, parse as comparison
        return $this->parseComparison();
    }

    /**
     * Parse a comparison expression (left > right, left >= right, etc.).
     *
     * @return Node Comparison or expression node
     */
    private function parseComparison(): Node
    {
        $node = $this->parseExpression();

        // Check for comparison operators
        if ($this->check(Token::TYPE_COMPARISON)) {
            // Peek ahead to see if this is part of a conditional or a standalone comparison
            // We need to handle comparisons in conditionals, not DC checks
            $operator = (string)$this->advance()->value;
            $right = $this->parseExpression();
            return new \Codryn\PHPDice\Parser\AST\ComparisonNode($node, $operator, $right);
        }

        return $node;
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

        while ($this->match(Token::TYPE_OPERATOR, ['*', '/', '%', '^'])) {
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
            $expr = $this->parseConditional(); // Support conditionals in parentheses
            $this->consume(Token::TYPE_RPAREN, 'Expected closing parenthesis');
            return $expr;
        }

        // Grouped expression { expression # comment }
        if ($this->match(Token::TYPE_LBRACE)) {
            return $this->parseGroup();
        }

        // Dice notation (XdY) - Check for modifiers and wrap in DiceExpressionNode if present
        if ($this->check(Token::TYPE_NUMBER) && $this->checkNext(Token::TYPE_DICE)) {
            $count = $this->consumeNumber();
            $diceToken = $this->advance();
            $diceValue = (string)$diceToken->value;

            // Check for special dice types
            $diceNode = null;
            if ($diceValue === 'dF') {
                // Fudge dice: count is specified, sides is always 3 (representing -1, 0, +1)
                $diceNode = new DiceNode($count, 3, \Codryn\PHPDice\Model\DiceType::FUDGE);
            } elseif ($diceValue === 'd%') {
                // Percentile dice: count is specified, sides is always 100
                $diceNode = new DiceNode($count, 100, \Codryn\PHPDice\Model\DiceType::PERCENTILE);
            } elseif ($diceValue === 'C') {
                // Coin dice: count is specified, sides is always 2 (representing 0, 1)
                $diceNode = new DiceNode($count, 2, \Codryn\PHPDice\Model\DiceType::COIN);
            } else {
                // Standard dice: get the sides
                $sides = $this->consumeNumber();
                $diceNode = new DiceNode($count, $sides);
            }

            // Check if modifiers follow (for use in function arguments)
            return $this->tryParseModifiersForDiceNode($diceNode);
        }

        // Standalone d% or dF (equivalent to 1d% or 1dF)
        if ($this->check(Token::TYPE_DICE)) {
            $diceToken = $this->peek();
            $diceValue = (string)$diceToken->value;

            $diceNode = null;
            if ($diceValue === 'd%') {
                $this->advance(); // Consume d%
                $diceNode = new DiceNode(1, 100, \Codryn\PHPDice\Model\DiceType::PERCENTILE);
            } elseif ($diceValue === 'dF') {
                $this->advance(); // Consume dF
                $diceNode = new DiceNode(1, 3, \Codryn\PHPDice\Model\DiceType::FUDGE);
            } elseif ($diceValue === 'C') {
                $this->advance(); // Consume C
                $diceNode = new DiceNode(1, 2, \Codryn\PHPDice\Model\DiceType::COIN);
            }

            // Check if modifiers follow (for use in function arguments)
            if ($diceNode !== null) {
                return $this->tryParseModifiersForDiceNode($diceNode);
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

        // Parse first argument
        $arguments = [$this->parseExpression()];

        // Parse additional arguments if comma is present
        while ($this->match(Token::TYPE_COMMA)) {
            $arguments[] = $this->parseExpression();
        }

        $this->consume(Token::TYPE_RPAREN, 'Expected closing parenthesis after function argument');

        // Pass single argument directly for backward compatibility, or array for multiple arguments
        return new FunctionNode($functionName, count($arguments) === 1 ? $arguments[0] : $arguments);
    }

    /**
     * Try to parse modifiers after a dice node. If modifiers are found,
     * wrap the dice node in a DiceExpressionNode. Otherwise, return the plain dice node.
     *
     * This method only parses modifiers when inside a function argument context.
     * For top-level expressions, modifiers are parsed separately by the parse() method.
     *
     * @param DiceNode $diceNode The dice node to potentially wrap
     * @return Node Either the original DiceNode or a DiceExpressionNode with modifiers
     */
    private function tryParseModifiersForDiceNode(DiceNode $diceNode): Node
    {
        // Check if the next token is a modifier keyword
        // We need to be careful not to consume tokens that belong to the parent expression
        // Only parse modifiers if we see a modifier keyword followed by something that indicates
        // we're in a function context (comma or closing parenthesis)

        if ($this->check(Token::TYPE_KEYWORD)) {
            $keyword = (string)$this->peek()->value;
            if (in_array($keyword, self::MODIFIER_KEYWORDS, true)) {
                // Look ahead to see if there's a comma or closing paren later
                // This indicates we're in a function argument context
                $hasCommaOrRParenAhead = false;
                $lookahead = $this->current + 1;
                while ($lookahead < count($this->tokens)) {
                    $token = $this->tokens[$lookahead];
                    if ($token->type === Token::TYPE_COMMA || $token->type === Token::TYPE_RPAREN) {
                        $hasCommaOrRParenAhead = true;
                        break;
                    }
                    // Stop looking if we hit EOF or an operator that suggests top-level expression
                    if ($token->type === Token::TYPE_EOF) {
                        break;
                    }
                    // If we see 'dc' keyword, we're likely in a top-level expression with comparison
                    // (dc = "difficulty class", used for success rolls like "1d20+5 dc >= 15")
                    if ($token->type === Token::TYPE_KEYWORD && $token->value === 'dc') {
                        break;
                    }
                    $lookahead++;
                }

                if ($hasCommaOrRParenAhead) {
                    // We're in a function argument - parse modifiers and wrap
                    $spec = new DiceSpecification(
                        count: $diceNode->getCount(),
                        sides: $diceNode->getSides(),
                        type: $diceNode->getType()
                    );

                    $modifiers = $this->parseModifiers($spec);
                    return new DiceExpressionNode($diceNode, $modifiers);
                }
            }
        }

        // No modifiers or not in function context - return plain dice node
        return $diceNode;
    }

    /**
     * Parse modifiers like advantage, disadvantage, keep.
     *
     * Order: explode/reroll/edge -> keep -> count -> dc
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
                throw new \Codryn\PHPDice\Exception\ValidationException(
                    'Cannot have both advantage and disadvantage',
                    'modifiers'
                );
            }
            // Roll spec->count extra dice, keep spec->count lowest
            $advantageCount = $spec->count;
            $keepLowest = $spec->count;
        }

        // STEP 1: Parse explode/reroll/edge (must come before keep)
        $rerollThreshold = null;
        $rerollOperator = null;
        $rerollLimit = 100; // Default limit
        $explosionThreshold = null;
        $explosionOperator = null;
        $explosionLimit = 100; // Default limit

        // Check for explode: "explode [limit] [operator threshold]"
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
                    throw new \Codryn\PHPDice\Exception\ValidationException(
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

        // Check for reroll: "reroll [limit] operator threshold"
        if ($this->match(Token::TYPE_KEYWORD, ['reroll'])) {
            // Validate: cannot combine explode and reroll on same dice
            if ($explosionThreshold !== null) {
                throw new \Codryn\PHPDice\Exception\ValidationException(
                    'Cannot combine explode and reroll on the same dice',
                    'modifiers'
                );
            }

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
                throw new \Codryn\PHPDice\Exception\ValidationException(
                    "Invalid reroll operator '{$rerollOperator}'",
                    'reroll'
                );
            }

            // Get threshold value
            $rerollThreshold = $this->consumeNumber();

            // Validate reroll range doesn't cover entire die (FR-005b)
            $this->validator->validateRerollRange($spec, $rerollThreshold, $rerollOperator);
        }

        // Check for edge: "edge [limit] [operator threshold]"
        $edgeThreshold = null;
        $edgeOperator = null;
        $edgeLimit = 100; // Default limit

        if ($this->match(Token::TYPE_KEYWORD, ['edge'])) {
            // Validate: cannot combine edge with explode or reroll
            if ($explosionThreshold !== null) {
                throw new \Codryn\PHPDice\Exception\ValidationException(
                    'Cannot combine edge and explode on the same dice',
                    'modifiers'
                );
            }
            if ($rerollThreshold !== null) {
                throw new \Codryn\PHPDice\Exception\ValidationException(
                    'Cannot combine edge and reroll on the same dice',
                    'modifiers'
                );
            }

            // Check for optional limit number
            if ($this->check(Token::TYPE_NUMBER)) {
                $nextPos = $this->current + 1;
                // Peek ahead to see if the next token after the number is a comparison operator or EOF
                $hasComparison = ($nextPos < count($this->tokens) && $this->tokens[$nextPos]->type === Token::TYPE_COMPARISON);

                if ($hasComparison) {
                    // This number is the limit
                    $edgeLimit = $this->consumeNumber();
                } else {
                    // This number might be the limit, check if we're at end or next is keyword
                    $nextIsEnd = ($nextPos >= count($this->tokens) || $this->tokens[$nextPos]->type === Token::TYPE_EOF);
                    $nextIsKeyword = (!$nextIsEnd && $this->tokens[$nextPos]->type === Token::TYPE_KEYWORD);

                    if ($nextIsEnd || $nextIsKeyword) {
                        // This number is the limit with no threshold
                        $edgeLimit = $this->consumeNumber();
                    }
                }
            }

            // Check for optional comparison operator and threshold
            if ($this->check(Token::TYPE_COMPARISON)) {
                $comparison = $this->advance();
                $edgeOperator = (string)$comparison->value;

                // Validate operator (only >= and <= allowed for edge per spec)
                if (!in_array($edgeOperator, ['>=', '<='], true)) {
                    throw new \Codryn\PHPDice\Exception\ValidationException(
                        "Invalid edge operator '{$edgeOperator}'. Only >= and <= are supported for edge dice.",
                        'edge'
                    );
                }

                // Get threshold value
                $edgeThreshold = $this->consumeNumber();

                // Validate edge range doesn't cover entire die
                $this->validator->validateEdgeRange($spec, $edgeThreshold, $edgeOperator);
            } else {
                // No threshold specified - default to maximum die value
                $edgeThreshold = $spec->sides;
                $edgeOperator = '>=';

                // Validate this doesn't create infinite loop (single-sided die)
                $this->validator->validateEdgeRange($spec, $edgeThreshold, $edgeOperator);
            }
        }

        // STEP 2: Parse keep (must come after explode/reroll/edge)
        // Check for keep X highest/lowest
        if ($this->match(Token::TYPE_KEYWORD, ['keep'])) {
            $count = $this->consumeNumber();

            if ($this->match(Token::TYPE_KEYWORD, ['highest'])) {
                if ($keepHighest !== null || $keepLowest !== null) {
                    throw new \Codryn\PHPDice\Exception\ValidationException(
                        'Cannot specify keep multiple times',
                        'modifiers'
                    );
                }
                $keepHighest = $count;
            } elseif ($this->match(Token::TYPE_KEYWORD, ['lowest'])) {
                if ($keepHighest !== null || $keepLowest !== null) {
                    throw new \Codryn\PHPDice\Exception\ValidationException(
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
                throw new \Codryn\PHPDice\Exception\ValidationException(
                    "Cannot keep {$keepHighest} dice when only rolling {$totalDiceToRoll}",
                    'keep'
                );
            }
            if ($keepLowest !== null && $keepLowest > $totalDiceToRoll) {
                throw new \Codryn\PHPDice\Exception\ValidationException(
                    "Cannot keep {$keepLowest} dice when only rolling {$totalDiceToRoll}",
                    'keep'
                );
            }
        }

        // STEP 3: Parse count (success counting - must come after keep)
        // Check for success counting: "count >=N", "count >N", "count even", "count odd",
        // or "success threshold N" (legacy)
        if ($this->match(Token::TYPE_KEYWORD, ['count'])) {
            // After 'count', we expect either a comparison operator, 'even', or 'odd'
            if ($this->match(Token::TYPE_KEYWORD, ['even', 'odd'])) {
                // Handle 'count even' or 'count odd'
                $successOperator = (string)$this->previous()->value;
                $successThreshold = null; // Not used for even/odd
            } elseif ($this->check(Token::TYPE_COMPARISON)) {
                // Handle 'count >=N' style
                $comparison = $this->advance();
                $operator = (string)$comparison->value;

                // Allow all comparison operators for success counting
                if (!in_array($operator, ['>=', '>', '<=', '<', '=='], true)) {
                    throw new \Codryn\PHPDice\Exception\ValidationException(
                        "Invalid success operator '{$operator}'. " .
                        'Only >=, >, <=, <, and == are supported for success counting.',
                        'success'
                    );
                }

                $successOperator = $operator;
                $successThreshold = $this->consumeNumber();
            } else {
                throw new ParseException(
                    "Expected comparison operator, 'even', or 'odd' after 'count' keyword",
                    $this->peek()->position
                );
            }
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

        // Check for auto success: "auto N"
        $autoSuccess = null;
        if ($this->match(Token::TYPE_KEYWORD, ['auto'])) {
            $autoSuccess = $this->consumeNumber();

            // Validate auto threshold is within die range
            $this->validator->validateCriticalThreshold($spec, $autoSuccess, 'auto');
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
            edgeThreshold: $edgeThreshold,
            edgeOperator: $edgeOperator,
            edgeLimit: $edgeLimit,
            criticalSuccess: $criticalSuccess,
            criticalFailure: $criticalFailure,
            autoSuccess: $autoSuccess,
            resolvedVariables: $this->usedVariables
        );
    }

    /**
     * Find the first dice node in the AST that isn't already wrapped in a DiceExpressionNode.
     *
     * @param Node $node Node to search
     * @return DiceNode|null Dice node if found
     */
    private function findDiceNode(Node $node): ?DiceNode
    {
        if ($node instanceof DiceNode) {
            return $node;
        }

        // Don't look inside DiceExpressionNode - those dice already have their modifiers
        // and should be treated as complete sub-expressions
        if ($node instanceof DiceExpressionNode) {
            return null;
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->findDiceNode($node->getLeft());
            if ($left !== null) {
                return $left;
            }
            return $this->findDiceNode($node->getRight());
        }

        if ($node instanceof FunctionNode) {
            // Check all arguments for dice
            foreach ($node->getArguments() as $argument) {
                $found = $this->findDiceNode($argument);
                if ($found !== null) {
                    return $found;
                }
            }
            return null;
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

    /**
     * Expand placeholders in a comment string to their numeric values.
     *
     * @param string $comment Comment string with possible placeholders
     * @return string Comment with placeholders expanded
     */
    private function expandPlaceholdersInComment(string $comment): string
    {
        // Replace all $variable$ placeholders with their resolved values
        $result = preg_replace_callback(
            '/\$([a-zA-Z0-9_.]+)\$/',
            function ($matches) {
                $variableName = $matches[1];
                if (array_key_exists($variableName, $this->variables)) {
                    return (string)$this->variables[$variableName];
                }
                // If variable not found, leave placeholder as-is
                return $matches[0];
            },
            $comment
        );

        return $result ?? $comment;
    }

    /**
     * Parse a group expression { expression [tag1, tag2] # comment }.
     *
     * @return GroupNode Group node
     * @throws ParseException If group is nested or empty
     */
    private function parseGroup(): GroupNode
    {
        // Check for nested groups
        if ($this->groupDepth > 0) {
            throw new ParseException(
                'Groups cannot be nested',
                $this->getCurrentPosition()
            );
        }

        $this->groupDepth++;

        // Parse the expression inside the group
        $expr = $this->parseExpression();

        // Check if there's an empty expression
        // Parse optional tags
        $tags = null;
        if ($this->match(Token::TYPE_TAGS)) {
            $tags = $this->parseTags((string)$this->previous()->value);
        }

        // Check for duplicate tag section before parsing comment
        if ($this->check(Token::TYPE_TAGS)) {
            throw new ParseException(
                'Multiple tag sections are not allowed in a group. Use a single tag section with comma-separated tags: [tag1, tag2, ...]',
                $this->peek()->position
            );
        }

        // Parse optional comment
        $comment = null;
        if ($this->match(Token::TYPE_COMMENT)) {
            $comment = $this->expandPlaceholdersInComment((string)$this->previous()->value);
        }

        // Expect closing brace
        $this->consume(Token::TYPE_RBRACE, 'Expected closing brace }');

        $this->groupDepth--;

        return new GroupNode($expr, $comment, $tags);
    }

    /**
     * Parse tags from tag content string.
     * Tags are case-insensitive and can contain a-z, 0-9, ., -, _
     * Returns lowercase normalized tag array.
     *
     * @param string $content Raw tag content (e.g., "tag1, tag2, tag3")
     * @return array<string> Array of tags
     * @throws ParseException If tag syntax is invalid
     */
    private function parseTags(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        // Split by comma
        $rawTags = explode(',', $content);
        $tags = [];

        foreach ($rawTags as $rawTag) {
            $tag = trim($rawTag);
            if ($tag !== '') {
                $tags[] = $this->normalizeTag($tag);
            }
        }

        return $tags;
    }

    /**
     * Normalize a tag to lowercase and validate characters.
     *
     * @param string $tag Raw tag string
     * @return string Normalized tag
     * @throws ParseException If tag contains invalid characters
     */
    private function normalizeTag(string $tag): string
    {
        $tag = trim($tag);
        $tag = strtolower($tag);

        // Validate: only a-z, 0-9, ., -, _
        if (!preg_match('/^[a-z0-9._-]+$/', $tag)) {
            throw new ParseException(
                "Invalid tag '{$tag}': tags can only contain a-z, 0-9, ., -, _",
                $this->getCurrentPosition()
            );
        }

        return $tag;
    }
}
