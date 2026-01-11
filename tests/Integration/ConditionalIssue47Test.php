<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\PHPDice;
use PHPUnit\Framework\TestCase;

/**
 * Test conditional expressions with examples from issue #47.
 */
class ConditionalIssue47Test extends TestCase
{
    private PHPDice $dice;

    protected function setUp(): void
    {
        $this->dice = new PHPDice();
    }

    /**
     * Test example 1 from issue #47:
     * if $crit$ > 0: 2d6+5 | 1d6+2
     * $crit$ == 0 -> roll 1d6+2
     * $crit$ != 0 -> roll 2d6+5
     */
    public function testIssueExample1(): void
    {
        $expression = 'if $crit$ > 0: 2d6+5 | 1d6+2';

        // When $crit$ = 0, should roll 1d6+2 (min: 3, max: 8)
        $result = $this->dice->roll($expression, ['crit' => 0]);
        $this->assertGreaterThanOrEqual(3, $result->total);
        $this->assertLessThanOrEqual(8, $result->total);

        // When $crit$ != 0, should roll 2d6+5 (min: 7, max: 17)
        $result = $this->dice->roll($expression, ['crit' => 1]);
        $this->assertGreaterThanOrEqual(7, $result->total);
        $this->assertLessThanOrEqual(17, $result->total);
    }

    /**
     * Test example 2 from issue #47:
     * 1d20 + (if $rank$ >= 10: 4 | 2)
     * $rank$ <= 9  -> roll 1d20 +2
     * $rank$ >= 10 -> roll 1d20 +4
     */
    public function testIssueExample2(): void
    {
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2)';

        // When $rank$ <= 9, should add 2 (min: 3, max: 22)
        $result = $this->dice->roll($expression, ['rank' => 9]);
        $this->assertGreaterThanOrEqual(3, $result->total);
        $this->assertLessThanOrEqual(22, $result->total);

        // When $rank$ >= 10, should add 4 (min: 5, max: 24)
        $result = $this->dice->roll($expression, ['rank' => 10]);
        $this->assertGreaterThanOrEqual(5, $result->total);
        $this->assertLessThanOrEqual(24, $result->total);
    }

    /**
     * Test example 3 from issue #47:
     * if 1d6 > 3: 1d20 + 5 | 1d12 - 1
     * Roll 1d6
     * on 1..3 roll -> 1d12 - 1 (min: 0, max: 11)
     * on 4..6 roll -> 1d20 + 5 (min: 6, max: 25)
     */
    public function testIssueExample3(): void
    {
        $expression = 'if 1d6 > 3: 1d20 + 5 | 1d12 - 1';

        // Roll multiple times to verify both branches can be reached
        $lowResults = [];  // Results 0-11 (1d12 - 1)
        $highResults = []; // Results 6-25 (1d20 + 5)

        for ($i = 0; $i < 30; $i++) {
            $result = $this->dice->roll($expression);
            if ($result->total <= 11) {
                $lowResults[] = $result->total;
            } else {
                $highResults[] = $result->total;
            }
        }

        // We should see both branches (with very high probability over 30 rolls)
        $this->assertNotEmpty($lowResults, 'Should have some results from false branch (1d12-1)');
        $this->assertNotEmpty($highResults, 'Should have some results from true branch (1d20+5)');

        // Verify ranges
        if (!empty($lowResults)) {
            $this->assertGreaterThanOrEqual(0, min($lowResults));
            $this->assertLessThanOrEqual(11, max($lowResults));
        }
        if (!empty($highResults)) {
            $this->assertGreaterThanOrEqual(6, min($highResults));
            $this->assertLessThanOrEqual(25, max($highResults));
        }
    }

    /**
     * Test comment example from issue description.
     */
    public function testConditionalWithComment(): void
    {
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2) # roll skill check with variable feat bonus';

        $result = $this->dice->roll($expression, ['rank' => 12]);
        $this->assertEquals('roll skill check with variable feat bonus', $result->comment);
        $this->assertGreaterThanOrEqual(5, $result->total);
        $this->assertLessThanOrEqual(24, $result->total);
    }
}
