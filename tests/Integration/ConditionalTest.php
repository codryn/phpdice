<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\PHPDice;
use PHPUnit\Framework\TestCase;

/**
 * Test conditional (boolean algebra) expressions.
 */
class ConditionalTest extends TestCase
{
    private PHPDice $dice;

    protected function setUp(): void
    {
        $this->dice = new PHPDice();
    }

    /**
     * Test basic conditional with placeholder comparison.
     */
    public function testBasicConditionalWithPlaceholder(): void
    {
        // if $crit$ > 0: 2d6+5 | 1d6+2
        $expression = 'if $crit$ > 0: 2d6+5 | 1d6+2';

        // When $crit$ = 0, should roll 1d6+2
        $result = $this->dice->roll($expression, ['crit' => 0]);
        $this->assertGreaterThanOrEqual(3, $result->total); // min: 1+2
        $this->assertLessThanOrEqual(8, $result->total); // max: 6+2

        // When $crit$ = 1, should roll 2d6+5
        $result = $this->dice->roll($expression, ['crit' => 1]);
        $this->assertGreaterThanOrEqual(7, $result->total); // min: 2+5
        $this->assertLessThanOrEqual(17, $result->total); // max: 12+5
    }

    /**
     * Test conditional with == comparison.
     */
    public function testConditionalWithEquality(): void
    {
        $expression = 'if $rank$ == 10: 4 | 2';

        // When $rank$ = 10, should return 4
        $result = $this->dice->roll($expression, ['rank' => 10]);
        $this->assertEquals(4, $result->total);

        // When $rank$ != 10, should return 2
        $result = $this->dice->roll($expression, ['rank' => 5]);
        $this->assertEquals(2, $result->total);
    }

    /**
     * Test conditional with != comparison.
     */
    public function testConditionalWithNotEqual(): void
    {
        $expression = 'if $crit$ != 0: 10 | 5';

        // When $crit$ != 0, should return 10
        $result = $this->dice->roll($expression, ['crit' => 1]);
        $this->assertEquals(10, $result->total);

        // When $crit$ == 0, should return 5
        $result = $this->dice->roll($expression, ['crit' => 0]);
        $this->assertEquals(5, $result->total);
    }

    /**
     * Test conditional with >= comparison.
     */
    public function testConditionalWithGreaterOrEqual(): void
    {
        $expression = 'if $rank$ >= 10: 4 | 2';

        // When $rank$ >= 10, should return 4
        $result = $this->dice->roll($expression, ['rank' => 10]);
        $this->assertEquals(4, $result->total);

        $result = $this->dice->roll($expression, ['rank' => 15]);
        $this->assertEquals(4, $result->total);

        // When $rank$ < 10, should return 2
        $result = $this->dice->roll($expression, ['rank' => 9]);
        $this->assertEquals(2, $result->total);
    }

    /**
     * Test conditional with < comparison.
     */
    public function testConditionalWithLessThan(): void
    {
        $expression = 'if $value$ < 5: 1 | 0';

        // When $value$ < 5, should return 1
        $result = $this->dice->roll($expression, ['value' => 4]);
        $this->assertEquals(1, $result->total);

        // When $value$ >= 5, should return 0
        $result = $this->dice->roll($expression, ['value' => 5]);
        $this->assertEquals(0, $result->total);
    }

    /**
     * Test conditional with <= comparison.
     */
    public function testConditionalWithLessOrEqual(): void
    {
        $expression = 'if $value$ <= 5: 1 | 0';

        // When $value$ <= 5, should return 1
        $result = $this->dice->roll($expression, ['value' => 5]);
        $this->assertEquals(1, $result->total);

        // When $value$ > 5, should return 0
        $result = $this->dice->roll($expression, ['value' => 6]);
        $this->assertEquals(0, $result->total);
    }

    /**
     * Test conditional used in arithmetic expression.
     */
    public function testConditionalInArithmetic(): void
    {
        // 1d20 + (if $rank$ >= 10: 4 | 2)
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2)';

        // When $rank$ >= 10, should add 4
        $result = $this->dice->roll($expression, ['rank' => 10]);
        $this->assertGreaterThanOrEqual(5, $result->total); // min: 1+4
        $this->assertLessThanOrEqual(24, $result->total); // max: 20+4

        // When $rank$ < 10, should add 2
        $result = $this->dice->roll($expression, ['rank' => 9]);
        $this->assertGreaterThanOrEqual(3, $result->total); // min: 1+2
        $this->assertLessThanOrEqual(22, $result->total); // max: 20+2
    }

    /**
     * Test conditional with dice roll in condition.
     */
    public function testConditionalWithDiceInCondition(): void
    {
        // This should evaluate the dice roll in the condition
        $expression = 'if 1d6 > 3: 10 | 5';

        // Roll multiple times to verify both branches can be reached
        $results = [];
        for ($i = 0; $i < 20; $i++) {
            $result = $this->dice->roll($expression);
            $results[] = $result->total;
        }

        // We should see both 10 and 5 in the results (with high probability)
        $this->assertContains(10, $results);
        $this->assertContains(5, $results);
    }

    /**
     * Test all comparison operators.
     */
    public function testAllComparisonOperators(): void
    {
        $operators = [
            ['>', 6, 5, true],  // 6 > 5 = true
            ['>', 5, 6, false], // 5 > 6 = false
            ['>=', 5, 5, true], // 5 >= 5 = true
            ['>=', 4, 5, false], // 4 >= 5 = false
            ['<', 4, 5, true],  // 4 < 5 = true
            ['<', 6, 5, false], // 6 < 5 = false
            ['<=', 5, 5, true], // 5 <= 5 = true
            ['<=', 6, 5, false], // 6 <= 5 = false
            ['==', 5, 5, true], // 5 == 5 = true
            ['==', 5, 6, false], // 5 == 6 = false
            ['!=', 5, 6, true], // 5 != 6 = true
            ['!=', 5, 5, false], // 5 != 5 = false
        ];

        foreach ($operators as [$op, $left, $right, $expected]) {
            $expression = "if \$a\$ {$op} \$b\$: 1 | 0";
            $result = $this->dice->roll($expression, ['a' => $left, 'b' => $right]);
            $this->assertEquals(
                $expected ? 1 : 0,
                $result->total,
                "Failed for: {$left} {$op} {$right}"
            );
        }
    }
}
