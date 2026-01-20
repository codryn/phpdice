<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser;

use Codryn\PHPDice\Exception\ParseException;

/**
 * Tokenizes dice expressions into a stream of tokens.
 */
class Lexer
{
    private int $position = 0;
    private int $length;

    /**
     * Create a new lexer.
     *
     * @param string $input Dice expression to tokenize
     */
    public function __construct(private readonly string $input)
    {
        $this->length = strlen($input);
    }

    /**
     * Get all tokens from the input.
     *
     * @return array<Token> Array of tokens
     * @throws ParseException If invalid syntax is encountered
     */
    public function tokenize(): array
    {
        $tokens = [];

        while ($this->position < $this->length) {
            $this->skipWhitespace();

            if ($this->position >= $this->length) {
                break;
            }

            $char = $this->input[$this->position];

            // Numbers
            if (ctype_digit($char)) {
                $tokens[] = $this->readNumber();
                continue;
            }

            // Placeholders ($name$)
            if ($char === '$') {
                $tokens[] = $this->readPlaceholder();
                continue;
            }

            // Keywords and function names (letters)
            if (ctype_alpha($char)) {
                $tokens[] = $this->readKeywordOrFunction();
                continue;
            }

            // Comparison operators (>=, >, <=, <, ==)
            if ($char === '>' || $char === '<' || $char === '=') {
                $tokens[] = $this->readComparison();
                continue;
            }

            // Operators
            if (in_array($char, ['+', '-', '*', '/', '%', '^'], true)) {
                $tokens[] = new Token(Token::TYPE_OPERATOR, $char, $this->position);
                $this->position++;
                continue;
            }

            // Parentheses
            if ($char === '(') {
                $tokens[] = new Token(Token::TYPE_LPAREN, '(', $this->position);
                $this->position++;
                continue;
            }

            if ($char === ')') {
                $tokens[] = new Token(Token::TYPE_RPAREN, ')', $this->position);
                $this->position++;
                continue;
            }

            // Braces (for groups)
            if ($char === '{') {
                $tokens[] = new Token(Token::TYPE_LBRACE, '{', $this->position);
                $this->position++;
                continue;
            }

            if ($char === '}') {
                $tokens[] = new Token(Token::TYPE_RBRACE, '}', $this->position);
                $this->position++;
                continue;
            }

            // Comma (for function arguments)
            if ($char === ',') {
                $tokens[] = new Token(Token::TYPE_COMMA, ',', $this->position);
                $this->position++;
                continue;
            }

            // Brackets (for tags) - read entire tag content
            if ($char === '[') {
                $tokens[] = $this->readTags();
                continue;
            }

            // Comment (# followed by everything until end of input)
            if ($char === '#') {
                $tokens[] = $this->readComment();
                continue;
            }

            // Colon (for conditionals)
            if ($char === ':') {
                $tokens[] = new Token(Token::TYPE_COLON, ':', $this->position);
                $this->position++;
                continue;
            }

            // Pipe (for conditional branches)
            if ($char === '|') {
                $tokens[] = new Token(Token::TYPE_PIPE, '|', $this->position);
                $this->position++;
                continue;
            }

            // Exclamation mark (for != operator)
            if ($char === '!') {
                if ($this->position + 1 < $this->length && $this->input[$this->position + 1] === '=') {
                    $tokens[] = new Token(Token::TYPE_COMPARISON, '!=', $this->position);
                    $this->position += 2;
                    continue;
                }
                // Standalone ! is not supported
                throw new ParseException("Unexpected character '!'", $this->position);
            }

            // Unknown character
            throw new ParseException("Unexpected character '{$char}'", $this->position);
        }

        $tokens[] = new Token(Token::TYPE_EOF, null, $this->position);

        return $tokens;
    }

    /**
     * Skip whitespace characters.
     */
    private function skipWhitespace(): void
    {
        while ($this->position < $this->length && ctype_space($this->input[$this->position])) {
            $this->position++;
        }
    }

    /**
     * Read a number token.
     *
     * @return Token Number token
     */
    private function readNumber(): Token
    {
        $start = $this->position;
        $number = '';
        $hasDecimal = false;

        while ($this->position < $this->length && ctype_digit($this->input[$this->position])) {
            $number .= $this->input[$this->position];
            $this->position++;
        }

        // Check for decimal point
        if ($this->position < $this->length && $this->input[$this->position] === '.') {
            // Look ahead to ensure there's a digit after the decimal point
            if ($this->position + 1 < $this->length && ctype_digit($this->input[$this->position + 1])) {
                $hasDecimal = true;
                $number .= '.';
                $this->position++; // Consume the decimal point

                // Read decimal digits
                while ($this->position < $this->length && ctype_digit($this->input[$this->position])) {
                    $number .= $this->input[$this->position];
                    $this->position++;
                }
            }
        }

        return new Token(Token::TYPE_NUMBER, $hasDecimal ? (float)$number : (int)$number, $start);
    }

