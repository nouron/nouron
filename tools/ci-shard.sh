#!/usr/bin/env bash
# Writes phpunit.shard.xml: the test files of one CI shard as test suite "shard".
# Usage: tools/ci-shard.sh <suite> <shard> <total>   (suite: default | playtest; shard counts from 1)
set -euo pipefail

suite=$1 shard=$2 total=$3

if [ "$suite" = playtest ]; then
    all=$(find tests/Feature/Playtest -name '*Test.php' | LC_ALL=C sort)
else
    all=$(find tests/Feature tests/Unit -name '*Test.php' -not -path 'tests/Feature/Playtest/*' | LC_ALL=C sort)
fi

{
    sed '/<testsuites>/q' phpunit.xml
    echo '<testsuite name="shard">'
    awk -v s="$shard" -v t="$total" '(NR - 1) % t == s - 1 { print "<file>./" $0 "</file>" }' <<< "$all"
    echo '</testsuite>'
    sed -n '/<\/testsuites>/,$p' phpunit.xml
} > phpunit.shard.xml
