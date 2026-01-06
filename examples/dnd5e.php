<?php

declare(strict_types=1);

/**
 * D&D 5e Examples
 *
 * Demonstrates dice rolling for Dungeons & Dragons 5th Edition
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPDice\PHPDice;

$phpdice = new PHPDice();

echo "=== D&D 5e Dice Rolling Examples ===\n\n";

// 1. Ability Score Generation (4d6, drop lowest)
echo "1. Generating Ability Scores (4d6, drop lowest):\n";
for ($i = 1; $i <= 6; $i++) {
    $result = $phpdice->roll('4d6 keep 3 highest');
    echo "   Ability {$i}: {$result->total} (rolled: " .
         implode(', ', $result->diceValues) . ")\n";
}
echo "\n";

// 2. Advantage on Attack Roll
echo "2. Attack Roll with Advantage:\n";
$result = $phpdice->roll('1d20 advantage + 5');
echo "   Rolled 2d20: " . implode(', ', $result->diceValues) . "\n";
echo "   Kept higher die, added +5 modifier\n";
echo "   Total: {$result->total}\n\n";

// 3. Disadvantage on Saving Throw
echo "3. Saving Throw with Disadvantage:\n";
$result = $phpdice->roll('1d20 disadvantage + 3');
echo "   Rolled 2d20: " . implode(', ', $result->diceValues) . "\n";
echo "   Kept lower die, added +3 modifier\n";
echo "   Total: {$result->total}\n\n";

// 4. Skill Check vs DC
echo "4. Skill Check (Athletics +5 vs DC 15):\n";
$result = $phpdice->roll('1d20+5 >= 15');
echo "   Roll: {$result->total}\n";
echo "   Result: " . ($result->isSuccess ? 'SUCCESS!' : 'FAILURE') . "\n\n";

// 5. Critical Hit Detection
echo "5. Attack Roll with Critical Detection:\n";
$result = $phpdice->roll('1d20 crit 15 glitch 1 +7 >= 15');
echo "   Result: {$result->total} (Rolled: " . implode(', ', $result->diceValues) . ")\n";
if ($result->isCriticalSuccess) {
    echo "   *** CRITICAL HIT CHANGE! ***\n";
} elseif ($result->isCriticalFailure) {
    echo "   *** CRITICAL MISS! ***\n";
} else {
    echo "   " . ($result->isSuccess ? 'Hit!' : 'Miss!') . "\n";
}
echo "\n";

// 6. Normal Weapon Damage
echo "6. Longsword Damage (1d8 + 3 STR):\n";
$variables = ['STR' => 3];
$result = $phpdice->roll('1d8+%STR%', $variables);
echo "   Damage: {$result->total} (Rolled: " . implode(', ', $result->diceValues) . ")\n\n";

// 7. Fireball Spell (8d6)
echo "7. Fireball Spell Damage (8d6):\n";
$result = $phpdice->roll('8d6');
echo "   Individual dice: " . implode(', ', $result->diceValues) . "\n";
echo "   Total damage: {$result->total}\n";
echo "   (Targets save for half)\n\n";

// 8. Initiative Roll
echo "8. Initiative Roll (+2 DEX):\n";
$result = $phpdice->roll('1d20+2');
echo "   Initiative: {$result->total}\n\n";

// 9. Death Saving Throw
echo "9. Death Saving Throw:\n";
$result = $phpdice->roll('1d20 crit 20 glitch 1');
$die = $result->diceValues[0];
echo "   Roll: {$die}\n";
if ($result->isCriticalSuccess) {
    echo "   Natural 20! Character stabilizes and regains 1 HP!\n";
} elseif ($result->isCriticalFailure) {
    echo "   Natural 1! Counts as TWO failures!\n";
} elseif ($die >= 10) {
    echo "   Success!\n";
} else {
    echo "   Failure.\n";
}
echo "\n";

// 10. Healing Spell (Cure Wounds at 3rd level)
echo "10. Cure Wounds (3rd level): 3d8 + 4 (WIS modifier):\n";
$result = $phpdice->roll('3d8+4');
echo "   HP restored: {$result->total}\n\n";

// 11. Character with Placeholders
echo "11. Character Sheet Integration (using variables):\n";
$character = [
    'str' => 3,        // STR modifier
    'proficiency' => 3, // Proficiency bonus
];

echo "   Athletics Check (STR + Prof):\n";
$result = $phpdice->roll('1d20+%str%+%proficiency%', $character);
echo "   1d20 + {$character['str']} (STR) + {$character['proficiency']} (Prof) = {$result->total}\n\n";

// 12. Probability Analysis
echo "12. Probability Analysis (Attack Roll +5 vs AC 15):\n";
$expression = $phpdice->parse('1d20+5');
$stats = $expression->getStatistics();
echo "   Minimum: {$stats->minimum}\n";
echo "   Maximum: {$stats->maximum}\n";
echo "   Expected: {$stats->expected}\n";
$hitChance = (20 - 10 + 1) / 20 * 100; // Need 10+ on die
echo "   Hit chance: {$hitChance}% (need 10+ on d20)\n\n";

echo "13. Advantage Probability Analysis:\n";
$expression = $phpdice->parse('1d20 advantage');
$stats = $expression->getStatistics();
echo "   Without advantage, average d20 roll: 10.5\n";
echo "   With advantage, average roll: {$stats->expected}\n";
echo "   Advantage gives approximately +" . round($stats->expected - 10.5, 1) . " bonus on average\n\n";

echo "=== Examples Complete ===\n";
