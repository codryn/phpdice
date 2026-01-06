<?php

declare(strict_types=1);

/**
 * FATE Core Examples
 *
 * Demonstrates Fudge dice (dF) mechanics
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPDice\PHPDice;

$phpdice = new PHPDice();

echo "=== FATE Core Dice Rolling Examples ===\n\n";

// 1. Basic FATE Roll
echo "1. Basic Skill Roll (Fair +2):\n";
$result = $phpdice->roll('4dF+2');

// Map dice values to symbols
$symbols = array_map(function ($v) {
    return match ($v) {
        -1 => '[-]',
        0 => '[ ]',
        1 => '[+]',
    };
}, $result->diceValues);

echo "   Dice: " . implode(' ', $symbols) . "\n";
$diceTotal = array_sum($result->diceValues);
echo "   Dice total: " . ($diceTotal >= 0 ? '+' : '') . "{$diceTotal}\n";
echo "   Skill level: +2\n";
echo "   Final result: " . ($result->total >= 0 ? '+' : '') . "{$result->total}\n";

// Interpret result
$ladder = [
    8 => 'Legendary',
    7 => 'Epic',
    6 => 'Fantastic',
    5 => 'Superb',
    4 => 'Great',
    3 => 'Good',
    2 => 'Fair',
    1 => 'Average',
    0 => 'Mediocre',
    -1 => 'Poor',
    -2 => 'Terrible',
];

foreach ($ladder as $value => $name) {
    if ($result->total >= $value) {
        echo "   That's a {$name} result!\n";
        break;
    }
}
echo "\n";

// 2. Opposed Roll
echo "2. Opposed Roll (Attacker vs Defender):\n";
echo "   Attacker (Fight +3):\n";
$attackResult = $phpdice->roll('4dF+3');
$attackSymbols = array_map(fn($v) => match ($v) {
    -1 => '[-]', 0 => '[ ]', 1 => '[+]',
}, $attackResult->diceValues);
echo "     Dice: " . implode(' ', $attackSymbols) . "\n";
echo "     Total: " . ($attackResult->total >= 0 ? '+' : '') . "{$attackResult->total}\n";

echo "   Defender (Defend +2):\n";
$defendResult = $phpdice->roll('4dF+2');
$defendSymbols = array_map(fn($v) => match ($v) {
    -1 => '[-]', 0 => '[ ]', 1 => '[+]',
}, $defendResult->diceValues);
echo "     Dice: " . implode(' ', $defendSymbols) . "\n";
echo "     Total: " . ($defendResult->total >= 0 ? '+' : '') . "{$defendResult->total}\n";

$shifts = $attackResult->total - $defendResult->total;
echo "\n   Outcome: ";
if ($shifts > 0) {
    echo "Attacker succeeds with {$shifts} shift(s)!\n";
    echo "   Defender takes {$shifts} stress (or consequences)\n";
} elseif ($shifts < 0) {
    echo "Defender successfully defends!\n";
} else {
    echo "Tie! Defender wins.\n";
}
echo "\n";

// 3. Overcome Obstacle
echo "3. Overcome Obstacle (Athletics +2 vs Difficulty +2):\n";
$result = $phpdice->roll('4dF+2');
$symbols = array_map(fn($v) => match ($v) {
    -1 => '[-]', 0 => '[ ]', 1 => '[+]',
}, $result->diceValues);

echo "   Dice: " . implode(' ', $symbols) . "\n";
echo "   Result: " . ($result->total >= 0 ? '+' : '') . "{$result->total}\n";
echo "   Difficulty: +2\n";

if ($result->total >= 4) {
    echo "   SUCCESS WITH STYLE! (3+ over difficulty)\n";
} elseif ($result->total >= 2) {
    echo "   SUCCESS!\n";
} elseif ($result->total >= 1) {
    echo "   TIE - Succeed at cost\n";
} else {
    echo "   FAILURE\n";
}
echo "\n";

// 4. Create Advantage
echo "4. Create Advantage (Lore +3):\n";
$result = $phpdice->roll('4dF+3');
$symbols = array_map(fn($v) => match ($v) {
    -1 => '[-]', 0 => '[ ]', 1 => '[+]',
}, $result->diceValues);

echo "   Dice: " . implode(' ', $symbols) . "\n";
echo "   Result: " . ($result->total >= 0 ? '+' : '') . "{$result->total}\n";
$difficulty = 2;
echo "   Difficulty: +{$difficulty}\n";

if ($result->total >= $difficulty + 3) {
    echo "   SUCCESS WITH STYLE!\n";
    echo "   Create aspect with 2 free invokes\n";
} elseif ($result->total >= $difficulty) {
    echo "   SUCCESS!\n";
    echo "   Create aspect with 1 free invoke\n";
} elseif ($result->total >= $difficulty - 1) {
    echo "   TIE - Create boost instead\n";
} else {
    echo "   FAILURE - Opponent gets free invoke\n";
}
echo "\n";

// 5. Using Fate Points
echo "5. Reroll Using Fate Point:\n";
echo "   Initial roll (Shoot +2):\n";
$firstResult = $phpdice->roll('4dF+2');
$firstSymbols = array_map(fn($v) => match ($v) {
    -1 => '[-]', 0 => '[ ]', 1 => '[+]',
}, $firstResult->diceValues);
echo "     Dice: " . implode(' ', $firstSymbols) . "\n";
echo "     Result: " . ($firstResult->total >= 0 ? '+' : '') . "{$firstResult->total}\n";

if ($firstResult->total < 2) {
    echo "   That's not good! Spending Fate Point to invoke aspect 'Quick Draw' for +2\n";
    $finalTotal = $firstResult->total + 2;
    echo "   New total: " . ($finalTotal >= 0 ? '+' : '') . "{$finalTotal}\n";
}
echo "\n";

// 6. Zone Movement Challenge
echo "6. Multiple Zone Movement (3 zones):\n";
$baseSkill = 1; // Average Athletics
for ($zone = 1; $zone <= 3; $zone++) {
    echo "   Zone {$zone} (Difficulty +1):\n";
    $result = $phpdice->roll("4dF+{$baseSkill}");
    $symbols = array_map(fn($v) => match ($v) {
        -1 => '[-]', 0 => '[ ]', 1 => '[+]',
    }, $result->diceValues);
    echo "     Dice: " . implode(' ', $symbols) . "\n";
    echo "     Result: " . ($result->total >= 0 ? '+' : '') . "{$result->total}\n";

    if ($result->total >= 1) {
        echo "     SUCCESS - Moved through zone\n";
    } else {
        echo "     FAILURE - Stopped!\n";
        break;
    }
}
echo "\n";

// 7. Stress Track (Tracking Damage)
echo "7. Taking Hits and Stress:\n";
$stress = [false, false, false]; // 3-box stress track
$consequences = [];

echo "   Hit 1 (2 shifts):\n";
echo "     Marking 2-stress box\n";
$stress[1] = true;

echo "   Hit 2 (3 shifts):\n";
echo "     No 3-stress box! Must absorb with stress boxes or consequences.\n";
echo "     Using 1-stress box (1 shift) + Mild Consequence (2 shifts)\n";
$stress[0] = true;
$consequences[] = 'Mild: Bruised Ribs';

echo "   Current status:\n";
echo "     Stress: [X] [X] [ ]\n";
echo "     Consequences: " . implode(', ', $consequences) . "\n\n";

// 8. Probability Analysis
echo "8. Probability Analysis (4dF):\n";
$expression = $phpdice->parse('4dF');
$stats = $expression->getStatistics();
echo "   Minimum: {$stats->minimum} (all minuses)\n";
echo "   Maximum: {$stats->maximum} (all pluses)\n";
echo "   Expected: {$stats->expected} (bell curve centered at 0)\n";
echo "   Most likely results: -1, 0, or +1 (81% combined probability)\n\n";

// 9. Different Skill Levels
echo "9. Comparing Skill Levels:\n";
$skills = ['Poor' => -1, 'Average' => 1, 'Good' => 3, 'Great' => 4, 'Superb' => 5];

foreach ($skills as $name => $level) {
    $result = $phpdice->roll("4dF" . ($level >= 0 ? '+' : '') . "{$level}");
    $stats = $expression->getStatistics();
    $min = $stats->minimum >= 0 ? '+' . $stats->minimum : $stats->minimum;
    $max = $stats->maximum >= 0 ? '+' . $stats->maximum : $stats->maximum;
    $exp = $stats->expected >= 0 ? '+' . $stats->expected : $stats->expected;
    echo "   {$name} ({$level}): Range {$min} to {$max}, Expected {$exp}\n";
}
echo "\n";

// 10. Contest (Best of Multiple Rolls)
echo "10. Contest - Resources Check (3 exchanges):\n";
$player1Score = 0;
$player2Score = 0;

for ($exchange = 1; $exchange <= 3; $exchange++) {
    echo "   Exchange {$exchange}:\n";

$p1Result = $phpdice->roll('4dF+3');

$p2Result = $phpdice->roll('4dF+2');

    echo "     Player 1: " . ($p1Result->total >= 0 ? '+' : '') . "{$p1Result->total}\n";
    echo "     Player 2: " . ($p2Result->total >= 0 ? '+' : '') . "{$p2Result->total}\n";

    if ($p1Result->total > $p2Result->total) {
        $player1Score++;
        echo "     Player 1 wins this exchange\n";
    } elseif ($p2Result->total > $p1Result->total) {
        $player2Score++;
        echo "     Player 2 wins this exchange\n";
    } else {
        echo "     Tie - no points\n";
    }
}

echo "\n   Final Score: Player 1: {$player1Score}, Player 2: {$player2Score}\n";
echo "   Winner: " . ($player1Score > $player2Score ? 'Player 1' :
                      ($player2Score > $player1Score ? 'Player 2' : 'Tie')) . "\n\n";

echo "=== Examples Complete ===\n";