    /**
     * Read a keyword or function name.
     *
     * @return Token Keyword or function token
     */
    private function readKeywordOrFunction(): Token
    {
        $start = $this->position;
        $text = '';

        while ($this->position < $this->length && ctype_alpha($this->input[$this->position])) {
            $text .= $this->input[$this->position];
            $this->position++;
        }

        $lower = strtolower($text);

        // Check for dF (fudge dice) - must check before 'd' alone
        if ($lower === 'df') {
            return new Token(Token::TYPE_DICE, 'dF', $start);
        }

        // Check for C (coin flip dice)
        if ($lower === 'c') {
            return new Token(Token::TYPE_DICE, 'C', $start);
        }

        // Check if it's 'd' for dice notation (might be d%)
        if ($lower === 'd') {
            // Check for d% (percentile dice)
            if ($this->position < $this->length && $this->input[$this->position] === '%') {
                $this->position++; // Consume '%'
                return new Token(Token::TYPE_DICE, 'd%', $start);
            }
            // Regular d notation
            return new Token(Token::TYPE_DICE, 'd', $start);
        }

        // Check if it's a known function
        $functions = ['floor', 'ceil', 'round', 'abs', 'min', 'max'];
        if (in_array($lower, $functions, true)) {
            return new Token(Token::TYPE_FUNCTION, $lower, $start);
        }

        // Check for advantage/disadvantage/success/reroll/explode/edge/critical/dc/if/switch keywords
        $keywords = ['advantage', 'disadvantage', 'keep', 'highest', 'lowest', 'success', 'threshold', 'reroll', 'explode', 'edge', 'crit', 'glitch', 'dc', 'count', 'auto', 'even', 'odd', 'if', 'switch', 'case', 'default', 'is', 'null'];
        if (in_array($lower, $keywords, true)) {
            return new Token(Token::TYPE_KEYWORD, $lower, $start);
        }

        // Otherwise it's an unknown keyword
        return new Token(Token::TYPE_KEYWORD, $lower, $start);
    }

    /**
     * Read a placeholder variable ($name$).
     *
     * @return Token Placeholder token
     * @throws ParseException If placeholder syntax is invalid
     */
    private function readPlaceholder(): Token
    {
        $start = $this->position;
        $this->position++; // Skip opening $

        if ($this->position >= $this->length) {
            throw new ParseException('Incomplete placeholder: expected variable name after $', $start);
        }

        // Read variable name (must be letters/digits/underscore)
        $name = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ($char === '$') {
                // End of placeholder
                $this->position++; // Skip closing $

                if ($name === '') {
                    throw new ParseException('Empty placeholder name: $$', $start);
                }

                return new Token(Token::TYPE_PLACEHOLDER, $name, $start);
            }

            if (ctype_alnum($char) || $char === '_' || $char === '.') {
                $name .= $char;
                $this->position++;
            } else {
                throw new ParseException("Invalid character '{$char}' in placeholder name", $this->position);
            }
        }

        // Reached end of input without finding closing $
        throw new ParseException('Unclosed placeholder: missing closing $', $start);
    }

    /**
     * Read a comment (# followed by text until end of input or closing brace).
     *
     * @return Token Comment token
     */
    private function readComment(): Token
    {
        $start = $this->position;
        $this->position++; // Skip opening #

        // Read until end of input or closing brace
        $comment = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            // Stop at closing brace (for group comments)
            if ($char === '}') {
                break;
            }

            $comment .= $char;
            $this->position++;
        }

        // Trim leading and trailing whitespace from comment
        $comment = trim($comment);

        return new Token(Token::TYPE_COMMENT, $comment, $start);
    }

    /**
     * Read tags [ tag1, tag2, tag3 ].
     * Reads the entire content between brackets as a single string.
     *
     * @return Token Tags token
     * @throws ParseException If tags are not closed
     */
    private function readTags(): Token
    {
        $start = $this->position;
        $this->position++; // Skip opening [

        // Read until closing bracket
        $content = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            // Found closing bracket
            if ($char === ']') {
                $this->position++; // Consume ]
                return new Token(Token::TYPE_TAGS, $content, $start);
            }

            $content .= $char;
            $this->position++;
        }

        // Reached end of input without finding closing bracket
        throw new ParseException('Unclosed tag: missing closing ]', $start);
    }

    /**
     * Read a comparison operator (>=, >, <=, <).
     *
     * @return Token Comparison token
     */
    private function readComparison(): Token
    {
        $start = $this->position;
        $operator = $this->input[$this->position];
        $this->position++;

        // Check for two-character operators (>=, <=, ==)
        if ($this->position < $this->length && $this->input[$this->position] === '=') {
            $operator .= '=';
            $this->position++;
        }

        return new Token(Token::TYPE_COMPARISON, $operator, $start);
    }
}
