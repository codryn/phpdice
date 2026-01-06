<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Model;

use PHPDice\Model\StatisticalData;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StatisticalData.
 *
 * @covers \PHPDice\Model\StatisticalData
 */
class StatisticalDataTest extends TestCase
{
    public function testConstructorAndProperties(): void
    {
        $stats = new StatisticalData(3, 18, 10.5);

        $this->assertSame(3, $stats->minimum);
        $this->assertSame(18, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    public function testWithFloatingPointExpected(): void
    {
        $stats = new StatisticalData(1, 20, 10.5);

        $this->assertSame(1, $stats->minimum);
        $this->assertSame(20, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    public function testWithIntegerExpected(): void
    {
        $stats = new StatisticalData(2, 12, 7);

        $this->assertSame(2, $stats->minimum);
        $this->assertSame(12, $stats->maximum);
        $this->assertSame(7.0, $stats->expected);
    }
}
