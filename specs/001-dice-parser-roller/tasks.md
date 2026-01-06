# Tasks: Dice Expression Parser and Roller

**Input**: Design documents from `/specs/001-dice-parser-roller/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/

**Tests**: Tests are NOT explicitly requested in the spec - tasks focus on implementation only. The spec mandates TDD approach per Constitution Check, but test creation will be part of implementation workflow (Red-Green-Refactor).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `- [ ] [ID] [P?] [Story?] Description`

- **Checkbox**: ALWAYS `- [ ]` (markdown checkbox)
- **[ID]**: Task ID (T001, T002, T003...)
- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, etc.) - only for user story phases
- Include exact file paths in descriptions

## Path Conventions

Single library project structure: `src/`, `tests/` at repository root per plan.md

---

## Phase 1: Setup (Project Initialization)

**Purpose**: Create project structure, configure development environment, and set up all development tools

### Devcontainer Setup (First Priority)

- [X] T001 Create .devcontainer/devcontainer.json for PHP 8.0+ development environment
- [X] T002 Configure devcontainer with Composer, Git, and development extensions (PHP Intelephense, PHPUnit, etc.)
- [X] T003 Add devcontainer features: PHP 8.0+, Composer, Xdebug for debugging and coverage
- [X] T004 Reopen workspace in devcontainer to ensure consistent development environment

### Project Structure Setup

- [X] T005 Create directory structure: src/, tests/, docs/ per plan.md
- [X] T006 Create composer.json with package metadata, PSR-4 autoloading for PHPDice namespace, require PHP 8.0+
- [X] T007 [P] Create phpunit.xml with 90% coverage threshold and test suite configuration
- [X] T008 [P] Create .php-cs-fixer.php for PSR-12 enforcement with strict_types requirement
- [X] T009 [P] Create phpstan.neon for static analysis at strict level
- [X] T010 [P] Create README.md with installation instructions and basic usage example
- [X] T011 [P] Create LICENSE file (MIT recommended per plan.md)
- [X] T012 [P] Create CHANGELOG.md with initial version 0.1.0-dev
- [X] T013 [P] Create .gitignore for vendor/, .phpunit.cache/, coverage reports
- [X] T014 Run composer install to verify package configuration in devcontainer

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T015 [P] Create ParseException class in src/Exception/ParseException.php
- [X] T016 [P] Create ValidationException class in src/Exception/ValidationException.php
- [X] T017 [P] Create DiceType enum in src/Model/DiceType.php with STANDARD, FUDGE, PERCENTILE cases
- [X] T018 [P] Create DiceSpecification entity in src/Model/DiceSpecification.php with count, sides, type fields
- [X] T019 [P] Create RollModifiers entity in src/Model/RollModifiers.php with all modifier fields per data-model.md
- [X] T020 [P] Create StatisticalData entity in src/Model/StatisticalData.php with min, max, expected fields
- [X] T021 Create DiceExpression entity in src/Model/DiceExpression.php with specification, modifiers, statistics
- [X] T022 Create RollResult entity in src/Model/RollResult.php with expression, total, diceValues, flags per data-model.md
- [X] T023 [P] Create RandomNumberGenerator abstraction in src/Roller/RandomNumberGenerator.php using random_int()
- [X] T024 Create base test case classes in tests/Unit/BaseTestCase.php and tests/Integration/BaseTestCase.php

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Basic Dice Rolling (Priority: P1) 🎯 MVP

**Goal**: Parse and roll basic dice expressions (XdY format) with numeric results

**Independent Test**: Parse "3d6", "1d20", "2d10" and roll to get sums between valid ranges

### Implementation for User Story 1

- [X] T025 [P] [US1] Create Lexer class in src/Parser/Lexer.php to tokenize basic XdY notation
- [X] T026 [P] [US1] Create Token class in src/Parser/Token.php with type and value properties
- [X] T027 [US1] Implement basic parser in src/Parser/DiceExpressionParser.php for XdY pattern parsing
- [X] T028 [US1] Add validation for dice count >= 1 (FR-027) in src/Parser/Validator.php
- [X] T029 [US1] Add validation for sides >= 2 (FR-028 updated) in src/Parser/Validator.php
- [X] T030 [US1] Add validation for max 100 dice total (FR-029) in src/Parser/Validator.php
- [X] T031 [US1] Add validation for max 100 sides per die (FR-030) in src/Parser/Validator.php
- [X] T032 [US1] Add validation to reject invalid syntax like "d6", "3d", "abc", "3d1" single-sided die (FR-026) in src/Parser/Validator.php
- [X] T033 [US1] Implement StatisticalCalculator in src/Model/StatisticalCalculator.php for basic dice statistics
- [X] T034 [US1] Implement basic DiceRoller in src/Roller/DiceRoller.php to roll standard dice and return RollResult
- [X] T035 [US1] Create PHPDice facade in src/PHPDice.php with parse() and roll() methods
- [X] T036 [US1] Write integration test in tests/Integration/BasicRollingTest.php covering all US1 acceptance scenarios
- [X] T037 [US1] Write unit tests in tests/Unit/Parser/LexerTest.php for tokenization
- [X] T038 [US1] Write unit tests in tests/Unit/Parser/ValidatorTest.php for all FR-026 through FR-030 validations
- [X] T039 [US1] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for basic rolling logic

**Checkpoint**: At this point, basic dice rolling (MVP) should be fully functional and testable independently

---

## Phase 4: User Story 2 - Modifiers and Arithmetic (Priority: P2)

**Goal**: Support arithmetic expressions with +, -, *, /, parentheses, and math functions

**Independent Test**: Parse "1d20+5", "(2d6+3)*2", "floor(1d20/2)" and verify arithmetic evaluation

### Implementation for User Story 2

- [X] T040 [P] [US2] Extend Lexer in src/Parser/Lexer.php to recognize +, -, *, /, (, ), function names
- [X] T041 [US2] Create ArithmeticNode classes in src/Parser/AST/ for expression tree representation
- [X] T042 [US2] Implement arithmetic expression parser using recursive descent in src/Parser/DiceExpressionParser.php
- [X] T043 [US2] Add operator precedence handling (*, / before +, -) in parser
- [X] T044 [US2] Add parenthesis matching validation (FR-033) in src/Parser/Validator.php
- [X] T045 [US2] Add division by zero detection (FR-031) in src/Parser/Validator.php
- [X] T046 [US2] Implement mathematical functions: floor(), ceil(), round() in src/Parser/Functions.php
- [X] T047 [US2] Add function argument validation (FR-032) - must have exactly 1 argument in src/Parser/Validator.php
- [X] T048 [US2] Update StatisticalCalculator to handle arithmetic expressions in src/Model/StatisticalCalculator.php
- [X] T049 [US2] Update DiceRoller to evaluate arithmetic expressions in src/Roller/DiceRoller.php
- [X] T050 [US2] Write integration test in tests/Integration/ModifiersTest.php covering all US2 acceptance scenarios
- [X] T051 [US2] Write unit tests in tests/Unit/Parser/ArithmeticParserTest.php for expression parsing
- [X] T052 [US2] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-031, FR-032, FR-033

**Checkpoint**: At this point, basic rolling AND arithmetic modifiers should both work independently

---

## Phase 5: User Story 3 - Advantage and Disadvantage (Priority: P3)

**Goal**: Support advantage/disadvantage mechanics (roll multiple, keep highest/lowest)

**Independent Test**: Parse "1d20 advantage", "4d6 keep 3 highest" and verify correct die selection

### Implementation for User Story 3

- [X] T053 [P] [US3] Extend Lexer to recognize keywords: advantage, disadvantage, keep, highest, lowest in src/Parser/Lexer.php
- [X] T054 [US3] Add advantage/disadvantage parsing in src/Parser/DiceExpressionParser.php
- [X] T055 [US3] Add keep count validation - must not exceed roll count (FR-003a, FR-004a) in src/Parser/Validator.php
- [X] T056 [US3] Add conflicting modifier detection - cannot have both advantage AND disadvantage (FR-034) in src/Parser/Validator.php
- [X] T057 [US3] Update StatisticalCalculator to compute advantage/disadvantage probabilities in src/Model/StatisticalCalculator.php
- [X] T058 [US3] Update DiceRoller to implement keep-highest and keep-lowest logic in src/Roller/DiceRoller.php
- [X] T059 [US3] Update RollResult to track keptDice and discardedDice indices in src/Roller/DiceRoller.php
- [X] T060 [US3] Write integration test in tests/Integration/AdvantageTest.php covering all US3 acceptance scenarios
- [X] T061 [US3] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-003a, FR-004a, FR-034
- [X] T062 [US3] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for advantage/disadvantage logic

**Checkpoint**: At this point, basic rolling, arithmetic, AND advantage/disadvantage should all work independently

---

## Phase 6: User Story 4 - Success Counting (Priority: P4)

**Goal**: Count dice above threshold instead of summing (for dice pool systems)

**Independent Test**: Parse "5d6 >=4", "10d10 threshold 7" and verify result is count, not sum

### Implementation for User Story 4

- [X] T063 [P] [US4] Extend Lexer to recognize keywords: success, threshold, comparison operators >=, >, <, <= in src/Parser/Lexer.php
- [X] T064 [US4] Add success counting syntax parsing in src/Parser/DiceExpressionParser.php
- [X] T065 [US4] Update StatisticalCalculator to compute expected success count in src/Model/StatisticalCalculator.php
- [X] T066 [US4] Update DiceRoller to count successes instead of summing when success threshold set in src/Roller/DiceRoller.php
- [X] T067 [US4] Update RollResult to set successCount field in success counting mode in src/Roller/DiceRoller.php
- [X] T068 [US4] Write integration test in tests/Integration/SuccessCountingTest.php covering all US4 acceptance scenarios
- [X] T069 [US4] Write unit tests in tests/Unit/Model/StatisticalCalculatorTest.php for success probability calculations
- [X] T070 [US4] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for success counting logic

**Checkpoint**: At this point, all previous stories PLUS success counting should work independently

---

## Phase 7: User Story 5 - Reroll Mechanics (Priority: P5)

**Goal**: Reroll dice meeting conditions with configurable limits (default 100, explicit count)

**Independent Test**: Parse "4d6 reroll <=2", "4d6 reroll 1 <=2" and verify correct reroll behavior and limits

### Implementation for User Story 5

- [X] T071 [P] [US5] Extend Lexer to recognize keyword: reroll and limit numbers in src/Parser/Lexer.php
- [X] T072 [US5] Add reroll syntax parsing with optional limit and threshold operators (FR-005) in src/Parser/DiceExpressionParser.php
- [X] T073 [US5] Add reroll threshold range validation - cannot cover entire die range (FR-005b) in src/Parser/Validator.php
- [ ] T074 [US5] Add reroll limit validation - must be non-negative, warn if >100 in src/Parser/Validator.php
- [X] T075 [US5] Update StatisticalCalculator to adjust statistics for reroll mechanics in src/Model/StatisticalCalculator.php
- [X] T076 [US5] Implement reroll logic with configurable limits (FR-005a): default 100, explicit count in src/Roller/DiceRoller.php
- [X] T077 [US5] Update RollResult to track complete reroll history with limit tracking per die in src/Roller/DiceRoller.php
- [X] T078 [US5] Write integration test in tests/Integration/RerollTest.php covering all US5 acceptance scenarios
- [ ] T079 [US5] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-005b reroll range validation
- [X] T080 [US5] Write unit tests in tests/Unit/Roller/DiceRollerTest.php verifying configurable reroll limits (FR-005a)

**Checkpoint**: At this point, all previous stories PLUS reroll mechanics with configurable limits should work independently

---

## Phase 8: User Story 5a - Exploding Dice Mechanics (Priority: P5a)

**Goal**: Support exploding dice with configurable limits and thresholds (reroll on threshold, add to total)

**Independent Test**: Parse "3d6 explode", "3d6 explode 2", "3d6 explode 3 >=5" and verify explosions add to totals

### Implementation for User Story 5a

- [X] T081 [P] [US5a] Extend Lexer to recognize keyword: explode in src/Parser/Lexer.php
- [X] T082 [US5a] Add exploding dice syntax parsing with optional limit and threshold (FR-038, FR-038a, FR-038b) in src/Parser/DiceExpressionParser.php
- [X] T083 [US5a] Add explosion threshold range validation - cannot cover entire die range (FR-038c) in src/Parser/Validator.php
- [ ] T084 [US5a] Add explosion limit validation - must be non-negative, warn if >100 in src/Parser/Validator.php
- [ ] T085 [US5a] Add explosion threshold operator validation - only >= and <= allowed in src/Parser/Validator.php
- [X] T086 [US5a] Update StatisticalCalculator to compute expected values with explosion mechanics in src/Model/StatisticalCalculator.php
- [X] T087 [US5a] Implement explosion logic: reroll and add when threshold met, up to limit (FR-039) in src/Roller/DiceRoller.php
- [X] T088 [US5a] Update RollResult to track explosion history with cumulative totals per die (FR-040) in src/Roller/DiceRoller.php
- [ ] T089 [US5a] Implement explosion in success counting mode - each explosion counts toward successes (FR-041) in src/Roller/DiceRoller.php
- [X] T090 [US5a] Write integration test in tests/Integration/ExplodingDiceTest.php covering all US5a acceptance scenarios
- [ ] T091 [US5a] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-038c explosion range validation
- [ ] T092 [US5a] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for explosion mechanics and limits

**Checkpoint**: At this point, all previous stories PLUS exploding dice mechanics should work independently

---

## Phase 9: User Story 6 - Special Dice Types (Priority: P6)

**Goal**: Support fudge dice (dF: -1/0/+1) and percentile dice (d%: 1-100)

**Independent Test**: Parse "4dF", "d%", "1d100" and verify correct value ranges

### Implementation for User Story 6

- [X] T093 [P] [US6] Extend Lexer to recognize special dice notation: dF, d% in src/Parser/Lexer.php
- [X] T094 [US6] Add fudge dice parsing (XdF format) in src/Parser/DiceExpressionParser.php
- [X] T095 [US6] Add percentile dice parsing (d% or Xd100 format) in src/Parser/DiceExpressionParser.php
- [X] T096 [US6] Update StatisticalCalculator for fudge dice (-1, 0, +1 values) in src/Model/StatisticalCalculator.php
- [X] T097 [US6] Update StatisticalCalculator for percentile dice (1-100 range) in src/Model/StatisticalCalculator.php
- [X] T098 [US6] Update DiceRoller to generate fudge dice values (-1, 0, +1) in src/Roller/DiceRoller.php
- [X] T099 [US6] Update DiceRoller to generate percentile values (1-100) in src/Roller/DiceRoller.php
- [X] T100 [US6] Write integration test in tests/Integration/SpecialDiceTest.php covering all US6 acceptance scenarios
- [ ] T101 [US6] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for fudge and percentile dice

**Checkpoint**: At this point, all previous stories PLUS special dice types should work independently

---

## Phase 10: User Story 7 - Placeholders and Variables (Priority: P7)

**Goal**: Support %name% placeholder syntax with variable binding at parse time

**Independent Test**: Parse "1d20+%str%+%proficiency%" with variables provided and verify resolution

### Implementation for User Story 7

- [X] T102 [P] [US7] Extend Lexer to recognize placeholder pattern %name% in src/Parser/Lexer.php
- [X] T103 [US7] Add placeholder parsing and variable substitution in src/Parser/DiceExpressionParser.php
- [X] T104 [US7] Add validation to reject unbound placeholders (FR-009a) in src/Parser/Validator.php
- [X] T105 [US7] Store resolved variables in RollModifiers.resolvedVariables in src/Parser/DiceExpressionParser.php
- [X] T106 [US7] Update parse() method signature to accept variables parameter in src/PHPDice.php
- [X] T107 [US7] Write integration test in tests/Integration/PlaceholdersTest.php covering all US7 acceptance scenarios
- [X] T108 [US7] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-009a (missing variables error)

**Checkpoint**: At this point, all previous stories PLUS placeholder variables should work independently

---

## Phase 11: User Story 8 - Success Rolls and Comparisons (Priority: P8)

**Goal**: Evaluate if roll meets target number (return boolean success flag)

**Independent Test**: Parse "1d20+3 >= 15" and verify result includes success/failure flag

### Implementation for User Story 8

- [X] T109 [P] [US8] Extend Lexer to recognize comparison operators at expression level (for success rolls) in src/Parser/Lexer.php
- [X] T110 [US8] Add comparison expression parsing (expression >= threshold) in src/Parser/DiceExpressionParser.php
- [X] T111 [US8] Store comparisonOperator and comparisonThreshold in DiceExpression in src/Parser/DiceExpressionParser.php
- [X] T112 [US8] Update DiceRoller to evaluate comparison and set isSuccess flag in src/Roller/DiceRoller.php
- [X] T113 [US8] Write integration test in tests/Integration/ComparisonTest.php covering all US8 acceptance scenarios
- [X] T114 [US8] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for comparison evaluation logic

**Checkpoint**: At this point, all previous stories PLUS success roll comparisons should work independently

---

## Phase 12: User Story 9 - Critical Success and Critical Failure (Priority: P9)

**Goal**: Detect critical success (e.g., natural 20) and critical failure (e.g., natural 1)

**Independent Test**: Parse "1d20 crit 20 glitch 1" and verify flags set when thresholds met

### Implementation for User Story 9

- [X] T115 [P] [US9] Extend Lexer to recognize keywords: crit, critical, glitch, failure in src/Parser/Lexer.php
- [X] T116 [US9] Add critical threshold parsing (crit N, glitch N syntax) in src/Parser/DiceExpressionParser.php
- [X] T117 [US9] Add validation that critical thresholds are within die range (FR-035, FR-036) in src/Parser/Validator.php
- [X] T118 [US9] Store criticalSuccess and criticalFailure thresholds in RollModifiers in src/Parser/DiceExpressionParser.php
- [X] T119 [US9] Update DiceRoller to check for critical success/failure and set flags in src/Roller/DiceRoller.php
- [X] T120 [US9] Write integration test in tests/Integration/CriticalTest.php covering all US9 acceptance scenarios
- [X] T121 [US9] Write unit tests in tests/Unit/Parser/ValidatorTest.php for FR-035, FR-036
- [X] T122 [US9] Write unit tests in tests/Unit/Roller/DiceRollerTest.php for critical detection logic

**Checkpoint**: At this point, all previous stories PLUS critical detection should work independently

---

## Phase 13: User Story 10 - Statistical Analysis (Priority: P10)

**Goal**: Provide min, max, expected value for any expression without rolling

**Independent Test**: Parse "3d6+5" and query statistics for min=8, max=23, expected=15.5

### Implementation for User Story 10

- [X] T123 [US10] Verify StatisticalCalculator handles all expression types from US1-US9 including explosions in src/Model/StatisticalCalculator.php
- [ ] T124 [US10] Add variance and standard deviation calculations (optional fields) in src/Model/StatisticalCalculator.php
- [X] T125 [US10] Ensure 3 decimal precision for all statistical calculations (SC-004) in src/Model/StatisticalCalculator.php
- [X] T126 [US10] Add getStatistics() method to DiceExpression for easy access in src/Model/DiceExpression.php
- [X] T127 [US10] Write integration test in tests/Integration/StatisticsTest.php covering all US10 acceptance scenarios
- [X] T128 [US10] Write unit tests in tests/Unit/Model/StatisticalCalculatorTest.php for all dice types and mechanics
- [X] T129 [US10] Verify statistical accuracy against known probability distributions in tests

**Checkpoint**: All user stories (P1-P10 including P5a) should now be independently functional with complete statistical analysis

---

## Phase 14: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories and final quality checks

- [X] T130 [P] Write comprehensive API documentation in docs/api.md covering all classes and methods
- [ ] T131 [P] Expand README.md with installation, usage examples, game system compatibility table (D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE, Savage Worlds)
- [X] T132 [P] Create examples/ directory with game system examples including Savage Worlds exploding dice
- [X] T133 [P] Update quickstart.md examples to match actual implementation with exploding dice examples
- [X] T134 Run PHPStan static analysis and fix all issues to achieve strict level compliance
- [X] T135 Run PHP-CS-Fixer to ensure 100% PSR-12 compliance
- [X] T136 Verify all files have declare(strict_types=1) as first statement
- [ ] T137 Run PHPUnit with coverage report and ensure 90%+ coverage threshold met
- [ ] T138 Add PHPDoc comments to all public methods and classes
- [ ] T139 [P] Create contract tests in tests/Contract/GameSystemContractTest.php for D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE, Savage Worlds
- [ ] T140 Performance profiling - verify parse <100ms, roll <50ms for all test cases including explosions
- [ ] T141 Memory profiling - verify <1MB per operation for all test cases
- [ ] T142 [P] Validate quickstart.md tutorial can be completed in 10 minutes (SC-006)
- [X] T143 Update CHANGELOG.md with all features and prepare for v1.0.0 release
- [X] T144 [P] Create CONTRIBUTING.md with development workflow and constitution compliance
- [X] T145 Final constitution check against all 8 principles in plan.md

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-13)**: All depend on Foundational phase completion
  - User stories can proceed in parallel (if staffed) OR
  - Sequentially in priority order: P1 → P2 → P3 → P4 → P5 → P5a → P6 → ... → P10
- **Polish (Phase 14)**: Depends on all desired user stories being complete

### User Story Dependencies

All user stories are designed to be independently testable, but they build incrementally:

- **US1 (Basic Rolling)**: Foundation - no dependencies after Phase 2
- **US2 (Arithmetic)**: Extends US1 parser/roller - can work independently
- **US3 (Advantage)**: Extends US1 roller - can work independently
- **US4 (Success Counting)**: Extends US1 roller - can work independently
- **US5 (Reroll)**: Extends US1 roller - can work independently
- **US5a (Exploding Dice)**: Extends US1/US5 roller - can work independently, shares infrastructure with rerolls
- **US6 (Special Dice)**: Extends US1 parser/roller - can work independently
- **US7 (Placeholders)**: Extends US2 parser - can work independently
- **US8 (Comparisons)**: Extends US2 parser - can work independently
- **US9 (Criticals)**: Extends US1 roller - can work independently
- **US10 (Statistics)**: Uses calculator from US1 - validates all previous stories

### Parallel Opportunities

Tasks marked [P] can run in parallel (different files). US5 and US5a share similar infrastructure (reroll mechanics), so they could be developed together by coordinating on the roller architecture.

---

## Implementation Strategy

### MVP First (Fastest Path to Value)

**Minimum Viable Product = User Story 1 Only**

1. Complete Phase 1: Setup (T001-T014) ≈ 1 day
2. Complete Phase 2: Foundational (T015-T024) ≈ 1 day
3. Complete Phase 3: User Story 1 (T025-T039) ≈ 3 days
4. **STOP and VALIDATE**: Run integration tests, verify basic dice rolling works

**Total MVP Time**: ~5 days

### Incremental Delivery (Recommended)

1. **Setup + Foundational** (T001-T024) → Foundation ready ≈ 2 days
2. **+ US1 Basic Rolling** (T025-T039) → MVP deployable ≈ 3 days total (5 days)
3. **+ US2 Arithmetic** (T040-T052) → Complex expressions ≈ 2 days (7 days)
4. **+ US3 Advantage** (T053-T062) → D&D 5e support ≈ 2 days (9 days)
5. **+ US4 Success Counting** (T063-T070) → Dice pool systems ≈ 1 day (10 days)
6. **+ US5 Reroll** (T071-T080) → Configurable reroll mechanics ≈ 2 days (12 days)
7. **+ US5a Exploding Dice** (T081-T092) → Savage Worlds support ≈ 2 days (14 days)
8. **+ US6 Special Dice** (T093-T101) → FATE/percentile ≈ 1 day (15 days)
9. **+ US7 Placeholders** (T102-T108) → Character integration ≈ 1 day (16 days)
10. **+ US8 Comparisons** (T109-T114) → Target numbers ≈ 1 day (17 days)
11. **+ US9 Criticals** (T115-T122) → Exceptional outcomes ≈ 1 day (18 days)
12. **+ US10 Statistics** (T123-T129) → Probability analysis ≈ 1 day (19 days)
13. **+ Polish** (T130-T145) → Production ready ≈ 2 days (21 days)

**Total Full Implementation**: ~21 days

---

## Validation Checkpoints

### After Each User Story

1. Run integration tests for that story
2. Verify story works independently
3. Run full test suite to ensure no regressions
4. Check code coverage still >= 90%
5. Run PHPStan and PHP-CS-Fixer
6. Commit with message: "feat: Implement [User Story N] - [Title]"

### Before Release (v1.0.0)

1. All 145 tasks completed and checked off
2. Constitution check passed (all 8 principles verified)
3. Success criteria SC-001 through SC-007 all met
4. All 41 functional requirements (FR-001 through FR-041) implemented
5. Game system contract tests passing (D&D, Shadowrun, FATE, Savage Worlds, etc.)
6. Performance benchmarks met (parse <100ms, roll <50ms)
7. Documentation complete and accurate

---

## Notes

- **TDD Workflow**: Follow Red-Green-Refactor for each task per constitution
- **[P] markers**: Tasks in different files with no dependencies can run in parallel
- **[Story] labels**: Enable traceability from tasks back to user requirements
- **Edge Case Coverage**: All FR-026 through FR-041 validation requirements included
- **Exploding Dice**: US5a builds on similar infrastructure as US5 (rerolls) but with additive behavior
- **Quality Gates**: Run PHPStan + PHP-CS-Fixer + PHPUnit before each commit

---

## Error Handling Requirements

All validation tasks enforce error handling per FR-037:

- **Dice constraints** (FR-027, FR-028, FR-029, FR-030): Tasks T028-T032, T038
- **Arithmetic** (FR-031, FR-032, FR-033): Tasks T044, T045, T047, T052
- **Modifiers** (FR-034): Tasks T056, T061
- **Reroll** (FR-005b): Tasks T073, T074, T079
- **Explode** (FR-038c): Tasks T083, T084, T085, T091
- **Criticals** (FR-035, FR-036): Tasks T117, T121
- **Placeholders** (FR-009a): Tasks T104, T108

All error messages must identify problem, indicate location, and specify expectations.
