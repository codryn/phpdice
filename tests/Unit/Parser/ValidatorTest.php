<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Parser;

use PHPDice\Exception\ValidationException;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPDice\Parser\Validator;
use PHPDice\Tests\Unit\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for Validator.
 *
 * @covers \PHPDice\Parser\Validator
 */
class ValidatorTest extends BaseTestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    /**
     * Test valid dice specification passes validation.
     */
    public function testValidDiceSpecification(): void
    {
        $spec = new DiceSpecification(3, 6, DiceType::STANDARD);

        $this->validator->validateDiceSpecification($spec);

        // If we get here without exception, validation passed
        $this->assertTrue(true);
    }

    /**
     * Test FR-027: Dice count must be >= 1.
     */
    public function testDiceCountMustBePositive(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Dice count must be at least 1');

        $spec = new DiceSpecification(0, 6, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);
    }

    /**
     * Test FR-027: Negative dice count.
     */
    public function testNegativeDiceCount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Dice count must be at least 1');

        $spec = new DiceSpecification(-1, 6, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);
    }

    /**
     * Test FR-028: Sides must be >= 2.
     */
    public function testSidesMustBeAtLeastTwo(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Dice must have at least 2 sides');

        $spec = new DiceSpecification(3, 1, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);
    }

    /**
     * Test FR-028: Zero sides.
     */
    public function testZeroSides(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Dice must have at least 2 sides');

        $spec = new DiceSpecification(3, 0, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);
    }

    /**
     * Test FR-029: Maximum 100 dice total.
     */
    public function testMaximumDiceCount(): void
    {
        $spec = new DiceSpecification(100, 6, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);

        // 100 should be OK
        $this->assertTrue(true);
    }

    /**
     * Test FR-029: More than 100 dice fails.
     */
    public function testTooManyDice(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot roll more than 100 dice');

        $spec = new DiceSpecification(101, 6, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);
    }

    /**
     * Test FR-030: Maximum 100 sides per die.
     */
    public function testMaximumSides(): void
    {
        $spec = new DiceSpecification(3, 100, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);

        // 100 should be OK
        $this->assertTrue(true);
    }

    /**
     * Test FR-030: More than 100 sides fails.
     */
    public function testTooManySides(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Dice cannot have more than 100 sides');

        $spec = new DiceSpecification(3, 101, DiceType::STANDARD);
        $this->validator->validateDiceSpecification($spec);
    }

    /**
     * Test FR-026: Valid expression format.
     */
    public function testValidExpression(): void
    {
        $this->validator->validateExpression('3d6');

        // If we get here without exception, validation passed
        $this->assertTrue(true);
    }

    /**
     * Test FR-026: Empty expression fails.
     */
    public function testEmptyExpression(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expression cannot be empty');

        $this->validator->validateExpression('');
    }

    /**
     * Test FR-033: Parenthesis matching validation.
     */
    public function testParenthesesMatching(): void
    {
        $this->validator->validateParentheses('(3d6+5)');
        $this->assertTrue(true);
    }

    /**
     * Test FR-033: Unmatched opening parenthesis.
     */
    public function testUnmatchedOpeningParenthesis(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unmatched opening parenthesis');

        $this->validator->validateParentheses('(3d6');
    }

    /**
     * Test FR-033: Unmatched closing parenthesis.
     */
    public function testUnmatchedClosingParenthesis(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unmatched closing parenthesis');

        $this->validator->validateParentheses('3d6)');
    }
}
