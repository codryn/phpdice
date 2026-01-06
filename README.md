# PHPDice

A comprehensive PHP library for parsing and rolling dice expressions for tabletop RPG systems.

## Features

- **Universal Dice Notation**: Support for all major RPG systems (D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE, Savage Worlds, etc.)
- **Advanced Mechanics**: Advantage/disadvantage, success counting, rerolls, exploding dice, critical detection
- **Statistical Analysis**: Pre-calculated min/max/expected values for any expression
- **Placeholder Variables**: Character sheet integration with `%variable%` syntax
- **Complex Arithmetic**: Full expression evaluation with operator precedence and parentheses
- **Error Handling**: Clear, specific error messages with location information
- **High Performance**: Parse <100ms, Roll <50ms for complex expressions
- **Type Safe**: Full PHP 8.0+ type declarations and strict mode
- **Well Tested**: 235+ tests with comprehensive coverage

## Requirements

- PHP 8.0 or higher

## Installation

```bash
composer require phpdice/phpdice
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use PHPDice\PHPDice;

$phpdice = new PHPDice();

// Basic dice rolling
$expression = $phpdice->parse('3d6+5');
$result = $phpdice->rollExpression($expression);
echo $result->total; // e.g., 18
print_r($result->diceValues); // e.g., [4, 6, 3]

// D&D 5e advantage
$result = $phpdice->roll('1d20 advantage');
echo $result->total; // Higher of two d20 rolls

// Shadowrun success counting
$result = $phpdice->roll('10d6 >=5');
echo $result->successCount; // Number of dice >= 5

// Savage Worlds exploding dice
$result = $phpdice->roll('1d6 explode + 1d8 explode');

// Statistical analysis (no rolling)
$expression = $phpdice->parse('3d6+5');
$stats = $expression->getStatistics();
echo "Min: {$stats->minimum}, Max: {$stats->maximum}, Expected: {$stats->expected}";
// Min: 8, Max: 23, Expected: 15.5
```

## Supported Dice Notation

### Basic Dice

```php
$phpdice->roll('3d6');        // Roll 3 six-sided dice
$phpdice->roll('1d20');       // Roll 1 twenty-sided die
$phpdice->roll('2d10+5');     // Roll 2d10 and add 5
$phpdice->roll('1d20 + 1d4'); // Roll 1d20 and 1d4, sum results
$phpdice->roll('(1d6+2)*3');  // Roll 1d6, add 2, multiply by 3
```

### Advantage/Disadvantage (D&D 5e)

```php
$phpdice->roll('1d20 advantage');     // Roll 2d20, keep highest
$phpdice->roll('1d20 disadvantage');  // Roll 2d20, keep lowest
$phpdice->roll('4d6 keep 3 highest'); // Roll 4d6, keep 3 highest
$phpdice->roll('4d6 keep 2 lowest');  // Roll 4d6, keep 2 lowest
```

### Success Counting (Shadowrun, World of Darkness)

```php
$phpdice->roll('10d6 >=5');   // Count dice >= 5
$phpdice->roll('8d10 >7');    // Count dice > 7
$phpdice->roll('12d6 >=4');   // Count dice >= 4
```

### Rerolls

```php
$phpdice->roll('4d6 reroll <=2');    // Reroll 1s and 2s once
$phpdice->roll('4d6 reroll 1 <=2');  // Reroll 1s and 2s, max 1 time per die
$phpdice->roll('3d6 reroll ==1');    // Reroll only 1s
```

### Exploding Dice (Savage Worlds)

```php
$phpdice->roll('1d6 explode');       // Explode on max (6)
$phpdice->roll('3d6 explode >=5');   // Explode on 5 or 6
$phpdice->roll('2d8 explode 3 >=8'); // Explode on 8, max 3 times per die
```

### Special Dice

```php
$phpdice->roll('4dF');    // FATE dice (-1, 0, +1)
$phpdice->roll('d%');     // Percentile dice (1-100)
$phpdice->roll('1d100');  // Same as d%
```

### Placeholders/Variables

```php
$expression = $phpdice->parse('1d20+%str%+%proficiency%', [
    'str' => 3,
    'proficiency' => 2
]);
$result = $phpdice->rollExpression($expression);
// Rolls 1d20 + 3 + 2
```

### Success Rolls (Comparisons)

```php
$expression = $phpdice->parse('1d20+5 >= 15');
$result = $phpdice->rollExpression($expression);
echo $result->total; // The actual roll
echo $result->isSuccess ? 'Success!' : 'Failure!';
```

### Critical Success/Failure

