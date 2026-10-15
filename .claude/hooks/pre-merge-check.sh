#!/usr/bin/env bash
# Pre-merge checklist (CHANGELOG + game-reference hard, GDD/ROADMAP warn) — see docs_check.py.
exec python3 "$(dirname "$0")/docs_check.py" merge
