# Research: Dice Expression Parser and Roller

**Feature**: 001-dice-parser-roller | **Date**: 2025-12-02
**Purpose**: Resolve technical decisions and establish patterns for implementation

## Parser Technology Selection

### Decision: Custom Recursive Descent Parser

**Rationale**: 
- Dice expression grammar is relatively simple and context-free
- Custom parser provides full control over error messages (critical for FR-024)
- Avoids heavyweight parser generator dependencies
- Better performance for small expressions (<100 chars typical)
- Educational value for maintainers

**Alternatives Considered**:
- **Parser generators (ANTLR, Hoa\Compiler)**: Rejected due to complexity overhead for simple grammar and additional dependency weight
- **Regular expressions**: Rejected as insufficient for nested expressions and complex operator precedence
- **Parser combinators**: Rejected as less idiomatic in PHP ecosystem

**Implementation Pattern**:
```
Lexer (tokenization) → Parser (AST construction) → Validator (semantic checks)
```

## Statistical Calculation Approach

### Decision: Analytical Calculation for Standard Distributions

**Rationale**:
- Basic dice (XdY) have well-known probability distributions
- Min = X × 1, Max = X × Y, Expected = X × (Y+1)/2
- Advantage/disadvantage calculations are mathematically tractable
- Success counting can use binomial distribution
- Deterministic results are reproducible and testable

**Alternatives Considered**:
- **Monte Carlo simulation**: Rejected as slower, non-deterministic, and unnecessary for known distributions
- **Pre-computed lookup tables**: Rejected as memory-intensive and limited to specific combinations

**Special Cases**:
- Fudge dice: Each dF has expected value 0, variance 2/3
- Rerolls: Adjust probabilities based on single-reroll constraint
- Placeholders: Require concrete values at parse time (resolved per spec clarification)

## Random Number Generation

### Decision: PHP random_int() with mt_rand() Fallback Detection

**Rationale**:
- `random_int()` is cryptographically secure (PHP 7.0+)
- Better distribution quality than `mt_rand()`
- Performance difference negligible for typical dice rolling (<100 dice)
- Available in PHP 8.0+ (our minimum version)

**Alternatives Considered**:
- **mt_rand()**: Rejected as primary due to weaker distribution quality
- **External RNG libraries**: Rejected as unnecessary complexity
- **Hardware RNG**: Out of scope per spec constraints

**Best Practice**: Use `random_int()` exclusively; no need for fallback at PHP 8.0+

## Expression Syntax Design

### Decision: Flexible Whitespace-Tolerant Syntax

**Rationale**:
- Users expect natural notation: "3d6 + 5" or "3d6+5" should both work
- Whitespace tolerance improves developer experience
- Lexer can normalize tokens, parser works with clean input

**Syntax Examples**:
```
Basic:          3d6, 1d20, 2d10
Simple Math:    1d20+5, 2d6-2, 3d8+%str%
Arithmetic:     (2d6+3)*2, 1d20*2+5, 3d6/2
Functions:      floor(1d20/2), ceil(3d6/2), round(1d20*1.5)
Grouping:       (1d8+%str%)*(1+%crit_multiplier%)
Advantage:      1d20 advantage, 4d6 keep 3 highest
Reroll:         4d6 reroll <=2, 6d6 reroll 1
Success:        5d6 >=4, 10d10 threshold 7
Critical:       1d20 crit 20, 1d20 glitch 1
Fudge:          4dF
Percentile:     d%, 1d100
Comparison:     1d20+5 >= 15
Placeholders:   1d20+%str%+%dex%, 2d6+%damage_bonus%
```

**Reserved Keywords**: `d, dF, d%, advantage, disadvantage, keep, highest, lowest, reroll, threshold, crit, glitch, floor, ceil, round`

**Placeholder Syntax**: `%name%` to avoid collisions with reserved keywords and operators

**Arithmetic Operators**: `+, -, *, /` with standard precedence (* and / before + and -)

**Grouping**: Parentheses `()` for explicit precedence control

**Mathematical Functions**: `floor()`, `ceil()`, `round()` for rounding operations

## Error Handling Philosophy

### Decision: Fail-Fast with Detailed Parse-Time Validation

**Rationale**:
- Catch errors at parse time, not roll time (aligns with spec clarifications)
- Provide specific error messages listing problems (FR-024, SC-003)
- Enable developers to fix issues immediately
- Statistical calculations require fully valid expressions

**Error Categories**:
1. **Syntax Errors**: Invalid notation (e.g., "3d", "abc", unmatched parentheses)
2. **Validation Errors**: Structurally valid but semantically invalid (e.g., keep 5 from 3 dice, division by zero)
3. **Binding Errors**: Missing placeholder values at parse time
4. **Constraint Errors**: Values outside valid ranges (e.g., 0d6, 3d-5)
5. **Function Errors**: Invalid function calls (e.g., "floor()" with no argument, unknown function)

