# Changelog

All notable changes to PHPDice will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Production-ready release
- Performance optimizations for large dice pools
- Extended test coverage to 90%+

## [0.4.0] - 2026-01-21

### Added
- **Expression Evaluation**: New `eval()` method for evaluating dice expressions with placeholder substitution and conditional resolution. This enables character sheet integration and dynamic rule systems. Examples: `eval('if $a$ == 1 : 1d20 | 1d12 + $b$', ['a' => 2, 'b' => 1])` returns `"1d12+1"`, and `eval('1d20+$bonus$', ['bonus' => 5])` returns `"1d20+5"` (#79, #80)
  - Supports partial evaluation where missing placeholders are preserved: `eval('1d20 + $b$', ['a' => 1], true)` returns `"1d20+$b$"`
  - Automatically resolves conditional expressions that can be evaluated
  - Validates expressions and throws on invalid input or missing placeholders
- **Switch Case Expressions**: New `switch` statement for multi-way branching based on dice roll or placeholder value. Supports ranges, individual values, and default cases. Example: `switch 1d6 case 1: 42 | case 2-5: 23 | case 6: 0` (#63, #64)
  - Supports negative values and ranges in cases
  - Can be used in arithmetic expressions
  - Supports nested dice in case result expressions
- **Coin Flip Dice**: New `C` notation for coin flips that return 0 (tails) or 1 (heads). Example: `5C` flips 5 coins, `1C + 3` for initiative with modifier (#59)
  - Works with all standard modifiers (success counting, comparison, etc.)
  - Includes proper statistical analysis (minimum, maximum, expected value)
- **Is Null Check**: New `is null` operator for checking if a placeholder variable is missing or null. Enables optional parameters and flexible conditional logic. Example: `if $bonus$ is null : 1d20 | 1d20+$bonus$` (#82)
  - Supports lazy evaluation - only the relevant branch is evaluated
  - Works with complex nested conditionals
- **Boolean Algebra**: Enhanced conditional expressions with comprehensive comparison operator support and improved test coverage (#47, #61)
  - All comparison operators (`<`, `<=`, `>`, `>=`, `==`, `!=`) now work consistently
  - Improved documentation with Boolean Algebra section
  - Better error messages and validation

### Changed
- **BREAKING**: Namespace changed from `PHPDice` to `Codryn\PHPDice` across entire package. Update all imports: `use Codryn\PHPDice\PHPDice;` (#60)
- Improved statistics calculation for groups and conditionals to handle complex expressions (#75)
- Enhanced example runner script to work from any directory and handle all examples dynamically (#77)

### Fixed
- Added explicit type hints to closure in `expandPlaceholdersInComment()` to satisfy PHPStan strict type checking on PHP 8.5 (#84)
- Fixed statistics calculation for math-only expressions with constant operands (#74)
- Fixed integer validation for negative floats with proper error handling (#84)
- PHPStan analysis now passes at level 10 strict on all PHP versions including 8.5

## [0.3.0] - 2026-01-11

### Added
- **Roll Comments**: Users can now add descriptive comments to dice rolls using the `#` character. Comments support placeholder expansion and are available in both `DiceExpression` and `RollResult` objects. Examples: `1d20 + 5 # Attack roll`, `1d20 + $str$ # Strength check (+$str$)` (#46)
- **Auto Success Mechanic**: New `auto N` keyword for automatic success/failure regardless of DC. This enables D&D/Pathfinder behavior where natural 20 is always a success. Example: `1d20 auto 20 crit 19 glitch 1 + 1 dc >= 25` (#39)
- **Roll Groups**: Support for groups in a roll using `{}`. Each group is evaluated separately with its own result and the overall roll result is returned normally. Example: `{ 2d6+3 } + {1d4}` for D&D damage with physical and fire in seperate groups (#50)
- **Roll Tags**: Ability to tag dice expressions with custom labels using `[tag]` syntax for categorization and filtering. Example: `1d20+5 [attack]; 2d6 [damage]` (#54)
- **Count Even/Odd**: New `count even` and `count odd` modifiers to count dice showing even or odd numbers. Useful for specialized game mechanics. Example: `6d6 count even` (#57)
- **Dice in Function Arguments**: Dice expressions can now be used inside mathematical function arguments. Example: `max(1d6, 2d4)`, `min(3d6, 10)` (#43)
- **Shadowrun Edge Mechanic**: New `edge` keyword for Shadowrun-style edge mechanics where 6s are rerolled and added to the pool. Example: `6d6 edge >=5` (#41)

### Changed
- **BREAKING**: Natural max roll (e.g., 20 on d20) no longer automatically succeeds without the `auto` keyword. This makes the behavior explicit and allows for rolls without automatic success mechanics. To maintain D&D 5e behavior, use `1d20 auto 20 crit 20` instead of just `1d20 crit 20`
- Improved CI/CD pipeline configuration and alignment with other package projects (#57)

### Fixed
- PHPStan null reference errors in even/odd counting logic
- Tag parsing order to handle all special characters correctly
- Group evaluation consistency across multiple code paths
- Sporadic test failures in edge feature implementation

## [0.2.1] - 2026-01-07

### Added
- **Math only Mode**: Introduced a "math only" mode to evaluate expressions without rolling dice (e.g., `5 + 3 * 2`) (#10)
- **Variable Argument Functions**: `min()` and `max()` now support multiple arguments (e.g., `min(1, 2, 3, 4)`) (#6)
- **Decimal Number Support**: Dice expressions can now include decimal numbers as modifiers (e.g., `3d6+2.5`, `1d20*1.5`) (#21)
- **Exponentiation Operator**: Added support for the exponentiation operator `^` in expressions (e.g., `2d6^2` raises the result of `2d6` to the power of 2)
- **Modulo Operator**: Added support for the modulo operator `%` in expressions (e.g., `5d10 % 3` computes the remainder of the roll divided by 3) (#30)

### Fixed
- **Allow dot in placeholder names**: Placeholders can now include dots in their names for hierarchical variable access (e.g., `$ability.str.bonus$`) (#31)
- **Comparison Operators**: All comparison operators (`<`, `<=`, `==`, `>`, `>=`) now work correctly with success counting (#20)
- **Duplicate Keywords**: Removed duplicate keyword entries that could cause parsing confusion (#27)
- **Operator Consistency**: Fixed loose vs strict comparison for equality operator to ensure consistent behavior
- **Modulo Operator**: use correct operator symbol `%` for modulo operations instead of incorrect `~` (#30)
- **Keyword order**: Adjusted keyword order in lexer to prevent misinterpretation of keywords (#21)

### Changed
- Improved code formatting to maintain PSR-12 compliance across all changes

## [0.2.0] - 2025-01-06

### Added

#### Core Features
- **Basic Dice Parsing** (US1): Parse standard XdY notation (e.g., `3d6`, `1d20+5`)
- **Arithmetic Operations** (US1): Full expression support with +, -, *, /, parentheses
- **Mathematical Functions** (US1): `floor()`, `ceil()`, `round()`, `abs()`, `min()`, `max()`
- **Dice Rolling** (US2): Execute parsed expressions with cryptographically secure random number generation
- **Individual Dice Values** (US2): Track each die result in roll history
- **Statistical Analysis** (US3): Calculate minimum, maximum, and expected values without rolling

#### Advanced Mechanics
- **Advantage/Disadvantage** (US4): Roll multiple d20s, automatically keep highest/lowest
- **Keep/Drop Mechanics** (US4a): Keep N highest/lowest dice from any pool (e.g., `4d6 keep 3 highest`)
- **Placeholder Variables** (US5): Dynamic values with `$variable$` syntax
- **Success Counting** (US5a): Count dice meeting thresholds (e.g., `5d6 >=4` for Shadowrun)
- **Reroll Mechanics** (US6): Reroll dice meeting conditions (e.g., `2d6 reroll <=2` for D&D Great Weapon Fighting)
  - Configurable reroll limits (default: 100, prevents infinite loops)
  - Tracks original values in reroll history
- **Exploding Dice** (US6a): Dice that roll again on max value (e.g., `3d6 explode` for Savage Worlds)
  - Configurable explosion limits (default: 100, prevents infinite loops)
  - Custom explosion conditions (e.g., `1d6 explode >=5`)
  - Complete explosion chain history
- **Special Dice Types** (US7): FATE/Fudge dice support with `dF` notation (-1, 0, +1 results)
- **Comparison Operators** (US8): Success/failure checks with `>=`, `>`, `<=`, `<`, `==`, `!=`
- **Critical Success/Failure** (US9): Detect natural 20s/1s or custom critical ranges
- **Statistical Analysis** (US10): Comprehensive probability calculations including:
  - Standard deviation and variance
  - Probability distributions
  - Success probabilities for comparisons
  - Expected value for complex expressions

#### Developer Experience
- **PHPDice Facade**: Simple, intuitive API - `$dice->roll("3d6+5")`
- **Immutable Data Models**: Thread-safe, predictable behavior
- **Rich Result Objects**: Complete roll information with history tracking
- **Type Safety**: Full PHP 8.1+ type declarations with strict mode
- **Comprehensive Error Handling**: Clear, actionable error messages
- **Zero Dependencies**: No external packages required (except dev dependencies)

#### Code Quality
- **360 Passing Tests**: 100% test coverage for critical paths (82% overall)
- **1510 Test Assertions**: Comprehensive validation of all features
- **PHPStan Level 10 Strict**: Maximum static analysis strictness
- **PSR-12 Compliant**: Industry-standard code formatting
- **Strict Types**: All 38 source files use `declare(strict_types=1)`

#### Documentation
- **Comprehensive API Documentation**: 500+ line reference with examples
- **Detailed README**: Installation, quick start, game system compatibility
- **Quick Start Guide**: Get running in 10 minutes
- **Game System Examples**: D&D 5e, Shadowrun 5e, Savage Worlds, FATE Core, Call of Cthulhu 7e
- **Error Handling Guide**: Common issues and solutions

### Game System Support

This release officially supports mechanics from:
- **D&D 5e**: Advantage/disadvantage, ability checks, critical hits, stat generation
- **Pathfinder**: Skill checks, damage rolls, critical confirmations
- **Shadowrun 5e**: Dice pools, success counting, edge mechanics, glitch detection
- **World of Darkness**: Dice pools, success thresholds, botch detection
- **FATE Core**: Fudge dice (dF), skill ladder, opposed rolls
- **Savage Worlds**: Exploding dice (Aces), wild die, raise mechanics
- **Call of Cthulhu**: Percentile rolls, success levels, bonus/penalty dice
- **Generic Systems**: Flexible notation for custom mechanics

### Technical Details

- **Minimum PHP Version**: 8.1
- **Architecture**: Clean separation of parsing, rolling, and statistics
- **Performance**: Optimized for repeated rolls with cached parsing
- **Security**: Cryptographically secure RNG, input validation, infinite loop protection

### Breaking Changes

None - this is the initial stable release.

### Known Limitations

- Reroll and explosion limits default to 100 (configurable, prevents infinite loops)
- Statistical analysis for complex exploding dice uses approximations
- Maximum recommended dice pool size: 10,000 dice (memory constraints)

### Migration Guide

N/A - Initial release

---

## [0.1.0-dev] - 2025-12-02

### Added
- Project initialization
- Composer package configuration
- Development tooling setup
- PSR-12 coding standards enforcement
- PHPUnit testing framework with 90% coverage threshold
- PHPStan 2.1 static analysis at level 10 strict

---

## Version History

- **0.4.0** (2026-01-21): Expression evaluation, switch case, coin flips, is null checks, namespace change
- **0.3.0** (2026-01-11): Roll comments, auto success, roll groups, tags, even/odd counting, edge mechanics
- **0.2.1** (2026-01-07): Math mode, decimal numbers, exponentiation, modulo, comparison fixes
- **0.2.0** (2024-01-06): Initial test release with initial game system support
- **0.1.0-dev** (2025-12-02): Development version

## Links

- [GitHub Repository](https://github.com/codryn/phpdice)
- [Issue Tracker](https://github.com/codryn/phpdice/issues)
- [API Documentation](docs/api.md)
- [Quick Start Guide](docs/quickstart.md)

---

[unreleased]: https://github.com/codryn/phpdice/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/codryn/phpdice/releases/tag/v0.4.0
[0.3.0]: https://github.com/codryn/phpdice/releases/tag/v0.3.0
[0.2.1]: https://github.com/codryn/phpdice/releases/tag/v0.2.1
[0.2.0]: https://github.com/codryn/phpdice/releases/tag/v0.2.0
[0.1.0-dev]: https://github.com/codryn/phpdice/releases/tag/v0.1.0-dev
