#!/bin/bash
set -e

echo "Running D&D 5e examples..."
php examples/dnd5e.php > /dev/null || { echo "ERROR: dnd5e.php failed"; exit 1; }

echo "Running Shadowrun examples..."
php examples/shadowrun.php > /dev/null || { echo "ERROR: shadowrun.php failed"; exit 1; }

echo "Running Savage Worlds examples..."
php examples/savage-worlds.php > /dev/null || { echo "ERROR: savage-worlds.php failed"; exit 1; }

echo "Running FATE examples..."
php examples/fate.php > /dev/null || { echo "ERROR: fate.php failed"; exit 1; }

echo "Running Call of Cthulhu examples..."
php examples/call-of-cthulhu.php > /dev/null || { echo "ERROR: call-of-cthulhu.php failed"; exit 1; }

echo "✓ All example scripts executed successfully"
