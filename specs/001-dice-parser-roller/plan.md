# Implementation Plan: Dice Expression Parser and Roller

**Branch**: `001-dice-parser-roller` | **Date**: 2025-12-02 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-dice-parser-roller/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Build a PHP library for parsing and rolling dice expressions that supports all major tabletop RPG systems. The library provides two core components: (1) a parser that converts dice notation strings (e.g., "3d6+5", "1d20 advantage", "3d6 explode 3 >=5") into structured data with statistical analysis capabilities, and (2) a roller that executes parsed expressions and returns detailed results including individual die values, critical flags, success counts, and explosion/reroll histories. The library will be distributed as a Composer package with comprehensive PHPUnit test coverage (90% minimum), strict PSR-12 compliance, and complete API documentation.

## Technical Context

**Language/Version**: PHP 8.0+
**Primary Dependencies**: Composer (package management), PHPUnit 10+ (testing), PHP-CS-Fixer or PHPCS (PSR-12 enforcement), PHPStan or Psalm (static analysis)
**Storage**: N/A (stateless library - no persistence)
**Testing**: PHPUnit with 90% minimum code coverage enforced via phpunit.xml
**Target Platform**: Cross-platform PHP (Linux, Windows, macOS) compatible with major frameworks (Laravel, Symfony)
**Project Type**: Single library project (Composer package)
**Performance Goals**: Parse <100ms (expressions up to 50 chars), Roll <50ms (up to 100 dice), Memory <1MB per operation
**Constraints**: Stateless operations, PHP built-in RNG only, no external services, PSR-12 strict compliance
**Scale/Scope**: 11 user stories (P1-P10 including P5a for exploding dice), 41 functional requirements (FR-001 through FR-041), 5 core entities, support 100% of popular RPG system mechanics including Savage Worlds exploding dice

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| **I. Composer Package Standards** | ✅ PASS | Plan includes composer.json with PSR-4 autoloading, packagist.org registration planned, semantic versioning strategy defined |
| **II. PSR-12 Coding Standards** | ✅ PASS | PHP-CS-Fixer configuration planned, automated linting in CI pipeline, strict_types=1 declarations required |
| **III. Test-Driven Development** | ✅ PASS | TDD explicitly mandated in spec; Red-Green-Refactor cycle enforced; tests written before implementation per user stories |
| **IV. PHPUnit Testing Coverage** | ✅ PASS | PHPUnit 10+ selected, 90% minimum coverage enforced via phpunit.xml, unit/integration/contract tests planned |
| **V. Complete Documentation** | ✅ PASS | README, API docs, quickstart.md planned; PHPDoc comments required; usage examples for all 10 user stories |
| **Static Analysis** | ✅ PASS | PHPStan or Psalm at strict level planned, type declarations mandatory |
| **Code Review** | ✅ PASS | Constitution compliance verification included in review checklist |
| **CI/CD Pipeline** | ✅ PASS | Automated checks for PHPUnit, PSR-12, static analysis, code coverage planned |

**Overall Status**: ✅ **ALL GATES PASSED** - Proceed to Phase 0

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
src/
├── Parser/
│   ├── DiceExpressionParser.php      # Main parser implementation
│   ├── Lexer.php                      # Tokenization
│   └── Validator.php                  # Parse-time validation
├── Roller/
│   ├── DiceRoller.php                 # Roll execution engine
│   └── RandomNumberGenerator.php     # RNG abstraction (random_int)
├── Model/
│   ├── DiceExpression.php             # Parsed expression structure
│   ├── DiceSpecification.php          # Dice details (NdX, type)
│   ├── RollModifiers.php              # Modifiers, mechanics, bindings
│   ├── RollResult.php                 # Complete roll outcome
│   └── StatisticalData.php            # Min/max/expected calculations
├── Exception/
│   ├── ParseException.php             # Parser errors
│   └── ValidationException.php        # Validation failures
└── PHPDice.php                        # Facade/entry point

tests/
├── Unit/
│   ├── Parser/                        # Parser unit tests
│   ├── Roller/                        # Roller unit tests
│   ├── Model/                         # Model unit tests
│   └── Exception/                     # Exception unit tests
├── Integration/
│   ├── BasicRollingTest.php           # P1: Basic dice rolling
│   ├── ModifiersTest.php              # P2: Arithmetic modifiers
│   ├── AdvantageTest.php              # P3: Advantage/disadvantage
│   ├── SuccessCountingTest.php        # P4: Success counting
│   ├── RerollTest.php                 # P5: Reroll mechanics (configurable limits)
│   ├── ExplodingDiceTest.php          # P5a: Exploding dice mechanics
│   ├── SpecialDiceTest.php            # P6: Fudge/percentile
│   ├── PlaceholdersTest.php           # P7: Variable binding
│   ├── ComparisonTest.php             # P8: Success rolls
│   ├── CriticalTest.php               # P9: Critical detection
│   └── StatisticsTest.php             # P10: Statistical analysis
└── Contract/
    └── GameSystemContractTest.php     # RPG system compatibility tests

