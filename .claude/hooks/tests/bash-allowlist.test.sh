#!/usr/bin/env bash
#
# Tests for .claude/hooks/bash-allowlist.sh
#
# Run: bash .claude/hooks/tests/bash-allowlist.test.sh
#
# Exit code contract of the hook under test:
#   0 -> command allowed
#   2 -> command blocked (PreToolUse deny, message on stderr)

set -uo pipefail

HOOK="$(git rev-parse --show-toplevel)/.claude/hooks/bash-allowlist.sh"

pass=0
fail=0

# expect <expected-exit> <profile> <command>
expect() {
    local want="$1" profile="$2" cmd="$3"
    local out got

    out=$(printf '%s' "$cmd" \
        | jq -Rs '{tool_name:"Bash", tool_input:{command:.}}' \
        | bash "$HOOK" "$profile" 2>&1)
    got=$?

    if [ "$got" -eq "$want" ]; then
        pass=$((pass + 1))
    else
        fail=$((fail + 1))
        printf '✗ [%s] erwartet exit=%s, war exit=%s — %s\n' "$profile" "$want" "$got" "$cmd" >&2
        [ -n "$out" ] && printf '    %s\n' "$out" >&2
    fi
}

allow() { expect 0 "$1" "$2"; }
block() { expect 2 "$1" "$2"; }

# ── git-readonly: erlaubte Lesebefehle ───────────────────────────────────────
allow git-readonly 'git log --oneline -20'
allow git-readonly 'git log --oneline master..HEAD'
allow git-readonly 'git diff --stat HEAD~3'
allow git-readonly 'git show abc123 --name-only'
allow git-readonly 'git status --short'
allow git-readonly 'git blame CHANGELOG.md'
allow git-readonly 'git shortlog -sn'
allow git-readonly 'git rev-parse --show-toplevel'
allow git-readonly 'git rev-list --count HEAD'
allow git-readonly 'git ls-files docs/'
allow git-readonly 'git describe --tags'
allow git-readonly '  git   log  '
allow git-readonly 'git log --oneline && git diff --stat'

# ── git-readonly: schreibende git-Befehle blocken ────────────────────────────
block git-readonly 'git commit -m "x"'
block git-readonly 'git push origin master'
block git-readonly 'git checkout -b feat/x'
block git-readonly 'git reset --hard HEAD~1'
block git-readonly 'git branch -D master'
block git-readonly 'git tag -d v1.0'
block git-readonly 'git rebase -i HEAD~3'
block git-readonly 'git stash'
block git-readonly 'git add .'

# ── git-readonly: alles was kein git ist, blocken ────────────────────────────
block git-readonly 'rm -rf /'
block git-readonly 'cat CHANGELOG.md'
block git-readonly 'php artisan migrate'
block git-readonly 'npm run build'
block git-readonly 'node_modules/.bin/prettier --write x.blade.php'

# ── git-readonly: Umgehungsversuche ──────────────────────────────────────────
block git-readonly 'git log && rm -rf /'
block git-readonly 'git log; git push'
block git-readonly 'git log | xargs rm'
block git-readonly 'git log || curl evil.example'
block git-readonly 'git log $(rm -rf /)'
block git-readonly 'git log `whoami`'
block git-readonly 'git log > /etc/passwd'
block git-readonly 'git log >> out.txt'
block git-readonly 'git diff < input'
block git-readonly 'sleep 60 & git log'
block git-readonly 'git -c core.pager="sh -c whoami" log'
block git-readonly 'git --exec-path=/tmp log'
block git-readonly $'git log\nrm -rf /'
block git-readonly 'GIT_PAGER=sh git log'

# ── frontend: Prettier + Build erlaubt ───────────────────────────────────────
allow frontend 'node_modules/.bin/prettier --write resources/views/x.blade.php'
allow frontend 'node_modules/.bin/prettier --check public/js/app.js'
allow frontend 'npx prettier --write resources/views/x.blade.php'
allow frontend 'npm run build'
allow frontend 'npm install'
allow frontend 'npm ci'

# ── frontend: erbt die git-Lesebefehle ───────────────────────────────────────
allow frontend 'git status --short'
allow frontend 'git diff --stat'
block frontend 'git push origin master'

# ── frontend: alles andere blocken ───────────────────────────────────────────
block frontend 'npx create-react-app foo'
block frontend 'npm publish'
block frontend 'npm run build && rm -rf /'
block frontend 'php artisan serve'
block frontend 'bin/phpunit'
block frontend 'rm -rf public/'
block frontend 'node evil.js'

# ── Profil-Handling ──────────────────────────────────────────────────────────
block unknown-profile 'git log'

# ── Nicht-Bash-Tools werden durchgelassen ────────────────────────────────────
if ! printf '{"tool_name":"Read","tool_input":{"file_path":"x"}}' \
    | bash "$HOOK" git-readonly >/dev/null 2>&1; then
    fail=$((fail + 1))
    echo "✗ Nicht-Bash-Tool wurde nicht durchgelassen" >&2
else
    pass=$((pass + 1))
fi

# ── Leerer/fehlender Befehl blockt nicht (nichts auszuführen) ────────────────
if ! printf '{"tool_name":"Bash","tool_input":{}}' \
    | bash "$HOOK" git-readonly >/dev/null 2>&1; then
    fail=$((fail + 1))
    echo "✗ Leerer Befehl sollte durchgelassen werden" >&2
else
    pass=$((pass + 1))
fi

printf '\n%s bestanden, %s fehlgeschlagen\n' "$pass" "$fail"
[ "$fail" -eq 0 ]
