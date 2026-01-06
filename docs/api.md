# API Documentation

PHPDice provides a complete dice expression parser and roller for tabletop RPG systems.

## Table of Contents

- [Quick Start](#quick-start)
- [Core Classes](#core-classes)
- [Models](#models)
- [Parser](#parser)
- [Roller](#roller)
- [Usage Examples](#usage-examples)

## Quick Start

```php
use PHPDice\PHPDice;

$phpdice = new PHPDice();

// Parse and roll a basic expression
$expression = $phpdice->parse('3d6+5');
$result = $phpdice->rollExpression($expression);

echo $result->total; // e.g., 18
print_r($result->diceValues); // e.g., [4, 6, 3]

// Get statistics without rolling
$stats = $expression->getStatistics();
echo "Min: {$stats->minimum}, Max: {$stats->maximum}, Expected: {$stats->expected}";
// Min: 8, Max: 23, Expected: 15.5
```

## Core Classes

### PHPDice

Main facade class for parsing and rolling dice expressions.

**Methods:**

#### `parse(string $expression, array $variables = []): DiceExpression`

Parses a dice expression string into a structured DiceExpression object.

**Parameters:**
- `$expression` (string): The dice notation string (e.g., "3d6+5", "1d20 advantage")
- `$variables` (array): Optional placeholder values (e.g., ['str' => 3, 'proficiency' => 2])

**Returns:** `DiceExpression` - Parsed expression ready for rolling

**Throws:** 
- `ParseException` - If expression syntax is invalid
- `ValidationException` - If expression violates constraints

**Example:**
```php
$expression = $phpdice->parse('1d20+%str%', ['str' => 3]);
```

#### `roll(string $expression, array $variables = []): RollResult`

Parses and executes a dice roll and returns the complete result.

**Parameters:**
- `$expression` (string): The dice expression string to roll
- `$variables` (array): Optional placeholder values (e.g., ['str' => 3, 'proficiency' => 2])

**Returns:** `RollResult` - Complete roll result with total, individual dice, and metadata

**Example:**
```php
$result = $phpdice->roll("1d20+5");
echo $result->total; // Final result
```

#### `rollExpression(DiceExpression $expression, array $variables = []): RollResult`

Executes a previously parsed dice roll and returns the complete result.

**Parameters:**
- `$expression` (DiceExpression): The parsed expression to roll
- `$variables` (array): Optional placeholder values (e.g., ['str' => 3, 'proficiency' => 2])

**Returns:** `RollResult` - Complete roll result with total, individual dice, and metadata

**Example:**
```php
$result = $phpdice->roll($expression);
echo $result->total; // Final result
```

---

## Models

### DiceExpression

Represents a fully parsed and validated dice expression.

**Properties:**
- `specification` (DiceSpecification): The base dice being rolled
- `modifiers` (RollModifiers): All modifiers and mechanics
- `statistics` (StatisticalData): Pre-calculated probability data
- `originalExpression` (string): Raw input string
- `comparisonOperator` (string|null): Operator for success rolls (>=, >, <=, <, ==)
- `comparisonThreshold` (int|null): Target number for comparisons

**Methods:**

#### `getStatistics(): StatisticalData`

Returns the statistical information (min, max, expected value) for this expression.

**Example:**
```php
$stats = $expression->getStatistics();
echo "Expected roll: {$stats->expected}";
```

---

### DiceSpecification

Defines the base dice configuration.

**Properties:**
- `count` (int): Number of dice to roll
- `sides` (int): Number of sides per die
- `type` (DiceType): Type of dice (STANDARD, FUDGE, PERCENTILE)

**Example:**
```php
$spec = new DiceSpecification(count: 3, sides: 6, type: DiceType::STANDARD);
```

---

### DiceType (enum)

Enumeration of dice types.

**Cases:**
- `STANDARD`: Normal dice (1 to sides)
- `FUDGE`: Fudge dice (-1, 0, +1)
- `PERCENTILE`: Percentile dice (1-100)

---

### RollModifiers

Contains all modifiers and mechanics applied to a roll.

**Properties:**
- `arithmeticModifier` (int): Simple +/- modifier
- `advantageCount` (int|null): Extra dice for advantage
- `keepHighest` (int|null): Number of highest dice to keep
- `keepLowest` (int|null): Number of lowest dice to keep
- `successThreshold` (int|null): Threshold for success counting
- `successOperator` (string|null): Operator for success counting (>=, >)
- `explosionThreshold` (int|null): Value that triggers explosion
- `explosionOperator` (string|null): Operator for explosions (>=, <=)
- `explosionLimit` (int): Maximum explosions per die (default 100)
- `rerollThreshold` (int|null): Value that triggers reroll
- `rerollOperator` (string|null): Operator for rerolls (>=, >, <=, <, ==)
- `rerollLimit` (int): Maximum rerolls per die (default 100)
- `criticalSuccess` (int|null): Threshold for critical success
- `criticalFailure` (int|null): Threshold for critical failure
- `resolvedVariables` (array): Placeholder values used

---

### RollResult

Complete result of a dice roll.

**Properties:**
- `expression` (DiceExpression): The original parsed expression
- `total` (int|float): Final calculated total
- `diceValues` (array<int>): Individual die values rolled
- `keptDice` (array<int>|null): Indices of dice that were kept
- `discardedDice` (array<int>|null): Indices of dice that were discarded
- `successCount` (int|null): Number of successes (for success counting mode)
- `isCriticalSuccess` (bool): Whether this roll is a critical success
- `isCriticalFailure` (bool): Whether this roll is a critical failure
- `isSuccess` (bool|null): Whether comparison check succeeded
- `rerollHistory` (array|null): History of rerolls per die
- `explosionHistory` (array|null): History of explosions per die

**Example:**
```php
$result = $phpdice->roll($expression);
echo "Total: {$result->total}\n";
echo "Dice: " . implode(', ', $result->diceValues) . "\n";
if ($result->isCriticalSuccess) {
    echo "Critical Success!\n";
}
```

---

### StatisticalData

Probability statistics for a dice expression.

**Properties:**
- `minimum` (int|float): Minimum possible result
- `maximum` (int|float): Maximum possible result
- `expected` (float): Expected value (mean), rounded to 3 decimals

**Example:**
```php
$stats = $expression->getStatistics();
echo "Range: {$stats->minimum}-{$stats->maximum}, Average: {$stats->expected}";
```

---

## Parser

### Lexer

Tokenizes dice expression strings.

**Methods:**

#### `tokenize(string $input): array<Token>`

Breaks input string into tokens.

**Example:**
```php
$lexer = new Lexer();
$tokens = $lexer->tokenize('3d6+5');
```

---

### DiceExpressionParser

Parses tokens into structured DiceExpression objects.

**Methods:**

#### `parse(string $expression, array $variables = []): DiceExpression`

Main parsing method. Validates syntax and builds expression tree.

---

### Validator

Validates dice expressions against constraints.

**Validation Rules:**
- Dice count: 1-100 (FR-027, FR-029)
- Die sides: >= 2 for standard dice (FR-028, FR-030)
- Keep count: Must not exceed roll count (FR-003a, FR-004a)
- Reroll range: Cannot cover entire die range (FR-005b)
- Explosion range: Cannot cover entire die range (FR-038c)
- Critical thresholds: Must be within die range (FR-035, FR-036)
- Arithmetic: No division by zero (FR-031)
- Parentheses: Must be balanced (FR-033)

---

## Roller

### DiceRoller

Executes dice rolls with all mechanics.

**Mechanics Supported:**
- Basic rolling
- Arithmetic (+, -, *, /)
- Advantage/disadvantage (keep highest/lowest)
- Success counting
- Rerolls (with configurable limits)
- Exploding dice (with configurable limits)
- Critical success/failure detection
- Success roll comparisons

**Methods:**

#### `roll(DiceExpression $expression): RollResult`

Performs the dice roll with all modifiers.

---

### RandomNumberGenerator

Provides cryptographically secure random number generation.

**Methods:**

#### `generate(int $min, int $max): int`

Generates a random integer using `random_int()`.

---

## Usage Examples

### Basic Dice Rolling

```php
$expression = $phpdice->parse('3d6');
$result = $phpdice->rollExpression($expression);
echo $result->total; // Sum of three d6
```

### Arithmetic Modifiers

```php
$expression = $phpdice->parse('1d20+5');
$result = $phpdice->rollExpression($expression);
echo $result->total; // d20 + 5

// Complex arithmetic
$expression = $phpdice->parse('(2d6+3)*2');
$result = $phpdice->rollExpression($expression);
```

### Advantage/Disadvantage

```php
// D&D 5e advantage
$expression = $phpdice->parse('1d20 advantage');
$result = $phpdice->rollExpression($expression);
echo "Rolled 2d20, kept: {$result->total}\n";
print_r($result->keptDice);    // [index of higher roll]
print_r($result->discardedDice); // [index of lower roll]

// Disadvantage
$expression = $phpdice->parse('1d20 disadvantage');
$result = $phpdice->rollExpression($expression);
```

### Keep Highest/Lowest

```php
// Character stats (4d6, drop lowest)
$expression = $phpdice->parse('4d6 keep 3 highest');
$result = $phpdice->rollExpression($expression);
echo $result->total; // Sum of 3 highest dice
```

### Success Counting

```php
// Shadowrun: count dice >= 5
$expression = $phpdice->parse('10d6 >=5');
$result = $phpdice->rollExpression($expression);
echo "Successes: {$result->successCount}\n";
```

### Rerolls

```php
// Reroll 1s and 2s (once per die)
$expression = $phpdice->parse('4d6 reroll <=2');
$result = $phpdice->rollExpression($expression);

// Limit rerolls
$expression = $phpdice->parse('4d6 reroll 1 <=2');
$result = $phpdice->rollExpression($expression);

// Check reroll history
if ($result->rerollHistory !== null) {
    foreach ($result->rerollHistory as $dieIndex => $history) {
        echo "Die {$dieIndex} rerolled {$history['count']} times\n";
    }
}
```

### Exploding Dice

```php
// Savage Worlds: explode on 6
$expression = $phpdice->parse('3d6 explode');
$result = $phpdice->rollExpression($expression);

// Custom explosion threshold
$expression = $phpdice->parse('3d6 explode >=5');
$result = $phpdice->rollExpression($expression);

// Limit explosions
$expression = $phpdice->parse('3d6 explode 3 >=6');
$result = $phpdice->rollExpression($expression);

// Check explosion history
if ($result->explosionHistory !== null) {
    foreach ($result->explosionHistory as $dieIndex => $history) {
        echo "Die {$dieIndex}: " . implode(' + ', $history['rolls']) . 
             " = {$history['cumulativeTotal']}\n";
    }
}
```

### Special Dice

```php
// FATE dice (4dF)
$expression = $phpdice->parse('4dF');
$result = $phpdice->rollExpression($expression);
echo $result->total; // Sum of values (-1, 0, or +1)

// Percentile dice
$expression = $phpdice->parse('d%');
$result = $phpdice->rollExpression($expression);
echo $result->total; // 1-100
```

### Placeholders/Variables

```php
// Character sheet integration
$expression = $phpdice->parse(
    '1d20+%str%+%proficiency%',
    ['str' => 3, 'proficiency' => 2]
);
$result = $phpdice->rollExpression($expression);
echo $result->total; // d20 + 3 + 2
```

### Success Rolls (Comparisons)

```php
// D&D 5e skill check
$expression = $phpdice->parse('1d20+5 >= 15');
$result = $phpdice->rollExpression($expression);
echo "Rolled: {$result->total}\n";
echo $result->isSuccess ? "Success!" : "Failure!";
```

### Critical Success/Failure

```php
// D&D 5e natural 20/1
$expression = $phpdice->parse('1d20 crit 20 glitch 1');
$result = $phpdice->rollExpression($expression);

if ($result->isCriticalSuccess) {
    echo "Natural 20! Critical Success!\n";
} elseif ($result->isCriticalFailure) {
    echo "Natural 1! Critical Failure!\n";
}

// Custom thresholds
$expression = $phpdice->parse('1d20 crit 19 glitch 2');
$result = $phpdice->rollExpression($expression);
```

### Statistics (No Rolling)

```php
// Calculate probabilities
$expression = $phpdice->parse('3d6+5');
$stats = $expression->getStatistics();

echo "Minimum: {$stats->minimum}\n";   // 8
echo "Maximum: {$stats->maximum}\n";   // 23
echo "Expected: {$stats->expected}\n"; // 15.5

// Works with all modifiers
$expression = $phpdice->parse('1d20 advantage');
$stats = $expression->getStatistics();
echo "Average with advantage: {$stats->expected}\n"; // ~14.0
```

### Complex Combinations

```php
// D&D 5e attack with advantage and critical
$expression = $phpdice->parse('1d20 advantage + 5 >= 15 crit 20');
$result = $phpdice->roll($expression);

echo "Attack roll: {$result->total}\n";
echo $result->isSuccess ? "Hit!\n" : "Miss!\n";
if ($result->isCriticalSuccess) {
    echo "Critical hit!\n";
}

// Shadowrun with rerolls
$expression = $phpdice->parse('12d6 reroll <=1 >=5');
$result = $phpdice->rollExpression($expression);
echo "Successes: {$result->successCount}\n";
```

---

## Error Handling

All parsing and validation errors throw exceptions:

```php
use PHPDice\Exception\ParseException;
use PHPDice\Exception\ValidationException;

try {
    $expression = $phpdice->parse('invalid');
    $result = $phpdice->rollExpression($expression);
} catch (ParseException $e) {
    echo "Syntax error: {$e->getMessage()}\n";
} catch (ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
}
```

Common validation errors:
- Too many dice (>100)
- Too many sides (>100)
- Invalid die sides (<2 for standard dice)
- Keep count exceeds roll count
- Reroll/explosion covers entire range
- Unbound placeholder variables
- Division by zero
- Unbalanced parentheses

---

## Performance

PHPDice is designed for high performance:

- **Parsing**: < 100ms for complex expressions
- **Rolling**: < 50ms for typical rolls
- **Memory**: < 1MB per operation

For best performance:
- Reuse parsed `DiceExpression` objects for repeated rolls
- Set reasonable explosion/reroll limits (default 100)
- Use StatisticalCalculator for probability analysis instead of Monte Carlo

---

## Game System Support

PHPDice supports dice notation for major RPG systems:

| System | Example | Features Used |
|--------|---------|---------------|
| D&D 5e | `1d20+5 >= 15` | Basic dice, modifiers, comparisons, advantage, criticals |
| Pathfinder | `3d6+2` | Basic dice, modifiers |
| Shadowrun 5e | `12d6 >=5` | Success counting, rerolls |
| World of Darkness | `10d10 >=8` | Success counting |
| FATE | `4dF+2` | Fudge dice, modifiers |
| Savage Worlds | `1d6 explode + 1d8 explode` | Exploding dice |
| Call of Cthulhu | `d%` | Percentile dice |

---

## Advanced Topics

### AST (Abstract Syntax Tree)

Complex arithmetic expressions are parsed into an AST for accurate evaluation:

```php
// "(2d6+3)*2" creates:
// BinaryOpNode('*',
//   BinaryOpNode('+',
//     DiceNode(2, 6),
//     NumberNode(3)
//   ),
//   NumberNode(2)
// )
```

The AST ensures proper operator precedence and supports nested expressions.

### Statistical Calculator

The `StatisticalCalculator` computes exact probability distributions:

- **Basic dice**: Simple average
- **Keep highest/lowest**: Order statistics approximation
- **Success counting**: Binomial probability
- **Rerolls**: Adjusted value ranges
- **Explosions**: Geometric series expectation
- **Precision**: All values rounded to 3 decimal places

---

## Version

Current version: 1.0.0

For changelog and migration guides, see [CHANGELOG.md](../CHANGELOG.md).

---

## License

MIT License. See [LICENSE](../LICENSE) for details.
