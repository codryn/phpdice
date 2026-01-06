# API Contract: Roller Interface

**Feature**: 001-dice-parser-roller | **Date**: 2025-12-02

## Roller API

### Method: `roll()`

**Purpose**: Execute a parsed dice expression and return detailed results

**Signature**:
```php
public function roll(DiceExpression $expression): RollResult
```

**Parameters**:
- `expression` (DiceExpression, required): Parsed expression from Parser

**Returns**: `RollResult` - Complete roll outcome with all dice values and flags

**Throws**: None (all validation done at parse time)

**Examples**:

```php
// Basic roll
$expr = $parser->parse("3d6");
$result = $roller->roll($expr);
// Returns: RollResult(
//   total: 14,
//   diceValues: [5, 6, 3],
//   isCriticalSuccess: false,
//   isCriticalFailure: false
// )

// Roll with modifier
$expr = $parser->parse("1d20+5");
$result = $roller->roll($expr);
// Returns: RollResult(
//   total: 18,  // 13 + 5
//   diceValues: [13],
//   ...
// )

// Advantage roll
$expr = $parser->parse("1d20 advantage");
$result = $roller->roll($expr);
// Returns: RollResult(
//   total: 17,
//   diceValues: [17, 8],
//   keptDice: [0],
//   discardedDice: [1],
//   ...
// )

// Success counting
$expr = $parser->parse("5d6 >=4");
$result = $roller->roll($expr);
// Returns: RollResult(
//   total: 3,  // 3 successes
//   diceValues: [6, 5, 4, 2, 1],
//   successCount: 3,
//   ...
// )

// Critical success
$expr = $parser->parse("1d20 crit 20");
$result = $roller->roll($expr);
// If rolled 20: RollResult(
//   total: 20,
//   diceValues: [20],
//   isCriticalSuccess: true,
//   isCriticalFailure: false,
//   ...
// )

// Comparison roll (DC check)
$expr = $parser->parse("1d20+5 dc >=15");
$result = $roller->roll($expr);
// If rolled 12: RollResult(
//   total: 17,  // 12 + 5
//   diceValues: [12],
//   isSuccess: true,  // 17 >= 15
//   ...
// )

// Reroll
$expr = $parser->parse("4d6 reroll <=2");
$result = $roller->roll($expr);
// If rolled [1, 5, 2, 6], rerolled to [4, 5, 3, 6]:
// Returns: RollResult(
//   total: 18,
//   diceValues: [4, 5, 3, 6],
//   rerolledDice: [0 => 1, 2 => 2],  // indices 0 and 2 were rerolled
//   ...
// )
```

**Contract Guarantees**:
1. Roll execution completes in <50ms for up to 100 dice
2. All dice values are within valid range for die type
3. Critical flags are set correctly based on thresholds
4. Success comparisons are evaluated accurately
5. All rolled dice are preserved in result for inspection
6. Reroll history is captured
7. Random number generation uses `random_int()`

---

## RollResult Structure

### Core Fields

```php
class RollResult {
    public DiceExpression $expression;      // Original parsed expression
    public int|float $total;                 // Final result (sum or success count)
    public array $diceValues;                // All individual die rolls [int, ...]
    public bool $isCriticalSuccess;          // True if critical success occurred
    public bool $isCriticalFailure;          // True if critical failure occurred
    public int $timestamp;                   // Unix timestamp of roll
}
```

### Optional Fields (Contextual)

```php
// For advantage/disadvantage
public ?array $keptDice = null;              // Indices of kept dice [0, 2, 3]
public ?array $discardedDice = null;         // Indices of discarded dice [1]

// For rerolls
public ?array $rerolledDice = null;          // Map: index => original_value

// For success counting
public ?int $successCount = null;            // Number of successful dice

// For comparison rolls
public ?bool $isSuccess = null;              // True if comparison passed
```

### Field Semantics

**`total`**:
- For standard rolls: Sum of kept dice + arithmetic modifier
- For success counting: Count of dice meeting threshold
- For comparison rolls: Evaluated result before comparison

**`diceValues`**:
- Contains ALL dice rolled (including discarded)
- Indices correspond to roll order
- For rerolled dice, contains final values (original in `rerolledDice`)

