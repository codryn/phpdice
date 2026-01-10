<?php

/**
 * Roll Comments Example
 * 
 * Demonstrates how to use roll comments to add descriptive text
 * to dice rolls with placeholder expansion.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPDice\PHPDice;

$dice = new PHPDice();

echo "Roll Comments Example\n";
echo "=====================\n\n";

// Basic comment
echo "1. Basic Comment\n";
$result = $dice->roll('1d20 + 5 # Attack roll');
echo "Expression: 1d20 + 5 # Attack roll\n";
echo "Result: {$result->total}\n";
echo "Comment: {$result->comment}\n\n";

// Comment with placeholder expansion
echo "2. Comment with Placeholder\n";
$character = [
    'strength' => 4,
    'proficiency' => 2,
];
$result = $dice->roll(
    '1d20 + $str$ + $prof$ # Strength check (STR: $str$, Prof: $prof$)',
    ['str' => $character['strength'], 'prof' => $character['proficiency']]
);
echo "Expression: 1d20 + \$str\$ + \$prof\$ # Strength check (STR: \$str\$, Prof: \$prof\$)\n";
echo "Result: {$result->total}\n";
echo "Comment: {$result->comment}\n\n";

// Comment with GitHub issue reference
echo "3. Comment with Issue Reference\n";
$result = $dice->roll('2d6 + 3 # Damage for issue #1');
echo "Expression: 2d6 + 3 # Damage for issue #1\n";
echo "Result: {$result->total}\n";
echo "Comment: {$result->comment}\n\n";

// Comment with advantage
echo "4. Comment with Advantage\n";
$result = $dice->roll('1d20 advantage + 3 # Attack with advantage');
echo "Expression: 1d20 advantage + 3 # Attack with advantage\n";
echo "Result: {$result->total}\n";
echo "Comment: {$result->comment}\n";
echo "Dice rolled: " . implode(', ', $result->diceValues) . "\n\n";

// Comment with DC check
echo "5. Comment with DC Check\n";
$result = $dice->roll('1d20 + 5 dc >= 15 # Saving throw vs poison');
echo "Expression: 1d20 + 5 dc >= 15 # Saving throw vs poison\n";
echo "Result: {$result->total}\n";
echo "Success: " . ($result->isSuccess ? 'Yes' : 'No') . "\n";
echo "Comment: {$result->comment}\n\n";

// Comment with multiple placeholders
echo "6. Complex Expression with Multiple Placeholders\n";
$abilities = [
    'dex' => 3,
    'prof' => 2,
    'magic' => 1,
];
$result = $dice->roll(
    '1d20 + $dex$ + $prof$ + $magic$ # Stealth check (DEX: $dex$, Prof: $prof$, Magic: $magic$)',
    $abilities
);
echo "Expression: 1d20 + \$dex\$ + \$prof\$ + \$magic\$ # Stealth check (DEX: \$dex\$, Prof: \$prof\$, Magic: \$magic\$)\n";
echo "Result: {$result->total}\n";
echo "Comment: {$result->comment}\n\n";

echo "Roll comments help document the purpose of each roll and\n";
echo "make it easier to understand roll results in logs and UIs!\n";
