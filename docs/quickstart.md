# Quick Start Guide: PHPDice Library

**Goal**: Get up and running with dice parsing and rolling in 10 minutes

## Installation

### Via Composer (Recommended)

```bash
composer require codryn/phpdice
```

### Manual Installation

```bash
git clone https://github.com/codryn/phpdice.git
cd phpdice
composer install
```

## Basic Usage

### 1. Simple Dice Roll (30 seconds)

```php
<?php
require 'vendor/autoload.php';

use PHPDice\PHPDice;

// Create instance
$dice = new PHPDice();

// Roll dice directly
$result = $dice->roll("3d6");

echo "Rolled: " . $result->total . "\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Example output:
// Rolled: 14
// Dice: 5, 6, 3
```

### 2. Roll with Modifiers (1 minute)

```php
// Attack roll with +5 bonus
$result = $dice->roll("1d20+5");

echo "Attack roll: {$result->total}\n";
echo "Natural die: {$result->diceValues[0]}\n";

// Example output:
// Attack roll: 18
// Natural die: 13

// Complex arithmetic with parentheses
$result = $dice->roll("(2d6+3)*2");

echo "Damage: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Example output:
// Damage: 22  // (2d6=8 + 3) * 2
// Dice: 5, 3

// Mathematical function
$result = $dice->roll("floor(1d20/2)");

echo "Result: {$result->total}\n";

// Example output:
// Result: 7  // floor(15/2) = floor(7.5) = 7
```

### 3. Advantage / Disadvantage (2 minutes)

```php
// D&D 5e advantage: roll 2d20, keep highest
$result = $dice->roll("1d20 advantage");

echo "Advantage roll: {$result->total}\n";
echo "Both dice: " . implode(", ", $result->diceValues) . "\n";
echo "Kept die index: " . $result->keptDice[0] . "\n";

// Example output:
// Advantage roll: 17
// Both dice: 17, 8
// Kept die index: 0
```

### 4. Character Stats with Variables (3 minutes)

```php
// Character has Strength 3, Dexterity 2
$result = $dice->roll("1d20+$str$+$dex$", [
    "str" => 3,
    "dex" => 2
]);

echo "Ability check: {$result->total}\n";
echo "Die roll: {$result->diceValues[0]}\n";
echo "Modified by: +5 (str +3, dex +2)\n";

// Example output:
// Ability check: 18
// Die roll: 13
// Modified by: +5 (str +3, dex +2)
```

### 5. Success Counting for Dice Pools (4 minutes)

```php
// Shadowrun-style: roll 5d6, count 4+ as successes
$result = $dice->roll("5d6 count >=4");

echo "Successes: {$result->successCount}\n";
echo "Dice rolled: " . implode(", ", $result->diceValues) . "\n";

// Mark successes
foreach ($result->diceValues as $index => $value) {
    echo ($value >= 4 ? "✓" : "✗") . " ";
}
echo "\n";

// Example output:
// Successes: 3
// Dice rolled: 6, 5, 4, 2, 1
// ✓ ✓ ✓ ✗ ✗
```

### 6. Critical Success Detection (5 minutes)

```php
// D&D attack with critical on natural 20
$result = $dice->roll("1d20+5 crit 20");

echo "Attack: {$result->total}\n";

if ($result->isCriticalSuccess) {
    echo "🎯 CRITICAL HIT!\n";
} else {
    echo "Regular hit\n";
}

// Example output (if rolled 20):
// Attack: 25
// 🎯 CRITICAL HIT!
```

### 7. Statistical Analysis (7 minutes)

```php
// Get probabilities without rolling - use parse() for statistics
$expression = $dice->parse("3d6+5");

$stats = $expression->getStatistics();

echo "Expression: 3d6+5\n";
echo "Minimum: {$stats->minimum}\n";
echo "Maximum: {$stats->maximum}\n";
echo "Expected: {$stats->expected}\n";

// Output:
// Expression: 3d6+5
// Minimum: 8
// Maximum: 23
// Expected: 15.5
```

## Common Game System Examples

### D&D 5e Attack Roll

```php
// Attack with weapon (+5 bonus), advantage, critical on 20
$result = $dice->roll("1d20+5 advantage crit 20");

echo "Attack: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

if ($result->isCriticalSuccess) {
    echo "Critical hit! Roll damage twice.\n";
}
```

