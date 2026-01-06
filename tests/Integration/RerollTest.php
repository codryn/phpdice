<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ValidationException;

/**
 * Integration tests for reroll mechanics (US5).
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Roller\DiceRoller
 */
class RerollTest extends BaseTestCase
{
    /**
     * @test
     * AC5.1: Parse and roll "4d6 reroll <= 2" with default limit of 100
     */
    public function testRerollWithDefaultLimit(): void
    {
        $expression = $this->phpdice->parse('4d6 reroll <= 2');

        // Check parsed modifiers
        $this->assertEquals(2, $expression->modifiers->rerollThreshold);
        $this->assertEquals('<=', $expression->modifiers->rerollOperator);
        $this->assertEquals(100, $expression->modifiers->rerollLimit);

        // Roll multiple times to verify rerolls happen
        $foundReroll = false;
        for ($i = 0; $i < 10; $i++) {
            $result = $this->phpdice->roll('4d6 reroll <= 2');

            // All final values should be > 2
            foreach ($result->diceValues as $value) {
                $this->assertGreaterThan(2, $value, 'Final die value should be > 2 after rerolling <= 2');
            }

            if ($result->rerollHistory !== null) {
                $foundReroll = true;
            }
        }

        // With 4d6 and threshold <=2, rerolls should happen frequently
        $this->assertTrue($foundReroll, 'Should have found at least one reroll in 10 attempts');
    }

    /**
     * @test
     * AC5.2: Parse and roll "4d6 reroll 1 <= 2" with explicit limit of 1
     */
    public function testRerollWithExplicitLimit(): void
    {
        $expression = $this->phpdice->parse('4d6 reroll 1 <= 2');

        // Check parsed limit
        $this->assertEquals(1, $expression->modifiers->rerollLimit);

        $result = $this->phpdice->roll('4d6 reroll 1 <= 2');

        // Verify if rerolls occurred, each die rerolled at most once
        if ($result->rerollHistory !== null) {
            foreach ($result->rerollHistory as $dieIndex => $history) {
                $this->assertLessThanOrEqual(1, $history['count'], 'Should reroll at most once per die');
                $this->assertCount(2, $history['rolls'], 'Should have original + 1 reroll = 2 rolls total');
            }
        }
    }

    /**
     * @test
     * AC5.3: Test "3d6 reroll 5 <= 3" with multiple rerolls allowed
     */
    public function testRerollWithMultipleRerollsAllowed(): void
    {
        $expression = $this->phpdice->parse('3d6 reroll 5 <= 3');

        $this->assertEquals(3, $expression->modifiers->rerollThreshold);
        $this->assertEquals('<=', $expression->modifiers->rerollOperator);
        $this->assertEquals(5, $expression->modifiers->rerollLimit);

        // Roll and verify final values
        $result = $this->phpdice->roll('3d6 reroll 5 <= 3');

        foreach ($result->diceValues as $value) {
            $this->assertGreaterThan(3, $value, 'Final values should all be > 3');
        }
    }

    /**
     * @test
     * AC5.4: Verify reroll history tracking
     */
    public function testRerollHistoryTracking(): void
    {
        // Force rerolls with a high threshold
        $foundHistory = false;

        for ($i = 0; $i < 20; $i++) {
            $result = $this->phpdice->roll('6d6 reroll <= 3');

            if ($result->rerollHistory !== null && count($result->rerollHistory) > 0) {
                $foundHistory = true;

                foreach ($result->rerollHistory as $dieIndex => $history) {
                    // Verify history structure
                    $this->assertArrayHasKey('rolls', $history);
                    $this->assertArrayHasKey('count', $history);
                    $this->assertArrayHasKey('limitReached', $history);

                    // First roll in history should have triggered reroll
                    $this->assertLessThanOrEqual(3, $history['rolls'][0]);

                    // Last roll should be the final value
                    $lastRoll = end($history['rolls']);
                    $this->assertEquals($result->diceValues[$dieIndex], $lastRoll);

                    // Count should match array size - 1
                    $this->assertEquals(count($history['rolls']) - 1, $history['count']);
                }
                break;
            }
        }

        $this->assertTrue($foundHistory, 'Should have found reroll history in 20 attempts');
    }

