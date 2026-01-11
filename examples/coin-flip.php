<?php

declare(strict_types=1);

/**
 * Coin Flip Examples
 *
 * Demonstrates coin flip dice (C) mechanics
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPDice\PHPDice;

$phpdice = new PHPDice();

echo "=== Coin Flip Dice Examples ===\n\n";

// 1. Basic Coin Flip
echo "1. Basic Coin Flip:\n";
$result = $phpdice->roll('1C');
$outcome = $result->total === 1 ? 'Heads' : 'Tails';
echo "   Result: {$outcome} ({$result->total})\n\n";

// 2. Multiple Coins
echo "2. Flip 5 Coins:\n";
$result = $phpdice->roll('5C');
$coins = array_map(fn($v) => $v === 1 ? 'H' : 'T', $result->diceValues);
echo "   Coins: " . implode(' ', $coins) . "\n";
echo "   Heads: {$result->total} / 5\n";
echo "   Tails: " . (5 - $result->total) . " / 5\n\n";

// 3. Best of Three
echo "3. Best of Three (Coin Toss Decision):\n";
$heads = 0;
$tails = 0;
for ($i = 1; $i <= 3; $i++) {
    $result = $phpdice->roll('1C');
    $outcome = $result->total === 1 ? 'Heads' : 'Tails';
    echo "   Flip {$i}: {$outcome}\n";
    if ($result->total === 1) {
        $heads++;
    } else {
        $tails++;
    }
}
echo "   Decision: " . ($heads > $tails ? 'Heads wins!' : ($tails > $heads ? 'Tails wins!' : 'Tie!')) . "\n\n";

// 4. Weighted Decision (Coin + Modifier)
echo "4. Weighted Coin Decision (1C + bonus):\n";
$bonus = 2; // Player has +2 bonus
$result = $phpdice->roll("1C+{$bonus}");
echo "   Coin: " . ($result->diceValues[0] === 1 ? 'Heads' : 'Tails') . " ({$result->diceValues[0]})\n";
echo "   Bonus: +{$bonus}\n";
echo "   Total: {$result->total}\n";
echo "   Success (>=2): " . ($result->total >= 2 ? 'Yes' : 'No') . "\n\n";

// 5. Coin Toss for Initiative
echo "5. Initiative Coin Toss (Two Players):\n";
$player1 = $phpdice->roll('1C+3'); // Player 1 with +3 initiative
$player2 = $phpdice->roll('1C+2'); // Player 2 with +2 initiative
echo "   Player 1: " . ($player1->diceValues[0] === 1 ? 'Heads' : 'Tails') . " + 3 = {$player1->total}\n";
echo "   Player 2: " . ($player2->diceValues[0] === 1 ? 'Heads' : 'Tails') . " + 2 = {$player2->total}\n";
echo "   First player: " . ($player1->total >= $player2->total ? 'Player 1' : 'Player 2') . "\n\n";

// 6. Probability of Success (Success Counting)
echo "6. Counting Successful Outcomes (Heads):\n";
$result = $phpdice->roll('10C count >=1');
echo "   Flipped 10 coins\n";
echo "   Heads (successes): {$result->successCount}\n";
echo "   Tails (failures): " . (10 - $result->successCount) . "\n\n";

// 7. Random Choice (0 = Option A, 1 = Option B)
echo "7. Random Choice Between Two Options:\n";
$result = $phpdice->roll('1C');
$choice = $result->total === 1 ? 'Go Left' : 'Go Right';
echo "   Choice: {$choice}\n\n";

// 8. Multiple Independent Decisions
echo "8. Three Independent Yes/No Decisions:\n";
$questions = ['Accept quest?', 'Take shortcut?', 'Rest at inn?'];
foreach ($questions as $i => $question) {
    $result = $phpdice->roll('1C');
    $answer = $result->total === 1 ? 'Yes' : 'No';
    echo "   {$question} {$answer}\n";
}
echo "\n";

// 9. Statistics
echo "9. Probability Analysis:\n";
$expression = $phpdice->parse('1C');
$stats = $expression->getStatistics();
echo "   Single coin flip (1C):\n";
echo "     Minimum: {$stats->minimum}\n";
echo "     Maximum: {$stats->maximum}\n";
echo "     Expected: {$stats->expected} (50% chance of each)\n\n";

$expression = $phpdice->parse('10C');
$stats = $expression->getStatistics();
echo "   Ten coins (10C):\n";
echo "     Minimum: {$stats->minimum} (all tails)\n";
echo "     Maximum: {$stats->maximum} (all heads)\n";
echo "     Expected: {$stats->expected} (average 5 heads)\n\n";

// 10. Complex Scenario: Coin Flip Competition
echo "10. Coin Flip Competition (Best Total in 3 Rounds):\n";
$player1Total = 0;
$player2Total = 0;

for ($round = 1; $round <= 3; $round++) {
    echo "   Round {$round}:\n";
    $p1 = $phpdice->roll('3C');
    $p2 = $phpdice->roll('3C');

    $p1Coins = array_map(fn($v) => $v === 1 ? 'H' : 'T', $p1->diceValues);
    $p2Coins = array_map(fn($v) => $v === 1 ? 'H' : 'T', $p2->diceValues);

    echo "     Player 1: " . implode(' ', $p1Coins) . " = {$p1->total} heads\n";
    echo "     Player 2: " . implode(' ', $p2Coins) . " = {$p2->total} heads\n";

    $player1Total += $p1->total;
    $player2Total += $p2->total;
}

echo "\n   Final Score:\n";
echo "     Player 1: {$player1Total} heads\n";
echo "     Player 2: {$player2Total} heads\n";
echo "     Winner: " . ($player1Total > $player2Total ? 'Player 1' :
                      ($player2Total > $player1Total ? 'Player 2' : 'Tie')) . "!\n\n";

echo "=== Examples Complete ===\n";
