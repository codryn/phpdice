<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Unit\Model;

use Codryn\PHPDice\Model\RollModifiers;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RollModifiers.
 *
 * @covers \Codryn\PHPDice\Model\RollModifiers
 */
class RollModifiersTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $modifiers = new RollModifiers();

        $this->assertNull($modifiers->arithmeticExpression);
        $this->assertSame(0, $modifiers->arithmeticModifier);
        $this->assertNull($modifiers->advantageCount);
        $this->assertNull($modifiers->keepHighest);
        $this->assertNull($modifiers->keepLowest);
        $this->assertNull($modifiers->successThreshold);
        $this->assertNull($modifiers->successOperator);
        $this->assertNull($modifiers->explosionThreshold);
        $this->assertNull($modifiers->explosionOperator);
        $this->assertSame(100, $modifiers->explosionLimit);
        $this->assertNull($modifiers->rerollThreshold);
        $this->assertNull($modifiers->rerollOperator);
        $this->assertSame(100, $modifiers->rerollLimit);
        $this->assertNull($modifiers->criticalSuccess);
        $this->assertNull($modifiers->criticalFailure);
        $this->assertEmpty($modifiers->resolvedVariables);
    }

    public function testConstructorWithAdvantage(): void
    {
        $modifiers = new RollModifiers(
            advantageCount: 2,
            keepHighest: 2
        );

        $this->assertSame(2, $modifiers->advantageCount);
        $this->assertSame(2, $modifiers->keepHighest);
    }

    public function testConstructorWithSuccessCounting(): void
    {
        $modifiers = new RollModifiers(
            successThreshold: 5,
            successOperator: '>='
        );

        $this->assertSame(5, $modifiers->successThreshold);
        $this->assertSame('>=', $modifiers->successOperator);
    }

    public function testConstructorWithExplosion(): void
    {
        $modifiers = new RollModifiers(
            explosionThreshold: 6,
            explosionOperator: '>=',
            explosionLimit: 10
        );

        $this->assertSame(6, $modifiers->explosionThreshold);
        $this->assertSame('>=', $modifiers->explosionOperator);
        $this->assertSame(10, $modifiers->explosionLimit);
    }

    public function testConstructorWithReroll(): void
    {
        $modifiers = new RollModifiers(
            rerollThreshold: 1,
            rerollOperator: '==',
            rerollLimit: 5
        );

        $this->assertSame(1, $modifiers->rerollThreshold);
        $this->assertSame('==', $modifiers->rerollOperator);
        $this->assertSame(5, $modifiers->rerollLimit);
    }

    public function testConstructorWithArithmeticModifier(): void
    {
        $modifiers = new RollModifiers(arithmeticModifier: 5);

        $this->assertSame(5, $modifiers->arithmeticModifier);
    }

    public function testConstructorWithCriticals(): void
    {
        $modifiers = new RollModifiers(
            criticalSuccess: 20,
            criticalFailure: 1
        );

        $this->assertSame(20, $modifiers->criticalSuccess);
        $this->assertSame(1, $modifiers->criticalFailure);
    }

    public function testConstructorWithResolvedVariables(): void
    {
        $variables = ['bonus' => 3, 'penalty' => -2];
        $modifiers = new RollModifiers(resolvedVariables: $variables);

        $this->assertSame($variables, $modifiers->resolvedVariables);
    }
}