    /**
     * @test
     * AC5.5: Test different comparison operators
     */
    public function testDifferentComparisonOperators(): void
    {
        // Test <
        $result1 = $this->phpdice->roll('4d6 reroll < 3');
        foreach ($result1->diceValues as $value) {
            $this->assertGreaterThanOrEqual(3, $value);
        }

        // Test >=
        $result2 = $this->phpdice->roll('4d6 reroll >= 5');
        foreach ($result2->diceValues as $value) {
            $this->assertLessThan(5, $value);
        }

        // Test >
        $result3 = $this->phpdice->roll('4d6 reroll > 4');
        foreach ($result3->diceValues as $value) {
            $this->assertLessThanOrEqual(4, $value);
        }

        // Test ==
        $result4 = $this->phpdice->roll('6d6 reroll == 1');
        foreach ($result4->diceValues as $value) {
            $this->assertNotEquals(1, $value);
        }
    }

    /**
     * @test
     * AC5.6: Test reroll with success counting
     */
    public function testRerollWithSuccessCounting(): void
    {
        $result = $this->phpdice->roll('5d6 reroll <= 2 count >= 4');

        // All dice should be > 2 (rerolled)
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThan(2, $value);
        }

        // Count successes (>= 4)
        $expectedSuccesses = 0;
        foreach ($result->diceValues as $value) {
            if ($value >= 4) {
                $expectedSuccesses++;
            }
        }

        $this->assertEquals($expectedSuccesses, $result->successCount);
    }

    /**
     * @test
     * AC5.7: Reject reroll condition covering entire die range
     */
    public function testRerollCoveringEntireRangeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('preventing termination');

        $this->phpdice->parse('1d6 reroll <= 6');
    }

    /**
     * @test
     * Test other invalid reroll ranges
     */
    public function testOtherInvalidRerollRanges(): void
    {
        // Test >= 1 on d6 (all values >= 1)
        try {
            $this->phpdice->parse('1d6 reroll >= 1');
            $this->fail('Should throw ValidationException for >= 1 on d6');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('preventing termination', $e->getMessage());
        }

        // Test < 7 on d6 (all values < 7)
        try {
            $this->phpdice->parse('1d6 reroll < 7');
            $this->fail('Should throw ValidationException for < 7 on d6');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('preventing termination', $e->getMessage());
        }
    }

    /**
     * @test
     * Test valid edge cases
     */
    public function testValidRerollEdgeCases(): void
    {
        // These should NOT throw - they don't cover the entire range
        $this->phpdice->parse('1d6 reroll <= 5');  // Allows 6
        $this->phpdice->parse('1d6 reroll >= 2');  // Allows 1
        $this->phpdice->parse('1d6 reroll < 6');   // Allows 6
        $this->phpdice->parse('1d6 reroll > 1');   // Allows 1
        $this->phpdice->parse('1d6 reroll == 3');  // Allows all except 3

        $this->assertTrue(true, 'All valid edge cases parsed successfully');
    }

    /**
     * @test
     * Test reroll limit enforcement
     */
    public function testRerollLimitEnforcement(): void
    {
        // Set a very low limit to test enforcement
        $result = $this->phpdice->roll('10d6 reroll 0 <= 2');

        // With limit 0, no rerolls should occur even if initial roll is <= 2
        $this->assertNull($result->rerollHistory, 'No rerolls should occur with limit 0');
    }

    /**
     * @test
     * Test reroll with keep mechanics
     */
    public function testRerollWithKeepMechanics(): void
    {
        // Parser expects modifiers in order: advantage/disadvantage, keep, reroll, success
        $result = $this->phpdice->roll('6d6 keep 4 highest reroll <= 2');

        // Should roll 6 dice
        $this->assertCount(6, $result->diceValues);

        // All should be > 2 (rerolled)
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThan(2, $value);
        }

        // Should keep 4 highest
        $this->assertCount(4, $result->keptDice ?? []);
    }

    /**
     * @test
     * Test statistics with rerolls (approximate)
     */
    public function testRerollStatistics(): void
    {
        $expression = $this->phpdice->parse('4d6 reroll <= 2');
        $stats = $expression->statistics;

        // With reroll <= 2, minimum should be 3 (first non-rerolled value)
        $this->assertGreaterThanOrEqual(3, $stats->minimum);

        // Maximum should still be 24 (4 * 6)
        $this->assertEquals(24, $stats->maximum);

        // Expected should be higher than normal 4d6 due to rerolling low values
        // Normal 4d6 expected: 4 * 3.5 = 14
        // With reroll <= 2, expected should be higher
        $this->assertGreaterThan(14, $stats->expected);
    }
}