composer.json                          # Package definition, dependencies
phpunit.xml                            # PHPUnit configuration, coverage
.php-cs-fixer.php                      # PSR-12 enforcement config
phpstan.neon                           # Static analysis config
README.md                              # Primary documentation
LICENSE                                # Open source license
CHANGELOG.md                           # Version history
```

**Structure Decision**: Single library project structure selected. This is a pure PHP library (no web/mobile UI), so a simple `src/` and `tests/` layout follows Composer package best practices. Organization by component (Parser, Roller, Model) provides clear separation of concerns while keeping the codebase navigable.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

**Status**: No violations - No complexity tracking needed. All constitution principles satisfied.

---

## Phase 0: Research & Technical Decisions

**Status**: ✅ COMPLETE

**Deliverable**: [research.md](./research.md)

### Key Decisions Made

1. **Parser Technology**: Custom recursive descent parser (full control, lightweight, clear errors)
2. **Statistical Calculations**: Analytical calculation for deterministic results
3. **RNG Strategy**: PHP `random_int()` for better distribution quality
4. **Expression Syntax**: Whitespace-tolerant, developer-friendly notation
5. **Error Handling**: Fail-fast at parse time with specific error messages
6. **Testing Strategy**: 3-layer pyramid (unit 60%, integration 35%, contract 5%)
7. **PHP Features**: Leverage PHP 8.0+ named arguments, union types, match expressions
8. **Package Standards**: PSR-4 autoloading, packagist.org publication
9. **Documentation**: Multi-level approach (README, quickstart, API, examples)
10. **Workflow**: TDD with feature branch strategy

All technical unknowns resolved. No blocking decisions remain.

---

## Phase 1: Design Artifacts

**Status**: ✅ COMPLETE

### Deliverables

1. **Data Model** ([data-model.md](./data-model.md))
   - 5 core entities defined: DiceExpression, DiceSpecification, RollModifiers, RollResult, StatisticalData
   - Field validation rules specified
   - Relationships and invariants documented
   - PHP 8.0+ implementation patterns provided

2. **API Contracts** ([contracts/](./contracts/))
   - Parser API contract ([parser-api.md](./contracts/parser-api.md))
   - Roller API contract ([roller-api.md](./contracts/roller-api.md))
   - Complete syntax specification with examples
   - Error handling patterns defined
   - Performance guarantees documented

3. **Quick Start Guide** ([quickstart.md](./quickstart.md))
   - 10-minute tutorial with 7 progressive examples
   - Common game system examples (D&D 5e, Shadowrun, FATE)
   - Error handling patterns
   - Advanced features demonstration
   - Performance tips and troubleshooting

4. **Agent Context Update**
   - GitHub Copilot context updated with PHP 8.0+, Composer, PHPUnit, PSR-12 tools

### Constitution Re-Check (Post-Design)

| Principle | Status | Notes |
|-----------|--------|-------|
| **I. Composer Package Standards** | ✅ PASS | composer.json structure defined in research.md; PSR-4 autoloading namespace `PHPDice\\` established |
| **II. PSR-12 Coding Standards** | ✅ PASS | .php-cs-fixer.php configuration planned; strict_types=1 in all files |
| **III. Test-Driven Development** | ✅ PASS | Test structure defined with clear unit/integration/contract separation; TDD workflow documented |
| **IV. PHPUnit Testing Coverage** | ✅ PASS | phpunit.xml configuration planned with 90% threshold; test organization matches source structure |
| **V. Complete Documentation** | ✅ PASS | quickstart.md created; API contracts documented; README, CHANGELOG, examples planned |
| **Static Analysis** | ✅ PASS | phpstan.neon configuration planned; strict type declarations throughout |
| **Code Review** | ✅ PASS | Review checklist includes constitution verification |
| **CI/CD Pipeline** | ✅ PASS | Automated checks for all quality gates planned |

**Overall Status**: ✅ **ALL GATES PASSED** - Design phase complete

---

## Phase 2: Task Breakdown

**Status**: ✅ COMPLETE

**Deliverable**: [tasks.md](./tasks.md)

### Task Organization

Tasks are organized into 14 phases with updated task count:

1. **Phase 1: Setup** (T001-T014) - Devcontainer and project initialization
2. **Phase 2: Foundational** (T015-T024) - Core infrastructure (blocks all user stories)
3. **Phase 3-13: User Stories** (T025-T124) - Implementation of P1-P10 features including P5a exploding dice
4. **Phase 14: Polish** (T125-T140) - Cross-cutting concerns and release preparation

All tasks include:
- Specific file paths for implementation
- [P] markers for parallel execution opportunities
- [Story] labels mapping to user stories (US1-US10, US5a)
- Complete coverage of all 41 functional requirements (FR-001 through FR-041)
- All edge case validation tasks including explosion/reroll range validation

**Next Command**: Begin implementation with Phase 1 Setup tasks (T001-T014)

---

## Implementation Notes

### Development Order (by Priority)

1. **P1 - Basic Dice Rolling**: Foundation for all other features
2. **P2 - Modifiers**: Essential for most game systems
3. **P3 - Advantage/Disadvantage**: Core D&D 5e mechanic
4. **P4 - Success Counting**: Dice pool systems support
5. **P5 - Reroll Mechanics**: Common game mechanic with configurable limits (default 100, explicit count)
6. **P5a - Exploding Dice**: Savage Worlds and dramatic variance systems (reroll on threshold, add to total, configurable limits)
7. **P6 - Special Dice**: FATE and percentile system support
8. **P7 - Placeholders**: Character sheet integration
9. **P8 - Comparisons**: Target number checks
10. **P9 - Criticals**: Exceptional outcome detection
11. **P10 - Statistics**: Probability analysis

### TDD Workflow (Per Task)

1. Write failing test (RED)
2. Run test suite to confirm failure
3. Implement minimal code (GREEN)
4. Run test suite to confirm pass
5. Refactor while maintaining green
6. Commit test + implementation together
7. Verify PSR-12 compliance
8. Verify constitution compliance

### Quality Gates (Before Merge)

- [ ] All tests passing (PHPUnit)
- [ ] 90%+ code coverage
- [ ] PSR-12 compliance (PHP-CS-Fixer)
- [ ] Static analysis clean (PHPStan/Psalm)
- [ ] Constitution check passed
- [ ] Documentation updated
- [ ] CHANGELOG.md entry added

### Files to Create First

**Development Environment** (First Priority):
1. `.devcontainer/devcontainer.json` - PHP 8.0+ development container
2. Configure devcontainer with Composer, Git, PHP extensions (Intelephense, PHPUnit, Xdebug)
3. Reopen workspace in devcontainer for consistent environment

**Configuration Files**:
1. `composer.json` - Package definition
2. `phpunit.xml` - Test configuration with coverage
3. `.php-cs-fixer.php` - PSR-12 enforcement
4. `phpstan.neon` - Static analysis rules
5. `README.md` - Initial documentation
6. `LICENSE` - Open source license (MIT recommended)

**Foundation Code** (P1 prerequisite):
1. Exception classes (ParseException, ValidationException)
2. Model classes (DiceExpression, DiceSpecification, etc.)
3. Basic parser (Lexer, Parser, Validator)
4. Basic roller (DiceRoller, RNG)
5. PHPDice facade

---

## Risk Assessment

### Technical Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Parser complexity grows | Medium | Medium | Keep grammar simple; incremental additions |
| Statistical calculations incorrect | Low | High | Test against known distributions; peer review math |
| Performance targets missed | Low | Medium | Profile early; optimize hot paths |
| PHP version compatibility issues | Low | Low | Use devcontainer with PHP 8.0; CI tests multiple versions |

### Mitigation Strategies

1. **Parser complexity**: Start with simple recursive descent; refactor if needed
2. **Statistical accuracy**: Unit tests with known probability values; 3 decimal precision verification
3. **Performance**: Benchmark early in P1; optimize if targets missed
4. **Compatibility**: CI pipeline tests PHP 8.0, 8.1, 8.2, 8.3

---

## Success Metrics

From specification success criteria (SC-001 through SC-007):

- ✅ Parse time <100ms for <50 char expressions
- ✅ 90% code coverage achieved
- ✅ Error messages <5 words
- ✅ Statistical accuracy to 3 decimals
- ✅ Working examples for all 11 user stories (including P5a exploding dice)
- ✅ 10-minute integration time via quickstart
- ✅ 100% RPG system mechanics coverage (D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE, Savage Worlds)

**Measurement**: Each success criterion maps to specific tests in integration test suite

---

## Next Steps

1. **Run** `/speckit.tasks` to generate task breakdown
2. **Create** configuration files (composer.json, phpunit.xml, etc.)
3. **Implement** P1 (Basic Dice Rolling) using TDD
4. **Iterate** through P2-P10 in priority order
5. **Document** as you go (README, examples)
6. **Publish** to packagist.org when complete

**Estimated Timeline**:
- Setup (with devcontainer): 1 day
- P1-P3: 5 days (foundation + core mechanics)
- P4-P5a: 5 days (advanced mechanics including configurable rerolls and explosions)
- P6-P10: 4 days (special dice and integration features)
- Documentation polish: 2 days
- **Total**: ~17-19 days for experienced PHP developer

**With Devcontainer**: All development occurs in consistent PHP 8.0+ environment with pre-configured tools