<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PHPDice\PHPDice;

$dice = new PHPDice();

// Test 1: Basic expression with tags
echo "Test 1: 1d6+2 [MAGIC, piercing, Silver] # roll damage\n";
try {
    $result = $dice->roll('1d6+2 [MAGIC, piercing, Silver] # roll damage');
    echo "Total: " . $result->total . "\n";
    echo "Dice: " . implode(', ', $result->diceValues) . "\n";
    echo "Comment: " . ($result->comment ?? 'null') . "\n";
    echo "Tags: " . (isset($result->tags) ? implode(', ', $result->tags) : 'not implemented yet') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Groups with tags
echo "Test 2: {1d6+6 [Piercing]} + {2d6 [Fire,magic]} # roll damage\n";
try {
    $result = $dice->roll('{1d6+6 [Piercing]} + {2d6 [Fire,magic]} # roll damage');
    echo "Total: " . $result->total . "\n";
    echo "Dice: " . implode(', ', $result->diceValues) . "\n";
    echo "Comment: " . ($result->comment ?? 'null') . "\n";
    echo "Groups: " . (isset($result->groups) ? count($result->groups) : 'none') . "\n";
    if (isset($result->groups)) {
        foreach ($result->groups as $idx => $group) {
            echo "  Group $idx: total=" . $group->total;
            echo ", comment=" . ($group->comment ?? 'null');
            echo ", tags=" . (isset($group->tags) ? implode(', ', $group->tags) : 'not implemented yet');
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
