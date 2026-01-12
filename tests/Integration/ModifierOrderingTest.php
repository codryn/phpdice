<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\Exception\ValidationException;

/**
 * Integration tests for modifier ordering rules.
 *
 * Tests that modifiers are accepted in the correct order:
 * explode/reroll -> keep -> count -> dc
 *
 * And that incorrect orderings are rejected with proper error messages.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 */
class ModifierOrderingTest extends BaseTestCaseMock
{
    /**
     * Test correct order: explode -> keep.
     */
    public function testExplodeThenKeep(): void
    {
        $result = $this->phpdice->roll('4d6 explode keep 3 highest');

        $this->assertNotNull($result);
        $this->assertCount(4, $result->diceValues);
        $this->assertCount(3, $result->keptDice ?? []);
    }

    /**
     * Test correct order: reroll -> keep.
     */
    public function testRerollThenKeep(): void
    {
        $result = $this->phpdice->roll('4d6 reroll <=1 keep 3 highest');

        $this->assertNotNull($result);
        $this->assertCount(4, $result->diceValues);
        $this->assertCount(3, $result->keptDice ?? []);
    }

    /**
     * Test correct order: explode -> keep -> count.
     */
    public function testExplodeThenKeepThenCount(): void
    {
        $result = $this->phpdice->roll('6d6 explode keep 4 highest count >=5');

        $this->assertNotNull($result);
        // Result total should be the count of successes
        $this->assertIsInt($result->total);
    }

    /**
     * Test correct order: reroll -> keep -> count.
     */
    public function testRerollThenKeepThenCount(): void
    {
        $result = $this->phpdice->roll('6d6 reroll <=1 keep 4 highest count >=5');

        $this->assertNotNull($result);
        // Result total should be the count of successes
        $this->assertIsInt($result->total);
    }

    /**
     * Test that keep before explode is rejected.
     */
    public function testKeepBeforeExplodeIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot specify multiple or conflicting keep modifiers');

        $this->phpdice->roll('4d6 keep 3 highest explode');
    }

    /**
     * Test that keep before reroll is rejected.
     */
    public function testKeepBeforeRerollIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot specify multiple or conflicting keep modifiers');

        $this->phpdice->roll('4d6 keep 3 highest reroll <=1');
    }

    /**
     * Test that explode and reroll cannot be combined.
     */
    public function testExplodeAndRerollCannotBeCombined(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot combine explode and reroll on the same dice');

        $this->phpdice->roll('4d6 explode reroll <=1');
    }

    /**
     * Test that reroll and explode cannot be combined (reverse order).
     */
    public function testRerollAndExplodeCannotBeCombined(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot specify multiple or conflicting keep modifiers');

        $this->phpdice->roll('4d6 reroll <=1 explode');
    }

    /**
     * Test correct order with all modifiers: explode -> keep -> count -> dc.
     */
    public function testFullCorrectOrdering(): void
    {
        $result = $this->phpdice->roll('4d6 explode keep 3 highest count >=5 dc >=10');

        $this->assertNotNull($result);
        // Check that the comparison was evaluated
        $this->assertIsBool($result->isSuccess);
    }

    /**
     * Test that only explode or reroll can be used, not both.
     */
    public function testOnlyExplodeOrReroll(): void
    {
        // Explode alone should work
        $result1 = $this->phpdice->roll('4d6 explode');
        $this->assertNotNull($result1);

        // Reroll alone should work
        $result2 = $this->phpdice->roll('4d6 reroll <=1');
        $this->assertNotNull($result2);
    }

    /**
     * Test explode with different thresholds.
     */
    public function testExplodeWithThresholds(): void
    {
        // Explode on max value (default)
        $result1 = $this->phpdice->roll('4d6 explode');
        $this->assertNotNull($result1);

        // Explode on 5 or higher
        $result2 = $this->phpdice->roll('4d6 explode >=5');
        $this->assertNotNull($result2);

        // Explode on 2 or lower
        $result3 = $this->phpdice->roll('4d6 explode <=2');
        $this->assertNotNull($result3);
    }

    /**
     * Test that explode with keep preserves correct behavior
     * Issue: Roll 4d6, explode on 6, keep 3 highest.
     */
    public function testIssueScenario(): void
    {
        $this->mockRng->expects($this->exactly(5))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(6, 3, 2, 5, 4);

        $result = $this->phpdice->roll('4d6 explode keep 3 highest');

        // Should have 4 dice values (one of which exploded)
        $this->assertCount(4, $result->diceValues);

        // First die exploded: 6 + 3 = 9
        $this->assertEquals(9, $result->diceValues[0]);

        // Other dice: 2, 5
        $this->assertEquals(2, $result->diceValues[1]);
        $this->assertEquals(5, $result->diceValues[2]);
        $this->assertEquals(4, $result->diceValues[3]);

        // Should keep 3 highest: 9, 5, 4
        $this->assertCount(3, $result->keptDice ?? []);

        // Total should be 9 + 5 + 4 = 18
        $this->assertEquals(18, $result->total);
    }
}
