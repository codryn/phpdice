<?php
/**
 * Examples of the eval() method (Issue #79).
 * 
 * The eval() method evaluates a dice expression and replaces placeholders with their values,
 * while resolving conditional expressions that can be evaluated.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPDice\PHPDice;

$dice = new PHPDice();

echo "=== PHPDice eval() Method Examples ===\n\n";

// Example 1: Conditional expression that resolves
echo "Example 1: Conditional resolution\n";
echo "Expression: if \$a$ == 1 : 1d20 | 1d12 + \$b$\n";
echo "Variables: a=2, b=1\n";
$result = $dice->eval('if $a$ == 1 : 1d20 | 1d12 + $b$', ['a' => 2, 'b' => 1]);
echo "Result: $result\n";
echo "Explanation: Since a=2 (not 1), the false branch is chosen: 1d12 + 1\n\n";

// Example 2: Conditional expression - true branch
echo "Example 2: Conditional resolution (true branch)\n";
echo "Expression: if \$a$ == 1 : 1d20 | 1d12 + \$b$\n";
echo "Variables: a=1, b=2\n";
$result = $dice->eval('if $a$ == 1 : 1d20 | 1d12 + $b$', ['a' => 1, 'b' => 2]);
echo "Result: $result\n";
echo "Explanation: Since a=1, the true branch is chosen: 1d20\n\n";

// Example 3: Simple placeholder replacement
echo "Example 3: Simple placeholder replacement\n";
echo "Expression: 1d20+\$bonus$\n";
echo "Variables: bonus=5\n";
$result = $dice->eval('1d20+$bonus$', ['bonus' => 5]);
echo "Result: $result\n";
echo "Explanation: Placeholder \$bonus$ is replaced with 5\n\n";

// Example 4: Partial evaluation (missing placeholders are preserved)
echo "Example 4: Partial evaluation\n";
echo "Expression: if \$a$ == 1 : 1d20 + \$b$ | 1d20\n";
echo "Variables: a=1 (b is missing)\n";
echo "Partial: true\n";
$result = $dice->eval('if $a$ == 1 : 1d20 + $b$ | 1d20', ['a' => 1], true);
echo "Result: $result\n";
echo "Explanation: Condition resolves to true, but \$b$ remains as a placeholder\n\n";

// Example 5: Multiple placeholders
echo "Example 5: Multiple placeholders\n";
echo "Expression: 1d20+\$str$+\$proficiency$\n";
echo "Variables: str=3, proficiency=2\n";
$result = $dice->eval('1d20+$str$+$proficiency$', ['str' => 3, 'proficiency' => 2]);
echo "Result: $result\n";
echo "Explanation: Both placeholders are replaced with their values\n\n";

// Example 6: Complex conditional with comparison
echo "Example 6: Complex conditional with comparison\n";
echo "Expression: if \$level$ >= 5 : 3d6 | 2d6\n";
echo "Variables: level=5\n";
$result = $dice->eval('if $level$ >= 5 : 3d6 | 2d6', ['level' => 5]);
echo "Result: $result\n";
echo "Explanation: Since level=5 (>= 5), the true branch is chosen: 3d6\n\n";

// Example 7: Math-only expressions are fully evaluated
echo "Example 7: Math-only expression evaluation\n";
echo "Expression: 1+2+3\n";
echo "Variables: none\n";
$result = $dice->eval('1+2+3', [], true);
echo "Result: $result\n";
echo "Explanation: Pure math expressions are fully evaluated\n\n";

// Example 8: Math with placeholders - partial evaluation
echo "Example 8: Partial math evaluation with placeholder\n";
echo "Expression: (1+\$a$-4)/2\n";
echo "Variables: none (partial mode)\n";
$result = $dice->eval('(1+$a$-4)/2', [], true);
echo "Result: $result\n";
echo "Explanation: Expression structure is preserved with placeholder\n\n";

// Example 9: Math with placeholders - full evaluation
echo "Example 9: Math with placeholder - full evaluation\n";
echo "Expression: 1+\$a$*2\n";
echo "Variables: a=4\n";
$result = $dice->eval('1+$a$*2', ['a' => 4], false);
echo "Result: $result\n";
echo "Explanation: When all placeholders are provided, math is fully evaluated (1+4*2=9)\n\n";

// Example 10: Conditional with division
echo "Example 10: Conditional with division\n";
echo "Expression: if \$a$ == 1 : \$a$ / 4 | \$b$ + 1\n";
echo "Variables: a=1, b=5\n";
$result = $dice->eval('if $a$ == 1 : $a$ / 4 | $b$ + 1', ['a' => 1, 'b' => 5], false);
echo "Result: $result\n";
echo "Explanation: Condition resolves to true, then 1/4 is evaluated to 0.25\n\n";

echo "=== Use Cases ===\n\n";
echo "1. Character Sheet Integration:\n";
echo "   Store expressions with placeholders in your game system,\n";
echo "   then evaluate them with current character stats.\n\n";

echo "2. Dynamic Rule Systems:\n";
echo "   Define rules with conditionals based on game state,\n";
echo "   then resolve them at runtime.\n\n";

echo "3. Template Dice Expressions:\n";
echo "   Create reusable dice expression templates that can be\n";
echo "   customized with different parameters.\n\n";
