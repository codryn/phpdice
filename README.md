# PHPDice

A comprehensive PHP library for parsing and rolling dice expressions for tabletop RPG systems.

## Features

- **Universal Dice Notation**: Support for all major RPG systems (D&D 5e, Pathfinder, Shadowrun, World of Darkness, FATE, Savage Worlds, etc.)
- **Advanced Mechanics**: Advantage/disadvantage, success counting, rerolls, exploding dice, critical detection, DC comparisons
- **Statistical Analysis**: Pre-calculated min/max/expected values for any expression
- **Placeholder Variables**: Character sheet integration with `$variable$` syntax
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

## Quick Start and Usage

```php
<?php
require 'vendor/autoload.php';

use PHPDice\PHPDice;

// Create instance
$dice = new PHPDice();

// Roll dice directly
$result = $dice->roll("3d6");

echo "Rolled: " . $result->total . "\n";
echo "Dice: " . implode(", ", $result->diceValues) . "\n";

// Example output:
// Rolled: 14
// Dice: 5, 6, 3
```

See [docs/quickstart.md](docs/quickstart.md) for a 10-minute tutorial.

## Dice Expressions

See [docs/expressions.md](docs/expressions.md) for all supported dice expressions.

## Game System Support

| System | Example | Features |
|--------|---------|----------|
| **D&D 5e** | `1d20 auto 20 crit 20 +5 dc >= 15` | Auto success, criticals, modifiers, comparisons |
| **Pathfinder** | `3d6+2` | Basic dice, modifiers |
| **Shadowrun 5e** | `12d6 edge count >=5` | Success counting, edge (Rule of Six) |
| **World of Darkness** | `10d10 >=8` | Success counting |
| **FATE** | `4dF+2` | Fudge dice, modifiers |
| **Savage Worlds** | `1d6 explode + 1d8 explode` | Exploding dice |
| **Call of Cthulhu** | `d%` | Percentile dice |
| **Even/Odd Counting** | `6d6 count even` or `12d4 count odd` | Count even or odd results |

## API Overview

See [docs/api.md](docs/api.md) for complete reference.

## Documentation

- **[Quick Start Guide](docs/quickstart.md)** - 10-minute tutorial
- **[Dice Expressions](docs/expressions.md)** - All supported dice expressions
- **[API Documentation](docs/api.md)** - Complete API reference with examples
- **[Examples](examples/)** - Game system specific examples

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

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

- **Issues**: [GitHub Issues](https://github.com/codryn/phpdice/issues)
- **Discussions**: [GitHub Discussions](https://github.com/codryn/phpdice/discussions)

## Credits

Developed with adherence to:
- PSR-12 coding standards
- PHPStan level 9 strict analysis
- Test-Driven Development (TDD)
- Comprehensive documentation

Built for the tabletop RPG community 🎲
