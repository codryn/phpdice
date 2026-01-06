# Data Model: Dice Expression Parser and Roller

**Feature**: 001-dice-parser-roller | **Date**: 2025-12-02
**Purpose**: Define entity structures, relationships, validation rules, and state transitions

## Entity Overview

The system has five core entities that work together to represent dice expressions and roll results:

```
DiceExpression (parse output)
├── DiceSpecification (what dice to roll)
├── RollModifiers (how to modify the roll)
└── StatisticalData (probability information)

RollResult (roll output)
├── references DiceExpression (original request)
└── contains rolled values and flags
```

## Entity: DiceExpression

**Purpose**: Represents a fully parsed and validated dice expression ready for rolling or statistical analysis.

**State**: Immutable once created (parse-time construction)

### Fields

| Field | Type | Required | Description | Validation |
|-------|------|----------|-------------|------------|
| `specification` | DiceSpecification | Yes | The base dice being rolled | Must be valid DiceSpecification |
| `modifiers` | RollModifiers | Yes | All modifiers and mechanics | Must be valid RollModifiers |
| `statistics` | StatisticalData | Yes | Pre-calculated probability data | Must be consistent with specification |
| `comparisonOperator` | ?string | No | Operator for success rolls (`>=`, `<=`, etc.) | One of: `>=`, `>`, `<=`, `<`, `==` or null |
| `comparisonThreshold` | ?int | No | Target number for comparisons | Required if comparisonOperator set |
| `originalExpression` | string | Yes | Raw input string | Non-empty string |

### Relationships

- Contains exactly one `DiceSpecification`
- Contains exactly one `RollModifiers`
- Contains exactly one `StatisticalData`
- Referenced by `RollResult` when rolled

### Validation Rules

1. If `comparisonOperator` is set, `comparisonThreshold` MUST also be set
2. If `comparisonThreshold` is set, `comparisonOperator` MUST also be set
3. `statistics` min/max MUST be consistent with `specification` and `modifiers`
4. All placeholder variables in `modifiers` MUST be resolved (no null values)

### State Transitions

No state transitions (immutable after parse-time construction)

---

## Entity: DiceSpecification

**Purpose**: Describes the basic dice pool being rolled (e.g., "3d6" = 3 six-sided dice)

**State**: Immutable

### Fields

| Field | Type | Required | Description | Validation |
|-------|------|----------|-------------|------------|
| `count` | int | Yes | Number of dice to roll | Must be > 0 |
| `sides` | int | Yes | Number of sides per die | Must be > 0 |
| `type` | DiceType | Yes | Type of dice | One of: STANDARD, FUDGE, PERCENTILE |

### DiceType Enum

```php
enum DiceType: string {
    case STANDARD = 'standard';    // Normal XdY dice
    case FUDGE = 'fudge';          // FATE dice (dF) with values -1/0/+1
    case PERCENTILE = 'percentile'; // d% or d100
}
```

### Validation Rules

1. `count` must be positive integer (1-10000 recommended limit)
2. `sides` must be positive integer
3. For FUDGE type: `sides` MUST be 3 (representing -1, 0, +1)
4. For PERCENTILE type: `sides` MUST be 100

### Examples

```php
// 3d6
new DiceSpecification(count: 3, sides: 6, type: DiceType::STANDARD)

// 4dF
new DiceSpecification(count: 4, sides: 3, type: DiceType::FUDGE)

// d%
new DiceSpecification(count: 1, sides: 100, type: DiceType::PERCENTILE)
```

---

## Entity: RollModifiers

**Purpose**: Contains all modifiers, special mechanics, and resolved variables for a roll

**State**: Immutable

### Fields

