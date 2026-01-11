<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Integration;

use Codryn\PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Performance tests for dice rolling operations.
 * 
 * These tests measure performance characteristics of parsing and rolling
 * complex dice expressions. They are designed to ensure the library meets
 * its performance goals stated in the documentation (Parse <100ms, Roll <50ms).
 */
#[CoversClass(PHPDice::class)]
class PerformanceTest extends BaseTestCase
{
    /**
     * Maximum allowed parsing time for complex expressions (milliseconds).
     */
    private const MAX_PARSE_TIME_MS = 100;

    /**
     * Maximum allowed rolling time for typical expressions (milliseconds).
     */
    private const MAX_ROLL_TIME_MS = 50;

    /**
     * Number of iterations for performance testing loops.
     */
    private const PERFORMANCE_ITERATIONS = 100;

    /**
     * Test parsing a worst-case expression with maximum dice and complex math.
     * 
     * This test creates the most complex valid expression with:
     * - Maximum dice count (100)
     * - Maximum sides (100)
     * - Multiple mathematical operations
     * - Nested parentheses
     * - Various operators (addition, subtraction, multiplication, division, modulo, power)
     * - Mathematical functions (floor, ceil, round, abs, min, max)
     * - Dice modifiers (keep, explode, reroll, count)
     */
    public function testParseWorstCaseExpression(): void
    {
        // Construct a worst-case expression with as many valid components as possible
        $worstCaseExpression = '((100d100 keep 50 highest + 100d100 keep 25 lowest) * 2 + ' .
                              '(100d100 explode >=99 + 100d100 reroll <=2) / 3 - ' .
                              'floor(100d100 * 1.5) + ceil(100d100 / 2.5) + ' .
                              'round((100d100) ^ 1.5) + abs(100d100 - 5000) + ' .
                              'min(100d100, 100d100, 100d100) + max(100d100, 100d100, 100d100)) % 1000';

        $startTime = microtime(true);
        $expression = $this->phpdice->parse($worstCaseExpression);
        $parseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Assert parsing was successful
        $this->assertNotNull($expression);
        $this->assertSame($worstCaseExpression, $expression->originalExpression);

        // Assert parsing time is reasonable
        $this->assertLessThan(
            self::MAX_PARSE_TIME_MS,
            $parseTime,
            sprintf('Parsing took %.2fms, expected less than %dms', $parseTime, self::MAX_PARSE_TIME_MS)
        );

        // Output timing information for reference
        fwrite(STDERR, sprintf("\nWorst-case expression parse time: %.2fms\n", $parseTime));
    }

    /**
     * Test rolling a worst-case expression multiple times in a loop using roll().
     * 
     * This measures the performance of parsing + rolling the expression on each iteration.
     * Each call to roll() includes both parsing and rolling overhead.
     */
    public function testRollLoopPerformance(): void
    {
        // Use a complex but more realistic expression for rolling tests
        $expression = '10d20 keep 5 highest + 10d10 + 5d6 * 2 + floor(3d8 / 2)';

        $startTime = microtime(true);
        $results = [];

        for ($i = 0; $i < self::PERFORMANCE_ITERATIONS; $i++) {
            $results[] = $this->phpdice->roll($expression);
        }

        $totalTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $avgTimePerRoll = $totalTime / self::PERFORMANCE_ITERATIONS;

        // Assert all rolls completed successfully
        $this->assertCount(self::PERFORMANCE_ITERATIONS, $results);
        foreach ($results as $result) {
            $this->assertIsInt($result->total);
            $this->assertIsArray($result->diceValues);
        }

        // Output timing information for reference
        fwrite(
            STDERR,
            sprintf(
                "\nroll() loop: %d iterations in %.2fms (avg %.2fms per iteration)\n",
                self::PERFORMANCE_ITERATIONS,
                $totalTime,
                $avgTimePerRoll
            )
        );
    }

    /**
     * Test rolling a pre-parsed expression multiple times using rollExpression().
     * 
     * This measures the performance of rolling only (no parsing overhead).
     * The expression is parsed once before the loop, then rolled multiple times.
     */
    public function testRollExpressionLoopPerformance(): void
    {
        // Use the same expression as testRollLoopPerformance for comparison
        $expressionString = '10d20 keep 5 highest + 10d10 + 5d6 * 2 + floor(3d8 / 2)';

        // Parse once before the loop
        $expression = $this->phpdice->parse($expressionString);

        $startTime = microtime(true);
        $results = [];

        for ($i = 0; $i < self::PERFORMANCE_ITERATIONS; $i++) {
            $results[] = $this->phpdice->rollExpression($expression);
        }

        $totalTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $avgTimePerRoll = $totalTime / self::PERFORMANCE_ITERATIONS;

        // Assert all rolls completed successfully
        $this->assertCount(self::PERFORMANCE_ITERATIONS, $results);
        foreach ($results as $result) {
            $this->assertIsInt($result->total);
            $this->assertIsArray($result->diceValues);
        }

        // Assert average roll time meets performance requirements
        $this->assertLessThan(
            self::MAX_ROLL_TIME_MS,
            $avgTimePerRoll,
            sprintf(
                'Average roll time was %.2fms, expected less than %dms',
                $avgTimePerRoll,
                self::MAX_ROLL_TIME_MS
            )
        );

        // Output timing information for reference
        fwrite(
            STDERR,
            sprintf(
                "\nrollExpression() loop: %d iterations in %.2fms (avg %.2fms per iteration)\n",
                self::PERFORMANCE_ITERATIONS,
                $totalTime,
                $avgTimePerRoll
            )
        );
    }

