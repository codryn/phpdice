# API Contract: Parser Interface

**Feature**: 001-dice-parser-roller | **Date**: 2025-12-02

## Parser API

### Method: `parse()`

**Purpose**: Parse a dice expression string into a structured DiceExpression object

**Signature**:
```php
public function parse(
    string $expression,
    array $variables = []
): DiceExpression
```

**Parameters**:
- `expression` (string, required): Dice notation string (e.g., "3d6+5", "1d20 advantage")
- `variables` (array, optional): Placeholder variable bindings (e.g., `["str" => 3, "dex" => 2]`)

**Returns**: `DiceExpression` - Fully parsed and validated expression with statistical data

**Throws**:
- `ParseException` - Invalid syntax (e.g., "3d", "abc")
- `ValidationException` - Valid syntax but invalid semantics (e.g., "keep 5 from 3d6")

**Examples**:

```php
// Basic dice
$expr = $parser->parse("3d6");
// Returns: DiceExpression(3d6, min=3, max=18, expected=10.5)

// With modifier
$expr = $parser->parse("1d20+5");
// Returns: DiceExpression(1d20+5, min=6, max=25, expected=15.5)

// With arithmetic expression
$expr = $parser->parse("(2d6+3)*2");
// Returns: DiceExpression((2d6+3)*2, min=10, max=30, expected=17.0)

// With mathematical function
$expr = $parser->parse("floor(1d20/2)");
// Returns: DiceExpression(floor(1d20/2), min=0, max=10, expected=5.0)

// With variables
$expr = $parser->parse("1d20+%str%+%dex%", ["str" => 3, "dex" => 2]);
// Returns: DiceExpression(1d20+5, min=6, max=25, expected=15.5)

// Advantage
$expr = $parser->parse("1d20 advantage");
// Returns: DiceExpression(2d20 keep highest, min=1, max=20, expected≈13.825)

// Success counting
$expr = $parser->parse("5d6 >=4");
// Returns: DiceExpression(5d6 count >=4, min=0, max=5, expected≈2.083)

// Critical thresholds
$expr = $parser->parse("1d20 crit 20 glitch 1");
// Returns: DiceExpression with critical success/failure thresholds
```

**Error Examples**:

```php
// Missing dice count
$parser->parse("d6");
// Throws: ParseException("Invalid dice notation")

// Missing variable
$parser->parse("1d20+%str%");
// Throws: ValidationException("Missing variable: %str%")

// Invalid keep count
$parser->parse("3d6 keep 5 highest");
// Throws: ValidationException("Keep count exceeds rolls")
```

**Contract Guarantees**:
1. Returned `DiceExpression` is fully validated and ready to roll
2. All placeholders are resolved (no null variables)
3. Statistical data is pre-calculated and accurate
4. Parse time is <100ms for expressions <50 characters
5. Error messages are specific and actionable (<5 words per SC-003)

---

## Supported Expression Syntax

### Basic Dice Notation
```
XdY          # Roll X dice with Y sides
3d6          # Roll 3 six-sided dice
1d20         # Roll 1 twenty-sided dice
```

### Arithmetic Modifiers
```
XdY+Z        # Add modifier
XdY-Z        # Subtract modifier
XdY*Z        # Multiply result
XdY/Z        # Divide result
3d6+5        # 3d6 plus 5
1d20-2       # 1d20 minus 2
2d6*3        # 2d6 multiplied by 3
4d6/2        # 4d6 divided by 2
```

### Arithmetic Expressions
```
(XdY+Z)*N              # Grouping with parentheses
XdY+Z*N                # Standard precedence (* before +)
((XdY+A)*B)+C          # Nested parentheses

(2d6+3)*2              # (2d6+3) then multiply by 2
1d20+5*2               # 1d20 + (5*2) = 1d20+10
((1d8+%str%)*2)+5      # Damage with strength multiplier
```

### Mathematical Functions
```
floor(expression)      # Round down to integer
ceil(expression)    # Round up to integer  
round(expression)      # Round to nearest integer

floor(1d20/2)          # Half of 1d20, rounded down
ceil(3d6/2)         # Half of 3d6, rounded up
round(1d20*1.5)        # 1d20 times 1.5, rounded
floor((2d6+%str%)/2)   # Complex expression with function
```

