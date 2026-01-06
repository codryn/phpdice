<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Roller;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\StatisticalData;
use PHPDice\Roller\DiceRoller;
use PHPDice\Roller\RandomNumberGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DiceRoller with mocked RNG
 * Tests edge cases: lowest/highest rolls, rerolls, explosions, keep mechanics.
 *
 * @covers \PHPDice\Roller\DiceRoller
 */
class DiceRollerTest extends TestCase
{
    /**
     * Test rolling minimum values (all 1s).
     */
    public function testRollAllMinimumValues(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->method('generate')->willReturn(1);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(3, 6),
            new RollModifiers(),
            new StatisticalData(3, 18, 10.5),
            '3d6'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([1, 1, 1], $result->diceValues);
        $this->assertEquals(3, $result->total);
    }

    /**
     * Test rolling maximum values (all 6s for d6).
     */
    public function testRollAllMaximumValues(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->method('generate')->willReturn(6);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(3, 6),
            new RollModifiers(),
            new StatisticalData(3, 18, 10.5),
            '3d6'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([6, 6, 6], $result->diceValues);
        $this->assertEquals(18, $result->total);
    }

    /**
     * Test specific roll sequence.
     */
    public function testRollSpecificSequence(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 3, 6, 2);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(4, 6),
            new RollModifiers(),
            new StatisticalData(4, 24, 14.0),
            '4d6'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([4, 3, 6, 2], $result->diceValues);
        $this->assertEquals(15, $result->total);
    }

    /**
     * Test reroll mechanics with mocked sequence
     * Roll 1 (triggers reroll <=2) → reroll to 5 (stops).
     */
    public function testRerollMechanicsLowestValue(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 5); // Initial 1, reroll to 5

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                rerollThreshold: 2,
                rerollOperator: '<=',
                rerollLimit: 10
            ),
            new StatisticalData(3, 6, 4.5),
            '1d6 reroll <=2'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([5], $result->diceValues);
        $this->assertEquals(5, $result->total);
        $this->assertNotNull($result->rerollHistory);
        $this->assertArrayHasKey(0, $result->rerollHistory);
        $this->assertEquals([1, 5], $result->rerollHistory[0]['rolls']);
        $this->assertEquals(1, $result->rerollHistory[0]['count']);
        $this->assertFalse($result->rerollHistory[0]['limitReached']);
    }

    /**
     * Test reroll hitting the limit.
     */
    public function testRerollHitsLimit(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        // All rolls are 1, which triggers reroll <=2
        $mockRng->method('generate')->willReturn(1);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                rerollThreshold: 2,
                rerollOperator: '<=',
                rerollLimit: 3
            ),
            new StatisticalData(3, 6, 4.5),
            '1d6 reroll 3 <=2'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([1], $result->diceValues);
        $this->assertNotNull($result->rerollHistory);
        $this->assertEquals(3, $result->rerollHistory[0]['count']);
        $this->assertTrue($result->rerollHistory[0]['limitReached']);
        // Should have 1 initial + 3 rerolls = 4 total rolls
        $this->assertCount(4, $result->rerollHistory[0]['rolls']);
    }

    /**
     * Test explosion mechanics with single explosion.
     */
    public function testExplosionMechanicsSingleExplosion(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 3); // Roll 6 (explodes), then 3 (stops)

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                explosionThreshold: 6,
                explosionOperator: '>=',
                explosionLimit: 10
            ),
            new StatisticalData(1, 60, 7.2),
            '1d6 explode'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([9], $result->diceValues); // 6 + 3 = 9
        $this->assertEquals(9, $result->total);
        $this->assertNotNull($result->explosionHistory);
        $this->assertEquals([6, 3], $result->explosionHistory[0]['rolls']);
        $this->assertEquals(1, $result->explosionHistory[0]['count']);
        $this->assertEquals(9, $result->explosionHistory[0]['cumulativeTotal']);
        $this->assertFalse($result->explosionHistory[0]['limitReached']);
    }

    /**
     * Test explosion hitting limit.
     */
    public function testExplosionHitsLimit(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        // All rolls are 6, which keeps exploding
        $mockRng->method('generate')->willReturn(6);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                explosionThreshold: 6,
                explosionOperator: '>=',
                explosionLimit: 2
            ),
            new StatisticalData(1, 18, 7.2),
            '1d6 explode 2'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([18], $result->diceValues); // 6 + 6 + 6 = 18
        $this->assertNotNull($result->explosionHistory);
        $this->assertEquals(2, $result->explosionHistory[0]['count']);
        $this->assertTrue($result->explosionHistory[0]['limitReached']);
        $this->assertCount(3, $result->explosionHistory[0]['rolls']); // 1 initial + 2 explosions
    }

    /**
     * Test explosion with threshold range (explode on 5-6).
     */
    public function testExplosionWithThresholdRange(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 6, 2); // 5 explodes, 6 explodes, 2 stops

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                explosionThreshold: 5,
                explosionOperator: '>=',
                explosionLimit: 10
            ),
            new StatisticalData(1, 60, 8.0),
            '1d6 explode >=5'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([13], $result->diceValues); // 5 + 6 + 2 = 13
        $this->assertEquals(13, $result->total);
        $this->assertEquals([5, 6, 2], $result->explosionHistory[0]['rolls']);
        $this->assertEquals(2, $result->explosionHistory[0]['count']);
    }

    /**
     * Test keep highest with specific values.
     */
    public function testKeepHighestWithSpecificValues(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 2, 5, 3);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(4, 6),
            new RollModifiers(keepHighest: 3),
            new StatisticalData(3, 18, 12.25),
            '4d6 keep 3 highest'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([6, 2, 5, 3], $result->diceValues);
        $this->assertEquals(14, $result->total); // 6 + 5 + 3 = 14 (drop the 2)
        $this->assertNotNull($result->keptDice);
        $this->assertCount(3, $result->keptDice);
        $this->assertContains(0, $result->keptDice); // 6
        $this->assertContains(2, $result->keptDice); // 5
        $this->assertContains(3, $result->keptDice); // 3
    }

    /**
     * Test keep lowest with specific values.
     */
    public function testKeepLowestWithSpecificValues(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 2, 5, 1);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(4, 6),
            new RollModifiers(keepLowest: 2),
            new StatisticalData(2, 12, 4.25),
            '4d6 keep 2 lowest'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([6, 2, 5, 1], $result->diceValues);
        $this->assertEquals(3, $result->total); // 2 + 1 = 3 (drop 6 and 5)
        $this->assertNotNull($result->keptDice);
        $this->assertCount(2, $result->keptDice);
        $this->assertContains(1, $result->keptDice); // 2
        $this->assertContains(3, $result->keptDice); // 1
    }

    /**
     * Test advantage (roll extra, keep highest).
     */
    public function testAdvantageRollsExtraDice(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 8);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 20),
            new RollModifiers(
                advantageCount: 1,
                keepHighest: 1
            ),
            new StatisticalData(1, 20, 13.82),
            '1d20 advantage'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([15, 8], $result->diceValues);
        $this->assertEquals(15, $result->total); // Keep the 15
    }

    /**
     * Test disadvantage (roll extra, keep lowest).
     */
    public function testDisadvantageRollsExtraDice(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 8);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 20),
            new RollModifiers(
                advantageCount: 1,
                keepLowest: 1
            ),
            new StatisticalData(1, 20, 7.18),
            '1d20 disadvantage'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([15, 8], $result->diceValues);
        $this->assertEquals(8, $result->total); // Keep the 8
    }

    /**
     * Test success counting with specific values.
     */
    public function testSuccessCountingWithSpecificValues(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 5, 3, 6, 2);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(5, 6),
            new RollModifiers(
                successThreshold: 5,
                successOperator: '>='
            ),
            new StatisticalData(0, 5, 1.67),
            '5d6 >=5'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([6, 5, 3, 6, 2], $result->diceValues);
        $this->assertEquals(3, $result->successCount); // 6, 5, 6 meet threshold
        $this->assertEquals(3, $result->total); // Total equals success count
    }

    /**
     * Test success counting with strict operator.
     */
    public function testSuccessCountingStrictOperator(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 5, 3, 6, 2);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(5, 6),
            new RollModifiers(
                successThreshold: 5,
                successOperator: '>'
            ),
            new StatisticalData(0, 5, 0.83),
            '5d6 >5'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([6, 5, 3, 6, 2], $result->diceValues);
        $this->assertEquals(2, $result->successCount); // Only 6, 6 meet threshold (5 doesn't count)
        $this->assertEquals(2, $result->total);
    }

    /**
     * Test combined reroll and explosion.
     */
    public function testCombinedRerollAndExplosion(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(
                1,  // Initial roll - triggers reroll
                6,  // Reroll result - triggers explosion
                4   // Explosion result - stops
            );

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                rerollThreshold: 2,
                rerollOperator: '<=',
                rerollLimit: 10,
                explosionThreshold: 6,
                explosionOperator: '>=',
                explosionLimit: 10
            ),
            new StatisticalData(3, 60, 8.0),
            '1d6 reroll <=2 explode'
        );

        $result = $roller->roll($expression);

        // After reroll: value is 6
        // After explosion: 6 + 4 = 10
        $this->assertEquals([10], $result->diceValues);
        $this->assertEquals(10, $result->total);
        $this->assertNotNull($result->rerollHistory);
        $this->assertEquals([1, 6], $result->rerollHistory[0]['rolls']);
        $this->assertNotNull($result->explosionHistory);
        $this->assertEquals([6, 4], $result->explosionHistory[0]['rolls']);
    }

    /**
     * Test multiple dice with mixed results.
     */
    public function testMultipleDiceWithMixedResults(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(
                1,  // Die 0: rerolls
                4,  // Die 0: after reroll
                6,  // Die 1: explodes
                6,  // Die 1: explodes again
                2   // Die 1: stops
            );

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(2, 6),
            new RollModifiers(
                rerollThreshold: 2,
                rerollOperator: '<=',
                rerollLimit: 10,
                explosionThreshold: 6,
                explosionOperator: '>=',
                explosionLimit: 10
            ),
            new StatisticalData(6, 120, 16.0),
            '2d6 reroll <=2 explode'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([4, 14], $result->diceValues); // 4, (6+6+2)
        $this->assertEquals(18, $result->total);

        // Die 0 rerolled
        $this->assertArrayHasKey(0, $result->rerollHistory);
        $this->assertEquals([1, 4], $result->rerollHistory[0]['rolls']);

        // Die 1 exploded twice
        $this->assertArrayHasKey(1, $result->explosionHistory);
        $this->assertEquals([6, 6, 2], $result->explosionHistory[1]['rolls']);
        $this->assertEquals(2, $result->explosionHistory[1]['count']);
    }

    /**
     * Test all dice roll maximum with keep highest.
     */
    public function testAllMaximumValuesWithKeep(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->method('generate')->willReturn(6);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(4, 6),
            new RollModifiers(keepHighest: 3),
            new StatisticalData(3, 18, 12.25),
            '4d6 keep 3 highest'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([6, 6, 6, 6], $result->diceValues);
        $this->assertEquals(18, $result->total); // Keep 3 highest (all are 6)
        $this->assertCount(3, $result->keptDice);
    }

    /**
     * Test edge case: single die, no modifiers.
     */
    public function testSingleDieNoModifiers(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->once())
            ->method('generate')
            ->with(1, 20)
            ->willReturn(10);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 20),
            new RollModifiers(),
            new StatisticalData(1, 20, 10.5),
            '1d20'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([10], $result->diceValues);
        $this->assertEquals(10, $result->total);
        $this->assertNull($result->rerollHistory);
        $this->assertNull($result->explosionHistory);
        $this->assertNull($result->keptDice);
    }

    /**
     * Test explosion with <= operator (explode on low values).
     */
    public function testExplosionWithLessThanOperator(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 2, 5); // 1 explodes, 2 explodes, 5 stops

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                explosionThreshold: 2,
                explosionOperator: '<=',
                explosionLimit: 10
            ),
            new StatisticalData(1, 60, 8.0),
            '1d6 explode <=2'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([8], $result->diceValues); // 1 + 2 + 5 = 8
        $this->assertEquals(8, $result->total);
        $this->assertEquals([1, 2, 5], $result->explosionHistory[0]['rolls']);
    }

    /**
     * Test reroll with == operator (reroll specific value).
     */
    public function testRerollWithEqualsOperator(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(3, 3, 5); // 3, reroll 3, finally 5

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 6),
            new RollModifiers(
                rerollThreshold: 3,
                rerollOperator: '==',
                rerollLimit: 10
            ),
            new StatisticalData(1, 6, 3.6),
            '1d6 reroll ==3'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([5], $result->diceValues);
        $this->assertEquals([3, 3, 5], $result->rerollHistory[0]['rolls']);
        $this->assertEquals(2, $result->rerollHistory[0]['count']);
    }

    /**
     * Test critical success scenario (natural 20).
     */
    public function testCriticalSuccess(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(20);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 20),
            new RollModifiers(),
            new StatisticalData(1, 20, 10.5),
            '1d20'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([20], $result->diceValues);
        $this->assertEquals(20, $result->total);
    }

    /**
     * Test critical failure scenario (natural 1).
     */
    public function testCriticalFailure(): void
    {
        $mockRng = $this->createMock(RandomNumberGenerator::class);
        $mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(1);

        $roller = new DiceRoller($mockRng);

        $expression = new DiceExpression(
            new DiceSpecification(1, 20),
            new RollModifiers(),
            new StatisticalData(1, 20, 10.5),
            '1d20'
        );

        $result = $roller->roll($expression);

        $this->assertEquals([1], $result->diceValues);
        $this->assertEquals(1, $result->total);
    }
}