    /**
     * Test comparison between roll() and rollExpression() performance.
     * 
     * This test demonstrates the performance benefit of pre-parsing expressions
     * when rolling the same expression multiple times.
     */
    public function testRollVsRollExpressionComparison(): void
    {
        $expressionString = '10d20 + 5d10 + 3d6 + 10';
        $iterations = self::PERFORMANCE_ITERATIONS;

        // Test roll() (with parsing on each iteration)
        $rollStartTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->phpdice->roll($expressionString);
        }
        $rollTotalTime = (microtime(true) - $rollStartTime) * 1000;
        $rollAvgTime = $rollTotalTime / $iterations;

        // Test rollExpression() (parse once, roll multiple times)
        $expression = $this->phpdice->parse($expressionString);
        $rollExpressionStartTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->phpdice->rollExpression($expression);
        }
        $rollExpressionTotalTime = (microtime(true) - $rollExpressionStartTime) * 1000;
        $rollExpressionAvgTime = $rollExpressionTotalTime / $iterations;

        // Calculate speedup factor
        $speedupFactor = $rollTotalTime / $rollExpressionTotalTime;

        // Assert rollExpression is faster than roll (it should be significantly faster)
        $this->assertGreaterThan(
            1.0,
            $speedupFactor,
            'rollExpression() should be faster than roll() when reusing parsed expressions'
        );

        // Output comparison results
        fwrite(
            STDERR,
            sprintf(
                "\nPerformance Comparison (%d iterations):\n" .
                "  roll()           : %.2fms total (avg %.2fms)\n" .
                "  rollExpression() : %.2fms total (avg %.2fms)\n" .
                "  Speedup factor   : %.2fx faster\n" .
                "  Time saved       : %.2fms (%.1f%%)\n",
                $iterations,
                $rollTotalTime,
                $rollAvgTime,
                $rollExpressionTotalTime,
                $rollExpressionAvgTime,
                $speedupFactor,
                $rollTotalTime - $rollExpressionTotalTime,
                (($rollTotalTime - $rollExpressionTotalTime) / $rollTotalTime) * 100
            )
        );
    }

    /**
     * Test performance with various expression complexities.
     * 
     * This test measures performance across different expression types
     * to validate consistent performance characteristics.
     */
    public function testVariousComplexityLevels(): void
    {
        $expressions = [
            'simple'     => '3d6',
            'moderate'   => '3d6 + 1d20 + 5',
            'complex'    => '(10d20 keep 5 highest) + (5d10 explode >=9) + 3d6',
            'very_complex' => 'max((20d20 keep 10 highest), 50d10 + 100) + min(10d100, 500) + floor(25d6 * 1.5)',
        ];

        fwrite(STDERR, "\nPerformance by expression complexity:\n");

        foreach ($expressions as $complexity => $expressionString) {
            // Measure parse time
            $parseStart = microtime(true);
            $expression = $this->phpdice->parse($expressionString);
            $parseTime = (microtime(true) - $parseStart) * 1000;

            // Measure roll time (pre-parsed)
            $rollStart = microtime(true);
            $iterations = 50;
            for ($i = 0; $i < $iterations; $i++) {
                $this->phpdice->rollExpression($expression);
            }
            $rollTime = ((microtime(true) - $rollStart) * 1000) / $iterations;

            $this->assertNotNull($expression);

            // Output results
            fwrite(
                STDERR,
                sprintf(
                    "  %-15s: parse %.2fms, roll avg %.2fms\n",
                    $complexity,
                    $parseTime,
                    $rollTime
                )
            );
        }
    }

    /**
     * Test performance with maximum dice count.
     * 
     * Tests rolling the maximum allowed number of dice (100) to ensure
     * performance remains acceptable at the upper limit.
     */
    public function testMaximumDiceCountPerformance(): void
    {
        $expression = '100d100';

        // Measure parse time
        $parseStart = microtime(true);
        $parsedExpression = $this->phpdice->parse($expression);
        $parseTime = (microtime(true) - $parseStart) * 1000;

        // Measure roll time
        $rollStart = microtime(true);
        $result = $this->phpdice->rollExpression($parsedExpression);
        $rollTime = (microtime(true) - $rollStart) * 1000;

        // Verify result
        $this->assertCount(100, $result->diceValues);
        $this->assertGreaterThanOrEqual(100, $result->total);
        $this->assertLessThanOrEqual(10000, $result->total);

        // Assert performance is reasonable
        $this->assertLessThan(
            self::MAX_PARSE_TIME_MS,
            $parseTime,
            sprintf('Parse time %.2fms exceeds limit %dms', $parseTime, self::MAX_PARSE_TIME_MS)
        );
        
        $this->assertLessThan(
            self::MAX_ROLL_TIME_MS,
            $rollTime,
            sprintf('Roll time %.2fms exceeds limit %dms', $rollTime, self::MAX_ROLL_TIME_MS)
        );

        // Output results
        fwrite(
            STDERR,
            sprintf(
                "\n100d100 performance: parse %.2fms, roll %.2fms\n",
                $parseTime,
                $rollTime
            )
        );
    }
}