```php
$expression = $phpdice->parse('1d20 crit 20 glitch 1');
$result = $phpdice->rollExpression($expression);
if ($result->isCriticalSuccess) {
    echo 'Natural 20!';
} elseif ($result->isCriticalFailure) {
    echo 'Natural 1!';
}

// Custom thresholds
$phpdice->roll('1d20 crit 19 glitch 2'); // Crit on 19-20, fail on 1-2
```

### Complex Combinations

```php
// D&D 5e attack with advantage
$phpdice->roll('1d20 advantage + 5 >= 15 crit 20');

// Shadowrun with rerolls
$phpdice->roll('12d6 reroll ==1 >=5');

// Multiple dice pools
$phpdice->roll('1d6 explode + 1d8 explode + 2');
```

## Game System Support

| System | Example | Features |
|--------|---------|----------|
| **D&D 5e** | `1d20+5 >= 15 crit 20` | Advantage, modifiers, comparisons, criticals |
| **Pathfinder** | `3d6+2` | Basic dice, modifiers |
| **Shadowrun 5e** | `12d6 reroll ==1 >=5` | Success counting, rerolls |
| **World of Darkness** | `10d10 >=8` | Success counting |
| **FATE** | `4dF+2` | Fudge dice, modifiers |
| **Savage Worlds** | `1d6 explode + 1d8 explode` | Exploding dice |
| **Call of Cthulhu** | `d%` | Percentile dice |

## API Overview

### PHPDice

Main facade class:

```php
$phpdice = new PHPDice();

// Parse expression into structured DiceExpression object with statistics. Validates syntax.
$expression = '3d6+5';
$parsedExpression = $phpdice->parse($expression);
// Execute the roll
$result = $phpdice->rollExpression($parsedExpression);

// OR

// roll directly, parsing and executing in one step
$result = $phpdice->roll('3d6+5');
```

### DiceExpression

Parsed expression with statistics:

```php
$expression->specification;      // DiceSpecification object
$expression->modifiers;          // RollModifiers object
$expression->originalExpression; // Original string
$expression->getStatistics();    // StatisticalData object
```

### RollResult

Complete roll result:

```php
$result->total;              // Final result (int|float)
$result->diceValues;         // Individual dice (array<int>)
$result->keptDice;           // Indices of kept dice
$result->discardedDice;      // Indices of discarded dice
$result->successCount;       // Number of successes (for success counting)
$result->isCriticalSuccess;  // Critical success flag
$result->isCriticalFailure;  // Critical failure flag
$result->isSuccess;          // Comparison result (for success rolls)
$result->rerollHistory;      // Reroll tracking
$result->explosionHistory;   // Explosion tracking
```

### StatisticalData

Probability information:

```php
$stats = $expression->getStatistics();
$stats->minimum;   // Minimum possible result
$stats->maximum;   // Maximum possible result
$stats->expected;  // Expected value (mean), 3 decimal precision
```

## Error Handling

```php
use PHPDice\Exception\ParseException;
use PHPDice\Exception\ValidationException;

try {
    $expression = $phpdice->parse('invalid expression');
    $result = $phpdice->rollExpression($expression);
} catch (ParseException $e) {
    echo "Syntax error: {$e->getMessage()}";
} catch (ValidationException $e) {
    echo "Validation error: {$e->getMessage()}";
}
```

Common errors:
- Invalid syntax
- Too many dice (>100)
- Too many sides (>100)
- Keep count exceeds roll count
- Reroll/explosion covers entire range
- Unbound placeholder variables
- Division by zero
- Unbalanced parentheses

## Documentation

- **[API Documentation](docs/api.md)** - Complete API reference with examples
- **[Quick Start Guide](docs/quickstart.md)** - 10-minute tutorial
- **[Examples](examples/)** - Game system specific examples

## Development

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

### Project Structure

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
└── tests/
    ├── Contract/                # Contract tests (planned)
    ├── Integration/             # Integration tests
    └── Unit/                    # Unit tests
```

## Performance

PHPDice is optimized for real-time use:

- **Parsing**: <100ms for complex expressions
- **Rolling**: <50ms for typical rolls
- **Memory**: <1MB per operation

Tips for best performance:
- Reuse parsed `DiceExpression` objects for repeated rolls
- Set reasonable explosion/reroll limits (default 100 is safe)
- Use `getStatistics()` for probability analysis instead of Monte Carlo simulation

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for:
- Development workflow
- Coding standards
- Testing requirements
- Pull request process

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and migration guides.

## Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/phpdice/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/phpdice/discussions)

## Credits

Developed with adherence to:
- PSR-12 coding standards
- PHPStan level 9 strict analysis
- Test-Driven Development (TDD)
- Comprehensive documentation

Built for the tabletop RPG community 🎲
