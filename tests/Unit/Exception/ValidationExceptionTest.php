<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Unit\Exception;

use Codryn\PHPDice\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ValidationException.
 *
 * @covers \Codryn\PHPDice\Exception\ValidationException
 */
class ValidationExceptionTest extends TestCase
{
    public function testConstructorWithMessage(): void
    {
        $exception = new ValidationException('Dice count must be positive');

        $this->assertSame('Dice count must be positive', $exception->getMessage());
        $this->assertSame('', $exception->getField());
    }

    public function testConstructorWithField(): void
    {
        $exception = new ValidationException('Invalid value', 'count');

        $this->assertSame('Invalid value', $exception->getMessage());
        $this->assertSame('count', $exception->getField());
    }

    public function testIsException(): void
    {
        $exception = new ValidationException('Test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testGetField(): void
    {
        $exception = new ValidationException('Out of range', 'sides');

        $this->assertSame('sides', $exception->getField());
    }

    public function testFieldDefaultsToEmpty(): void
    {
        $exception = new ValidationException('General validation error');

        $this->assertSame('', $exception->getField());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        throw new ValidationException('Validation failed', 'modifiers');
    }
}
