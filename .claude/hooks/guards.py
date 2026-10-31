#!/usr/bin/env python3
"""PreToolUse(Bash) guards for the dev database and for bot batches.

  dev_db_guard  destructive artisan commands (migrate:fresh, db:wipe, db:seed, game:reset-player, ...)
                must never hit the dev database `nouron`; allowed only on a throwaway DB
                (DB_DATABASE=... in the command, or the working directory's .env) or with the
                explicit marker NOURON_ALLOW_DEV_DB=1 in the command (owner's say-so).
  bot_guard     `artisan game:playtest` needs the owner's approval: a file
                `.claude/.bot-approved` no older than 3 hours (`touch` it after agreeing on a
                duration estimate).

Each guard runs in "warn" (message only) or "block" (exit 2) mode, set in guards.json.
"""

import json
import os
import re
import sys
import time

DEV_DB = "nouron"
DESTRUCTIVE = re.compile(r"artisan\s+(migrate:fresh|migrate:refresh|migrate:reset|db:wipe|db:seed|db:reset|game:reset-player)\b")
PLAYTEST = re.compile(r"artisan\s+game:playtest\b")
APPROVAL_MAX_AGE = 3 * 3600


def target_db(command, cwd):
    """Database name the command will use, or None if unknown."""
    m = re.search(r"\bDB_DATABASE=([\w\-]+)", command)
    if m:
        return m.group(1)
    m = re.search(r"\bcd\s+(\S+)", command)
    directory = m.group(1).strip("\"'") if m else cwd
    try:
        env = open(os.path.join(directory or ".", ".env"), encoding="utf-8").read()
    except OSError:
        return None
    m = re.search(r"^DB_DATABASE=([\w\-]+)", env, re.M)
    return m.group(1) if m else None


def dev_db_problem(command, cwd):
    if not DESTRUCTIVE.search(command) or "NOURON_ALLOW_DEV_DB=1" in command:
        return None
    db = target_db(command, cwd)
    if db == DEV_DB or db is None:
        what = "die Dev-Datenbank `nouron`" if db else "eine unbekannte Datenbank (keine .env gefunden)"
        return (f"Zerstörerischer Artisan-Befehl gegen {what}. Wegwerf-DB per `DB_DATABASE=nouron_x_unused` angeben "
                "oder den Owner um Freigabe bitten (dann `NOURON_ALLOW_DEV_DB=1` vor den Befehl).")
    return None


def bot_problem(command, approval_file, now=None):
    if not PLAYTEST.search(command):
        return None
    now = now or time.time()
    try:
        if now - os.path.getmtime(approval_file) <= APPROVAL_MAX_AGE:
            return None
    except OSError:
        pass
    return ("Bot-Lauf (`game:playtest`) braucht die Freigabe des Owners mit Dauerschätzung. "
            "Nach der Freigabe: `touch .claude/.bot-approved` (gilt 3 Stunden).")


def mode_of(guard, base):
    try:
        return json.load(open(os.path.join(base, ".claude/hooks/guards.json")))[guard]
    except (OSError, ValueError, KeyError):
        return "warn"


def main():
    try:
        data = json.load(sys.stdin)
    except (ValueError, OSError):
        return 0
    if data.get("tool_name") != "Bash":
        return 0
    command = data.get("tool_input", {}).get("command", "")
    base = os.environ.get("CLAUDE_PROJECT_DIR", os.getcwd())
    cwd = data.get("cwd") or base
    checks = [
        ("dev_db_guard", dev_db_problem(command, cwd)),
        ("bot_guard", bot_problem(command, os.path.join(base, ".claude/.bot-approved"))),
    ]
    blocked, warned = [], []
    for guard, problem in checks:
        if problem:
            (blocked if mode_of(guard, base) == "block" else warned).append(problem)
    if blocked:
        sys.stderr.write("BLOCKIERT: " + " ".join(blocked) + "\n")
        return 2
    if warned:
        msg = "Schutz-Hinweis: " + " ".join(warned)
        print(json.dumps({"hookSpecificOutput": {"hookEventName": "PreToolUse", "additionalContext": msg},
                          "systemMessage": msg}))
    return 0


if __name__ == "__main__":
    sys.exit(main())
