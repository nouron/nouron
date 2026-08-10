#!/usr/bin/env bash
#
# PreToolUse-Guard: schränkt Bash für Subagenten auf ein schmales Profil ein.
#
# Aufruf (aus .claude/agents/<agent>.md, Frontmatter-Hook):
#   bash .claude/hooks/bash-allowlist.sh <profil>
#
# Profile:
#   git-readonly  -> nur lesende git-Befehle (project-manager: CHANGELOG, PR-Body)
#   frontend      -> git-readonly + Prettier + npm build/install (ui-specialist)
#
# Exit-Codes (PreToolUse-Vertrag):
#   0  Befehl erlaubt
#   2  Befehl blockiert, Begründung auf stderr
#
# Tests: bash .claude/hooks/tests/bash-allowlist.test.sh

set -uo pipefail
set -f # kein Globbing beim Wort-Splitting der Segmente

PROFILE="${1:-}"
INPUT=$(cat)

# Nur Bash-Aufrufe prüfen — alles andere durchlassen.
TOOL=$(printf '%s' "$INPUT" | jq -r '.tool_name // empty')
[ "$TOOL" = "Bash" ] || exit 0

CMD=$(printf '%s' "$INPUT" | jq -r '.tool_input.command // empty')
[ -n "${CMD//[[:space:]]/}" ] || exit 0

GIT_READONLY="log diff show status blame shortlog describe rev-parse rev-list ls-files"
NPM_SCRIPTS="build"

case "$PROFILE" in
    git-readonly)
        ALLOWED_HINT="git ${GIT_READONLY// /|}"
        ;;
    frontend)
        ALLOWED_HINT="git ${GIT_READONLY// /|}, node_modules/.bin/prettier, npx prettier, npm run ${NPM_SCRIPTS// /|}, npm install|ci"
        ;;
    *)
        echo "bash-allowlist.sh: unbekanntes Profil '${PROFILE}' — erlaubt: git-readonly, frontend" >&2
        exit 2
        ;;
esac

deny() {
    printf 'Bash blockiert (Profil %s): %s\n' "$PROFILE" "$1" >&2
    printf 'Erlaubt sind nur: %s\n' "$ALLOWED_HINT" >&2
    printf 'Brauchst du mehr, gib den Befehl an den Aufrufer zurück statt ihn auszuführen.\n' >&2
    exit 2
}

# ── Shell-Konstrukte, die die Allowlist unterlaufen würden ───────────────────
case "$CMD" in
    *'$('*) deny 'Kommando-Substitution $(...)' ;;
    *'`'*) deny 'Kommando-Substitution `...`' ;;
    *'${'*) deny 'Variablen-Expansion ${...}' ;;
    *'>'*) deny 'Ausgabe-Umleitung (>)' ;;
    *'<'*) deny 'Eingabe-Umleitung (<)' ;;
esac

# ── In Einzelsegmente zerlegen; jedes muss für sich erlaubt sein ─────────────
# Reihenfolge wichtig: '||' vor '|', '&&' vor der '&'-Prüfung.
SEGMENTS="${CMD//&&/$'\n'}"
SEGMENTS="${SEGMENTS//||/$'\n'}"
SEGMENTS="${SEGMENTS//;/$'\n'}"
SEGMENTS="${SEGMENTS//|/$'\n'}"

case "$SEGMENTS" in
    *'&'*) deny 'Hintergrundausführung (&)' ;;
esac

check_segment() {
    local t1="$1" t2="${2:-}" t3="${3:-}"

    # VAR=wert vor dem Befehl kann Verhalten umbiegen (GIT_PAGER, PATH, ...)
    if [[ "$t1" =~ ^[A-Za-z_][A-Za-z0-9_]*= ]]; then
        deny "Env-Zuweisung vor dem Befehl: ${t1}"
    fi

    if [ "$t1" = "git" ]; then
        # Nur direkte Subkommandos — globale Optionen wie -c/--exec-path
        # können externe Programme starten und landen hier bewusst im deny.
        for sub in $GIT_READONLY; do
            [ "$t2" = "$sub" ] && return 0
        done
        deny "git-Subkommando nicht erlaubt: git ${t2:-<leer>}"
    fi

    if [ "$PROFILE" = "frontend" ]; then
        case "$t1" in
            node_modules/.bin/prettier | ./node_modules/.bin/prettier)
                return 0
                ;;
            npx)
                [ "$t2" = "prettier" ] && return 0
                deny "npx nur für prettier erlaubt, nicht: npx ${t2:-<leer>}"
                ;;
            npm)
                case "$t2" in
                    install | ci) return 0 ;;
                    run)
                        for script in $NPM_SCRIPTS; do
                            [ "$t3" = "$script" ] && return 0
                        done
                        deny "npm-Script nicht erlaubt: npm run ${t3:-<leer>}"
                        ;;
                esac
                deny "npm-Subkommando nicht erlaubt: npm ${t2:-<leer>}"
                ;;
        esac
    fi

    deny "Befehl nicht in der Allowlist: ${t1}"
}

while IFS= read -r segment; do
    # shellcheck disable=SC2086  # Wort-Splitting ist hier gewollt (set -f aktiv)
    set -- $segment
    [ $# -eq 0 ] && continue
    check_segment "$@"
done <<<"$SEGMENTS"

exit 0
