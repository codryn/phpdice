<?php

declare(strict_types=1);

namespace PHPDice\Exception;

use Exception;

/**
 * Exception thrown when parsing a dice expression fails.
 */
class ParseException extends Exception
{
    /**
     * Create a new ParseException.
     *
     * @param string $message Error message describing what went wrong
     * @param int $position Position in the expression where error occurred (0-indexed)
     */
    public function __construct(string $message, private readonly int $position = 0)
    {
        parent::__construct($message);
    }

    /**
     * Get the position in the expression where the error occurred.
     *
     * @return int Position (0-indexed)
     */
    public function getPosition(): int
    {
        return $this->position;
    }
}
