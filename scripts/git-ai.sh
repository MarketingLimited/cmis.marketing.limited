#!/usr/bin/env bash
set -euo pipefail

show_help() {
  cat <<'USAGE'
Usage: scripts/git-ai.sh [--env-file PATH] [git-args...]

Loads Git credentials from an env file (default: .env) and runs a git command.
If no git-args are supplied, `git status` is executed.

Environment variables required:
  GIT_USERNAME   Git user.name to configure.
  GIT_EMAIL      Git user.email to configure.
  GIT_TOKEN      Personal access token for HTTPS authentication.
  GIT_REPOSITORY HTTPS repository URL, e.g. https://github.com/org/repo.git

Example:
  scripts/git-ai.sh pull
  scripts/git-ai.sh commit -am "Update"
USAGE
}

ENV_FILE=".env"
POSITIONAL=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    --env-file)
      if [[ $# -lt 2 ]]; then
        echo "--env-file requires a path" >&2
        exit 1
      fi
      ENV_FILE="$2"
      shift 2
      ;;
    -h|--help)
      show_help
      exit 0
      ;;
    --)
      shift
      POSITIONAL+=("$@")
      break
      ;;
    *)
      POSITIONAL+=("$1")
      shift
      ;;
  esac
done

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Environment file '$ENV_FILE' not found" >&2
  exit 1
fi

# shellcheck source=/dev/null
set -o allexport
source "$ENV_FILE"
set +o allexport

missing_vars=()
for var in GIT_USERNAME GIT_EMAIL GIT_TOKEN GIT_REPOSITORY; do
  if [[ -z "${!var:-}" ]]; then
    missing_vars+=("$var")
  fi
done

if (( ${#missing_vars[@]} > 0 )); then
  printf 'Missing required variables: %s\n' "${missing_vars[*]}" >&2
  exit 1
fi

auth_repo="$GIT_REPOSITORY"
if [[ "$auth_repo" =~ ^https:// ]]; then
  without_scheme="${auth_repo#https://}"
  auth_repo="https://${GIT_USERNAME}:${GIT_TOKEN}@${without_scheme}"
fi

if git rev-parse --git-dir > /dev/null 2>&1; then
  git config user.name "$GIT_USERNAME"
  git config user.email "$GIT_EMAIL"

  if git remote get-url origin > /dev/null 2>&1; then
    git remote set-url origin "$auth_repo"
  else
    git remote add origin "$auth_repo"
  fi
else
  echo "This script must be run inside a Git repository" >&2
  exit 1
fi

if (( ${#POSITIONAL[@]} == 0 )); then
  set -- status
else
  set -- "${POSITIONAL[@]}"
fi

git "$@"