See ../examples/dnd5e.php for more D&D 5e examples.

### D&D Character Stat Rolling

```php
// Roll 4d6, drop lowest (standard stat generation)
$result = $dice->roll("4d6 keep 3 highest");

echo "Stat: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";
echo "Dropped: {$result->diceValues[$result->discardedDice[0]]}\n";

// Example output:
// Stat: 15
// Dice: 5, 6, 4, 2
// Dropped: 2
```

See ../examples/dnd5e.php for more D&D 5e examples.

### Pathfinder Ability Check

```php
// Skill check with +7 modifier against DC 15
$result = $dice->roll("1d20+7 dc >=15");

echo "Roll: {$result->total}\n";
echo $result->isSuccess ? "✓ Success!" : "✗ Failure";
echo "\n";
```

### Shadowrun Dice Pool

```php
// Roll 8 dice, count 5+ as successes
$result = $dice->roll("8d6 count >=5");

echo "Hits: {$result->successCount}\n";

if ($result->successCount >= 4) {
    echo "Overwhelming success!\n";
} elseif ($result->successCount >= 2) {
    echo "Success\n";
} else {
    echo "Failure\n";
}
```

See ../examples/shadowrun.php for more Shadowrun examples.

### Savage Worlds (Exploding Dice)

```php
// Savage Worlds trait test: exploding d6
$result = $dice->roll("1d6 explode");

echo "Trait test: {$result->total}\n";

if ($result->explosionHistory) {
    echo "Exploded! Chain: " . implode(" + ", $result->explosionHistory[0]) . "\n";
}

// Example output:
// Trait test: 14
// Exploded! Chain: 6 + 6 + 2

// Wild die (explode on 6, max 10 explosions)
$result = $dice->roll("1d6 explode 10 >=6");

echo "Wild die: {$result->total}\n";
```

See ../examples/savage-worlds.php for more Savage Worlds examples.

### FATE / Fudge Dice

```php
// 4 FATE dice for skill check
$result = $dice->roll("4dF+3"); // +3 skill bonus

echo "Result: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Interpret FATE ladder
$ladder = [
    -2 => "Terrible", -1 => "Poor", 0 => "Mediocre",
    1 => "Average", 2 => "Fair", 3 => "Good",
    4 => "Great", 5 => "Superb", 6 => "Fantastic"
];

echo "Level: " . ($ladder[$result->total] ?? "Legendary") . "\n";
```

See ../examples/fate.php for more Fate examples.

## Error Handling

### Invalid Syntax

```php
try {
    $expression = $dice->parse("3d"); // Invalid
} catch (\PHPDice\Exception\ParseException $e) {
    echo "Parse error: {$e->getMessage()}\n";
    // Output: Parse error: Invalid dice notation
}
```

### Missing Variables

```php
try {
    $expression = $dice->parse("1d20+$str$"); // Missing variable
} catch (\PHPDice\Exception\ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
    // Output: Validation error: Missing variable: $str$
}
```

### Invalid Parameters

```php
try {
    // Can't keep 5 dice from a 3d6 roll
    $expression = $dice->parse("3d6 keep 5 highest");
} catch (\PHPDice\Exception\ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
    // Output: Validation error: Keep count exceeds rolls
}
```

## Advanced Features

### Reroll Mechanics

```php
// Reroll 1s and 2s once (Great Weapon Fighting in D&D 5e)
// Default limit: 100 rerolls per die
$result = $dice->roll("2d6 reroll <=2");

echo "Damage: {$result->total}\n";
echo "Final dice: " . implode(", ", $result->diceValues) . "\n";

if ($result->rerolledDice) {
    echo "Rerolled:\n";
    foreach ($result->rerolledDice as $index => $original) {
        echo "  Position $index: $original → {$result->diceValues[$index]}\n";
    }
}

// Explicit reroll limit: reroll 1s up to 2 times per die
$result = $dice->roll("4d6 reroll 2 <=1");

echo "Roll: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Example output:
// Roll: 18
// Dice: 5, 4, 6, 3
// (Each 1 was rerolled up to 2 times)
```

### Exploding Dice (Savage Worlds)

