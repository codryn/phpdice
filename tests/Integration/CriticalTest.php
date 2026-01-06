<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ValidationException;

/**
 * Integration tests for critical success and critical failure detection (US9).
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Parser\Validator
 * @covers \PHPDice\Roller\DiceRoller
 */
final class CriticalTest extends BaseTestCaseMock
{
    /**
     * AC1: Natural 20 is flagged as critical success.
     *
     * Given a d20 roll with critical success threshold 20
     * When a natural 20 is rolled
     * Then the result is flagged as a critical success
     */
    public function testNatural20IsCriticalSuccess(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(20, 15);

        // Test critical success
        $result = $this->phpdice->roll('1d20 crit 20');
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertTrue($result->isCriticalSuccess, 'Expected critical success flag when rolling 20');

        // Test non-critical
        $result2 = $this->phpdice->roll('1d20 crit 20');
        $this->assertEquals(15, $result2->diceValues[0]);
        $this->assertFalse($result2->isCriticalSuccess, 'Expected no critical success flag when not rolling 20');
    }

    /**
     * AC2: Natural 1 is flagged as critical failure.
     *
     * Given a d20 roll with critical failure threshold 1
     * When a natural 1 is rolled
     * Then the result is flagged as a critical failure (glitch)
     */
    public function testNatural1IsCriticalFailure(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 10);

        // Test critical failure
        $result = $this->phpdice->roll('1d20 glitch 1');
        $this->assertEquals(1, $result->diceValues[0]);
        $this->assertTrue($result->isCriticalFailure, 'Expected critical failure flag when rolling 1');

