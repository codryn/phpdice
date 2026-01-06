<?php

declare(strict_types=1);

namespace PHPDice\Exception;

use Exception;

/**
 * Exception thrown when validation of a dice expression fails.
 */
class ValidationException extends Exception
{
    /**
     * Create a new ValidationException.
     *
     * @param string $message Error message describing the validation failure
     * @param string $field Field or component that failed validation
     */
    public function __construct(string $message, private readonly string $field = '')
    {
        parent::__construct($message);
    }

    /**
     * Get the field that failed validation.
     *
     * @return string Field name
     */
    public function getField(): string
    {
        return $this->field;
    }
}
