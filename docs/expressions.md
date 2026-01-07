# Dice Expressions

The PHPDice library supports a wide range of dice expressions commonly used in tabletop RPGs. Below is a comprehensive guide to the supported syntax and features.

General rules:
- Dice expressions can include basic dice rolls, arithmetic operations, functions, and special mechanics.
- Supported dice types: standard (d4, d6, d8, d10, d12, d20, d%), FATE dice (dF).
- Expressions can contain space characters for readability.
- Parentheses can be used to group sub-expressions.
- Mathematical functions supported: `floor()`, `ceil()`, `round(), abs(), min(), max()`.
- Arethmetic operators supported: `+`, `-`, `*`, `/`, `%`, `^`.
- Keywords are used for special mechanics: `advantage`, `disadvantage`, `keep [highest|lowest]`, `count`, `reroll`, `explode`, `crit`, `glitch`, `dc`.

## Basic Dice Notation
Roll X dice with Y sides:

```
XdY
```

Examples:

```3d6``` - Roll three six-sided dice

```1d20``` - Roll one twenty-sided die

## Arithmetic Modifiers
Roll X dice with Y sides and add/subtract/multiply/divide/modulo/exponentiate by Z:

```
XdY+Z
XdY-Z
XdY*Z
XdY/Z
XdY%Z
XdY^Z
```

Examples:

```3d6 + 3``` - Roll three six-sided dice and add 3

```1d20 - 2``` - Roll one twenty-sided die and subtract 2

```2d6 * 2``` - Roll two six-sided dice and multiply the result by 2

```4d6 / 2``` - Roll four six-sided dice and divide the result by 2 (results in float, use `floor()`, `ceil()`, or `round()` to convert to integer)

```5d10 % 3``` - Roll five ten-sided dice and take the result modulo 3

```2d8 ^ 2``` - Roll two eight-sided dice and raise the result to the power of 2

## Arithmetic Expressions
Group expressions with parentheses and use standard operator precedence:
```
(XdY + Z) * N
XdY + Z * N
```

Examples:

```(2d6 + 3) * 2``` - Roll two six-sided dice, add 3, then multiply by 2

```1d20 + 5 * 2``` - Roll one twenty-sided die, add 5 times 2 (i.e., add 10)


## Mathematical Functions

Use `floor()`, `ceil()`, and `round()` to round results:

```
floor(expression)
ceil(expression)
round(expression)
abs(expression)
min(expression1, expression2, ...)
max(expression1, expression2, ...)
```

Examples:

```floor(1d20 / 2)``` - Roll one twenty-sided die, divide by 2, round down

```ceil(3d6 / 2)``` - Roll three six-sided dice, divide by 2, round up

```round(1d20 * 1.5)``` - Roll one twenty-sided die, multiply by 1.5, round to nearest integer

```abs(1d6 - 10)``` - Roll one six-sided die, subtract from 10, take absolute value

```min(1d6, 1d8)``` - Roll one six-sided die and one eight-sided die, take the minimum result

```max(2d10, 3d6)``` - Roll two ten-sided dice and three six-sided dice, take the maximum result

```max(2d10, 3d6, 2d8)``` - Min/max functions can take multiple arguments

## Math only expressions

You can use mathematical expressions without dice rolls:

```
Z + N * (M - P) / Q
```

Examples:

```5 + 3 * (10 - 2) / 4``` - Basic arithmetic expression without dice

```(15 - 4) ^ 2 + 10 % 3``` - Another arithmetic expression without dice

## Advantage/Disadvantage

Use advantage/disadvantage mechanics:

```
XdY advantage
XdY disadvantage
```

Examples:

```1d20 advantage``` - Roll two d20s, keep the higher

```1d20 disadvantage``` - Roll two d20s, keep the lower

## Keep Highest/Lowest

Use keep highest/lowest mechanics:

 ```
 XdY keep N highest
 XdY keep N lowest
 ```

Examples:

```4d6 keep 3 highest``` - Roll four d6s, keep the highest three

```4d6 keep 2 lowest``` - Roll four d6s, keep the lowest two

