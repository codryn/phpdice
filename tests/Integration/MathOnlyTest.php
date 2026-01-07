<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ValidationException;
use PHPDice\Model\DiceType;
use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for math-only expressions (no dice).
 */
#[CoversClass(PHPDice::class)]
class MathOnlyTest extends BaseTestCaseMock
{
    /**
     * Test simple addition without dice.
     */
    public function testSimpleAddition(): void
    {
        $result = $this->phpdice->roll('1+1');

        $this->assertEquals(2, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test max function without dice.
     */
    public function testMaxFunction(): void
    {
        $result = $this->phpdice->roll('max(1,2)');

        $this->assertEquals(2, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test min function without dice.
     */
    public function testMinFunction(): void
    {
        $result = $this->phpdice->roll('min(5,3)');

        $this->assertEquals(3, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test max with multiple arguments.
     */
    public function testMaxWithMultipleArguments(): void
    {
        $result = $this->phpdice->roll('max(1,5,3)');

        $this->assertEquals(5, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test round function with addition.
     */
    public function testRoundWithAddition(): void
    {
        $result = $this->phpdice->roll('round(1.6)+1');

        $this->assertEquals(3, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test single constant number.
     */
    public function testSingleConstant(): void
    {
        $result = $this->phpdice->roll('42');

        $this->assertEquals(42, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test complex math expression.
     */
    public function testComplexMathExpression(): void
    {
        $result = $this->phpdice->roll('(5+3)*2');

        $this->assertEquals(16, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test math expression has NONE dice type.
     */
    public function testMathOnlyHasNoneDiceType(): void
    {
        $expression = $this->phpdice->parse('1+1');

        $this->assertSame(DiceType::NONE, $expression->specification->type);
        $this->assertSame(0, $expression->specification->count);
        $this->assertSame(0, $expression->specification->sides);
    }

    /**
     * Test statistics for math-only expressions.
     */
    public function testMathOnlyStatistics(): void
    {
        $expression = $this->phpdice->parse('5+3');
        $stats = $expression->statistics;

        // Math-only expressions have deterministic results
        $this->assertEquals(8, $stats->minimum);
        $this->assertEquals(8, $stats->maximum);
        $this->assertEquals(8, $stats->expected);
    }

    /**
     * Test that empty string still throws error.
     */
    public function testEmptyStringThrowsError(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expression cannot be empty');

        $this->phpdice->roll('');
    }

    /**
     * Test that whitespace-only string throws error.
     */
    public function testWhitespaceOnlyThrowsError(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expression cannot be empty');

        $this->phpdice->roll('   ');
    }

    /**
     * Test subtraction without dice.
     */
    public function testSubtraction(): void
    {
        $result = $this->phpdice->roll('10-3');

        $this->assertEquals(7, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test multiplication without dice.
     */
    public function testMultiplication(): void
    {
        $result = $this->phpdice->roll('6*7');

        $this->assertEquals(42, $result->total);
        $this->assertEmpty($result->diceValues);
    }

    /**
     * Test division without dice.
     */
    public function testDivision(): void
    {
        $result = $this->phpdice->roll('15/3');

        $this->assertEquals(5, $result->total);
        $this->assertEmpty($result->diceValues);
    }
}
