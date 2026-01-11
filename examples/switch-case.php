<?php

declare(strict_types=1);

/**
 * Example demonstrating switch case expressions.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPDice\PHPDice;

$dice = new PHPDice();

echo "Switch Case Expression Examples\n";
echo "================================\n\n";

// Example 1: Basic switch with dice
echo "1. Basic switch with 1d6:\n";
echo "   Expression: switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0\n";
echo "   Rolling 5 times:\n";
for ($i = 0; $i < 5; $i++) {
    $result = $dice->roll('switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0');
    echo "   Roll " . ($i + 1) . ": " . $result->total . "\n";
}

// Example 2: Switch with placeholder and default
echo "\n2. Switch with placeholder and default:\n";
echo "   Expression: switch \$value\$ case 1: 42 | case 2-5: 23 | case 6: 0 | default: -1\n";
echo "   Testing different values:\n";
foreach ([0, 1, 3, 6, 10] as $value) {
    $result = $dice->roll('switch $value$ case 1: 42 | case 2-5: 23 | case 6: 0 | default: -1', ['value' => $value]);
    echo "   value=" . $value . " => " . $result->total . "\n";
}

// Example 3: Switch in arithmetic expression
echo "\n3. Switch in arithmetic expression:\n";
echo "   Expression: 10 + (switch \$bonus\$ case 1: 5 | case 2: 3 | case 3: 1 | default: 0)\n";
foreach ([1, 2, 3, 4] as $bonus) {
    $result = $dice->roll('10 + (switch $bonus$ case 1: 5 | case 2: 3 | case 3: 1 | default: 0)', ['bonus' => $bonus]);
    echo "   bonus=" . $bonus . " => " . $result->total . "\n";
}

// Example 4: Nested dice in case expressions
echo "\n4. Nested dice in case expressions:\n";
echo "   Expression: switch 1d6 case 1-3: 1d4 | case 4-6: 1d8\n";
echo "   Rolling 5 times:\n";
for ($i = 0; $i < 5; $i++) {
    $result = $dice->roll('switch 1d6 case 1-3: 1d4 | case 4-6: 1d8');
    echo "   Roll " . ($i + 1) . ": " . $result->total . " (dice: " . implode(', ', $result->diceValues) . ")\n";
}

// Example 5: Error handling - no default and unmatched value
echo "\n5. Error handling - no default and unmatched value:\n";
echo "   Expression: switch \$x\$ case 1: 10 | case 2: 20\n";
echo "   Testing with x=5 (should error):\n";
try {
    $dice->roll('switch $x$ case 1: 10 | case 2: 20', ['x' => 5]);
    echo "   ERROR: Should have thrown exception!\n";
} catch (Exception $e) {
    echo "   ✓ Exception caught: " . $e->getMessage() . "\n";
}

echo "\nAll examples completed successfully!\n";