## Success Counting

Use the 'count' keyword with comparison operators to count successes:

```
XdY count >= N
XdY count > N
```

Examples:

```5d6 count >= 4``` - Roll five d6s, count how many are 4 or higher

```10d10 count > 7``` - Roll ten d10s, count how many are greater than 7

**Note**: The `count` keyword is **required** to distinguish success counting from DC checks (e.g., `1d20+5 dc >= 15`), rerolls and explosions.

## Rerolls

Use reroll mechanics:

```
XdY reroll <= N
XdY reroll < N
XdY reroll >= N
XdY reroll N
```

Examples:

```4d6 reroll <= 2``` - Roll four d6s, reroll any die that is 2 or less, unlimited rerolls (up to system limit to prevent infinite loops)

```6d6 reroll == 1``` - Roll six d6s, reroll any die that is exactly 1, unlimited rerolls (up to system limit to prevent infinite loops)

### Limit rerolls

Use reroll dice mechanics with limits:

```
XdY reroll M <= N
XdY reroll M < N
XdY reroll M >= N
XdY reroll M N
```

Examples:

```4d6 reroll 2 <= 2``` - Roll four d6s, reroll any die that is 2 or less, up to 2 rerolls per die

```6d6 reroll 3 1``` - Roll six d6s, reroll any die that is exactly 1, up to 3 rerolls per die

## Exploding Dice

Use exploding dice mechanics:

```
XdY explode
XdY explode >= N
XdY explode M
XdY explode M >= N
```

Examples:

```3d6 explode``` - Roll three d6s, any die that rolls a 6 explodes (rolls again), unlimited explosions (up to system limit to prevent infinite loops)

```3d6 explode >= 5``` - Roll three d6s, any die that rolls 5 or 6 explodes, unlimited explosions (up to system limit to prevent infinite loops)

### Limit exploding dice

Use exploding dice mechanics with limits:
```
XdY explode M
XdY explode M >= N
```

Examples:

```3d6 explode 3``` - Roll three d6s, any die that rolls a 6 explodes, up to 3 explosions per die

```3d6 explode 3 >= 5``` - Roll three d6s, any die that rolls 5 or 6 explodes, up to 3 explosions per die

## Special Dice

Use special dice types:

```
XdF
d%
```

Examples:

```2dF``` - Roll two FATE dice (values -1, 0, +1)

```d%``` - Roll one percentile die (1-100)

## Placeholders/Variables

Use placeholders for dynamic values:

```
$name$
```

Examples:

```1d20 + $str$ + $proficiency$``` - Roll one d20, add strength and proficiency 
modifiers from variables

```(1d8 + $ability.str.bonus$) * 2 + 5``` - Damage roll with strength multiplier from variable

### Use in code:
```php
// Character sheet integration
$expression = '1d20 + $str$ + $proficiency$';
$values = ['str' => 3, 'proficiency' => 2];
$result = $phpdice->roll($expression, $values);
echo $result->total; // d20 + 3 + 2
```

## Success Rolls (DC Checks)

Use 'dc' keyword before comparison operators for DC checks:

```
XdY + Z dc >= N
XdY + Z dc > N
XdY + Z dc <= N
XdY + Z dc < N
XdY + Z dc == N
```

Examples:

```1d20 + 5 dc >= 15``` - Roll one d20, add 5, check if total is at least 15 (DC check)

```1d20 + 3 dc == 18``` - Roll one d20, add 3, check if total equals 18 (DC check)

Notes: 

- The success check is done on the final roll result with all modifiers applied.
- Without the `auto` keyword, a roll must meet the DC threshold to be considered a success.

## Auto Success/Failure

Use the auto mechanic to designate certain die values as automatic successes or failures, regardless of the DC:

```
XdY auto N
```

Examples:

```1d20 auto 20 dc >= 25``` - Roll one d20, natural 20 is automatic success even though DC 25 is impossible

```1d20 auto 20 crit 19 dc >= 15``` - Roll one d20, natural 20 is auto success and critical, 19-20 are critical if DC is met

