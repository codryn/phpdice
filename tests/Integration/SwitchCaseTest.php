<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

require_once __DIR__ . '/BaseTestCaseMock.php';

/**
 * Test switch-case expressions.
 *
 * @covers \Codryn\PHPDice\PHPDice
 * @covers \Codryn\PHPDice\Parser\DiceExpressionParser
 * @covers \Codryn\PHPDice\Roller\DiceRoller
 * @covers \Codryn\PHPDice\Model\DiceExpression
 * @covers \Codryn\PHPDice\Model\RollResult
 * @covers \Codryn\PHPDice\Parser\AST\SwitchCaseNode
 */
class SwitchCaseTest extends BaseTestCaseMock
{
    /**
     * Test basic switch case with single values - case 1.
     */
    public function testBasicSwitchCaseSingleValueCase1(): void
    {
        $expression = 'switch 1d6 case 1: 42 | case 2: 23 | case 3: 10 | case 4: 5 | case 5: 3 | case 6: 0';

        // When 1d6 rolls 1, should return 42
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(1);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(42, $result->total);
    }

    /**
     * Test basic switch case with single values - case 6.
     */
    public function testBasicSwitchCaseSingleValueCase6(): void
    {
        $expression = 'switch 1d6 case 1: 42 | case 2: 23 | case 3: 10 | case 4: 5 | case 5: 3 | case 6: 0';

        // When 1d6 rolls 6, should return 0
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(6);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(0, $result->total);
    }

    /**
     * Test switch case with range - matches range.
     */
    public function testSwitchCaseWithRangeMatch(): void
    {
        $expression = 'switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0';

        // When 1d6 rolls 3 (in range 2-5), should return 23
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(3);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(23, $result->total);
    }

    /**
     * Test switch case with range - matches start of range.
     */
    public function testSwitchCaseWithRangeMatchStart(): void
    {
        $expression = 'switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0';

        // When 1d6 rolls 2 (start of range), should return 23
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(2);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(23, $result->total);
    }

    /**
     * Test switch case with range - matches end of range.
     */
    public function testSwitchCaseWithRangeMatchEnd(): void
    {
        $expression = 'switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0';

        // When 1d6 rolls 5 (end of range), should return 23
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(5);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(23, $result->total);
    }

    /**
     * Test switch case with placeholder.
     */
    public function testSwitchCaseWithPlaceholder(): void
    {
        $expression = 'switch $arg$ case 1: 42 | case 2-5: 23 | case 6: 0 | default: -1';

        // When $arg$ = 3, should return 23
        $result = $this->phpdice->roll($expression, ['arg' => 3]);
        $this->assertEquals(23, $result->total);
    }

    /**
     * Test switch case with default - matches case.
     */
    public function testSwitchCaseWithDefaultMatchesCase(): void
    {
        $expression = 'switch $arg$ case 1: 42 | case 2-5: 23 | case 6: 0 | default: -1';

        // When $arg$ = 1, should return 42
        $result = $this->phpdice->roll($expression, ['arg' => 1]);
        $this->assertEquals(42, $result->total);
    }

    /**
     * Test switch case with default - uses default.
     */
    public function testSwitchCaseWithDefaultUsesDefault(): void
    {
        $expression = 'switch $arg$ case 1: 42 | case 2-5: 23 | case 6: 0 | default: -1';

        // When $arg$ = 10 (not matched by any case), should return -1
        $result = $this->phpdice->roll($expression, ['arg' => 10]);
        $this->assertEquals(-1, $result->total);
    }

    /**
     * Test switch case with dice in case expressions.
     */
    public function testSwitchCaseWithDiceInCaseExpression(): void
    {
        $expression = 'switch 1d6 case 1: 2d6 | case 2-5: 1d6 | case 6: 0';

        // When 1d6 rolls 1, should roll 2d6
        $this->mockRng->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(1, 4, 5);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(9, $result->total); // 4+5
    }

    /**
     * Test switch case in arithmetic expression.
     */
    public function testSwitchCaseInArithmeticExpression(): void
    {
        $expression = '10 + (switch 1d6 case 1: 5 | case 2-5: 2 | case 6: 0)';

        // When 1d6 rolls 3, should add 2 to 10
        $this->mockRng->expects($this->once())
            ->method('generate')
            ->willReturn(3);

        $result = $this->phpdice->roll($expression);
        $this->assertEquals(12, $result->total); // 10+2
    }

    /**
     * Test example 1 from issue: switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0
     */
    public function testIssueExample1(): void
    {
        $expression = 'switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0';

        // Test all possible outcomes
        $expectations = [
            1 => 42,
            2 => 23,
            3 => 23,
            4 => 23,
            5 => 23,
            6 => 0,
        ];

        foreach ($expectations as $roll => $expected) {
            $this->mockRng = $this->createMock(\Codryn\PHPDice\Roller\RandomNumberGenerator::class);
            $this->mockRng->expects($this->once())
                ->method('generate')
                ->willReturn($roll);
            $this->phpdice = new \Codryn\PHPDice\PHPDice(rng: $this->mockRng);

            $result = $this->phpdice->roll($expression);
            $this->assertEquals($expected, $result->total, "Failed for roll {$roll}");
        }
    }

    /**
     * Test example 2 from issue: switch %arg% case 1: 42 | case 2-5: 23 | case 6: 0 | default: -1
     */
    public function testIssueExample2(): void
    {
        $expression = 'switch $arg$ case 1: 42 | case 2-5: 23 | case 6: 0 | default: -1';

        // Test all cases including default
        $expectations = [
            1 => 42,
            2 => 23,
            3 => 23,
            4 => 23,
            5 => 23,
            6 => 0,
            7 => -1,  // default
            10 => -1, // default
            0 => -1,  // default
        ];

        foreach ($expectations as $arg => $expected) {
            $result = $this->phpdice->roll($expression, ['arg' => $arg]);
            $this->assertEquals($expected, $result->total, "Failed for arg {$arg}");
        }
    }
}