### Advantage / Disadvantage
```
XdY advantage              # Roll 2XdY, keep highest X
XdY disadvantage           # Roll 2XdY, keep lowest X
XdY keep N highest         # Roll XdY, keep N highest
XdY keep N lowest          # Roll XdY, keep N lowest

1d20 advantage             # Roll 2d20, keep highest
4d6 keep 3 highest         # Roll 4d6, drop lowest
```

### Reroll Mechanics
```
XdY reroll <=N             # Reroll once if <= N
XdY reroll <N              # Reroll once if < N
XdY reroll >=N             # Reroll once if >= N
XdY reroll N               # Reroll once if == N

4d6 reroll <=2             # Reroll 1s and 2s once
6d6 reroll 1               # Reroll 1s once
```

### Success Counting
```
XdY >=N                    # Count dice >= N
XdY >N                     # Count dice > N
XdY threshold N            # Count dice >= N (alias)

5d6 >=4                    # Count sixes, fives, fours
10d10 threshold 7          # Count 7+ results
```

### Special Dice
```
XdF          # Fudge dice (-1, 0, +1)
d%           # Percentile (1-100)
Xd100        # Alternative percentile

4dF          # 4 FATE dice
d%           # Roll percentile
```

### Placeholders
```
XdY+%var%                  # Variable substitution using %name% syntax
XdY+%var1%+%var2%          # Multiple variables

1d20+%str%                 # Requires: ["str" => value]
1d20+%str%+%dex%           # Requires: ["str" => X, "dex" => Y]
1d20+%damage_bonus%        # Variable names can include underscores
```

**Note**: The `%name%` syntax prevents collisions with reserved keywords like `advantage`, `disadvantage`, `keep`, etc.

### Success Rolls (Comparison)
```
XdY+Z >=N                  # Roll >= target
XdY+Z >N                   # Roll > target
XdY+Z <=N                  # Roll <= target
XdY+Z <N                   # Roll < target
XdY+Z ==N                  # Roll == target

1d20+5 >=15                # DC 15 check
2d6 >7                     # Beat 7
```

### Critical Thresholds
```
XdY crit N                 # Critical success on N
XdY glitch N               # Critical failure on N
XdY crit N glitch M        # Both thresholds

1d20 crit 20               # Natural 20 is critical
1d20 crit 20 glitch 1      # Nat 20 crit, nat 1 glitch
1d20 crit 19-20 glitch 1-2 # Range syntax (future)
```

### Whitespace Rules
```
# All equivalent:
3d6+5
3d6 + 5
3d6+ 5
3 d 6 + 5

# Operators can have surrounding whitespace
# Numbers must be contiguous (no spaces within)
```

### Reserved Keywords
```
d, dF, d%, advantage, disadvantage, keep, highest, lowest, 
reroll, threshold, crit, glitch
```

### Operator Precedence
```
1. Parentheses ()
2. Function calls (floor, ceil, round)
3. Dice notation (XdY)
4. Reroll modifiers
5. Keep/drop modifiers
6. Multiplication and division (*, /)
7. Addition and subtraction (+, -)
8. Success counting (>=, >, etc.)
9. Comparison operators (for success rolls)
10. Critical thresholds
```

---

## Validation Rules

### Structural Validation (Parse Time)

1. **Dice notation**: Must match `\d+d\d+` or `\d*d[F%]` pattern
2. **Modifiers**: Must be valid integers
3. **Keep counts**: Must not exceed total dice rolled
4. **Operators**: Must be recognized operators
5. **Variables**: Must all be provided in `variables` parameter
6. **Syntax**: Must follow grammar rules

### Semantic Validation (Parse Time)

1. **Dice count**: Must be > 0
2. **Dice sides**: Must be > 0
3. **Keep counts**: Must be > 0 and <= dice count
4. **Critical thresholds**: Must be within die range
5. **Success thresholds**: Must be valid for die type
6. **Reroll thresholds**: Must be valid for die type
7. **Variable values**: Must be integers
8. **Parentheses**: Must be properly matched and nested
9. **Division**: Divisor must not be zero (can be detected at parse time for literal values)
10. **Function arguments**: Mathematical functions must have exactly one argument

### Error Messages

All errors must be specific and actionable:

```php
// Good error messages (SC-003: <5 words)
"Invalid dice notation"
"Missing variable: str"
"Keep count exceeds rolls"
"Dice count must be positive"
"Unknown operator: !="

// Bad error messages (too vague)
"Parse error"
"Invalid expression"
"Something went wrong"
```
