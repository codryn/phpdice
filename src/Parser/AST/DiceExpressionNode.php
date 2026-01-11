<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Parser\AST;

use Codryn\PHPDice\Model\DiceSpecification;
use Codryn\PHPDice\Model\RollModifiers;

/**
 * Represents a complete dice expression with modifiers in the AST.
 * This allows dice expressions like "12d6 count >4" to be used as function arguments.
 */
class DiceExpressionNode extends Node
{
    private int|float $rollResult = 0;

    public function __construct(
        private readonly DiceNode $diceNode,
        private readonly RollModifiers $modifiers
    ) {
    }

    public function evaluate(): int|float
    {
        return $this->rollResult;
    }

    public function setRollResult(int|float $result): void
    {
        $this->rollResult = $result;
    }

    public function getDiceNode(): DiceNode
    {
        return $this->diceNode;
    }

    public function getModifiers(): RollModifiers
    {
        return $this->modifiers;
    }

    /**
     * Create a DiceSpecification from the embedded DiceNode.
     */
    public function getSpecification(): DiceSpecification
    {
        return new DiceSpecification(
            count: $this->diceNode->getCount(),
            sides: $this->diceNode->getSides(),
            type: $this->diceNode->getType()
        );
    }
}