```1d100 auto 100 crit 95 dc >= 105``` - Roll one d100, natural 100 is automatic success and critical

Notes:

- Auto success means the roll is counted as a success regardless of whether the total meets the DC.
- Auto success is typically used with critical success (D&D/Pathfinder: natural 20 is always a success and a crit).
- When auto success is triggered, if the value is also in the crit range, it's treated as a critical success.
- Without the `auto` keyword, rolls must meet the DC to be successful, even on maximum die values.

## Critical Success/Failure

Use the crit and glitch mechanics:

```
XdY crit N
XdY glitch N
XdY crit N glitch M
```

Examples:

```1d20 crit 20``` - Roll one d20, critical success on natural 20 (if DC is met or no DC)

```1d20 glitch 1``` - Roll one d20, critical failure on natural 1

```1d20 crit 19 glitch 2``` - Roll one d20, critical success on 19 or 20, critical failure on 1 or 2

```1d20 auto 20 crit 19 dc >= 15``` - Roll one d20, 19-20 are critical if DC is met, natural 20 is always a success

Notes: 

- Crit/glitch apply to natural dice result (normally on 1d20) without modifiers and should not be combined with multiple dice, reroll or explode.
- Crit and glitch keywords can be used together to define both critical success and failure ranges.
- A crit is only counted when the roll is also a success according to any DC check in the expression.
- To make a critical value automatically succeed regardless of DC, use the `auto` keyword.
- Without `auto`, a roll in the crit range still needs to meet the DC to be a critical success.

## Complex Combinations

Combine multiple mechanics in one expression.

Examples:

```1d20 crit 20 glitch 1 advantage + 5 dc >= 15``` - Roll d20 (crit and glitch) with advantage, add 5, check against DC 15

```12d6 reroll <=1 count >=5``` - Roll 12d6, reroll any 1s, count successes of 5 or higher

```4d6 explode keep 3 highest + $modifier$``` - Roll 4d6 with exploding dice, keep highest 3, add modifier from variable

## Modifier Ordering Rules

When combining multiple modifiers, they must be specified in the following order:

1. **advantage** or **disadvantage** or **reroll** or **explode**  (cannot combine these keywords on the same dice)
2. **keep** or **drop** (highest/lowest)
3. **auto** (automatic success/failure)
4. **crit** and/or **glitch** (critical success/failure)
5. **count** (success counting)
6. **modifiers** (addition, subtraction, etc.)
7. **dc** (difficulty class comparison)

**Important:** 
- `explode` and `reroll` cannot be combined on the same dice roll
- `explode` or `reroll` must come before `keep`
- `auto` must come before `crit` and `glitch`
- `count` must come after `explode`/`reroll` and `keep`
- `dc` must be at the end

Examples of correct ordering:
- `4d6 explode keep 3 highest` - ✓ Correct
- `6d6 reroll <=1 keep 4 highest count >=5` - ✓ Correct
- `1d20 auto 20 crit 19 glitch 1 dc >= 15` - ✓ Correct
- `4d6 keep 3 highest explode` - ✗ Incorrect (keep before explode)
- `4d6 explode reroll <=1` - ✗ Incorrect (both explode and reroll)
- `1d20 crit 19 auto 20` - ✗ Incorrect (crit before auto)

Notes:
- Advantage/Disadvantage shall be the first keyword to ensure correct die selection.
- Reroll/explode must come before keep/drop to ensure all dice are considered for rerolls/explosions.
- Auto must come before crit/glitch to establish automatic success behavior first.
- Crit/Glitch must come after auto and any rerolls to apply to the final selected die.
- Crit/glitch apply to natural dice result (normally on 1d20) without modifiers and are normally not combined with multiple dice, reroll or explode.
- Keep/drop must come before count to ensure the correct dice are counted.
- The `count` keyword must be after any reroll/explode/keep mechanics to ensure correct success counting and can only be used once per expression.
- Modifiers (addition, subtraction, etc.) must come after all dice mechanics to apply to the final roll total / number of successes.
- The `dc` keyword must be the last keyword in the expression to ensure correct DC checking with the roll total or number of successes and all modifiers.
