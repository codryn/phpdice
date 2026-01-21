<?php
/**
 * Examples of "is null" checks in conditional expressions.
 * 
 * The "is null" check allows you to test if a placeholder variable is missing
 * or explicitly set to null, enabling flexible handling of optional parameters.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPDice\PHPDice;

$dice = new PHPDice();

echo "=== PHPDice 'is null' Check Examples ===\n\n";

// Example 1: Basic null check with missing variable
echo "Example 1: Basic null check\n";
echo "Expression: if \$bonus\$ is null : 1d20 | 1d20+\$bonus\$\n";
echo "Variables: none (bonus is missing)\n";
$result = $dice->roll('if $bonus$ is null : 1d20 | 1d20+$bonus$', []);
echo "Result: " . $result->total . "\n";
echo "Explanation: Since \$bonus\$ is missing, the true branch (1d20) is used\n\n";

// Example 2: Null check with variable present
echo "Example 2: Null check with value present\n";
echo "Expression: if \$bonus\$ is null : 1d20 | 1d20+\$bonus\$\n";
echo "Variables: bonus=5\n";
$result = $dice->roll('if $bonus$ is null : 1d20 | 1d20+$bonus$', ['bonus' => 5]);
echo "Result: " . $result->total . "\n";
echo "Explanation: Since \$bonus\$ is 5, the false branch (1d20+5) is used\n\n";

// Example 3: Optional bonus in arithmetic expression
echo "Example 3: Optional bonus in arithmetic\n";
echo "Expression: 1d20 + (if \$proficiency\$ is null : 0 | \$proficiency\$)\n";
echo "Variables: none (proficiency is missing)\n";
$result = $dice->roll('1d20 + (if $proficiency$ is null : 0 | $proficiency$)', []);
echo "Result: " . $result->total . "\n";
echo "Explanation: When proficiency is missing, add 0. Otherwise add the proficiency value\n\n";

// Example 4: Multiple optional parameters
echo "Example 4: Multiple optional parameters\n";
echo "Expression: 1d20 + (if \$str\$ is null : 0 | \$str\$) + (if \$prof\$ is null : 0 | \$prof\$)\n";
echo "Variables: str=3 (prof is missing)\n";
$result = $dice->roll('1d20 + (if $str$ is null : 0 | $str$) + (if $prof$ is null : 0 | $prof$)', ['str' => 3]);
echo "Result: " . $result->total . "\n";
echo "Explanation: Add str modifier (3) and proficiency (0, since missing)\n\n";

// Example 5: Default damage value
echo "Example 5: Default damage value\n";
echo "Expression: if \$damage_bonus\$ is null : 1d6 | 1d6+\$damage_bonus\$\n";
echo "Variables: damage_bonus=2\n";
$result = $dice->roll('if $damage_bonus$ is null : 1d6 | 1d6+$damage_bonus$', ['damage_bonus' => 2]);
echo "Result: " . $result->total . "\n";
echo "Explanation: Add damage bonus when available\n\n";

// Example 6: Zero is not null
echo "Example 6: Zero is not null\n";
echo "Expression: if \$value\$ is null : -1 | \$value\$\n";
echo "Variables: value=0\n";
$result = $dice->roll('if $value$ is null : -1 | $value$', ['value' => 0]);
echo "Result: " . $result->total . "\n";
echo "Explanation: Zero is a valid value, not null. Result is 0, not -1\n\n";

// Example 7: Nested null checks
echo "Example 7: Nested null checks\n";
echo "Expression: if \$bonus1\$ is null : (if \$bonus2\$ is null : 1d20 | 1d20+\$bonus2\$) | 1d20+\$bonus1\$\n";
echo "Variables: bonus2=3 (bonus1 is missing)\n";
$result = $dice->roll('if $bonus1$ is null : (if $bonus2$ is null : 1d20 | 1d20+$bonus2$) | 1d20+$bonus1$', ['bonus2' => 3]);
echo "Result: " . $result->total . "\n";
echo "Explanation: Since bonus1 is null, check bonus2. Since bonus2 is 3, use 1d20+3\n\n";

// Example 8: Combining null checks with other conditionals
echo "Example 8: Combining null checks with comparisons\n";
echo "Expression: if \$level\$ is null : 1d6 | (if \$level\$ >= 5 : 2d6 | 1d6)\n";
echo "Variables: level=7\n";
$result = $dice->roll('if $level$ is null : 1d6 | (if $level$ >= 5 : 2d6 | 1d6)', ['level' => 7]);
echo "Result: " . $result->total . "\n";
echo "Explanation: Level is 7 (not null), so check if >= 5. Since true, roll 2d6\n\n";

echo "=== Use Cases ===\n\n";
echo "1. Optional Character Bonuses:\n";
echo "   Handle situational modifiers that may or may not apply\n";
echo "   Example: 1d20 + (if \$inspiration\$ is null : 0 | \$inspiration\$)\n\n";

echo "2. Conditional Mechanics:\n";
echo "   Apply different roll mechanics based on variable presence\n";
echo "   Example: if \$damage_bonus\$ is null : 1d6 | 1d6+\$damage_bonus\$\n\n";

echo "3. Default Values:\n";
echo "   Provide fallback values when variables aren't specified\n";
echo "   Example: if \$damage_bonus\$ is null : 0 | \$damage_bonus\$\n\n";

echo "4. Progressive Complexity:\n";
echo "   Different dice expressions based on character level\n";
echo "   Example: if \$level\$ is null : 1d6 | (if \$level\$ >= 5 : 2d6 | 1d6)\n\n";
