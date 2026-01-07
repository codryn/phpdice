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
     * Without auto keyword, natural 20 does NOT automatically succeed if DC is not met.
     */
    public function testParserCapturesCriticalHitWithThresholdAbove(): void
    {
        $expression = '1d20 crit 19 glitch 1 dc >= 21';

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
        $this->assertFalse($result->isSuccess, 'Not expected hit (no auto keyword, DC not met)');
        $this->assertFalse($result->isCriticalSuccess, 'Not expected critical success (DC not met)');
        $this->assertFalse($result->isCriticalFailure, 'Not expected critical failure');
    }

    /**
     * Parser captures critical hits with thresholds.
     */
    public function testParserCapturesCriticalHitWithThreshold(): void
    {
        $expression = '1d20 crit 18 glitch 1 dc >= 19';

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
     * Test critical detection with advantage.
     */
    public function testCriticalWithModifier(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(19, 20);

        $result = $this->phpdice->roll('1d20 crit 20 + 1');

        $this->assertCount(1, $result->diceValues);
        $this->assertSame(20, $result->total);
        $this->assertFalse($result->isCriticalSuccess);

        $result = $this->phpdice->roll('1d20 crit 20 + 1');

        $this->assertCount(1, $result->diceValues);
        $this->assertSame(21, $result->total);
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
            ->willReturnOnConsecutiveCalls(6, 5);

        $result = $this->phpdice->roll('1d6 explode crit 6');
        $this->assertTrue($result->isCriticalSuccess);
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

        $result = $this->phpdice->roll('1d20 crit 20 dc >= 15');
        $this->assertIsBool($result->isSuccess);
        $this->assertTrue($result->isCriticalSuccess);
        $this->assertTrue($result->isSuccess);

        $result = $this->phpdice->roll('1d20 crit 20 dc >= 15');
        $this->assertIsBool($result->isSuccess);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertTrue($result->isSuccess);

        $result = $this->phpdice->roll('1d20 crit 20 dc >= 15');
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

    /**
     * Test critical success with placeholder modifiers.
     *
     * Issue: Crit does not work with placeholder modifiers
     * Given: 1d20 crit 15 + $str.bonus$ + $dex.bonus$ dc >= 10
     * When: Die rolls 15-20 (in crit range)
     * Then: Roll should be marked as critical success
     */
    public function testCriticalSuccessWithPlaceholders(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 19, 14);

        $variables = ['str.bonus' => 3, 'dex.bonus' => 4];

        // Test die value at crit threshold (15)
        $result = $this->phpdice->roll('1d20 crit 15 + $str.bonus$ + $dex.bonus$ dc >= 10', $variables);
        $this->assertEquals(15, $result->diceValues[0]);
        $this->assertEquals(22, $result->total); // 15 + 3 + 4
        $this->assertTrue($result->isSuccess);
        $this->assertTrue($result->isCriticalSuccess, 'Expected critical success when die rolls 15');

        // Test die value in crit range (19)
        $result = $this->phpdice->roll('1d20 crit 15 + $str.bonus$ + $dex.bonus$ dc >= 10', $variables);
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertEquals(26, $result->total); // 19 + 3 + 4
        $this->assertTrue($result->isSuccess);
        $this->assertTrue($result->isCriticalSuccess, 'Expected critical success when die rolls 19');

        // Test die value below crit threshold
        $result = $this->phpdice->roll('1d20 crit 15 + $str.bonus$ + $dex.bonus$ dc >= 10', $variables);
        $this->assertEquals(14, $result->diceValues[0]);
        $this->assertEquals(21, $result->total); // 14 + 3 + 4
        $this->assertTrue($result->isSuccess);
        $this->assertFalse($result->isCriticalSuccess, 'Should not be critical when die rolls below 15');
    }

    /**
     * Test critical failure with placeholder modifiers.
     */
    public function testCriticalFailureWithPlaceholders(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 5, 6);

        $variables = ['bonus' => 2];

        // Test die value at glitch threshold (1)
        $result = $this->phpdice->roll('1d20 glitch 5 + $bonus$ dc >= 10', $variables);
        $this->assertEquals(1, $result->diceValues[0]);
        $this->assertEquals(3, $result->total); // 1 + 2
        $this->assertFalse($result->isSuccess);
        $this->assertTrue($result->isCriticalFailure, 'Expected critical failure when die rolls 1');

        // Test die value in glitch range (5)
        $result = $this->phpdice->roll('1d20 glitch 5 + $bonus$ dc >= 10', $variables);
        $this->assertEquals(5, $result->diceValues[0]);
        $this->assertEquals(7, $result->total); // 5 + 2
        $this->assertFalse($result->isSuccess);
        $this->assertTrue($result->isCriticalFailure, 'Expected critical failure when die rolls 5');

        // Test die value above glitch threshold
        $result = $this->phpdice->roll('1d20 glitch 5 + $bonus$ dc >= 10', $variables);
        $this->assertEquals(6, $result->diceValues[0]);
        $this->assertEquals(8, $result->total); // 6 + 2
        $this->assertFalse($result->isSuccess);
        $this->assertFalse($result->isCriticalFailure, 'Should not be critical failure when die rolls above 5');
    }

    /**
     * Test both critical success and failure with placeholders.
     */
    public function testBothCriticalThresholdsWithPlaceholders(): void
    {
        $this->mockRng->expects($this->exactly(4))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(18, 3, 10, 2);

        $variables = ['mod' => 5];

        // Test critical success
        $result = $this->phpdice->roll('1d20 crit 18 glitch 3 + $mod$', $variables);
        $this->assertEquals(18, $result->diceValues[0]);
        $this->assertTrue($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);

        // Test critical failure
        $result = $this->phpdice->roll('1d20 crit 18 glitch 3 + $mod$', $variables);
        $this->assertEquals(3, $result->diceValues[0]);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertTrue($result->isCriticalFailure);

        // Test neither critical
        $result = $this->phpdice->roll('1d20 crit 18 glitch 3 + $mod$', $variables);
        $this->assertEquals(10, $result->diceValues[0]);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);

        // Test critical failure at lower bound
        $result = $this->phpdice->roll('1d20 crit 18 glitch 3 + $mod$', $variables);
        $this->assertEquals(2, $result->diceValues[0]);
        $this->assertFalse($result->isCriticalSuccess);
        $this->assertTrue($result->isCriticalFailure);
    }

    /**
     * Test critical success with multiple placeholders in complex expression.
     */
    public function testCriticalWithMultiplePlaceholders(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(20, 19);

        $variables = [
            'str.bonus' => 3,
            'dex.bonus' => 2,
            'proficiency' => 4,
        ];

        // Test natural 20 (always crit)
        $result = $this->phpdice->roll('1d20 crit 19 + $str.bonus$ + $dex.bonus$ + $proficiency$ dc >= 15', $variables);
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertEquals(29, $result->total); // 20 + 3 + 2 + 4
        $this->assertTrue($result->isSuccess);
        $this->assertTrue($result->isCriticalSuccess);

        // Test crit threshold with success
        $result = $this->phpdice->roll('1d20 crit 19 + $str.bonus$ + $dex.bonus$ + $proficiency$ dc >= 15', $variables);
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertEquals(28, $result->total); // 19 + 3 + 2 + 4
        $this->assertTrue($result->isSuccess);
        $this->assertTrue($result->isCriticalSuccess);
    }

    /**
     * Test that placeholder modifiers are properly stored in expression.
     */
    public function testCriticalThresholdsStoredWithPlaceholders(): void
    {
        $variables = ['bonus' => 3];

        $expr = $this->phpdice->parse('1d20 crit 19 glitch 2 + $bonus$', $variables);

        // Verify critical thresholds are stored in modifiers
        $this->assertSame(19, $expr->modifiers->criticalSuccess);
        $this->assertSame(2, $expr->modifiers->criticalFailure);

        // Verify placeholders are resolved
        $this->assertArrayHasKey('bonus', $expr->modifiers->resolvedVariables);
        $this->assertSame(3, $expr->modifiers->resolvedVariables['bonus']);
    }

    /**
     * Test auto success mechanic - Example 1 from issue.
     * 1d20 auto 20 crit 19 glitch 1 + 1 dc 25
     * Roll: 1 -> critical failure, total 2
     * Roll: 19 -> failure, total 20
     * Roll: 20 -> critical success, total 21 (below dc, but auto success)
     */
    public function testAutoSuccessWithImpossibleDC(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 19, 20);

        // Test roll 1 - critical failure
        $result = $this->phpdice->roll('1d20 auto 20 crit 19 glitch 1 + 1 dc >= 25');
        $this->assertEquals(1, $result->diceValues[0]);
        $this->assertEquals(2, $result->total); // 1 + 1
        $this->assertFalse($result->isSuccess, 'Roll 1: not expected success');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 1: not expected critical success');
        $this->assertTrue($result->isCriticalFailure, 'Roll 1: expected critical failure');

        // Test roll 19 - failure (in crit range but DC not met)
        $result = $this->phpdice->roll('1d20 auto 20 crit 19 glitch 1 + 1 dc >= 25');
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertEquals(20, $result->total); // 19 + 1
        $this->assertFalse($result->isSuccess, 'Roll 19: not expected success (DC not met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 19: not expected critical success (DC not met)');
        $this->assertFalse($result->isCriticalFailure, 'Roll 19: not expected critical failure');

        // Test roll 20 - critical success (auto success + crit range)
        $result = $this->phpdice->roll('1d20 auto 20 crit 19 glitch 1 + 1 dc >= 25');
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertEquals(21, $result->total); // 20 + 1
        $this->assertTrue($result->isSuccess, 'Roll 20: expected success (auto success)');
        $this->assertTrue($result->isCriticalSuccess, 'Roll 20: expected critical success (auto + crit range)');
        $this->assertFalse($result->isCriticalFailure, 'Roll 20: not expected critical failure');
    }

    /**
     * Test crit without auto - Example 2 from issue.
     * 1d20 crit 20 glitch 1 + 1 dc 25
     * Roll: 1 -> critical failure, total 2
     * Roll: 19 -> failure, total 20
     * Roll: 20 -> failure, total 21 (in crit range but no auto, DC not met)
     */
    public function testCritWithoutAutoSuccess(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 19, 20);

        // Test roll 1 - critical failure
        $result = $this->phpdice->roll('1d20 crit 20 glitch 1 + 1 dc >= 25');
        $this->assertEquals(1, $result->diceValues[0]);
        $this->assertEquals(2, $result->total);
        $this->assertFalse($result->isSuccess, 'Roll 1: not expected success');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 1: not expected critical success');
        $this->assertTrue($result->isCriticalFailure, 'Roll 1: expected critical failure');

        // Test roll 19 - failure
        $result = $this->phpdice->roll('1d20 crit 20 glitch 1 + 1 dc >= 25');
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertEquals(20, $result->total);
        $this->assertFalse($result->isSuccess, 'Roll 19: not expected success');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 19: not expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Roll 19: not expected critical failure');

        // Test roll 20 - failure (no auto success, DC not met)
        $result = $this->phpdice->roll('1d20 crit 20 glitch 1 + 1 dc >= 25');
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertEquals(21, $result->total);
        $this->assertFalse($result->isSuccess, 'Roll 20: not expected success (no auto, DC not met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 20: not expected critical success (DC not met)');
        $this->assertFalse($result->isCriticalFailure, 'Roll 20: not expected critical failure');
    }

    /**
     * Test auto success with achievable DC - Example 3 from issue.
     * 1d20 auto 20 crit 19 glitch 1 + 1 dc 21
     * Roll: 1 -> critical failure, total 2
     * Roll: 19 -> failure, total 20 (not a crit because below dc)
     * Roll: 20 -> critical success, total 21 (hits dc AND crit range)
     */
    public function testAutoSuccessWithAchievableDC(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 19, 20);

        // Test roll 1 - critical failure
        $result = $this->phpdice->roll('1d20 auto 20 crit 19 glitch 1 + 1 dc >= 21');
        $this->assertEquals(1, $result->diceValues[0]);
        $this->assertEquals(2, $result->total);
        $this->assertFalse($result->isSuccess, 'Roll 1: not expected success');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 1: not expected critical success');
        $this->assertTrue($result->isCriticalFailure, 'Roll 1: expected critical failure');

        // Test roll 19 - failure (in crit range but DC not met)
        $result = $this->phpdice->roll('1d20 auto 20 crit 19 glitch 1 + 1 dc >= 21');
        $this->assertEquals(19, $result->diceValues[0]);
        $this->assertEquals(20, $result->total);
        $this->assertFalse($result->isSuccess, 'Roll 19: not expected success (DC not met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 19: not expected critical success (DC not met)');
        $this->assertFalse($result->isCriticalFailure, 'Roll 19: not expected critical failure');

        // Test roll 20 - critical success (both auto success AND DC met)
        $result = $this->phpdice->roll('1d20 auto 20 crit 19 glitch 1 + 1 dc >= 21');
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertEquals(21, $result->total);
        $this->assertTrue($result->isSuccess, 'Roll 20: expected success (auto + DC met)');
        $this->assertTrue($result->isCriticalSuccess, 'Roll 20: expected critical success (auto + crit range)');
        $this->assertFalse($result->isCriticalFailure, 'Roll 20: not expected critical failure');
    }

    /**
     * Test inverted logic with auto success.
     * 1d20 auto 1 crit 1 glitch 20 dc <= 5
     * Lower rolls are better, 1 is auto success, 20 is glitch
     */
    public function testAutoSuccessInvertedLogic(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 10, 20);

        // Test roll 1 - auto success and critical success
        $result = $this->phpdice->roll('1d20 auto 1 crit 1 glitch 20 dc <= 5');
        $this->assertEquals(1, $result->diceValues[0]);
        $this->assertEquals(1, $result->total);
        $this->assertTrue($result->isSuccess, 'Roll 1: expected success (auto + DC met)');
        $this->assertTrue($result->isCriticalSuccess, 'Roll 1: expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Roll 1: not expected critical failure');

        // Test roll 10 - failure (DC not met)
        $result = $this->phpdice->roll('1d20 auto 1 crit 1 glitch 20 dc <= 5');
        $this->assertEquals(10, $result->diceValues[0]);
        $this->assertEquals(10, $result->total);
        $this->assertFalse($result->isSuccess, 'Roll 10: not expected success (DC not met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 10: not expected critical success');
        $this->assertFalse($result->isCriticalFailure, 'Roll 10: not expected critical failure');

        // Test roll 20 - critical failure
        $result = $this->phpdice->roll('1d20 auto 1 crit 1 glitch 20 dc <= 5');
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertEquals(20, $result->total);
        $this->assertFalse($result->isSuccess, 'Roll 20: not expected success (DC not met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 20: not expected critical success');
        $this->assertTrue($result->isCriticalFailure, 'Roll 20: expected critical failure');
    }

    /**
     * Test auto success with d100.
     */
    public function testAutoSuccessWithD100(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(99, 100, 50);

        // Test roll 99 - failure (in crit range but DC not met, no auto)
        $result = $this->phpdice->roll('1d100 auto 100 crit 95 dc >= 105');
        $this->assertEquals(99, $result->diceValues[0]);
        $this->assertFalse($result->isSuccess, 'Roll 99: not expected success');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 99: not expected critical success (DC not met)');

        // Test roll 100 - auto success and critical success
        $result = $this->phpdice->roll('1d100 auto 100 crit 95 dc >= 105');
        $this->assertEquals(100, $result->diceValues[0]);
        $this->assertTrue($result->isSuccess, 'Roll 100: expected success (auto)');
        $this->assertTrue($result->isCriticalSuccess, 'Roll 100: expected critical success (auto + crit range)');

        // Test roll 50 - failure
        $result = $this->phpdice->roll('1d100 auto 100 crit 95 dc >= 105');
        $this->assertEquals(50, $result->diceValues[0]);
        $this->assertFalse($result->isSuccess, 'Roll 50: not expected success');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 50: not expected critical success');
    }

    /**
     * Test auto success with d12.
     */
    public function testAutoSuccessWithD12(): void
    {
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(11, 12, 6);

        // Test roll 11 - failure (in crit range but DC not met)
        $result = $this->phpdice->roll('1d12 auto 12 crit 11 glitch 1 dc >= 15');
        $this->assertEquals(11, $result->diceValues[0]);
        $this->assertFalse($result->isSuccess, 'Roll 11: not expected success (DC not met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 11: not expected critical success (DC not met)');

        // Test roll 12 - auto success and critical success
        $result = $this->phpdice->roll('1d12 auto 12 crit 11 glitch 1 dc >= 15');
        $this->assertEquals(12, $result->diceValues[0]);
        $this->assertTrue($result->isSuccess, 'Roll 12: expected success (auto)');
        $this->assertTrue($result->isCriticalSuccess, 'Roll 12: expected critical success (auto + crit range)');

        // Test roll 6 - failure
        $result = $this->phpdice->roll('1d12 auto 12 crit 11 glitch 1 dc >= 15');
        $this->assertEquals(6, $result->diceValues[0]);
        $this->assertFalse($result->isSuccess, 'Roll 6: not expected success');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 6: not expected critical success');
    }

    /**
     * Test that auto success is stored in parsed expression.
     */
    public function testAutoSuccessStoredInExpression(): void
    {
        $expr = $this->phpdice->parse('1d20 auto 20 crit 19 glitch 1');

        $this->assertSame(20, $expr->modifiers->autoSuccess);
        $this->assertSame(19, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);
    }

    /**
     * Test auto validation - threshold must be within die range.
     */
    public function testAutoSuccessValidation(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('threshold 25 is outside die range (1-20)');

        $this->phpdice->parse('1d20 auto 25');
    }

    /**
     * Test that rolls without auto/crit/glitch work as before.
     */
    public function testRollsWithoutAutoCritGlitch(): void
    {
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(15, 20);

        // Test normal roll without any special mechanics
        $result = $this->phpdice->roll('1d20 + 5 dc >= 20');
        $this->assertEquals(15, $result->diceValues[0]);
        $this->assertEquals(20, $result->total);
        $this->assertTrue($result->isSuccess, 'Roll 15+5: expected success (DC met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 15+5: not expected critical success (no crit keyword)');

        // Test roll 20 without auto - should succeed only if DC is met
        $result = $this->phpdice->roll('1d20 + 5 dc >= 20');
        $this->assertEquals(20, $result->diceValues[0]);
        $this->assertEquals(25, $result->total);
        $this->assertTrue($result->isSuccess, 'Roll 20+5: expected success (DC met)');
        $this->assertFalse($result->isCriticalSuccess, 'Roll 20+5: not expected critical success (no crit keyword)');
    }
}
