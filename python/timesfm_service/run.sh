#!/usr/bin/env bash
# Dev startup — uses venv if present, otherwise system Python + ~/.local user packages.
set -euo pipefail

cd "$(dirname "$0")"

if [ -f venv/bin/activate ]; then
    # shellcheck disable=SC1091
    source venv/bin/activate
fi

if [ ! -f .env ]; then
    echo ".env not found — copy .env.example and adjust." >&2
    exit 1
fi

HOST=$(grep -E '^HOST=' .env | head -1 | cut -d= -f2 | tr -d '"' || echo "127.0.0.1")
PORT=$(grep -E '^PORT=' .env | head -1 | cut -d= -f2 | tr -d '"' || echo "8081")

# Prefer venv uvicorn; fallback to user-installed (~/.local/bin) or python3 -m uvicorn
if command -v uvicorn >/dev/null 2>&1; then
    UVICORN=uvicorn
elif [ -x "$HOME/.local/bin/uvicorn" ]; then
    UVICORN="$HOME/.local/bin/uvicorn"
else
    UVICORN="python3 -m uvicorn"
fi

exec $UVICORN main:app \
    --host "${HOST:-127.0.0.1}" \
    --port "${PORT:-8081}" \
    --workers 1 \
    --log-level info
