<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for integration tests.
 */
abstract class BaseTestCase extends TestCase
{
    protected PHPDice $phpdice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phpdice = new PHPDice();
    }
}
