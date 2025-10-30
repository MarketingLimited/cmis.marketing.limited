#!/bin/bash
set -x

# =========================================
# git-ai.sh — CMIS Git Orchestrator
# -----------------------------------------
# يدير عمليات Git والتحديثات التشغيلية في بيئة Plesk.
# =========================================

ENV_FILE="/httpdocs/.env"
if [ ! -f "$ENV_FILE" ]; then
  echo "❌ Environment file not found at $ENV_FILE"
  exit 1
fi

# تحميل متغيرات البيئة
set -a
source "$ENV_FILE"
set +a

COMMAND="$1"

# تحديد دالة الطباعة الملونة
function info()  { echo -e "\033[1;34m[INFO]\033[0m $1"; }
function ok()    { echo -e "\033[1;32m[OK]\033[0m $1"; }
function warn()  { echo -e "\033[1;33m[WARN]\033[0m $1"; }
function error() { echo -e "\033[1;31m[ERROR]\033[0m $1"; }

# التحقق من وجود Git
if ! command -v git &> /dev/null; then
  error "Git not installed."
  exit 1
fi

# الانتقال إلى مجلد المشروع
cd /httpdocs || exit 1

case "$COMMAND" in
  pull)
    info "Pulling latest changes from GitHub..."
    git pull origin main && ok "Repository updated successfully."
    ;;

  push)
    info "Pushing local changes to GitHub..."
    git add . && git commit -m "Auto-sync from server" && git push origin main && ok "Changes pushed successfully."
    ;;

  status)
    git status
    ;;

  deploy)
    info "Deploying latest code..."
    git pull origin main && ok "Code updated."
    info "Running Laravel optimizations..."
    /opt/plesk/php/8.3/bin/php artisan migrate --force
    /opt/plesk/php/8.3/bin/php artisan cache:clear
    /opt/plesk/php/8.3/bin/php artisan config:cache
    ok "Deployment completed successfully."
    ;;

  sync)
    info "Synchronizing changes with GitHub..."
    git add . && git commit -m "Sync from production server" && git pull origin main --rebase && git push origin main
    ok "Sync completed successfully."
    ;;

  rollback)
    info "Rolling back to previous commit..."
    git reset --hard HEAD~1 && git pull origin main
    ok "Rollback completed."
    ;;

  backup)
    info "Creating backup of /httpdocs directory..."
    BACKUP_DIR=$(/bin/date +"/httpdocs/backups/%Y-%m-%d_%H-%M-%S")
    mkdir -p "$BACKUP_DIR"
    TMP_FILE="/tmp/backup_httpdocs_$(/bin/date +%Y-%m-%d_%H-%M-%S).tar.gz"
    tar --exclude=/httpdocs/backups -czf "$TMP_FILE" -C / httpdocs
    mv "$TMP_FILE" "$BACKUP_DIR/backup_httpdocs.tar.gz"
    ok "Backup created at $BACKUP_DIR/backup_httpdocs.tar.gz"
    find /httpdocs/backups -maxdepth 1 -type d -mtime +7 -exec rm -rf {} \; 2>/dev/null
    info "Old backups (older than 7 days) have been cleaned up."
    ;;

  help|--help|-h|*)
    echo "Available commands:"
    echo "  pull       - Fetch and merge latest changes from GitHub"
    echo "  push       - Commit and push local changes to GitHub"
    echo "  status     - Show repository status"
    echo "  deploy     - Pull latest code and run Laravel optimizations"
    echo "  sync       - Commit, rebase, and push local changes"
    echo "  rollback   - Revert to previous commit"
    echo "  backup     - Create a compressed backup of /httpdocs"
    echo "  help       - Show this help message"
    ;;
esac
