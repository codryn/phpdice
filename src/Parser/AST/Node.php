<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

/**
 * Base class for AST nodes.
 */
abstract class Node
{
    /**
     * Evaluate this node and return its numeric value.
     *
     * @return int|float Evaluated result
     */
    abstract public function evaluate(): int|float;
}
