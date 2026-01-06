# Feature Specification: Dice Expression Parser and Roller

**Feature Branch**: `001-dice-parser-roller`
**Created**: 2025-12-02
**Status**: Draft
**Input**: User description: "The library provides: a dice expression parser and a dice roller. The parser shall support all possible game systems with supported roll expressions: Basic expression (e.g. 4d6), modifiers (e.g. 1d20+12), advantage (roll n times and keep m rolls, for dnd5 roll 1d20 2 times and keep highest), disadvantage (same but keep lowest), rerolls if equal or below a threshold, success counting (all dice above a threshold), fudge dices, percent dices, placeholders (1d20+str+luck), success rolls (1d20 >= 18), critical success thresholds (e.g. natural 20), critical failure thresholds (e.g. natural 1). A parsed dice expression shall be returned in a well documented data structure that can be passed to the dice roller. The data structure shall also allow to get statistic data for the roll such as minimal value, maximal value, expected value. The dice roller shall evaluate the parsed roll expression and return the result of the roll in a well documented data structure. The result shall contain: all data from the roll request (see above), the result, all rolled dices (without any modifiers), flags for critical success or failures. On success counting, the result number shall be the number of successes instead of the rolled sum."

## Clarifications

### Session 2025-12-02

- Q: When a placeholder variable is referenced in a roll expression but not provided at roll time (e.g., "1d20+%str%" rolled without binding "str"), what should happen? → A: Reject with clear error message listing missing variables
- Q: How should reroll mechanics handle potentially infinite reroll scenarios (e.g., "4d6 reroll <= 6" on a d6 where every result would trigger another reroll)? → A: Validate and define hard limit for maximal rerolls.
- Q: What is the minimum PHP version the library must support? → A: PHP 8.0
- Q: What should happen when advantage/disadvantage is requested with invalid parameters (e.g., "roll 3d6 keep 5 highest" where you're trying to keep more dice than you rolled)? → A: Reject at parse time with validation error
- Q: When should critical success/failure thresholds be specified - at parse time (part of the expression syntax) or at roll time (as parameters to the roll function)? → A: Parse time (expression syntax)
- Q: When should placeholder variables be bound - at parse time or roll time? → A: Parse time (required for statistical calculations to work)
- Q: What syntax should be used for placeholder variables to avoid collisions with reserved keywords? → A: Use %name% prefix/suffix syntax (e.g., "1d20+%str%+%dex%")
- Q: Should the parser support full arithmetic expressions beyond simple addition/subtraction? → A: Yes, support parentheses for grouping and mathematical functions: floor(), ceil(), round()
- Q: How should exploding dice work when a die rolls its maximum value? → A: Reroll and add to total/successes; dice can explode multiple times up to a hard limit of 100 explosions per die
- Q: Should the explosion limit be configurable in the expression? → A: Yes, use syntax like "3d6 explode 3" to limit to 3 explosions per die; omitting the number defaults to 100
- Q: Can explosion be triggered by a range of values instead of just the maximum? → A: Yes, use threshold syntax like "3d6 explode 3 >=5" to explode on values >= 5, limited to 3 explosions per die
- Q: What validation is needed for explosion ranges? → A: Explosion threshold range cannot cover the entire die range (must have non-exploding outcomes possible)
- Q: Should reroll mechanics support configurable limits like explosions? → A: Yes, use syntax like "3d6 reroll 1 <=2" to limit rerolls; default single reroll changes to hard limit of 100 when no count specified
- Q: What is the minimum number of sides a die must have? → A: At least 2 sides (to prevent degenerate single-outcome dice)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Basic Dice Rolling (Priority: P1)

As a game developer, I need to parse and roll basic dice expressions so that my players can perform simple dice rolls for their game actions.

**Why this priority**: This is the foundational functionality that every dice rolling library must support. All other features build upon basic dice parsing and rolling. Without this, no dice rolling is possible.

**Independent Test**: Can be fully tested by parsing expressions like "3d6", "1d20", "2d10" and rolling them to get numeric results. Delivers immediate value as a minimal viable dice roller.

**Acceptance Scenarios**:

1. **Given** a dice expression "3d6", **When** I parse the expression, **Then** I receive a data structure describing 3 six-sided dice
2. **Given** a parsed expression for "3d6", **When** I roll the dice, **Then** I receive a result containing the sum and individual die values
3. **Given** a dice expression "1d20", **When** I parse and roll it, **Then** the result is between 1 and 20 inclusive
4. **Given** a rolled result, **When** I inspect it, **Then** I can see all individual die values before summation

---

### User Story 2 - Modifiers and Arithmetic (Priority: P2)

As a game developer, I need to support dice expressions with arithmetic modifiers so that players can apply bonuses and penalties to their rolls.

**Why this priority**: Nearly all tabletop RPGs require adding or subtracting modifiers from dice rolls (e.g., ability bonuses, situational modifiers). This is essential for most game systems. Support for multiplication, division, parentheses, and mathematical functions enables complex calculations.

**Independent Test**: Can be tested by parsing "1d20+5", "2d6-2", "3d8+12", "(2d6+3)*2", "floor(1d20/2)" and verifying results include correct arithmetic evaluation.

**Acceptance Scenarios**:

1. **Given** an expression "1d20+5", **When** parsed, **Then** the structure shows one d20 plus a modifier of +5
2. **Given** a parsed "1d20+5", **When** rolled, **Then** the final result equals the d20 roll plus 5
3. **Given** an expression "2d6-3", **When** rolled, **Then** the result equals the sum of 2d6 minus 3
4. **Given** an expression "(2d6+3)*2", **When** rolled, **Then** the result equals (sum of 2d6 plus 3) multiplied by 2
5. **Given** an expression "floor(1d20/2)", **When** rolled, **Then** the result is the floor of (1d20 divided by 2)
6. **Given** a rolled result with complex arithmetic, **When** inspected, **Then** I can see the dice values and the final calculated result

---

### User Story 3 - Advantage and Disadvantage (Priority: P3)

As a game developer, I need to support advantage/disadvantage mechanics so that I can implement D&D 5e and similar systems where players roll multiple times and keep the best or worst.

**Why this priority**: Critical for D&D 5e compatibility and other modern RPG systems. Advantage/disadvantage is a core mechanic that affects nearly every roll in these games.

**Independent Test**: Can be tested by rolling with advantage (2d20 keep highest), disadvantage (2d20 keep lowest), and verifying the correct die is selected.

**Acceptance Scenarios**:

1. **Given** an expression "1d20 advantage" (roll 2, keep highest), **When** rolled, **Then** the result shows both rolls and uses the higher value
2. **Given** an expression "1d20 disadvantage" (roll 2, keep lowest), **When** rolled, **Then** the result shows both rolls and uses the lower value
3. **Given** generalized "roll N keep M highest", **When** rolled with 4d6 keep 3 highest, **Then** the result discards the lowest die
4. **Given** a rolled advantage/disadvantage result, **When** inspected, **Then** I can see all dice rolled and which were kept vs discarded

---

### User Story 4 - Success Counting (Priority: P4)

As a game developer, I need success counting mechanics so that I can support dice pool systems like Shadowrun, World of Darkness, and FATE where success is measured by counting dice above a threshold.

**Why this priority**: Essential for dice pool-based game systems. These systems don't sum dice but count how many exceed a target number.

**Independent Test**: Can be tested by rolling expressions like "5d6 count successes >= 4" and verifying the result is a count, not a sum.

**Acceptance Scenarios**:

1. **Given** an expression "5d6 success threshold 4", **When** rolled, **Then** the result is a count of dice showing 4 or higher
2. **Given** a success counting roll, **When** inspected, **Then** I can see which individual dice counted as successes and which didn't
3. **Given** an expression "10d10 >= 7", **When** rolled, **Then** only dice showing 7, 8, 9, or 10 contribute to the success count
4. **Given** a success counting result, **When** statistical data is requested, **Then** expected value represents average number of successes

---

### User Story 5 - Reroll Mechanics (Priority: P5)

As a game developer, I need reroll mechanics with configurable limits so that players can reroll dice that meet certain conditions (e.g., reroll all 1s and 2s) with control over how many times rerolls can occur.

**Why this priority**: Common in many game systems for handling critical failures or improving poor rolls. Adds strategic depth and game balance. Configurable limits prevent infinite loops and allow game-specific tuning.

**Independent Test**: Can be tested by rolling "4d6 reroll <= 2" (default limit of 100), "4d6 reroll 1 <= 2" (single reroll), and verifying correct reroll behavior and limits.

**Acceptance Scenarios**:

1. **Given** an expression "4d6 reroll <= 2" without explicit limit, **When** rolled, **Then** any die showing 1 or 2 can be rerolled up to 100 times per die
2. **Given** an expression "4d6 reroll 1 <= 2" with explicit limit of 1, **When** rolled, **Then** any die showing 1 or 2 is rerolled exactly once (no further rerolls)
3. **Given** an expression "3d6 reroll 5 <= 3", **When** rolled, **Then** each die showing 1, 2, or 3 can be rerolled up to 5 times
4. **Given** a reroll result, **When** inspected, **Then** I can see which dice were rerolled, their original values, and the complete reroll history
5. **Given** a reroll expression, **When** parsed, **Then** the structure includes the reroll threshold condition and the reroll limit
6. **Given** multiple reroll-eligible dice, **When** rolled, **Then** each is rerolled independently according to its own reroll history
7. **Given** an expression where the reroll threshold covers the entire die range (e.g., "1d6 reroll <= 6"), **When** parsed, **Then** the parser MUST reject with a validation error indicating reroll range cannot be the entire die range

---

### User Story 5a - Exploding Dice Mechanics (Priority: P5a)

As a game developer, I need exploding dice mechanics with configurable limits and thresholds so that I can implement systems where exceptional rolls trigger additional dice rolls (e.g., Savage Worlds, some d20 variants).

**Why this priority**: Exploding dice create exciting moments where exceptional rolls can compound into legendary results. Essential for game systems like Savage Worlds, and adds dramatic variance to any system. Positioned after basic rerolls as it builds on similar reroll infrastructure but with additive rather than replacement behavior.

**Independent Test**: Can be tested by rolling "3d6 explode" (default 100 limit on max value), "3d6 explode 2" (limit 2 explosions), "3d6 explode 3 >=5" (explode on 5-6, max 3 times), and verifying explosions add to totals.

**Acceptance Scenarios**:

1. **Given** an expression "3d6 explode" without explicit limit or threshold, **When** a die rolls its maximum value (6), **Then** it explodes (rerolls and adds result) up to 100 times per die
2. **Given** an expression "3d6 explode 2", **When** a die rolls maximum value, **Then** it can explode at most 2 times (original roll + 2 explosion rolls = 3 total rolls for that die)
3. **Given** an expression "3d6 explode 3 >=5" with range threshold, **When** a die shows 5 or 6, **Then** it explodes and adds the new roll, up to 3 explosions per die
4. **Given** an exploding die that triggers multiple explosions, **When** the result is inspected, **Then** I can see the complete explosion chain (all rolled values) and the cumulative total for that die
5. **Given** an exploding dice expression, **When** parsed, **Then** the structure includes the explosion limit, threshold condition, and threshold operator
6. **Given** an explosion threshold that covers the entire die range (e.g., "1d6 explode >=1"), **When** parsed, **Then** the parser MUST reject with validation error indicating explosion range cannot guarantee termination
7. **Given** exploding dice in success counting mode (e.g., "5d6 explode success >=4"), **When** rolled, **Then** each explosion that meets the success threshold increments the success count
8. **Given** a die that explodes 100 times (reaching the default limit), **When** inspected, **Then** the result shows all 100 explosion rolls and indicates the limit was reached

---

### User Story 6 - Special Dice Types (Priority: P6)

As a game developer, I need support for fudge dice and percentile dice so that I can implement FATE and percentile-based game systems.

**Why this priority**: Required for specific game systems. Fudge dice (FATE) show +1/0/-1 instead of numbers. Percentile dice generate 1-100 results.

**Independent Test**: Can be tested by rolling "4dF" (fudge) and "1d100" or "d%" (percentile) and verifying appropriate value ranges.

**Acceptance Scenarios**:

1. **Given** an expression "4dF" (fudge dice), **When** rolled, **Then** each die shows -1, 0, or +1 and they are summed
2. **Given** a fudge dice result, **When** inspected, **Then** I can see individual die values as -1, 0, or +1
3. **Given** an expression "1d100" or "d%", **When** rolled, **Then** the result is between 1 and 100 inclusive
4. **Given** percentile dice, **When** rolled, **Then** the result shows how the percentage was generated (e.g., tens die + ones die)

---

### User Story 7 - Placeholders and Variables (Priority: P7)

As a game developer, I need placeholder support in expressions so that I can create reusable roll templates with character-specific values (e.g., "1d20+%str%+%luck%").

**Why this priority**: Enables dynamic roll evaluation where modifiers come from character attributes. Critical for character sheet integration.

**Independent Test**: Can be tested by parsing "1d20+%str%+%proficiency%", providing variable values, and rolling to get correct results.

**Acceptance Scenarios**:

1. **Given** an expression "1d20+%str%+%luck%" with variable values provided (str=3, luck=2), **When** parsed, **Then** the structure resolves "%str%" and "%luck%" placeholders to their numeric values
2. **Given** a parsed expression with resolved placeholders, **When** rolled, **Then** the roll evaluates correctly using the bound values
3. **Given** an expression with unbound placeholders, **When** parsed without providing values, **Then** the parser MUST reject the expression with a clear error message listing the missing variable names
4. **Given** a parsed expression with resolved placeholders, **When** inspected, **Then** I can see which placeholders were used and their bound values

---

### User Story 8 - Success Rolls and Comparisons (Priority: P8)

As a game developer, I need comparison operators in expressions so that I can evaluate whether a roll meets a target number (e.g., "1d20+5 >= 15" returns true/false).

**Why this priority**: Many game systems require checking if a roll beats a difficulty class or target number. This enables pass/fail evaluation.

**Independent Test**: Can be tested by rolling "1d20+3 >= 15" and receiving a boolean success indicator along with the actual roll value.

**Acceptance Scenarios**:

1. **Given** an expression "1d20 >= 15", **When** rolled, **Then** the result includes both the die value and a success/failure flag
2. **Given** a comparison expression, **When** the roll meets the threshold, **Then** the success flag is true
3. **Given** a comparison expression, **When** the roll fails the threshold, **Then** the success flag is false
4. **Given** a success roll result, **When** inspected, **Then** I can see the actual roll value, the threshold, and the success status

---

### User Story 9 - Critical Success and Critical Failure (Priority: P9)

As a game developer, I need critical success and critical failure detection so that I can identify exceptional roll outcomes (e.g., natural 20 or natural 1 on a d20).

**Why this priority**: Critical hits and fumbles are iconic RPG mechanics that create memorable moments. Essential for proper game system support.

**Independent Test**: Can be tested by configuring critical thresholds (e.g., 20 for success, 1 for failure on d20) and rolling until criticals occur.

**Acceptance Scenarios**:

1. **Given** a d20 roll with critical success threshold 20, **When** a natural 20 is rolled, **Then** the result is flagged as a critical success
2. **Given** a d20 roll with critical failure threshold 1, **When** a natural 1 is rolled, **Then** the result is flagged as a critical failure (glitch)
3. **Given** custom critical thresholds specified in the expression syntax at parse time, **When** parsed, **Then** the parser captures the threshold values in the DiceExpression structure
4. **Given** a critical result, **When** inspected, **Then** I can see which die value triggered the critical and the threshold that was configured
5. **Given** multiple dice rolled, **When** any single die is critical, **Then** the result is flagged appropriately

---

### User Story 10 - Statistical Analysis (Priority: P10)

As a game developer, I need statistical data for parsed expressions so that I can display probability information to players (minimum, maximum, expected values).

**Why this priority**: Valuable for game balance testing, player education, and UI enhancements. Non-critical for basic functionality but enhances user experience.

**Independent Test**: Can be tested by parsing "3d6+5" and querying for min (8), max (23), and expected value (15.5) without rolling.

**Acceptance Scenarios**:

1. **Given** a parsed expression "3d6+5", **When** I request statistics, **Then** I receive minimum value (8), maximum value (23), and expected value (15.5)
2. **Given** a complex expression with advantage, **When** statistics are calculated, **Then** they reflect the keep-highest mechanic
3. **Given** a success counting expression, **When** statistics are requested, **Then** expected value represents average number of successes
4. **Given** any valid expression, **When** parsed, **Then** statistical data is available without performing a roll

---

### Edge Cases

The parser MUST fail with clear, actionable error messages for all invalid inputs. The following edge cases define required validation and error handling:

#### Invalid Dice Expressions

- **Invalid syntax** (e.g., "abc", "d", "3d", "xyz+5"): Parser MUST reject with error identifying the invalid syntax at the specific position
- **Malformed dice notation** (e.g., "d6" without number, "3d" without sides): Parser MUST reject with error indicating missing required component

#### Dice Constraints Validation

- **Zero dice** (e.g., "0d6"): Parser MUST reject - number of dice must be at least 1
- **Negative dice** (e.g., "-3d6"): Parser MUST reject - number of dice cannot be negative
- **Single-sided die** (e.g., "3d1"): Parser MUST reject - dice must have at least 2 sides to provide meaningful randomness
- **Zero sides** (e.g., "3d0"): Parser MUST reject - dice must have at least 2 sides
- **Negative sides** (e.g., "2d-5"): Parser MUST reject - dice cannot have negative sides
- **Excessive dice count in expression** (e.g., "101d6", "50d8+52d10"): Parser MUST reject when total dice across entire expression exceeds 100
- **Excessive sides on single die** (e.g., "1d101", "2d150"): Parser MUST reject when any single die has more than 100 sides

#### Arithmetic Expression Validation

- **Division by zero** (e.g., "1d20/0", "2d6/(3-3)"): Parser MUST reject with error identifying division by zero
- **Mathematical function with missing argument** (e.g., "floor()", "ceil()", "round()"): Parser MUST reject with error indicating required argument missing
- **Mathematical function with invalid argument count** (e.g., "floor(1d20, 5)" with too many args): Parser MUST reject with error about argument count
- **Parenthesis mismatch** (e.g., "(1d20+5", "2d6+3)", "((1d20)"): Parser MUST reject with error identifying unmatched parenthesis and its position

#### Modifier Conflicts and Validation

- **Conflicting advantage and disadvantage** (e.g., "1d20 advantage disadvantage"): Parser MUST reject - cannot apply both modifiers simultaneously
- **Invalid advantage/disadvantage parameters** (e.g., "roll 3d6 keep 5 highest"): Parser MUST reject when keep-count exceeds roll-count (already covered in FR-003a, FR-004a)

#### Reroll Mechanics Validation

- **Reroll threshold covering entire die range** (e.g., "1d6 reroll <= 6", "1d20 reroll >= 1"): Parser MUST reject - reroll condition cannot guarantee all outcomes trigger reroll (must have at least one non-rerolling outcome)
- **Reroll with single-sided die** (e.g., "3d1 reroll <= 1"): Parser MUST reject due to minimum 2-sided die requirement
- **Negative reroll limit** (e.g., "3d6 reroll -1 <= 3"): Parser MUST reject - reroll limit must be non-negative
- **Reroll limit of zero** (e.g., "3d6 reroll 0 <= 2"): Parser MUST accept - indicates no rerolls allowed (effectively disables reroll mechanic)
- **Excessive reroll limit** (e.g., "3d6 reroll 101 <= 2"): Parser SHOULD warn or reject - reroll limit exceeds reasonable bounds (implementation may cap at 100)

#### Exploding Dice Validation

- **Explosion threshold covering entire die range** (e.g., "1d6 explode >= 1", "1d20 explode <= 20"): Parser MUST reject - explosion condition must allow non-exploding outcomes to guarantee termination
- **Explosion with single-sided die** (e.g., "3d1 explode"): Parser MUST reject due to minimum 2-sided die requirement
- **Negative explosion limit** (e.g., "3d6 explode -1"): Parser MUST reject - explosion limit must be non-negative
- **Explosion limit of zero** (e.g., "3d6 explode 0"): Parser MUST accept - indicates no explosions allowed (effectively disables explosion mechanic)
- **Excessive explosion limit** (e.g., "3d6 explode 101"): Parser SHOULD warn or reject - explosion limit exceeds reasonable bounds (implementation may cap at 100)
- **Explosion with incompatible threshold operator** (e.g., "3d6 explode == 6"): Parser MUST reject or normalize - only >= and <= operators are meaningful for ranges; == should either be rejected or treated as ">= value"

#### Critical Threshold Validation

- **Critical success threshold out of range** (e.g., "1d20 critical success >= 25"): Parser MUST reject when threshold exceeds maximum die value
- **Critical failure threshold out of range** (e.g., "1d20 critical failure <= 0"): Parser MUST reject when threshold is below minimum die value (typically 1)
- **Critical failure threshold out of range** (e.g., "1d6 glitch <= -1"): Parser MUST reject when critical failure threshold is outside valid die range [1, sides]

#### Placeholder Variable Validation

- **Unbound placeholder variables** (e.g., "1d20+%str%" parsed without providing "str" value): Parser MUST reject with error message listing all missing variable names (already covered in FR-009a)

#### Edge Case Interactions

- **Reroll with configurable limit** (e.g., "4d6 reroll 5 <= 2"): Roller MUST reroll each die showing <= 2 up to 5 times, then accept the final result even if still <= 2
- **Explosion reaching limit** (e.g., "3d6 explode 10 >= 5"): Roller MUST stop exploding after 10 explosions per die and sum all rolled values including the non-exploding final roll
- **Combined reroll and explode** (e.g., "3d6 reroll 1 <= 2 explode 3 >= 5"): Parser MUST accept; roller applies rerolls first, then explosion mechanics to final rerolled values
- **Success counting with fudge dice** (e.g., "4dF success >= 0"): Parser MUST accept and roller evaluates fudge dice (-1, 0, +1) against threshold normally
- **Success counting with percentile dice** (e.g., "1d100 success >= 75"): Parser MUST accept and roller evaluates d100 result against threshold normally
- **Exploding dice in success counting mode** (e.g., "5d6 explode success >= 4"): Roller MUST count each die value (including explosion values) that meets threshold as a success

#### Error Message Requirements

- All error messages MUST identify the specific problem (e.g., "division by zero", "unmatched opening parenthesis")
- All error messages SHOULD indicate the position/location in the expression where the error occurred when feasible
- All error messages for missing/invalid components MUST specify what was expected (e.g., "expected number of sides after 'd'")
- All error messages for constraint violations MUST specify the limit that was exceeded (e.g., "total dice count 105 exceeds maximum of 100")

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Parser MUST accept basic dice notation strings (e.g., "3d6", "1d20", "2d10") and return a structured representation
- **FR-002**: Parser MUST support arithmetic expressions including addition, subtraction, multiplication, division, parentheses for grouping, and mathematical functions floor(), ceil(), round() (e.g., "1d20+5", "(2d6+3)*2", "floor(1d20/2)"). Round always rounds to nearest integer and accepts only one argument.
- **FR-003**: Parser MUST support advantage mechanics (roll N times, keep M highest) for any dice type
- **FR-003a**: Parser MUST validate that keep-count does not exceed roll-count for advantage mechanics and reject invalid expressions at parse time
- **FR-004**: Parser MUST support disadvantage mechanics (roll N times, keep M lowest) for any dice type
- **FR-004a**: Parser MUST validate that keep-count does not exceed roll-count for disadvantage mechanics and reject invalid expressions at parse time
- **FR-005**: Parser MUST support reroll conditions based on threshold comparisons with configurable limits (e.g., "reroll if <= X", "reroll 5 <= X")
- **FR-005a**: Roller MUST support configurable reroll limits: explicit count (e.g., "reroll 1 <= 2") limits rerolls to that number; no count specified defaults to hard limit of 100 rerolls per die
- **FR-005b**: Parser MUST validate that reroll threshold does not cover the entire die range and reject expressions where all possible outcomes would trigger reroll
- **FR-006**: Parser MUST support success counting mode where dice above a threshold are counted instead of summed
- **FR-007**: Parser MUST support fudge dice notation (e.g., "4dF") that generate values of -1, 0, or +1
- **FR-008**: Parser MUST support percentile dice notation (e.g., "1d100" or "d%") that generate values 1-100
- **FR-009**: Parser MUST support placeholder variables using %name% syntax (e.g., "1d20+%str%+%proficiency%") with values provided at parse time to avoid collisions with reserved keywords
- **FR-009a**: Parser MUST reject expressions with unbound placeholder variables by throwing an error that lists all missing variable names
- **FR-010**: Parser MUST support comparison operators for success/failure evaluation (e.g., "1d20+3 >= 15")
- **FR-011**: Parser MUST support configurable critical success thresholds as part of expression syntax (e.g., natural 20) captured at parse time
- **FR-012**: Parser MUST support configurable critical failure thresholds as part of expression syntax (e.g., natural 1) captured at parse time
- **FR-013**: Parsed expression data structure MUST be well-documented and comprehensible to library users
- **FR-014**: Parsed expression MUST provide statistical data including minimum possible value, maximum possible value, and expected value
- **FR-015**: Roller MUST accept a parsed expression data structure and execute the roll
- **FR-016**: Roller MUST return a result data structure containing all original request parameters
- **FR-017**: Roller result MUST include the final numeric result (sum or success count as appropriate)
- **FR-018**: Roller result MUST include all individual die values before any modifiers or selections were applied
- **FR-019**: Roller result MUST include flags indicating critical success when applicable
- **FR-020**: Roller result MUST include flags indicating critical failure when applicable
- **FR-021**: For success counting mode, the result MUST be the count of successes rather than a sum
- **FR-022**: For advantage/disadvantage rolls, the result MUST show all dice rolled and which were kept/discarded
- **FR-023**: For reroll mechanics, the result MUST show which dice were rerolled and their original values
- **FR-024**: Parser MUST validate expressions and provide meaningful error messages for invalid input
- **FR-025**: System MUST support all requirements while remaining agnostic to specific game system implementations
- **FR-026**: Parser MUST reject invalid dice expressions with clear error messages identifying the syntax problem and its location
- **FR-027**: Parser MUST enforce dice count minimum of 1 (reject zero or negative dice count)
- **FR-028**: Parser MUST enforce dice sides minimum of 2 (reject dice with fewer than 2 sides to ensure meaningful randomness)
- **FR-029**: Parser MUST enforce maximum of 100 total dice across entire expression (sum of all dice in expression)
- **FR-030**: Parser MUST enforce maximum of 100 sides per individual die
- **FR-031**: Parser MUST detect and reject division by zero in arithmetic expressions
- **FR-032**: Parser MUST validate mathematical function calls have required arguments and reject invalid argument counts
- **FR-033**: Parser MUST validate parentheses are properly matched and reject mismatched expressions with position information
- **FR-034**: Parser MUST reject expressions that specify both advantage AND disadvantage modifiers simultaneously
- **FR-035**: Parser MUST validate critical success thresholds are within valid die range [1, max_sides] and reject out-of-range values
- **FR-036**: Parser MUST validate critical failure thresholds are within valid die range [1, max_sides] and reject out-of-range values
- **FR-037**: All parser error messages MUST identify the specific problem, indicate the location when feasible, and specify what was expected or what limit was exceeded
- **FR-038**: Parser MUST support exploding dice mechanics where dice that meet a threshold condition are rerolled and added to the total
- **FR-038a**: Parser MUST support configurable explosion limits: explicit count (e.g., "explode 3") limits explosions per die; no count specified defaults to hard limit of 100 explosions per die
- **FR-038b**: Parser MUST support range-based explosion thresholds using comparison operators (e.g., "explode >= 5", "explode <= 2"); default with no threshold is maximum die value
- **FR-038c**: Parser MUST validate that explosion threshold does not cover the entire die range and reject expressions where all possible outcomes would trigger explosion (preventing guaranteed non-termination)
- **FR-039**: Roller MUST execute explosion mechanics by rerolling and adding results when explosion condition is met, up to the configured limit per die
- **FR-040**: Roller result MUST include explosion history showing all explosion rolls, their values, and cumulative totals per die
- **FR-041**: For success counting mode with explosions, each explosion roll that meets the success threshold MUST increment the success count

### Key Entities *(include if feature involves data)*

- **DiceExpression**: Represents a parsed dice roll expression containing: the dice specification (number and sides), modifiers, special mechanics (advantage, reroll, success counting), placeholders, comparison operators, critical thresholds
- **DiceSpecification**: Describes the base dice being rolled: number of dice, number of sides per die, type (standard, fudge, percentile)
- **RollModifiers**: Contains arithmetic modifiers, advantage/disadvantage settings, reroll conditions with limits, explosion conditions with limits and thresholds, success counting thresholds, resolved placeholder variable values
- **RollResult**: Contains the complete outcome of a dice roll: original expression/request, final numeric result, array of individual die values, critical success flag, critical failure flag, success/failure flag (for comparison rolls), kept vs discarded dice (for advantage/disadvantage), rerolled dice history with limit tracking, exploded dice history with limit tracking and cumulative values per die
- **StatisticalData**: Provides probability information for an expression: minimum possible value, maximum possible value, expected/average value, distribution data (optional)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Developers can parse any standard dice notation (XdY format) and receive a valid data structure in under 100ms for expressions under 50 characters
- **SC-002**: System correctly handles all ten core dice mechanic types (basic, modifiers, advantage, disadvantage, reroll, explode, success counting, fudge, percentile, placeholders) as verified by comprehensive test suite
- **SC-003**: Parsing errors provide actionable error messages that identify the specific problem location and nature within 5 words or less
- **SC-004**: Statistical calculations (min, max, expected value) are mathematically accurate to 3 decimal places for all supported expression types
- **SC-005**: Documentation includes working code examples for all ten user stories that execute without modification
- **SC-006**: Developers can integrate the library into their project and perform their first dice roll within 10 minutes of reading the quickstart guide
- **SC-007**: Library supports 100% of dice rolling mechanics used in popular RPG systems (D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE)

## Assumptions *(optional)*

### Technical Assumptions

- The library will be consumed as a Composer package following PSR-4 autoloading standards
- All code will be written in PHP 8.0+ with strict type declarations enabled
- PHPUnit will be used for all testing with minimum 90% code coverage
- The library will be stateless - each parse/roll operation is independent
- Randomness will use PHP's built-in random number generation (random_int preferred over mt_rand)

### Usage Assumptions

- Dice expressions will typically be under 100 characters in length
- Most use cases involve single rolls rather than batch rolling
- Developers integrating this library have basic understanding of tabletop RPG dice mechanics
- Critical thresholds are specified at parse time as part of expression syntax (not provided at roll time)
- Success counting thresholds are specified at parse time as part of expression syntax
- Placeholder variable values will be provided at parse time to enable statistical calculations

## Constraints *(optional)*

### Performance Constraints

- Expression parsing must complete in under 100ms for typical expressions (up to 50 characters)
- Rolling must complete in under 50ms for expressions with up to 100 total dice
- Memory usage should not exceed 1MB per roll operation

### Compatibility Constraints

- Must support PHP 8.0 and higher
- Must work on all major PHP platforms (Linux, Windows, macOS)
- Must be compatible with common PHP frameworks (Laravel, Symfony, etc.) without conflicts

### Scope Constraints

- Library focuses on dice expression parsing and rolling only - no game logic, character sheets, or persistence
- No GUI or web interface - this is a pure PHP library
- No network functionality - all operations are local
- Random number generation quality is limited to PHP's built-in capabilities (not cryptographically secure)

## Dependencies *(optional)*

### External Dependencies

- Minimal external dependencies to maintain library simplicity
- May require a parsing library for expression parsing (or implement custom parser)
- All dependencies must be justified and documented in composer.json

### Integration Points

- Library output (RollResult) should be easily serializable to JSON for API/web use
- DiceExpression structure should support persistence if developers want to save roll templates with resolved placeholders
- Placeholder mechanism requires character attribute values at parse time for statistical calculation support

## Out of Scope *(optional)*

The following are explicitly **NOT** part of this feature:

- **Character sheet management** - The library only rolls dice; it doesn't store character data
- **Game rules engines** - The library provides mechanics but doesn't enforce game-specific rules
- **User interface** - No CLI, web UI, or GUI components
- **Persistence/database** - No built-in saving or loading of expressions or results
- **Network features** - No multiplayer, remote rolling, or online functionality
- **Probability visualization** - Statistical data is provided, but graphing/charting is out of scope
- **Natural language parsing** - Expressions must follow defined syntax; no "roll three six-sided dice" text parsing
- **Macro/scripting system** - No complex roll sequences or conditional logic
- **Random number generation alternatives** - No custom RNG algorithms or cryptographic randomness
- **Dice rolling history/logging** - Each roll is independent; no built-in audit trail

## Related Features *(optional)*

### Future Enhancement Opportunities

- **Expression builder API**: Programmatic construction of dice expressions without string parsing
- **Roll history tracking**: Optional component for maintaining roll audit trails
- **Probability distribution calculator**: Detailed probability tables for complex expressions
- **Natural language interface**: "Roll three six-sided dice with advantage" → "3d6 advantage"
- **Custom dice types**: Support for non-standard dice (d3, d7, d30, etc.)
- **Penetrating dice**: Exploding dice variant where each explosion subtracts 1 from the result (diminishing returns)

### Related Documentation

- Composer package best practices guide
- PSR-12 coding standards reference
- PHPUnit testing guidelines
- Game system compatibility matrix (D&D 5e, Pathfinder, etc.)
