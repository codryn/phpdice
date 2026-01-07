# Dice Expressions

The PHPDice library supports a wide range of dice expressions commonly used in tabletop RPGs. Below is a comprehensive guide to the supported syntax and features.

General rules:
- Dice expressions can include basic dice rolls, arithmetic operations, functions, and special mechanics.
- Supported dice types: standard (d4, d6, d8, d10, d12, d20, d%), FATE dice (dF).
- Expressions can contain space characters for readability.
- Parentheses can be used to group sub-expressions.
- Mathematical functions supported: `floor()`, `ceil()`, `round()`.

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

```(1d8 + $str$) * 2 + 5``` - Damage roll with strength multiplier from variable

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

## Critical Success/Failure

Use the crit and glitch mechanics:

```
XdY crit N
XdY glitch N
XdY crit N glitch M
```

Examples:

```1d20 crit 20``` - Roll one d20, critical success on natural 20

```1d20 glitch 1``` - Roll one d20, critical failure on natural 1

```1d20 crit 19 glitch 2``` - Roll one d20, critical success on 19 or 20, critical failure on 1 or 2

## Complex Combinations

Combine multiple mechanics in one expression.

Examples:

```1d20 crit 20 glitch 1 advantage + 5 dc >= 15``` - Roll d20 (crit and glitch) with advantage, add 5, check against DC 15

```12d6 reroll <=1 count >=5``` - Roll 12d6, reroll any 1s, count successes of 5 or higher

```4d6 explode keep 3 highest + $modifier$``` - Roll 4d6 with exploding dice, keep highest 3, add modifier from variable
