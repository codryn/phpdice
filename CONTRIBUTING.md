# Contributing to PHPDice

Thank you for considering contributing to PHPDice! This document outlines the development workflow, coding standards, and guidelines for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Pull Request Process](#pull-request-process)
- [Project Structure](#project-structure)
- [Running Tests](#running-tests)
- [Static Analysis](#static-analysis)
- [Documentation](#documentation)

## Code of Conduct

This project follows a simple code of conduct:

- **Be respectful**: Treat all contributors with respect and professionalism
- **Be constructive**: Provide helpful feedback and suggestions
- **Be collaborative**: Work together to improve the project
- **Be inclusive**: Welcome contributors of all backgrounds and skill levels

## Getting Started

### Prerequisites

- **PHP 8.1+** (strict requirement)
- **Composer** for dependency management
- **Git** for version control
- Recommended: PHPStorm or VS Code with PHP extensions

### Initial Setup

1. **Clone**
   ```bash
   git clone https://github.com/codryn/phpdice.git
   cd phpdice
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Verify installation**
   ```bash
   composer test        # Run all tests
   composer phpstan     # Run static analysis
   composer cs-check    # Check code style
   ```

4. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Workflow

### 1. Test-Driven Development (TDD)

PHPDice follows strict TDD practices:

1. **Write tests first** before implementing features
2. **Run tests** to see them fail (red)
3. **Implement** the minimum code to pass (green)
4. **Refactor** while keeping tests green
5. **Repeat** for each feature increment

**Example workflow:**

```bash
# 1. Write a failing test
vim tests/Unit/Parser/DiceExpressionParserTest.php

# 2. Run the test (should fail)
composer test

# 3. Implement the feature
vim src/Parser/DiceExpressionParser.php

# 4. Run tests (should pass)
composer test

# 5. Refactor and verify
composer test
composer phpstan
composer cs-check
```


### Setup

```bash
git clone https://github.com/codryn/phpdice.git
cd phpdice
composer install
```

Note: Its is recommended to use the proviced vs code devcontainer for consistent environment. Please refere to https://code.visualstudio.com/docs/devcontainers/containers for more information.

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Integration
```

### Code Quality

```bash
# PSR-12 compliance check and fix
./vendor/bin/php-cs-fixer fix

# Static analysis (PHPStan level 9)
./vendor/bin/phpstan analyse

# Run all quality checks
composer test
./vendor/bin/phpstan analyse
./vendor/bin/php-cs-fixer fix --dry-run
```




### 2. Feature Development Process

For new features:

1. **Discuss first**: Open an issue to discuss the feature
2. **Design**: Document expected behavior and API
3. **Write tests**: Create comprehensive test cases
4. **Implement**: Write code to pass tests
5. **Document**: Update README, API docs, and examples
6. **Review**: Submit PR for review

### 3. Bug Fixes

For bug fixes:

1. **Reproduce**: Create a failing test that reproduces the bug
2. **Fix**: Implement the minimal fix
3. **Verify**: Ensure all tests pass
4. **Document**: Add comments explaining the fix if non-obvious

## Coding Standards

### PHP Standards

PHPDice strictly adheres to:

- **PSR-12**: Extended coding style guide
- **PHPStan Level 9**: Maximum static analysis strictness
- **Strict Types**: All files MUST have `declare(strict_types=1);`

### Code Style Requirements

1. **Type Declarations**
   ```php
   <?php

   declare(strict_types=1);  // REQUIRED

   namespace Codryn\PHPDice\Model;

   // GOOD: Full type declarations
   public function roll(string $expression, array $variables = []): RollResult
   {
       // ...
   }

   // BAD: Missing types
   public function roll($expression, $variables = [])
   {
       // ...
   }
   ```

2. **PHPDoc Comments**
   ```php
   /**
    * Parse a dice expression into an AST
    *
    * @param string $expression Dice notation (e.g., "3d6+5")
    * @param array<string, int> $variables Placeholder variables
    * @return DiceExpression Parsed expression with statistics
    * @throws ParseException If expression is invalid
    */
   public function parse(string $expression, array $variables = []): DiceExpression
   ```

3. **Immutability**
   ```php
   // GOOD: Readonly properties
   public readonly int $total;
   public readonly array $diceValues;

   // BAD: Mutable public properties
   public int $total;
   ```

4. **Naming Conventions**
   - **Classes**: PascalCase (e.g., `DiceExpressionParser`)
   - **Methods**: camelCase (e.g., `parseExpression()`)
   - **Constants**: SCREAMING_SNAKE_CASE (e.g., `MAX_DICE_COUNT`)
   - **Private properties**: camelCase with underscore prefix optional (e.g., `$tokens` or `$_tokens`)

### Automatic Formatting

**Before committing**, run:

```bash
# Fix code style automatically
composer cs-fix

# Or check without fixing
composer cs-check
```

Configuration is in `.php-cs-fixer.php`:
- PSR-12 compliance
- Strict types enforcement
- Ordered imports
- No unused imports
- Array syntax normalization

## Testing Requirements

### Test Coverage

- **Minimum overall coverage**: 66%+ (current)
- **Critical paths**: 100% coverage required
  - All public methods
  - Error handling paths
  - Edge cases

### Test Organization

```
tests/
├── Unit/              # Unit tests (isolated, fast)
│   ├── Model/
│   ├── Parser/
│   └── Roller/
└── Integration/       # Integration tests (end-to-end)
    └── PHPDiceTest.php
```

### Writing Tests

**Unit Test Example:**

```php
<?php

declare(strict_types=1);

namespace Codryn\PHPDice\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Codryn\PHPDice\Parser\DiceExpressionParser;

final class DiceExpressionParserTest extends TestCase
{
    private DiceExpressionParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DiceExpressionParser();
    }

    public function testParseSimpleDiceNotation(): void
    {
        $expression = $this->parser->parse('3d6');

        $this->assertSame('3d6', $expression->originalExpression);
        $this->assertSame(3, $expression->diceCount);
        $this->assertSame(6, $expression->sides);
    }

    public function testInvalidNotationThrowsException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid dice notation');

        $this->parser->parse('invalid');
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Unit/Parser/DiceExpressionParserTest.php

# Run with coverage
composer test-coverage

# Run specific test method
vendor/bin/phpunit --filter testParseSimpleDiceNotation
```

## Static Analysis

### PHPStan

PHPDice uses **PHPStan Level 9** (strictest):

```bash
# Run PHPStan
composer phpstan

# Analyze specific file
vendor/bin/phpstan analyse src/Parser/DiceExpressionParser.php --level 9
```

### Common PHPStan Issues

1. **Nullable types**: Always check for null
   ```php
   // BAD
   $value = $array['key'];

   // GOOD
   $value = $array['key'] ?? null;
   if ($value !== null) {
       // Use $value
   }
   ```

2. **Array shapes**: Document array structures
   ```php
   /**
    * @param array<int, string> $items
    * @return array{total: int, items: array<int, string>}
    */
   ```

## Pull Request Process

### Before Submitting

1. **Run all checks**
   ```bash
   composer test          # All tests must pass
   composer phpstan       # No errors allowed
   composer cs-fix        # Fix code style
   ```

2. **Update documentation**
   - Update README.md if needed
   - Add/update API documentation
   - Update CHANGELOG.md

3. **Write a clear PR description**
   - What: Brief summary of changes
   - Why: Reason for the change
   - How: Technical approach
   - Testing: How you tested the change

### PR Checklist

- [ ] Tests added/updated and passing
- [ ] PHPStan level 9 passes
- [ ] PSR-12 code style applied
- [ ] All files have `declare(strict_types=1)`
- [ ] Documentation updated
- [ ] CHANGELOG.md updated (for features/fixes)
- [ ] No merge conflicts
- [ ] Commit messages are clear and descriptive

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding/updating tests
- `chore`: Maintenance tasks

**Example:**
```
feat(roller): Add exploding dice support for Savage Worlds

Implement explosion mechanic where dice roll again on max value.
Configurable explosion limit prevents infinite loops.

Closes #42
```

## Project Structure

```
phpdice/
├── docs/                        # Documentation
├── examples/                    # Example code
├── scripts/                     # Scripts and utilities
├── specs/                       # Specification files, organized by iteration (github spec kit)
├── src/
│   ├── PHPDice.php              # Main facade
│   ├── Model/                   # Domain models
│   ├── Parser/                  # Expression parsing
│   ├── Roller/                  # Dice rolling
│   └── Exception/               # Custom exceptions
├── tests/
│   ├── Contract/                # Contract tests (planned)
│   ├── Integration/             # Integration tests
│   └── Unit/                    # Unit tests
├── .php-cs-fixer.php            # Code style config
├── phpstan.neon                 # Static analysis config
├── phpunit.xml                  # Test configuration
├── composer.json                # Dependencies
└── README.md                    # Main documentation
```

## Development Commands

```bash
# Testing
composer test              # Run all tests
composer test-coverage     # Generate coverage report

# Code Quality
composer phpstan           # Static analysis
composer cs-check          # Check code style
composer cs-fix            # Fix code style

# Combined
composer ci                # Run all CI checks (test + phpstan + cs-check)
```

## Documentation

### API Documentation

Update `docs/api.md` when adding/changing public APIs:

- Class descriptions
- Method signatures
- Parameter types and descriptions
- Return types
- Usage examples
- Error conditions

### README Updates

Update `README.md` for:

- New features visible to users
- Installation changes
- Breaking changes
- New game system support

### Code Comments

- **Public APIs**: Always include PHPDoc
- **Complex logic**: Explain why, not what
- **Algorithms**: Reference sources or papers
- **Workarounds**: Document why they exist

## Getting Help

- **Issues**: [GitHub Issues](https://github.com/marcowuelser/phpdice/issues)
- **Discussions**: [GitHub Discussions](https://github.com/marcowuelser/phpdice/discussions)
- **Questions**: Open a discussion or issue

## License

By contributing to PHPDice, you agree that your contributions will be licensed under the same license as the project (see LICENSE file).

---

**Thank you for contributing to PHPDice!** 🎲