**`keptDice` / `discardedDice`**:
- Arrays of indices into `diceValues`
- Only set for advantage/disadvantage/keep mechanics
- Indices are disjoint and cover all dice

**`rerolledDice`**:
- Map of index => original_value for dice that were rerolled
- Only contains dice that actually triggered reroll
- Final values are in `diceValues`

**`successCount`**:
- Only set when success counting mode active
- Equals `total` in success counting mode
- Represents number of dice meeting success threshold

**`isSuccess`**:
- Only set when comparison operator used
- Result of comparing `total` to threshold
- Independent of critical flags

**`isCriticalSuccess` / `isCriticalFailure`**:
- Set based on individual die values matching thresholds
- Only one can be true (mutually exclusive)
- Independent of `isSuccess` (can have critical success but fail DC check)

---

## Roll Execution Behavior

### Standard Dice (XdY)

1. Roll X dice with Y sides using `random_int(1, Y)`
2. Store all values in `diceValues`
3. Calculate sum
4. Apply arithmetic modifier
5. Set `total`

### Fudge Dice (XdF)

1. Roll X dice with 3 "sides" using `random_int(1, 3)`
2. Map: 1 → -1, 2 → 0, 3 → +1
3. Store mapped values in `diceValues`
4. Calculate sum
5. Set `total`

### Percentile Dice (d%)

1. Roll 1d100 using `random_int(1, 100)`
2. Store in `diceValues`
3. Set `total`

### Advantage / Disadvantage

1. Roll total dice count (base + extra)
2. Store all values in `diceValues`
3. Identify keep indices (highest or lowest N)
4. Set `keptDice` and `discardedDice`
5. Calculate sum of kept dice only
6. Apply arithmetic modifier
7. Set `total`

### Reroll Mechanics

1. Roll all dice
2. Identify dice meeting reroll threshold
3. For each eligible die:
   - Store original value in `rerolledDice[index]`
   - Roll once more
   - Update `diceValues[index]` with new value
4. Continue with standard processing

**Important**: Each die rerolls at most once (no recursive rerolls)

### Success Counting

1. Roll all dice
2. Store values in `diceValues`
3. Count dice meeting success threshold
4. Set `successCount` and `total` to count
5. Do NOT sum dice values

### Comparison Rolls

1. Execute standard roll or success count
2. Get `total` value
3. Apply comparison operator with threshold
4. Set `isSuccess` based on result

### Critical Detection

1. After rolling, check each die value
2. If any die matches `criticalSuccess` threshold: set `isCriticalSuccess = true`
3. If any die matches `criticalFailure` threshold: set `isCriticalFailure = true`
4. Critical checks are independent of other mechanics

---

## Randomness Guarantees

**RNG Function**: `random_int(min, max)`
- Cryptographically secure random integers
- Uniform distribution across range
- Available in PHP 7.0+ (guaranteed in PHP 8.0+)

**Distribution Quality**:
- Each die face has equal probability
- No bias or predictability
- Suitable for game purposes (not cryptographic security context)

**Performance**:
- `random_int()` is fast enough for gaming (<1ms per call)
- No caching or pre-generation needed

---

## Error Handling

**No Roll-Time Errors**: All validation occurs at parse time. The `roll()` method assumes a valid `DiceExpression` and never throws exceptions.

**Reasoning**:
1. Parse-time validation catches all structural issues
2. Placeholders already resolved
3. Statistical calculations require valid expressions
4. Simplifies roller implementation
5. Better developer experience (fail fast at parse)

---

## Memory and Performance

**Memory Usage**:
- Each `RollResult` is <1KB for typical rolls
- `diceValues` array is primary memory consumer
- For 100 dice: ~400 bytes for dice values + overhead

**Performance Targets** (from spec):
- Roll execution: <50ms for up to 100 dice
- Typical roll (3-5 dice): <1ms
- Complex roll (advantage + reroll + criticals): <5ms

**Optimization Notes**:
- No database or I/O operations
- Pure computation and RNG calls
- Stateless (no session or cache overhead)
- Minimal object allocation
