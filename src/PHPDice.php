<?php

declare(strict_types=1);

namespace PHPDice;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\RollResult;
use PHPDice\Parser\DiceExpressionParser;
use PHPDice\Roller\DiceRoller;
use PHPDice\Roller\RandomNumberGenerator;

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
     * Roll dice based on an expression.
     *
     * @param string $expression Dice expression (e.g., "3d6", "1d20+5")
     * @param array<string, int> $variables Optional placeholder variables
     * @return RollResult Roll result with total and individual values
     */
    public function roll(string $expression, array $variables = []): RollResult
    {
        $parsed = $this->parse($expression, $variables);
        $ast = $this->parser->getAstRoot();
        return $this->roller->roll($parsed, $ast);
    }
}
