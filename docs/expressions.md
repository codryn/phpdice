# Dice Expressions

The PHPDice library supports a wide range of dice expressions commonly used in tabletop RPGs. Below is a comprehensive guide to the supported syntax and features.

General rules:
- Dice expressions can include basic dice rolls, arithmetic operations, functions, and special mechanics.
- Supported dice types: standard (d4, d6, d8, d10, d12, d20, d%), FATE dice (dF), coin flip dice (C).
- Expressions can contain space characters for readability.
- Parentheses can be used to group sub-expressions.
- Mathematical functions supported: `floor()`, `ceil()`, `round(), abs(), min(), max()`.
- Arethmetic operators supported: `+`, `-`, `*`, `/`, `%`, `^`.
- Keywords are used for special mechanics: `advantage`, `disadvantage`, `keep [highest|lowest]`, `count`, `reroll`, `explode`, `edge`, `crit`, `glitch`, `dc`.
- Comments can be added at the end of expressions using the `#` character.

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

Use `floor()`, `ceil()`, `round()` to round results.
Use `abs()` for absolute value.
Use `min()` and `max()` to get minimum/maximum of multiple expressions.

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

## Boolean Algebra (Conditional Expressions)

Roll expressions can model decisions using conditional expressions.

A boolean expression contains a condition and true/false branches in the following format:
```
if condition: trueBranch | falseBranch
```

The condition is a comparison expression using one of the comparison operators: `<`, `<=`, `>`, `>=`, `==`, `!=`.

The condition can compare any two expressions, including:
- Math expressions (`if 5 > 3: ...`)
- Placeholders (`if $crit$ > 0: ...`)
- Complete dice rolls (`if 1d6 > 3: ...`)

The final result of the roll will be the branch used (true or false).

Examples:

```if $crit$ > 0: 2d6+5 | 1d6+2``` - If `$crit$` is greater than 0, roll 2d6+5; otherwise roll 1d6+2

```1d20 + (if $rank$ >= 10: 4 | 2)``` - Roll skill check with variable feat bonus: 1d20 +4 if rank >= 10, otherwise 1d20 +2

```if 1d6 > 3: 1d20 + 5 | 1d12 - 1``` - Roll 1d6; on 4-6 roll 1d20+5, on 1-3 roll 1d12-1

```if $value$ == 5: 100 | 0``` - Return 100 if value equals 5, otherwise 0

```if $status$ != 0: 2d6 | 1d4``` - Roll 2d6 if status is not zero, otherwise roll 1d4

Notes:
- The condition must evaluate to a comparison expression that results in true (1) or false (0)
- Only the branch corresponding to the condition result is evaluated (lazy evaluation)
- Conditionals can be nested and combined with other expressions
- The `if` expression follows standard operator precedence and can be used in parentheses

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

Note: Advantage and disadvantage cannot be combined on the same roll. It can also not be combined with reroll, explode or edge mechanics.

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
XdY count even
XdY count odd
```

Examples:

```5d6 count >= 4``` - Roll five d6s, count how many are 4 or higher

```10d10 count > 7``` - Roll ten d10s, count how many are greater than 7

```6d6 count even``` - Roll six d6s, count how many are even (2, 4, 6)

```12d4 count odd``` - Roll twelve d4s, count how many are odd (1, 3)

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

Note: Rerolling dice cannot be combined with advantage, disadvantage, exploding, or edge dice on the same roll.

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

Note: Exploding dice cannot be combined with advantage, disadvantage, reroll, or edge dice on the same roll.

### Limit exploding dice

Use exploding dice mechanics with limits:
```
XdY explode M
XdY explode M >= N
```

Examples:

```3d6 explode 3``` - Roll three d6s, any die that rolls a 6 explodes, up to 3 explosions per die

```3d6 explode 3 >= 5``` - Roll three d6s, any die that rolls 5 or 6 explodes, up to 3 explosions per die

## Edge Dice (Shadowrun Rule of Six)

Use edge mechanics to add additional dice when a threshold is met (different from explode which sums):

```
XdY edge
XdY edge >= N
XdY edge M
XdY edge M >= N
```

Examples:

```3d6 edge``` - Roll three d6s, any die that rolls a 6 adds an additional die to the pool (not summed into original die), unlimited edge dice (up to system limit to prevent infinite loops)

```5d6 edge count >=5``` - Roll five d6s with Shadowrun success counting, 6s add additional dice that can also be successes

```3d6 edge >= 5``` - Roll three d6s, any die that rolls 5 or 6 adds an additional die to the pool, unlimited edge dice

**Key Difference from Explode**: Edge adds new dice to the pool, while explode sums additional rolls into one die. For example:
- `1d6 explode` rolling a 6 then 4 results in one die with value 10
- `1d6 edge` rolling a 6 then 4 results in two dice with values [6, 4]

Note: Edge dice cannot be combined with advantage, disadvantage, reroll, or explode mechanics on the same roll.

### Limit edge dice

Use edge dice mechanics with limits:
```
XdY edge M
XdY edge M >= N
```

Examples:

```3d6 edge 3``` - Roll three d6s, any die that rolls a 6 adds additional dice, up to 3 edge dice per original die

```5d6 edge 2 >= 5``` - Roll five d6s, any die that rolls 5 or 6 adds additional dice, up to 2 edge dice per original die

## Special Dice

Use special dice types:

```
XdF
d%
XC
```

Examples:

```2dF``` - Roll two FATE dice (values -1, 0, +1)

```d%``` - Roll one percentile die (1-100)

```1C``` - Roll one coin flip (values 0 or 1, representing tails/heads)

```3C``` - Roll three coin flips (total 0 to 3)

**Note**: Coin dice work exactly as `1C` equivalent to `1d2` but return 0 or 1 instead of 1 or 2. The interpretation of heads vs tails is done by the caller.

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

## Roll Comments

Add descriptive comments to your rolls using the `#` character. Comments are placed at the end of the expression and can include placeholders that will be expanded to their numeric values.

