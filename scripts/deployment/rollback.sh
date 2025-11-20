#!/bin/bash

###############################################################################
# CMIS Rollback Script
#
# Usage: ./rollback.sh [commit_sha|HEAD~1]
#
# This script rolls back the application to a previous commit
###############################################################################

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
APP_DIR="/var/www/cmis"
BACKUP_DIR="/var/backups/cmis"
ROLLBACK_TARGET=${1:-HEAD~1}

# Functions
log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
success() { echo -e "${GREEN}✓${NC} $1"; }
error() { echo -e "${RED}✗${NC} $1"; }
warning() { echo -e "${YELLOW}⚠${NC} $1"; }

# Confirmation
warning "This will rollback the application to: $ROLLBACK_TARGET"
read -p "Are you sure you want to continue? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    log "Rollback cancelled"
    exit 0
fi

cd "$APP_DIR" || exit 1

# Get current state
CURRENT_COMMIT=$(git rev-parse --short HEAD)
log "Current commit: $CURRENT_COMMIT"

# Backup current database
log "Creating database backup before rollback..."
BACKUP_FILE="$BACKUP_DIR/cmis-rollback-backup-$(date +%Y%m%d-%H%M%S).sql"
docker-compose exec -T postgres pg_dump -U cmis -d cmis > "$BACKUP_FILE"
success "Database backed up to $BACKUP_FILE"

# Stop services
log "Stopping services..."
docker-compose down
success "Services stopped"

# Rollback code
log "Rolling back code to $ROLLBACK_TARGET..."
git reset --hard "$ROLLBACK_TARGET"
NEW_COMMIT=$(git rev-parse --short HEAD)
success "Code rolled back to commit: $NEW_COMMIT"

# Rebuild and restart
log "Rebuilding application..."
docker-compose build
success "Application rebuilt"

log "Starting services..."
docker-compose up -d
success "Services started"

# Wait for health
log "Waiting for services..."
sleep 15

# Run migrations (may need to rollback)
warning "You may need to manually rollback migrations if database schema changed"
log "Current database state preserved in: $BACKUP_FILE"

# Health check
if curl -f http://localhost/health > /dev/null 2>&1; then
    success "Health check passed"
else
    error "Health check failed - manual intervention required"
    exit 1
fi

log "========================================"
log "Rollback Summary:"
log "  From commit: $CURRENT_COMMIT"
log "  To commit:   $NEW_COMMIT"
log "  DB Backup:   $BACKUP_FILE"
log "========================================"

success "Rollback completed!"

exit 0
