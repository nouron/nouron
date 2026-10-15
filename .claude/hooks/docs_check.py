#!/usr/bin/env python3
"""Doc-freshness checks (CHANGELOG, game-reference hard; GDD, ROADMAP warn).

Modes (hook input JSON arrives on stdin):
  merge  PreToolUse on mcp__github__merge_pull_request and Bash(`gh pr merge`)
         -> exit 2 + stderr blocks the merge; warnings go out as additionalContext.
  stop   Stop hook -> reminds once (stop_hook_active guard) when the hard rules are violated.

Rules (diff = PR diff for merge, branch-vs-master + working tree for stop):
  hard  any app/, config/, lang/, resources/, public/, database/ change needs CHANGELOG.md in the diff
  hard  config/{game,buildings,advisors,ships,missions,knowledge}.php needs docs/game-reference.md in the diff,
        or the PR body says "game-reference: nicht nötig" (merge mode only)
  warn  mechanic-relevant change (app/, config/) without docs/GDD.md or ROADMAP.md in the diff
"""

import datetime
import json
import re
import subprocess
import sys

CODE = ("app/", "config/", "lang/", "resources/", "public/", "database/")
REF_CONFIGS = {f"config/{n}.php" for n in ("game", "buildings", "advisors", "ships", "missions", "knowledge")}
SKIP_MARK = "game-reference: nicht nötig"


def run(*cmd):
    r = subprocess.run(cmd, capture_output=True, text=True)
    return r.returncode, r.stdout.strip()


def changed_files(pr):
    if pr is not None:
        rc, out = run("gh", "pr", "diff", str(pr), "--name-only")
    else:
        rc, out = run("gh", "pr", "diff", "--name-only")
    if rc == 0 and out:
        return set(out.splitlines())
    # No PR (stop mode / offline): branch vs master plus uncommitted tracked changes.
    rc, base = run("git", "merge-base", "HEAD", "master")
    if rc != 0:
        return set()
    _, out = run("git", "diff", "--name-only", base)
    _, untracked = run("git", "ls-files", "--others", "--exclude-standard")
    return set(out.splitlines()) | set(untracked.splitlines())


def evaluate(files, pr_body=""):
    code = {f for f in files if f.startswith(CODE)}
    hard, warn = [], []
    if not code:
        return hard, warn
    if "CHANGELOG.md" not in files:
        hard.append("CHANGELOG.md ist nicht im Diff (Code/Config/Lang geändert)")
    if REF_CONFIGS & files and "docs/game-reference.md" not in files and SKIP_MARK not in pr_body:
        hard.append(
            "Config geändert ("
            + ", ".join(sorted(REF_CONFIGS & files))
            + ") aber docs/game-reference.md nicht — oder im PR-Text \"%s\" vermerken" % SKIP_MARK
        )
    if any(f.startswith(("app/", "config/")) for f in code) and not ({"docs/GDD.md", "ROADMAP.md"} & files):
        warn.append("Mechanik-relevante Änderung ohne GDD.md oder ROADMAP.md im Diff — Doku-Matrix in CLAUDE.md prüfen")
    return hard, warn


def merge_mode(data):
    ti = data.get("tool_input", {})
    pr = ti.get("pull_number") or ti.get("pullNumber")
    if data.get("tool_name") == "Bash":
        m = re.search(r"\bgh\s+pr\s+merge\b(?:\s+(\d+))?", ti.get("command", ""))
        if not m:
            return 0  # some other Bash call
        pr = m.group(1)
    body = ""
    rc, out = run("gh", "pr", "view", *( [str(pr)] if pr else [] ), "--json", "body")
    if rc == 0:
        body = json.loads(out).get("body", "")
    hard, warn = evaluate(changed_files(pr), body)

    # Existing rule: an entry for today in the changelog.
    today = datetime.date.today().isoformat()
    try:
        if f"## {today}" not in open("CHANGELOG.md", encoding="utf-8").read():
            hard.append(f"CHANGELOG.md hat keinen Eintrag für heute (## {today})")
    except OSError:
        pass

    if hard:
        sys.stderr.write("MERGE BLOCKIERT — folgende Punkte fehlen noch:\n" + "".join(f"  • {h}\n" for h in hard))
        if warn:
            sys.stderr.write("Außerdem (Warnung): " + "; ".join(warn) + "\n")
        return 2
    if warn:
        print(json.dumps({"hookSpecificOutput": {"hookEventName": "PreToolUse", "additionalContext": "Doku-Warnung: " + "; ".join(warn)},
                          "systemMessage": "Doku-Warnung: " + "; ".join(warn)}))
    return 0


def stop_mode(data):
    if data.get("stop_hook_active"):
        return 0  # already reminded once in this stop cycle
    _, branch = run("git", "rev-parse", "--abbrev-ref", "HEAD")
    if branch in ("master", "main", "HEAD"):
        return 0
    hard, _ = evaluate(changed_files(None))
    if hard:  # warnings stay merge-time only; the stop reminder is for the hard rules
        print(json.dumps({"decision": "block", "reason": "Doku-Check vor dem Abschluss: " + "; ".join(hard)
                          + ". Nachziehen oder kurz begründen, warum nicht nötig."}))
    return 0


if __name__ == "__main__":
    mode = sys.argv[1] if len(sys.argv) > 1 else "merge"
    try:
        payload = json.load(sys.stdin)
    except (ValueError, OSError):
        payload = {}
    sys.exit(merge_mode(payload) if mode == "merge" else stop_mode(payload))
