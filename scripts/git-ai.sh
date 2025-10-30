#!/usr/bin/env bash
set -euo pipefail

show_help() {
  cat <<'USAGE'
Usage: scripts/git-ai.sh [--env-file PATH] <command> [args...]

Loads Git credentials from an env file (default: .env), configures Git, and executes
common Git workflows. If an unknown command is provided, the arguments are forwarded
directly to the `git` CLI.

Required environment variables:
  GIT_USERNAME    Git user.name to configure
  GIT_EMAIL       Git user.email to configure
  GIT_TOKEN       Personal access token used for HTTPS authentication
  GIT_REPOSITORY  HTTPS repository URL, e.g. https://github.com/org/repo.git

Supported commands:
  status                     Show the working tree status
  pull [args...]             Pull latest changes
  fetch [args...]            Fetch from origin
  add <paths...>             Stage files
  commit [args...]           Commit staged changes (pass -m "msg", etc.)
  push [args...]             Push to origin (defaults to `origin HEAD`)
  checkout <branch>          Checkout or create branches
  branch [args...]           Manage branches
  init [args...]             Initialize a new repository in the current directory
  merge <branch>             Merge branch into current branch
  rebase [args...]           Rebase current branch
  log [args...]              Show commit log
  diff [args...]             Show diffs
  tag [args...]              Manage tags
  reset [args...]            Reset current HEAD
  stash [args...]            Manage stashes
  clone [dir]                Clone the configured repository (can be run outside a repo)
  remote [args...]           Manage remotes (defaults to configured origin)
  help                       Show this help message

Examples:
  scripts/git-ai.sh status
  scripts/git-ai.sh add .
  scripts/git-ai.sh commit -m "Update"
  scripts/git-ai.sh push --force-with-lease
  scripts/git-ai.sh checkout feature/new-feature

USAGE
}

ENV_FILE="/httpdocs/.env"
COMMAND=""
ARGS=()

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
    -h|--help|help)
      show_help
      exit 0
      ;;
    *)
      COMMAND="$1"
      ARGS=("${@:2}")
      break
      ;;
    esac
  done

if [[ -z "$COMMAND" ]]; then
  COMMAND="status"
fi

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

configure_git() {
  if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "This command must be run inside a Git repository" >&2
    exit 1
  fi

  git config user.name "$GIT_USERNAME"
  git config user.email "$GIT_EMAIL"

  if git remote get-url origin > /dev/null 2>&1; then
    git remote set-url origin "$auth_repo"
  else
    git remote add origin "$auth_repo"
  fi
}

clone_repo() {
  local target_dir="${ARGS[0]:-}"
  if [[ -z "$target_dir" ]]; then
    git clone "$auth_repo"
  else
    git clone "$auth_repo" "$target_dir"
  fi
}

init_repo() {
  git init "${ARGS[@]}"
  configure_git
}

run_git_command() {
  configure_git
  git "$@"
}

case "$COMMAND" in
  clone)
    clone_repo
    ;;
  init)
    init_repo
    ;;
  status)
    run_git_command status "${ARGS[@]}"
    ;;
  pull)
    run_git_command pull "${ARGS[@]}"
    ;;
  fetch)
    run_git_command fetch "${ARGS[@]}"
    ;;
  add)
    if (( ${#ARGS[@]} == 0 )); then
      echo "Usage: scripts/git-ai.sh add <paths...>" >&2
      exit 1
    fi
    run_git_command add "${ARGS[@]}"
    ;;
  commit)
    run_git_command commit "${ARGS[@]}"
    ;;
  push)
    if (( ${#ARGS[@]} == 0 )); then
      run_git_command push origin HEAD
    else
      run_git_command push "${ARGS[@]}"
    fi
    ;;
  checkout)
    if (( ${#ARGS[@]} == 0 )); then
      echo "Usage: scripts/git-ai.sh checkout <branch>" >&2
      exit 1
    fi
    run_git_command checkout "${ARGS[@]}"
    ;;
  branch)
    run_git_command branch "${ARGS[@]}"
    ;;
  merge)
    if (( ${#ARGS[@]} == 0 )); then
      echo "Usage: scripts/git-ai.sh merge <branch>" >&2
      exit 1
    fi
    run_git_command merge "${ARGS[@]}"
    ;;
  rebase)
    run_git_command rebase "${ARGS[@]}"
    ;;
  log)
    run_git_command log "${ARGS[@]}"
    ;;
  diff)
    run_git_command diff "${ARGS[@]}"
    ;;
  tag)
    run_git_command tag "${ARGS[@]}"
    ;;
  reset)
    run_git_command reset "${ARGS[@]}"
    ;;
  stash)
    run_git_command stash "${ARGS[@]}"
    ;;
  remote)
    run_git_command remote "${ARGS[@]}"
    ;;
  *)
    run_git_command "$COMMAND" "${ARGS[@]}"
    ;;
esac