| Field | Type | Required | Description | Validation |
|-------|------|----------|-------------|------------|
| `arithmeticExpression` | ?string | No | Full arithmetic expression tree (AST or string) | Must be valid arithmetic expression if provided |
| `arithmeticModifier` | int | Yes | Flat bonus/penalty (for simple cases) | Can be negative, default 0 (deprecated in favor of arithmeticExpression) |
| `advantageCount` | ?int | No | Number of extra dice for advantage | Must be > 0 if set |
| `keepHighest` | ?int | No | Keep N highest dice | Must be > 0 and <= total dice |
| `keepLowest` | ?int | No | Keep N lowest dice | Must be > 0 and <= total dice |
| `rerollThreshold` | ?int | No | Reroll if <= this value | Must be > 0 |
| `rerollOperator` | ?string | No | Reroll comparison operator | One of: `<=`, `<`, `>=`, `>`, `==` |
| `rerollLimit` | int | Yes | Max rerolls per die | Must be > 0, default 100 |
| `explosionThreshold` | ?int | No | Explode if >= this value | Must be > 0, default max die value |
| `explosionOperator` | ?string | No | Explosion comparison operator | One of: `>=`, `<=` |
| `explosionLimit` | int | Yes | Max explosions per die | Must be > 0, default 100 |
| `successThreshold` | ?int | No | Count successes >= this value | Must be > 0 |
| `successOperator` | ?string | No | Success comparison operator | One of: `>=`, `>` |
| `criticalSuccess` | ?int | No | Flag critical success on this value | Must be within die range |
| `criticalFailure` | ?int | No | Flag critical failure on this value | Must be within die range |
| `resolvedVariables` | array | Yes | Placeholder values (name => value) | All values must be integers, default empty array |

### Relationships

- Owned by exactly one `DiceExpression`

### Validation Rules

1. Cannot have both `keepHighest` and `keepLowest` set
2. `advantageCount` is mutually exclusive with `keepHighest`/`keepLowest` (advantage is sugar for keep)
3. If `rerollThreshold` set, `rerollOperator` MUST be set
4. If `successThreshold` set, `successOperator` MUST be set
5. `criticalSuccess` must be achievable on the specified dice
6. `criticalFailure` must be achievable on the specified dice
7. All variable names in `resolvedVariables` must be valid identifiers (alphanumeric + underscore)
8. If `explosionThreshold` set, `explosionOperator` MUST be set
9. **Explosion range constraint**: `explosionThreshold` with `explosionOperator` cannot cover entire die range (prevents infinite loops)
10. **Reroll range constraint**: `rerollThreshold` with `rerollOperator` cannot cover entire die range (prevents infinite loops)
11. `explosionLimit` and `rerollLimit` MUST be positive integers

### State Transitions

No state transitions (values resolved at parse time)

---

## Entity: StatisticalData

**Purpose**: Provides pre-calculated probability information for an expression

**State**: Immutable (calculated at parse time)

### Fields

| Field | Type | Required | Description | Validation |
|-------|------|----------|-------------|------------|
| `minimum` | int\|float | Yes | Minimum possible result | Must be <= expected |
| `maximum` | int\|float | Yes | Maximum possible result | Must be >= expected |
| `expected` | float | Yes | Expected value (mean) | Must be between min and max |
| `variance` | ?float | No | Variance (optional) | Must be >= 0 if provided |
| `standardDeviation` | ?float | No | Standard deviation (optional) | Must be >= 0 if provided |

### Relationships

- Owned by exactly one `DiceExpression`

### Validation Rules

1. `minimum` <= `expected` <= `maximum`
2. For success counting: values represent count of successes, not sum
3. Precision: 3 decimal places minimum (per SC-004)
4. If `variance` provided, `standardDeviation` SHOULD be sqrt(variance)

### Calculation Examples

```php
// 3d6 standard dice
min = 3 × 1 = 3
max = 3 × 6 = 18
expected = 3 × (6+1)/2 = 10.5

// 3d6+5 with modifier
min = 3 + 5 = 8
max = 18 + 5 = 23
expected = 10.5 + 5 = 15.5

// 4dF fudge dice
min = 4 × (-1) = -4
max = 4 × 1 = 4
expected = 4 × 0 = 0

// 2d20 keep highest (advantage)
min = 1
max = 20
expected ≈ 13.825 (calculated via probability distribution)
```

---

## Entity: RollResult

**Purpose**: Contains the complete outcome of executing a dice roll

**State**: Immutable once created (roll-time construction)

### Fields