```php
// Basic explosion: explode on max value (6 for d6)
// Default limit: 100 explosions per die
$result = $dice->roll("3d6 explode");

echo "Total: {$result->total}\n";
echo "Final values: " . implode(", ", $result->diceValues) . "\n";

if ($result->explosionHistory) {
    echo "Explosion chains:\n";
    foreach ($result->explosionHistory as $index => $chain) {
        echo "  Die $index: " . implode(" + ", $chain) . " = {$result->diceValues[$index]}\n";
    }
}

// Example output:
// Total: 28
// Final values: 16, 5, 7
// Explosion chains:
//   Die 0: 6 + 6 + 4 = 16
//   Die 2: 6 + 1 = 7

// Explode on 5 or 6, max 3 explosions per die
$result = $dice->roll("3d6 explode 3 >=5");

echo "Total: {$result->total}\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Example output:
// Total: 25
// Dice: 14, 6, 5
// (Die 0 exploded 3 times: 6+5+3=14, stopped at limit)
```

### Comparison Operators (DC Checks)

```php
// Save against DC 15 using 'dc' keyword
$result = $dice->roll("1d20+3 dc >=15");

echo "Saving throw: {$result->total}\n";
echo ($result->isSuccess ? "✓ Saved!" : "✗ Failed") . "\n";
```

### Multiple Modifiers Combined

```php
// Complex roll: advantage, modifier, critical, DC check
$result = $dice->roll("1d20+5 advantage crit 20 dc >=15");

echo "Roll: {$result->total}\n";
echo "Success: " . ($result->isSuccess ? "Yes" : "No") . "\n";
echo "Critical: " . ($result->isCriticalSuccess ? "Yes" : "No") . "\n";
```

## Next Steps

- **Full Documentation**: See [api.md](API.md) for complete API reference
- **Examples**: Browse [../examples](examples) for more game system examples
- **Testing**: Learn how to test dice expressions in your application
- **Contributing**: Check [../CONTRIBUTING.md](CONTRIBUTING.md) to contribute

## Performance Tips

1. **Reuse expressions**: Parse once, roll many times
   ```php
   // For statistics only, parse once
   $expr = $dice->parse("3d6+5");
   $stats = $expr->getStatistics();
   
   // For repeated rolls with same expression
   for ($i = 0; $i < 100; $i++) {
       $result = $dice->roll("3d6+5");  // Optimized internally
   }
   ```

2. **Statistics are free**: Get probabilities without rolling
   ```php
   $expr = $dice->parse("1d20+5");
   $stats = $expr->getStatistics();
   $min = $stats->minimum;  // No roll needed
   ```

3. **Validate early**: Parser catches errors immediately
   ```php
   // This validates immediately at parse/roll time
   $result = $dice->roll("1d20+$str$", ["str" => 3]);
   ```

## Troubleshooting

**Q: "Missing variable" error**
- Ensure all placeholders have values in the `variables` array
- Use `$name$` syntax for all placeholders (e.g., `1d20+$str$` not `1d20+str`)
- Variable names are case-sensitive

**Q: Expression parses but rolls incorrectly**
- Check operator precedence (see documentation)
- Verify whitespace doesn't split numbers

**Q: Statistics seem wrong**
- Remember success counting returns count, not sum
- Advantage/disadvantage affect expected values

**Q: Need to support custom dice?**
- Standard dice support any XdY notation
- For truly custom mechanics, extend the library

**Q: "Invalid explosion range" error**
- Cannot explode on all possible die values (infinite loop)
- Invalid: `1d6 explode <=6` (all values explode)
- Valid: `1d6 explode >=6` (only max explodes), `1d6 explode <=1` (only min explodes)

**Q: "Invalid reroll range" error**
- Cannot reroll all possible die values (infinite loop)
- Invalid: `1d6 reroll <=6` (all values reroll)
- Valid: `1d6 reroll <=2` (only 1-2 reroll), `1d6 reroll >=6` (only max rerolls)

**Q: "Dice must have at least 2 sides" error**
- Minimum die size is 2 sides (1-sided dice are not valid)
- Use constants or modifiers instead: `+5` not `1d1*5`

## Support

- **Issues**: [GitHub Issues](https://github.com/codryn/phpdice/issues)
- **Discussions**: [GitHub Discussions](https://github.com/codryn/phpdice/discussions)
- **Documentation**: [Full API Reference](api.md)
