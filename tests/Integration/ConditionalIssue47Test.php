<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

require_once __DIR__ . '/BaseTestCaseMock.php';

/**
 * Test conditional expressions with examples from issue #47.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 * @covers \Codryn\PHPDice\Model\DiceExpression
 * @covers \Codryn\PHPDice\Model\RollResult
 * @covers \Codryn\PHPDice\Parser\AST\ConditionalNode
 */
class ConditionalIssue47Test extends BaseTestCaseMock
{
    /**
     * Test example 1 from issue #47 - false branch:
     * if $crit$ > 0: 2d6+5 | 1d6+2
     * $crit$ == 0 -> roll 1d6+2
     */
    public function testIssueExample1FalseBranch(): void
    {
        $expression = 'if $crit$ > 0: 2d6+5 | 1d6+2';

        // When $crit$ = 0, should roll 1d6+2
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(5);

        $result = $this->phpdice->roll($expression, ['crit' => 0]);
        $this->assertEquals(7, $result->total); // 5+2
    }

    /**
     * Test example 1 from issue #47 - true branch:
     * if $crit$ > 0: 2d6+5 | 1d6+2
     * $crit$ != 0 -> roll 2d6+5
     */
    public function testIssueExample1TrueBranch(): void
    {
        $expression = 'if $crit$ > 0: 2d6+5 | 1d6+2';

        // When $crit$ != 0, should roll 2d6+5
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(4, 6);

        $result = $this->phpdice->roll($expression, ['crit' => 1]);
        $this->assertEquals(15, $result->total); // 4+6+5
    }

    /**
     * Test example 2 from issue #47 - false branch:
     * 1d20 + (if $rank$ >= 10: 4 | 2)
     * $rank$ <= 9  -> roll 1d20 +2
     */
    public function testIssueExample2FalseBranch(): void
    {
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2)';

        // When $rank$ <= 9, should add 2
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(10);

        $result = $this->phpdice->roll($expression, ['rank' => 9]);
        $this->assertEquals(12, $result->total); // 10+2
    }

    /**
     * Test example 2 from issue #47 - true branch:
     * 1d20 + (if $rank$ >= 10: 4 | 2)
     * $rank$ >= 10 -> roll 1d20 +4
     */
    public function testIssueExample2TrueBranch(): void
    {
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2)';

        // When $rank$ >= 10, should add 4
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(15);

        $result = $this->phpdice->roll($expression, ['rank' => 10]);
        $this->assertEquals(19, $result->total); // 15+4
    }

    /**
     * Test example 3 from issue #47 - false branch:
     * if 1d6 > 3: 1d20 + 5 | 1d12 - 1
     * Roll 1d6 on 1..3 roll -> 1d12 - 1
     */
    public function testIssueExample3FalseBranch(): void
    {
        $expression = 'if 1d6 > 3: 1d20 + 5 | 1d12 - 1';

        // Test false branch: 1d6 rolls 2 (<= 3), should roll 1d12 - 1
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(2, 8); // 2 for condition, 8 for 1d12

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(7, $result->total); // 8-1
    }

    /**
     * Test example 3 from issue #47 - true branch:
     * if 1d6 > 3: 1d20 + 5 | 1d12 - 1
     * Roll 1d6 on 4..6 roll -> 1d20 + 5
     */
    public function testIssueExample3TrueBranch(): void
    {
        $expression = 'if 1d6 > 3: 1d20 + 5 | 1d12 - 1';

        // Test true branch: 1d6 rolls 5 (> 3), should roll 1d20 + 5
        $this->mockRng->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(5, 12); // 5 for condition, 12 for 1d20

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(17, $result->total); // 12+5
    }

    /**
     * Test comment example from issue description.
     */
    public function testConditionalWithComment(): void
    {
        $expression = '1d20 + (if $rank$ >= 10: 4 | 2) # roll skill check with variable feat bonus';

        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(18);

        $result = $this->phpdice->roll($expression, ['rank' => 12]);
        $this->assertEquals('roll skill check with variable feat bonus', $result->comment);
        $this->assertEquals(22, $result->total); // 18+4
    }
}
