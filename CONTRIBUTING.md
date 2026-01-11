# Contributing to PHPDice

Thank you for considering contributing to PHPDice! This document outlines the development workflow, coding standards, and guidelines for contributing.

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
- Recommended: VS Code with devcontainer and PHP extensions

### Initial Setup

1. **Clone**
   ```bash
   git clone https://github.com/codryn/phpdice.git
   cd phpdice
   ```

Note: Its is recommended to use the proviced vs code devcontainer for consistent environment. Please refere to https://code.visualstudio.com/docs/devcontainers/containers for more information.

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

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage and HTML report
composer test-coverage-html

# Run specific test suite
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Integration
```

### Code Quality

```bash
# PSR-12 compliance check and fix
composer cs-check
composer cs-fix

# Static analysis (PHPStan 2.1 level 10 strict)
composer analyse

# Run all quality checks
composer ci
```

### Strict Types

All PHP files must declare strict types:

```php
<?php

declare(strict_types=1);
```

### Type Hints

- Use PHP 8.1 type hints for all parameters and return values
- Document complex types with PHPDoc annotations
- Use union types when appropriate (e.g., `string|null`)

### PHPDoc Blocks

All public methods must have comprehensive PHPDoc blocks:

```php
/**
 * Brief description of what the method does
 *
 * @param int $year Year to check
 * @param bool $strict Enable strict validation
 * @return bool True if valid
 * @throws InvalidArgumentException if year is invalid
 */
public function isValid(int $year, bool $strict = false): bool
{
    // implementation
}
```





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
├── Acceptance/      # Acceptance tests for user stories
├── Contract/        # API Contract tests (planned)
├── Unit/            # Unit tests (isolated, fast)
└── Integration/     # Integration tests (end-to-end)
```

### Writing Tests

See existing tests for examples. Key guidelines:
- Use descriptive test method names: `testCreateGregorianCalendarWithCorrectProperties()`
- Follow Arrange-Act-Assert pattern
- One assertion concept per test
- Use data providers for testing multiple scenarios

## Pull Request Process

### Before Submitting

1. **Run all checks**
   ```bash
   composer ci
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
- [ ] PHPStan 2.1 Level 10 Strict passes
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
- `feature`: New feature
- `bug`: Bug fix
- `task`: Any other change

**Example:**
```
feature/savage-world: Add exploding dice support for Savage Worlds

Implement explosion mechanic where dice roll again on max value.
Configurable explosion limit prevents infinite loops.

Closes #42
```

## Code Review Guidelines

### For Contributors

- Respond to feedback promptly
- Keep PRs focused and reasonably sized
- Update tests when changing functionality
- Maintain backward compatibility when possible

### For Reviewers

- Be respectful and constructive
- Focus on code quality and maintainability
- Check test coverage
- Verify documentation is updated

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
├── tests/                       # Test cases
├── .php-cs-fixer.php            # Code style config
├── phpstan.neon                 # Static analysis config
├── phpunit.xml                  # Test configuration
├── composer.json                # Dependencies
└── README.md                    # Main documentation
```

## Development Commands

```bash
# Testing
composer test               # Run all tests
composer test-coverage      # Generate coverage report
composer test-coverage-html # Generate HTML coverage report
# Code Quality
composer analyse           # Static analysis
composer cs-check          # Check code style
composer cs-fix            # Fix code style
# Combined
composer ci                # Run all CI checks (test + analyse + cs-check)
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
