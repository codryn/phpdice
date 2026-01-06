<?php

declare(strict_types=1);

/**
 * Call of Cthulhu 7th Edition Examples
 *
 * Demonstrates percentile dice mechanics and special rules
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPDice\PHPDice;

$phpdice = new PHPDice();

echo "=== Call of Cthulhu 7th Edition Examples ===\n\n";

// 1. Basic Skill Check
echo "1. Basic Skill Check (Library Use 65%):\n";
$result = $phpdice->roll('1d100');
$skillLevel = 65;

echo "   Roll: {$result->total}\n";
echo "   Skill: {$skillLevel}%\n";

if ($result->total <= $skillLevel / 5) {
    echo "   Result: EXTREME SUCCESS! (1/5 of skill)\n";
} elseif ($result->total <= $skillLevel / 2) {
    echo "   Result: HARD SUCCESS! (1/2 of skill)\n";
} elseif ($result->total <= $skillLevel) {
    echo "   Result: SUCCESS!\n";
} else {
    echo "   Result: FAILURE\n";
}
echo "\n";

// 2. Opposed Roll
echo "2. Opposed Roll (Stealth 50% vs Spot Hidden 60%):\n";
echo "   Attacker (Stealth 50%):\n";
$attackRoll = $phpdice->roll('1d100');
echo "     Roll: {$attackRoll->total}\n";

$attackSuccess = 'fail';
if ($attackRoll->total <= 10) {
    $attackSuccess = 'extreme';
    echo "     EXTREME SUCCESS\n";
} elseif ($attackRoll->total <= 25) {
    $attackSuccess = 'hard';
    echo "     HARD SUCCESS\n";
} elseif ($attackRoll->total <= 50) {
    $attackSuccess = 'regular';
    echo "     Regular success\n";
} else {
    echo "     Failure\n";
}

echo "   Defender (Spot Hidden 60%):\n";
$defendRoll = $phpdice->roll('1d100');
echo "     Roll: {$defendRoll->total}\n";

$defendSuccess = 'fail';
if ($defendRoll->total <= 12) {
    $defendSuccess = 'extreme';
    echo "     EXTREME SUCCESS\n";
} elseif ($defendRoll->total <= 30) {
    $defendSuccess = 'hard';
    echo "     HARD SUCCESS\n";
} elseif ($defendRoll->total <= 60) {
    $defendSuccess = 'regular';
    echo "     Regular success\n";
} else {
    echo "     Failure\n";
}

// Compare success levels
$levels = ['fail' => 0, 'regular' => 1, 'hard' => 2, 'extreme' => 3];
echo "\n   Outcome: ";
if ($levels[$attackSuccess] > $levels[$defendSuccess]) {
    echo "Attacker wins!\n";
} elseif ($levels[$defendSuccess] > $levels[$attackSuccess]) {
    echo "Defender wins!\n";
} else {
    echo "Tie - highest roll wins: ";
    echo ($attackRoll->total > $defendRoll->total ? "Defender" : "Attacker") . "\n";
}
echo "\n";

// 3. Pushed Roll
echo "3. Pushed Roll (desperate second attempt):\n";
echo "   First attempt (Climb 40%):\n";
$firstResult = $phpdice->roll('1d100');
echo "     Roll: {$firstResult->total}\n";

if ($firstResult->total > 40) {
    echo "     Failed! Pushing the roll...\n";
    echo "   Second attempt (consequences on failure):\n";
$secondResult = $phpdice->roll('1d100');
    echo "     Roll: {$secondResult->total}\n";

    if ($secondResult->total <= 40) {
        echo "     SUCCESS! Made it up safely.\n";
    } else {
        echo "     FAILURE! Fall damage - rolling 1d6...\n";
$damage = $phpdice->roll('1d6');
        echo "     Take {$damage->total} damage!\n";
    }
} else {
    echo "     Success on first try!\n";
}
echo "\n";

// 4. Sanity Check
echo "4. Sanity Check (seeing a Mi-Go):\n";
$currentSanity = 65;
echo "   Current SAN: {$currentSanity}\n";

$result = $phpdice->roll('1d100');
echo "   Roll: {$result->total}\n";

if ($result->total <= $currentSanity) {
    echo "   SUCCESS - Sanity check passed!\n";
    echo "   Loss: 0 SAN\n";
} else {
    echo "   FAILURE - Sanity check failed!\n";
    echo "   Rolling 1d10 SAN loss...\n";
$loss = $phpdice->roll('1d10');
    $newSanity = $currentSanity - $loss->total;
    echo "   Lost {$loss->total} SAN (now at {$newSanity})\n";

    if ($loss->total >= 5) {
        echo "   TEMPORARY INSANITY! (lost 5+ SAN in one go)\n";
    }
}
echo "\n";

// 5. Combat - Handgun Attack
echo "5. Combat - Handgun Attack (Firearms 45%):\n";
$result = $phpdice->roll('1d100');
echo "   Attack roll: {$result->total}\n";

if ($result->total <= 45) {
    echo "   HIT!\n";
    echo "   Rolling damage (1d10)...\n";
$damage = $phpdice->roll('1d10');
    echo "   Damage: {$damage->total}\n";

    if ($result->total <= 9) { // Extreme success
        echo "   EXTREME SUCCESS - Maximum damage and Impale!\n";
        echo "   Total damage: 10 + {$damage->total} = " . (10 + $damage->total) . "\n";
    } elseif ($result->total <= 22) { // Hard success
        echo "   HARD SUCCESS - Impale!\n";
        echo "   Roll again for extra damage...\n";
        $extraDamage = $phpdice->roll($damageExpr);
        echo "   Total damage: {$damage->total} + {$extraDamage->total} = " .
             ($damage->total + $extraDamage->total) . "\n";
    }
} else {
    echo "   MISS!\n";
}
echo "\n";

// 6. Bonus/Penalty Dice
echo "6. Bonus Die (aiming carefully, +1 bonus die):\n";
echo "   Rolling 1d100 with bonus die (pick lowest tens digit):\n";
$units = $phpdice->roll('1d10-1');

$tens1 = $phpdice->roll('1d10-1');

$tens2 = $phpdice->roll('1d10-1');

$chosenTens = min($tens1->total, $tens2->total);
$finalRoll = ($chosenTens * 10) + $units->total;

echo "   Units die: {$units->total}\n";
echo "   Tens die 1: {$tens1->total}0\n";
echo "   Tens die 2: {$tens2->total}0\n";
echo "   Choose lowest tens: {$chosenTens}0\n";
echo "   Final roll: {$finalRoll}\n\n";

echo "7. Penalty Die (rushed action, -1 penalty die):\n";
echo "   Rolling 1d100 with penalty die (pick highest tens digit):\n";
$units = $phpdice->roll('1d10-1');

$tens1 = $phpdice->roll('1d10-1');

$tens2 = $phpdice->roll('1d10-1');

$chosenTens = max($tens1->total, $tens2->total);
$finalRoll = ($chosenTens * 10) + $units->total;

echo "   Units die: {$units->total}\n";
echo "   Tens die 1: {$tens1->total}0\n";
echo "   Tens die 2: {$tens2->total}0\n";
echo "   Choose highest tens: {$chosenTens}0\n";
echo "   Final roll: {$finalRoll}\n\n";

// 7. Luck Roll
echo "8. Luck Roll (Current Luck 50):\n";
$result = $phpdice->roll('1d100');
echo "   Roll: {$result->total}\n";
echo "   Luck: 50\n";

if ($result->total <= 50) {
    echo "   SUCCESS - Lucky!\n";
} else {
    echo "   FAILURE - Unlucky!\n";
}
echo "   (Spending Luck reduces your Luck stat permanently)\n\n";

// 8. Characteristic Roll (STR vs STR contest)
echo "9. Characteristic Roll - Breaking Down Door:\n";
echo "   STR 65 vs Door STR 50\n";
echo "   Rolling STR check:\n";
$result = $phpdice->roll('1d100');
echo "   Roll: {$result->total}\n";

if ($result->total <= 65) {
    echo "   SUCCESS - Door breaks!\n";
} else {
    echo "   FAILURE - Door holds!\n";
}
echo "\n";

// 9. Fumble and Critical
echo "10. Critical Success and Fumbles:\n";
$result = $phpdice->roll('1d100');
$skillLevel = 55;

echo "   Roll: {$result->total}\n";
echo "   Skill: {$skillLevel}%\n";

if ($result->total == 1) {
    echo "   CRITICAL SUCCESS! (rolled 01)\n";
} elseif ($result->total == 100 || ($result->total >= 96 && $skillLevel < 50)) {
    echo "   FUMBLE! (rolled " . ($result->total == 100 ? "00/100" : "96+") . ")\n";
    echo "   Roll on Fumble table for consequences!\n";
} elseif ($result->total <= $skillLevel) {
    echo "   Regular success\n";
} else {
    echo "   Failure\n";
}
echo "\n";

echo "=== Examples Complete ===\n";
