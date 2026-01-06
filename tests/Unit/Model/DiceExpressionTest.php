<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Model;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\StatisticalData;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DiceExpression.
 *
 * @covers \PHPDice\Model\DiceExpression
 */
class DiceExpressionTest extends TestCase
{
    public function testConstructorAndProperties(): void
    {
        $specification = new DiceSpecification(3, 6, DiceType::STANDARD);
        $modifiers = new RollModifiers();
        $statistics = new StatisticalData(3, 18, 10.5);
        $originalExpression = '3d6';

        $expression = new DiceExpression(
            $specification,
            $modifiers,
            $statistics,
            $originalExpression
        );

        $this->assertSame($specification, $expression->specification);
        $this->assertSame($modifiers, $expression->modifiers);
        $this->assertSame($statistics, $expression->statistics);
        $this->assertSame($originalExpression, $expression->originalExpression);
        $this->assertNull($expression->comparisonOperator);
        $this->assertNull($expression->comparisonThreshold);
    }

    public function testConstructorWithComparison(): void
    {
        $specification = new DiceSpecification(1, 20, DiceType::STANDARD);
        $modifiers = new RollModifiers();
        $statistics = new StatisticalData(1, 20, 10.5);
        $originalExpression = '1d20>=15';

        $expression = new DiceExpression(
            $specification,
            $modifiers,
            $statistics,
            $originalExpression,
            '>=',
            15
        );

        $this->assertSame('>=', $expression->comparisonOperator);
        $this->assertSame(15, $expression->comparisonThreshold);
    }

    public function testGetStatistics(): void
    {
        $statistics = new StatisticalData(2, 12, 7.0);
        $expression = new DiceExpression(
            new DiceSpecification(2, 6, DiceType::STANDARD),
            new RollModifiers(),
            $statistics,
            '2d6'
        );

        $result = $expression->getStatistics();

        $this->assertSame($statistics, $result);
        $this->assertSame(2, $result->minimum);
        $this->assertSame(12, $result->maximum);
        $this->assertSame(7.0, $result->expected);
    }
}
