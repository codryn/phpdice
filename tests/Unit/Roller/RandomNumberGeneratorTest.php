<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Unit\Roller;

use Codryn\PHPDice\Roller\RandomNumberGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RandomNumberGenerator.
 *
 * @covers \Codryn\PHPDice\Roller\RandomNumberGenerator
 */
class RandomNumberGeneratorTest extends TestCase
{
    private RandomNumberGenerator $rng;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rng = new RandomNumberGenerator();
    }

    public function testGenerateWithinRange(): void
    {
        $result = $this->rng->generate(1, 6);

        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(6, $result);
        $this->assertIsInt($result);
    }

    public function testGenerateMinimumValue(): void
    {
        // Test that minimum value can be generated (statistical test)
        $foundMin = false;
        for ($i = 0; $i < 1000; $i++) {
            if ($this->rng->generate(1, 100) === 1) {
                $foundMin = true;
                break;
            }
        }

        $this->assertTrue($foundMin, 'Should be able to generate minimum value');
    }

    public function testGenerateMaximumValue(): void
    {
        // Test that maximum value can be generated (statistical test)
        $foundMax = false;
        for ($i = 0; $i < 1000; $i++) {
            if ($this->rng->generate(1, 100) === 100) {
                $foundMax = true;
                break;
            }
        }

        $this->assertTrue($foundMax, 'Should be able to generate maximum value');
    }

    public function testGenerateSingleValueRange(): void
    {
        $result = $this->rng->generate(5, 5);

        $this->assertSame(5, $result);
    }

    public function testGenerateWithLargeRange(): void
    {
        $result = $this->rng->generate(1, 1000000);

        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(1000000, $result);
    }

    public function testGenerateWithNegativeRange(): void
    {
        $result = $this->rng->generate(-10, -5);

        $this->assertGreaterThanOrEqual(-10, $result);
        $this->assertLessThanOrEqual(-5, $result);
    }

    public function testGenerateMultipleTimes(): void
    {
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->rng->generate(1, 6);
        }

        $this->assertCount(10, $results);
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(1, $result);
            $this->assertLessThanOrEqual(6, $result);
        }
    }
}
