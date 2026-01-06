<?php

declare(strict_types=1);

/**
 * Shadowrun 5e Examples
 *
 * Demonstrates dice pool and success counting mechanics
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPDice\PHPDice;

$phpdice = new PHPDice();

echo "=== Shadowrun 5e Dice Rolling Examples ===\n\n";

// 1. Basic Skill Test
echo "1. Skill Test (12 dice, threshold 5):\n";
$result = $phpdice->roll('12d6 >=5');
echo "   Dice rolled: " . implode(', ', $result->diceValues) . "\n";
echo "   Successes (5s and 6s): {$result->successCount}\n\n";

// 2. Opposed Test
echo "2. Opposed Test:\n";
echo "   Attacker (10 dice):\n";
$attackerResult = $phpdice->roll('10d6 >=5');
echo "   Rolled: " . implode(', ', $attackerResult->diceValues) . "\n";
echo "   Successes: {$attackerResult->successCount}\n\n";

echo "   Defender (8 dice):\n";
$defenderResult = $phpdice->roll('8d6 >=5');
echo "   Rolled: " . implode(', ', $defenderResult->diceValues) . "\n";
echo "   Successes: {$defenderResult->successCount}\n\n";

$netHits = $attackerResult->successCount - $defenderResult->successCount;
if ($netHits > 0) {
    echo "   Result: Attacker wins with {$netHits} net hits!\n";
} elseif ($netHits < 0) {
    echo "   Result: Defender wins! Attack blocked.\n";
} else {
    echo "   Result: Tie! Defender wins by default.\n";
}
echo "\n";

// 3. Edge - Reroll 1s (Rule of Six not modeled, using reroll)
echo "3. Using Edge (reroll failures - modeling with reroll 1s):\n";
$result = $phpdice->roll('10d6 reroll ==1 >=5');
echo "   Final dice: " . implode(', ', $result->diceValues) . "\n";
echo "   Successes: {$result->successCount}\n";
if ($result->rerollHistory !== null) {
    echo "   Rerolled " . count($result->rerollHistory) . " dice\n";
}
echo "\n";

// 4. Glitch Detection (more than half dice are 1s)
echo "4. Glitch Check (14 dice):\n";
$result = $phpdice->roll('14d6 >=5');
echo "   Rolled: " . implode(', ', $result->diceValues) . "\n";
echo "   Successes: {$result->successCount}\n";
$ones = count(array_filter($result->diceValues, fn($v) => $v === 1));
$isGlitch = $ones > (count($result->diceValues) / 2);
echo "   Ones rolled: {$ones}\n";
if ($isGlitch) {
    if ($result->successCount === 0) {
        echo "   *** CRITICAL GLITCH! ***\n";
    } else {
        echo "   *** GLITCH! (but succeeded) ***\n";
    }
} else {
    echo "   No glitch.\n";
}
echo "\n";

// 5. Extended Test
echo "5. Extended Test (Need 15 total successes):\n";
$totalSuccesses = 0;
$interval = 1;
$targetSuccesses = 20;
$pool = 10;

while ($totalSuccesses < $targetSuccesses && $interval <= 10 && $pool > 0) {
    $result = $phpdice->roll("{$pool}d6 >=5");
    $totalSuccesses += $result->successCount;

    echo "   Interval {$interval}, pool {$pool}: {$result->successCount} successes " .
         "(total: {$totalSuccesses})\n";
    $interval++;
    $pool--;
}

if ($totalSuccesses >= $targetSuccesses) {
    echo "   SUCCESS! Completed in " . ($interval - 1) . " intervals.\n";
} else {
    echo "   FAILED after " . ($interval - 1) . " intervals.\n";
}
echo "\n";

// 6. Hacking - Multiple Attack Tests
echo "6. Hacking Test (12 dice vs 10 dice defense):\n";
for ($i = 1; $i <= 3; $i++) {
    echo "   Attack {$i}:\n";
    $attackResult = $phpdice->roll('12d6 >=5');

    
    $defenseResult = $phpdice->roll('10d6 >=5');    $netHits = $attackResult->successCount - $defenseResult->successCount;
    echo "     Attacker: {$attackResult->successCount} successes\n";
    echo "     Defender: {$defenseResult->successCount} successes\n";
    echo "     Net hits: " . max(0, $netHits) . "\n";
}
echo "\n";

// 7. Damage Resistance
echo "7. Damage Resistance Test:\n";
$damageValue = 10;
echo "   Incoming damage: {$damageValue}P\n";
echo "   Resistance Test (9 dice):\n";
$result = $phpdice->roll('9d6 >=5');
echo "   Rolled: " . implode(', ', $result->diceValues) . "\n";
echo "   Successes: {$result->successCount}\n";
$finalDamage = max(0, $damageValue - $result->successCount);
echo "   Final damage: {$finalDamage}P\n\n";

// 8. Summoning - Variable Dice Pool
echo "8. Summoning Spirit (Force 6):\n";
$force = 6;
$summoningSkill = 5;
$dicePool = $summoningSkill + $force;
echo "   Summoning dice pool: {$dicePool} (Skill {$summoningSkill} + Force {$force})\n";
$result = $phpdice->roll("{$dicePool}d6 >=5");
echo "   Rolled: " . implode(', ', $result->diceValues) . "\n";
echo "   Successes: {$result->successCount}\n";
echo "   Spirit owes " . max(0, $result->successCount) . " services.\n\n";

// 9. Probability Analysis
echo "9. Probability Analysis (10d6 pool):\n";
$expression = $phpdice->parse('10d6 >=5');
$stats = $expression->getStatistics();
echo "   Minimum successes: {$stats->minimum}\n";
echo "   Maximum successes: {$stats->maximum}\n";
echo "   Expected successes: {$stats->expected}\n";
echo "   (Each die has 33.33% chance to succeed)\n\n";

// 10. Scatter (for grenades - simplified)
echo "10. Grenade Scatter Distance (2d6 - Successes):\n";
$result = $phpdice->roll('2d6 - 3');
if ($result->total <= 0) {
    echo "   No scatter! Hits target directly.\n\n";
} else {
    echo "   Scatter distance: " . max(0, $result->total) . " meters\n";
    $dir = $phpdice->roll('2d6');
    $dirs = [
        2 => 'Backward',
        3 => 'Backward-Left',
        4 => 'Left',
        5 => 'Forward-Left',
        6 => 'Forward-Left',
        7 => 'Forward',
        8 => 'Forward-Right',
        9 => 'Forward-Right',
        10 => 'Right',
        11 => 'Backward-Right',
        12 => 'Backward'
    ];
    echo "   Direction: {$dirs[$dir->total]} (Roll {$dir->total})\n\n";
}

echo "=== Examples Complete ===\n";