**Error Message Format** (SC-003: <5 words):
- ❌ BAD: "An error occurred while parsing the dice expression"
- ✅ GOOD: "Invalid dice notation: '3d'"
- ✅ GOOD: "Keep count exceeds rolls"
- ✅ GOOD: "Missing variable: %str%"

## PHPUnit Testing Strategy

### Decision: Three-Layer Test Pyramid

**Rationale**:
- Unit tests for isolated components (Parser, Roller, Models)
- Integration tests for each user story (P1-P10)
- Contract tests for game system compatibility
- Supports TDD Red-Green-Refactor workflow

**Test Organization**:
```
Unit Tests (60% of suite):
- Parser components (Lexer, Validator)
- Roller components (RNG, execution)
- Model classes (DiceExpression, RollResult, etc.)
- Exception handling

Integration Tests (35% of suite):
- One test class per user story
- End-to-end parse → roll workflows
- Statistical calculation verification
- Edge case coverage

Contract Tests (5% of suite):
- D&D 5e mechanics verification
- Pathfinder compatibility
- Shadowrun/World of Darkness dice pools
- FATE fudge dice
```

**Coverage Target**: 90% minimum enforced via phpunit.xml

## PHP 8.0+ Feature Utilization

### Decision: Leverage Modern PHP Features

**Rationale**:
- PHP 8.0 minimum version enables modern syntax (per spec clarification)
- Named arguments improve API clarity
- Union types strengthen type safety
- Match expressions simplify conditional logic

**Key Features to Use**:
- **Named Arguments**: `parse(expression: "3d6", variables: ["str" => 3])`
- **Union Types**: `int|float` for statistical calculations
- **Match Expressions**: Token type handling in parser
- **Constructor Property Promotion**: Reduce boilerplate in models
- **Nullsafe Operator**: Optional statistical data access
- **Attributes**: Potential use for metadata/annotations

**Example**:
```php
readonly class DiceExpression {
    public function __construct(
        public DiceSpecification $dice,
        public RollModifiers $modifiers,
        public ?StatisticalData $statistics = null,
    ) {}
}
```

## Composer Package Best Practices

### Decision: Follow PHP-FIG and Packagist Standards

**Rationale**:
- Ensures professional package quality
- Improves discoverability on packagist.org
- Facilitates integration with PHP frameworks

**composer.json Requirements**:
```json
{
  "name": "marcowuelser/phpdice",
  "description": "Dice expression parser and roller for tabletop RPG systems",
  "type": "library",
  "license": "MIT",
  "keywords": ["dice", "rpg", "parser", "roller", "gaming"],
  "authors": [{"name": "Marco Wuelser"}],
  "require": {
    "php": "^8.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0",
    "friendsofphp/php-cs-fixer": "^3.0",
    "phpstan/phpstan": "^1.10"
  },
  "autoload": {
    "psr-4": {"PHPDice\\": "src/"}
  },
  "autoload-dev": {
    "psr-4": {"PHPDice\\Tests\\": "tests/"}
  }
}
```

## Documentation Structure

### Decision: Multi-Level Documentation Approach

**Rationale**:
- Different audiences need different detail levels
- Quick start for rapid onboarding
- API reference for comprehensive usage
- Examples for learning by doing

**Documentation Deliverables**:
1. **README.md**: Overview, installation, quick start, links
2. **quickstart.md**: 10-minute tutorial with working examples
3. **API.md**: Complete API reference with PHPDoc
4. **EXAMPLES.md**: Real-world game system examples (D&D 5e, Shadowrun, etc.)
5. **CHANGELOG.md**: Version history

## Development Workflow

### Decision: TDD with Feature Branch Strategy

**Rationale**:
- TDD enforced per constitution
- Feature branch already created (001-dice-parser-roller)
- Incremental implementation by priority (P1 → P10)

**Workflow**:
1. Write failing test for requirement
2. Run test suite (RED)
3. Implement minimal code to pass
4. Run test suite (GREEN)
5. Refactor while maintaining green
6. Commit with test + implementation together
7. Continue until user story complete
8. Review constitution compliance
9. Merge to master

**Branch Strategy**: One feature branch per major feature set (current: 001-dice-parser-roller)

## Key Decisions Summary

| Decision Area | Choice | Key Benefit |
|--------------|--------|-------------|
| Parser Technology | Custom Recursive Descent | Full control, lightweight, clear errors |
| Statistics | Analytical Calculation | Deterministic, fast, testable |
| RNG | random_int() | Better distribution, PHP 8.0+ standard |
| Syntax | Whitespace-tolerant, %var% placeholders, full arithmetic | Developer-friendly, no keyword collisions, powerful expressions |
| Error Handling | Fail-fast at parse time | Early detection, statistical requirements |
| Testing | 3-layer pyramid | 90% coverage, TDD-friendly |
| PHP Features | PHP 8.0+ modern syntax | Type safety, readability |
| Package | PSR-4, packagist.org | Professional, discoverable |
| Docs | Multi-level | All audiences served |
| Workflow | TDD + feature branches | Constitution compliance |