```
expression # comment text
```

Examples:

```1d20 + 5 # Roll for initiative``` - Basic comment

```1d20 + $ini$ # Roll for initiative (bonus $ini$)!``` - Comment with placeholder expansion

```1d20 + 15 # Attack codryn/phpdice#1``` - Comment with GitHub issue reference (subsequent `#` characters are part of the comment)

```1d20 + 5 # Attack codryn/phpdice#2 (-5 Penalty)``` - Comment with multiple details

### Use in code:
```php
// Roll with comment
$expression = '1d20 + $str$ # Strength check (+$str$)';
$values = ['str' => 4];
$result = $phpdice->roll($expression, $values);
echo $result->comment; // "Strength check (+4)"
echo $result->total; // d20 + 4
```

Notes:

- Comments are separated from the expression by the `#` character
- Leading and trailing whitespace is trimmed from the comment
- Any subsequent `#` characters are part of the comment string
- Placeholders in comments are expanded to their numeric values
- The comment is available in both the parsed `DiceExpression` and the `RollResult`

## Roll Tags

Add metadata tags to your rolls using square brackets `[tag1, tag2, ...]`. Tags are useful for categorizing rolls by damage type, condition, or other attributes. Tags are case-insensitive and normalized to lowercase, and can contain letters (a-z), numbers (0-9), dots (.), hyphens (-), and underscores (_).

```
expression [tag1, tag2, ...]
expression [tag1, tag2, ...] # comment
```

### Tags on Main Expression

Tags can be placed at the end of the main expression (after DC checks if present, before comments):

```
1d6+2 [magic, piercing] # roll damage
```

Examples:

```1d6+2 [MAGIC, piercing, Silver]``` - Roll with tags, normalized to: `[magic, piercing, silver]`

```1d20+5 dc >= 15 [saving-throw] # wisdom save``` - Tags after DC check, before comment

```2d6 [fire, magic-damage, dmg.type]``` - Tags with various allowed characters

### Tags on Groups

Tags can also be placed on individual roll groups. Group tags are isolated and appear only in their respective group results, not in the main roll result:

```
{1d6+6 [Piercing]} + {2d6 [Fire, magic]}
```

In this example:
- Main roll result has no tags (or keeps its own tags if specified separately)
- First group result has tags: `[piercing]`
- Second group result has tags: `[fire, magic]`

### Main Expression and Group Tags Together

Both the main expression and groups can have their own independent tags:

```
{1d6 [piercing]} + {2d6 [fire]} [slashing, magic] # total damage
```

Result:
- Main roll result tags: `[slashing, magic]`
- First group tags: `[piercing]`
- Second group tags: `[fire]`

### Use in code:

```php
// Roll with tags
$expression = '1d6+2 [magic, piercing] # spell damage';
$result = $phpdice->roll($expression);
echo implode(', ', $result->tags); // "magic, piercing"

// Groups with tags
$expression = '{1d6 [piercing]} + {2d6 [fire]} # mixed damage';
$result = $phpdice->roll($expression);
echo implode(', ', $result->groups[0]->tags); // "piercing"
echo implode(', ', $result->groups[1]->tags); // "fire"
```