        // Test non-critical failure
        $result2 = $this->phpdice->roll('1d20 glitch 1');
        $this->assertEquals(10, $result2->diceValues[0]);
        $this->assertFalse($result2->isCriticalFailure, 'Expected no critical failure flag when not rolling 1');
    }

    /**
     * AC3: Parser captures critical thresholds.
     *
     * Given custom critical thresholds specified in the expression syntax at parse time
     * When parsed
     * Then the parser captures the threshold values in the DiceExpression structure
     */
    public function testParserCapturesCriticalThresholds(): void
    {
        $expression = '1d20 crit 19 glitch 2';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame(19, $expr->modifiers->criticalSuccess);
        $this->assertSame(2, $expr->modifiers->criticalFailure);

        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(19, 2);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertTrue($result->isCriticalSuccess, 'Expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Not expected critical failure');

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(2, $result->diceValues[0]);
        $this->assertTrue($result->isCriticalFailure, 'Expected critical failure');
        $this->assertFalse($result->isCriticalSuccess, 'Not expected critical success');
    }

    /**
     * Parser captures critical hits with thresholds.
     */
    public function testParserCapturesCriticalHitWithThrehsholdAbove(): void
    {
        $expression = '1d20 crit 19 glitch 1 >= 21';

        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(19, 20);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertFalse($result->isSuccess, 'Not expected hit');
        $this->assertFalse($result->isCriticalSuccess, 'Not expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Not expected critical failure');

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertTrue($result->isSuccess, 'Expected hit (nat 20)');
        $this->assertTrue($result->isCriticalSuccess, 'Expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Not expected critical failure');
    }

    /**
     * Parser captures critical hits with thresholds.
     */
    public function testParserCapturesCriticalHitWithThrehshold(): void
    {
        $expression = '1d20 crit 18 glitch 1 >= 19';

        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(17, 18, 19);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(17, $result->diceValues[0]);
        $this->assertFalse($result->isSuccess, 'Not expected hit');
        $this->assertFalse($result->isCriticalSuccess, 'Not expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Not expected critical failure');

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(18, $result->diceValues[0]);
        $this->assertFalse($result->isSuccess, 'Not expected hit');
        $this->assertFalse($result->isCriticalSuccess, 'Not expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Not expected critical failure');

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertTrue($result->isSuccess, 'Expected hit');
        $this->assertTrue($result->isCriticalSuccess, 'Expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Not expected critical failure');
    }

    /**
     * AC3: Test different keyword variations.
     */
    public function testCriticalKeywordVariations(): void
    {
        // Test "crit" keyword
        $expr1 = $this->phpdice->parse('1d20 crit 20');
        $this->assertSame(20, $expr1->modifiers->criticalSuccess);

        // Test "critical" keyword
        $expr2 = $this->phpdice->parse('1d20 critical 20');
        $this->assertSame(20, $expr2->modifiers->criticalSuccess);

        // Test "glitch" keyword
        $expr3 = $this->phpdice->parse('1d20 glitch 1');
        $this->assertSame(1, $expr3->modifiers->criticalFailure);

        // Test "failure" keyword
        $expr4 = $this->phpdice->parse('1d20 failure 1');
        $this->assertSame(1, $expr4->modifiers->criticalFailure);
    }

    /**
     * AC4: Can inspect critical die value and threshold.
     *
     * Given a critical result
     * When inspected
     * Then I can see which die value triggered the critical and the threshold that was configured
     */
    public function testCanInspectCriticalDetails(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6);

        $expression = '1d6 crit 6 glitch 1';

        // Parse to get thresholds
        $expr = $this->phpdice->parse($expression);
        $this->assertSame(6, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        $result = $this->phpdice->roll($expression);
        $this->assertContains(6, $result->diceValues);
    }

    /**
     * AC5: Any single die triggers critical flag.
     *
     * Given multiple dice rolled
     * When any single die is critical
     * Then the result is flagged appropriately
     */
    public function testMultipleDiceCriticalDetection(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 6, 2);

        $result = $this->phpdice->roll('3d6 crit 6');
        $this->assertTrue($result->isCriticalSuccess, 'Expected critical flag when any die is 6');
    }

    /**
     * Test both critical success and failure can be configured together.
     */
    public function testBothCriticalThresholds(): void
    {
        $expression = '1d20 crit 20 glitch 1';
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(20, 1, 19);

        $expr = $this->phpdice->parse($expression);
        $this->assertSame(20, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        $result = $this->phpdice->roll($expression);
        $this->assertTrue($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);

        $result = $this->phpdice->roll($expression);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertTrue($result->isCriticalFailure);

        $result = $this->phpdice->roll($expression);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);
    }

    /**
     * Test critical threshold validation (FR-035).
     */
    public function testCriticalSuccessThresholdMustBeWithinDieRange(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical success threshold 25 is outside die range (1-20)');

        $this->phpdice->parse('1d20 crit 25');
    }

    /**
     * Test critical threshold below minimum.
     */
    public function testCriticalSuccessThresholdBelowMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical success threshold 0 is outside die range (1-20)');

        $this->phpdice->parse('1d20 crit 0');
    }

    /**
     * Test critical failure threshold validation (FR-036).
     */
    public function testCriticalFailureThresholdMustBeWithinDieRange(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical failure threshold 25 is outside die range (1-20)');

        $this->phpdice->parse('1d20 glitch 25');
    }

    /**
     * Test critical failure threshold below minimum.
     */
    public function testCriticalFailureThresholdBelowMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical failure threshold 0 is outside die range (1-6)');

        $this->phpdice->parse('1d6 glitch 0');
    }

    /**
     * Test critical detection with advantage.
     */
    public function testCriticalWithAdvantage(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(20, 1);

        $result = $this->phpdice->roll('1d20 advantage crit 20');

        // Should roll 2 dice
        $this->assertCount(2, $result->diceValues);
        $this->assertTrue($result->isCriticalSuccess);
    }

    /**
     * Test critical detection with keep mechanics.
     */
    public function testCriticalWithKeepHighest(): void
    {
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 6, 1, 1);

        $result = $this->phpdice->roll('4d6 keep 3 highest crit 6');
        $this->assertCount(4, $result->diceValues);
        $this->assertTrue($result->isCriticalSuccess);
    }

    /**
     * Test critical thresholds can be anywhere in valid range.
     */
    public function testCustomCriticalThresholds(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 4);

        $result = $this->phpdice->roll('1d6 crit 5');
        $this->assertTrue($result->isCriticalSuccess);

        $result = $this->phpdice->roll('1d6 crit 5');
        $this->assertFalse($result->isCriticalSuccess);
    }

    /**
     * Test critical with success counting.
     */
    public function testCriticalWithSuccessCounting(): void
    {
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 1, 1, 1, 1);

        $expression = '5d6 success threshold 4 crit 6 glitch 1';

        // Can combine critical detection with success counting
        // TODO Makes not a lot of sense, remove ?
        $expr = $this->phpdice->parse($expression);
        $this->assertSame(4, $expr->modifiers->successThreshold);
        $this->assertSame(6, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        $result = $this->phpdice->roll($expression);
        $this->assertIsInt($result->successCount);
        $this->assertFalse($result->isCriticalSuccess);
    }

    /**
     * Test critical with reroll mechanics.
     */
    public function testCriticalWithReroll(): void
    {
        // Reroll 1s, but a rerolled 1 should still count as critical failure
        $expr = $this->phpdice->parse('1d20 reroll <= 1 glitch 1');
        $this->assertSame(1, $expr->modifiers->rerollThreshold);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 1);

        $result = $this->phpdice->roll('1d20 reroll 1 <= 1 glitch 1');
        $this->assertTrue($result->isCriticalFailure);
    }

    /**
     * Test critical with explosion.
     */
    public function testCriticalWithExplosion(): void
    {
        // When dice explode, the explosion mechanism adds new dice
        // Critical detection should work on any die rolled
        $expr = $this->phpdice->parse('1d6 explode crit 6');

        $this->assertSame(6, $expr->modifiers->criticalSuccess);
        $this->assertSame(6, $expr->modifiers->explosionThreshold);

        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 6);

        $result = $this->phpdice->roll('1d6 explode 1 crit 6');
        $this->assertFalse($result->isCriticalSuccess);
    }

    /**
     * Test critical flags default to false.
     */
    public function testCriticalFlagsDefaultToFalse(): void
    {
        $this->mockRng->expects($this->exactly(1))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(20);

        // No critical thresholds configured
        $result = $this->phpdice->roll('1d20');

        $this->assertFalse($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);
    }

    /**
     * Test only critical success configured.
     */
    public function testOnlyCriticalSuccess(): void
    {
        $expr = $this->phpdice->parse('1d20 crit 20');

        $this->assertSame(20, $expr->modifiers->criticalSuccess);
        $this->assertNull($expr->modifiers->criticalFailure);
    }

    /**
     * Test only critical failure configured.
     */
    public function testOnlyCriticalFailure(): void
    {
        $expr = $this->phpdice->parse('1d20 glitch 1');

        $this->assertNull($expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);
    }

    /**
     * Test critical with comparison (success roll).
     */
    public function testCriticalWithComparison(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(20, 15, 14);

        $result = $this->phpdice->roll('1d20 crit 20 >= 15');
        $this->assertIsBool($result->isSuccess);
        $this->assertTrue($result->isCriticalSuccess);
        $this->assertTrue($result->isSuccess);

        $result = $this->phpdice->roll('1d20 crit 20 >= 15');
        $this->assertIsBool($result->isSuccess);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertTrue($result->isSuccess);

        $result = $this->phpdice->roll('1d20 crit 20 >= 15');
        $this->assertIsBool($result->isSuccess);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertFalse($result->isSuccess);
    }

    /**
     * Test critical with fudge dice.
     */
    public function testCriticalWithFudgeDice(): void
    {
        // Fudge dice are -1, 0, +1 but stored internally as 1, 2, 3
        // Let's configure crit on the max value (3 = +1)
        $expr = $this->phpdice->parse('1dF crit 3 glitch 1');

        $this->assertSame(3, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);
    }
}