| Field | Type | Required | Description | Validation |
|-------|------|----------|-------------|------------|
| `expression` | DiceExpression | Yes | Original parsed expression | Valid DiceExpression |
| `total` | int\|float | Yes | Final result (sum or success count) | - |
| `diceValues` | array<int> | Yes | All individual die rolls | Length must match dice count |
| `keptDice` | ?array<int> | No | Indices of kept dice for advantage/disadvantage | Indices must be valid |
| `discardedDice` | ?array<int> | No | Indices of discarded dice | Indices must be valid |
| `rerolledDice` | ?array | No | Map of index => original value for rerolls | Keys must be valid indices |
| `explosionHistory` | ?array | No | Map of index => [values] for explosion chains | Keys must be valid indices, cumulative totals |
| `successCount` | ?int | No | Number of successful dice | Required if success counting mode |
| `isCriticalSuccess` | bool | Yes | True if critical success triggered | Default false |
| `isCriticalFailure` | bool | Yes | True if critical failure triggered | Default false |
| `isSuccess` | ?bool | No | True if comparison passed | Required if comparison operator used |
| `timestamp` | int | Yes | Unix timestamp of roll | - |

### Relationships

- References exactly one `DiceExpression`

### Validation Rules

1. `diceValues` length MUST equal total dice rolled (including advantage/reroll extras)
2. If success counting: `total` MUST equal `successCount`
3. If comparison operator: `isSuccess` MUST be set based on `total` vs threshold
4. `keptDice` and `discardedDice` indices MUST be disjoint and cover all dice
5. Cannot have both `isCriticalSuccess` and `isCriticalFailure` true simultaneously
6. For rerolled dice: original values MUST be preserved in `rerolledDice`
7. For exploded dice: all values in explosion chain MUST be preserved in `explosionHistory`, cumulative total in `diceValues`

### State Transitions

No state transitions (immutable snapshot of roll execution)

---

## Cross-Entity Validation

### Parse-Time Validation (DiceExpression Creation)

1. All placeholder variables MUST be resolved in `RollModifiers.resolvedVariables`
2. Advantage/disadvantage keep counts MUST NOT exceed total dice in `DiceSpecification`
3. Critical thresholds MUST be achievable on the die type
4. Statistical data MUST be calculable from specification and modifiers
5. **Minimum die sides**: All dice MUST have at least 2 sides (prevents degenerate 1-sided dice)
6. **Explosion/reroll range validation**: Threshold with operator cannot cover entire die range

### Roll-Time Validation (RollResult Creation)

1. Number of values in `diceValues` MUST match expected count from expression
2. All die values MUST be within valid range for die type
3. Critical flags MUST only be set if thresholds matched
4. Success boolean MUST match comparison evaluation

## Invariants

1. **Immutability**: All entities are immutable after construction
2. **Type Safety**: All fields use strict types (PHP 8.0+ type declarations)
3. **Validation**: All entities validate themselves in constructor
4. **Completeness**: All required data for a roll is captured in these entities
5. **Statelessness**: No entity maintains mutable state or dependencies on external services

## Entity Lifecycle

```
Parse Time:
  Input String → Lexer → Parser → DiceExpression (with DiceSpecification, RollModifiers, StatisticalData)

Roll Time:
  DiceExpression → Roller → RollResult (with diceValues, flags, etc.)

Query Time:
  DiceExpression.statistics → StatisticalData (no roll needed)
  RollResult.expression → DiceExpression (trace back to request)
```

## PHP 8.0+ Implementation Notes

**Use Constructor Property Promotion**:
```php
readonly class DiceExpression {
    public function __construct(
        public DiceSpecification $specification,
        public RollModifiers $modifiers,
        public StatisticalData $statistics,
        public ?string $comparisonOperator = null,
        public ?int $comparisonThreshold = null,
        public string $originalExpression = '',
    ) {
        // Validation in constructor
    }
}
```

**Use Readonly Properties** (PHP 8.1+) or immutable objects pattern
**Use Enums** for DiceType (PHP 8.1+) or class constants
**Use Named Arguments** for clarity: `new DiceSpecification(count: 3, sides: 6, type: DiceType::STANDARD)`
