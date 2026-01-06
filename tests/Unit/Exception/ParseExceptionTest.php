<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Exception;

use PHPDice\Exception\ParseException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ParseException.
 *
 * @covers \PHPDice\Exception\ParseException
 */
class ParseExceptionTest extends TestCase
{
    public function testConstructorWithMessage(): void
    {
        $exception = new ParseException('Invalid dice notation');

        $this->assertSame('Invalid dice notation', $exception->getMessage());
        $this->assertSame(0, $exception->getPosition());
    }

    public function testConstructorWithPosition(): void
    {
        $exception = new ParseException('Unexpected token', 5);

        $this->assertSame('Unexpected token', $exception->getMessage());
        $this->assertSame(5, $exception->getPosition());
    }

    public function testIsException(): void
    {
        $exception = new ParseException('Test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testGetPosition(): void
    {
        $exception = new ParseException('Error at position 10', 10);

        $this->assertSame(10, $exception->getPosition());
    }

    public function testPositionDefaultsToZero(): void
    {
        $exception = new ParseException('No position specified');

        $this->assertSame(0, $exception->getPosition());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Parse error');

        throw new ParseException('Parse error', 3);
    }
}