### Tag Rules and Restrictions:

- Tags are placed in square brackets `[...]` with comma-separated values
- Tags are case-insensitive and normalized to lowercase
- Allowed characters: a-z, 0-9, dot (.), hyphen (-), underscore (_)
- Tags appear after DC checks and before comments in the expression order
- **Only one tag section is allowed** per expression or group (multiple `[...]` sections will cause an error)
- Group tags are isolated to their respective groups and do not appear in the main result
- Main expression can have its own tags independently of group tags
- Empty tag sections `[]` are valid and result in an empty tags array

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

1. **if** (conditional expression - can wrap entire expressions)
2. **advantage** or **disadvantage** or **reroll** or **explode** or **edge** (cannot combine these keywords on the same dice)
3. **keep** or **drop** (highest/lowest)
4. **auto** (automatic success/failure)
5. **crit** and/or **glitch** (critical success/failure)
6. **count** (success counting)
7. **modifiers** (addition, subtraction, etc.)
8. **dc** (difficulty class comparison)
9. **tags** (metadata tags in square brackets [...])
10. **comment** (descriptive text starting with #)

**Important:** 
- `if` expressions can wrap entire expressions or be used in sub-expressions with parentheses
- `advantage`, `disadvantage`, `explode`, `reroll`, and `edge` cannot be combined on the same dice roll.
- `advantage`, `disadvantage`, `explode`, `reroll`, or `edge` must come before `keep`.
- `auto` must come before `crit` and `glitch`
- `count` must come after `advantage`/`disadvantage`/`explode`/`reroll`/`edge`/`keep`
- `dc` must come before tags and comments
- **Tags** `[...]` must come after `dc` and before comments
- **Only one tag section is allowed** per expression or group
- `#` comment must be at the very end of the expression

Examples of correct ordering:
- `if $crit$ > 0: 2d6+5 | 1d6+2` - ✓ Correct (conditional wrapping dice rolls)
- `1d20 + (if $rank$ >= 10: 4 | 2)` - ✓ Correct (conditional in arithmetic)
- `4d6 explode keep 3 highest` - ✓ Correct
- `6d6 reroll <=1 keep 4 highest count >=5` - ✓ Correct
- `1d20 auto 20 crit 19 glitch 1 dc >= 15` - ✓ Correct
- `1d20 + 5 dc >= 15 [saving-throw] # Saving throw` - ✓ Correct (tags after dc, comment at end)
- `if 1d6 > 3: 1d20 + 5 | 1d12 - 1` - ✓ Correct (dice in condition and branches)
- `1d6 [fire, magic] # damage` - ✓ Correct (tags before comment)
- `4d6 keep 3 highest explode` - ✗ Incorrect (keep before explode)
- `4d6 explode reroll <=1` - ✗ Incorrect (both explode and reroll)
- `1d20 [tag1] [tag2]` - ✗ Incorrect (multiple tag sections)
- `1d20 [fire] dc >= 15` - ✗ Incorrect (tags before dc)
- `1d20 crit 19 auto 20` - ✗ Incorrect (crit before auto)
- `1d20 # Comment dc >= 15` - ✗ Incorrect (comment before dc)

Notes:
- Conditional expressions (`if`) have the highest precedence and can wrap entire expressions or be nested.
- Advantage/Disadvantage shall be the first keyword to ensure correct die selection.
- Reroll/explode/edge must come before keep/drop to ensure all dice are considered for rerolls/explosions/rule of 6.
- Auto must come before crit/glitch to establish automatic success behavior first.
- Crit/Glitch must come after auto and any rerolls to apply to the final selected die.
- Crit/glitch apply to natural dice result (normally on 1d20) without modifiers and are normally not combined with multiple dice, reroll, explode, or edge.
- Keep/drop must come before count to ensure the correct dice are counted.
- The `count` keyword must be after any reroll/explode/keep mechanics to ensure correct success counting and can only be used once per expression.
- Modifiers (addition, subtraction, etc.) must come after all dice mechanics to apply to the final roll total / number of successes.
- The `dc` keyword must come before tags and comments to ensure correct DC checking with the roll total or number of successes and all modifiers.
- Tags `[...]` must come after `dc` and before comments. Only one tag section is allowed per expression or group.
- Comments (starting with `#`) must be the very last element in the expression.
