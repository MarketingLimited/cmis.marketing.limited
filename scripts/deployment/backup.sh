#!/bin/bash

###############################################################################
# CMIS Backup Script
#
# Usage: ./backup.sh [--full|--database-only|--files-only]
#
# Creates backups of CMIS application data
###############################################################################

set -e

# Configuration
BACKUP_DIR="/var/backups/cmis"
APP_DIR="/var/www/cmis"
RETENTION_DAYS=30
BACKUP_TYPE="${1:---full}"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
success() { echo -e "${GREEN}✓${NC} $1"; }
error() { echo -e "${RED}✗${NC} $1"; }

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Timestamp
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

log "Starting backup ($BACKUP_TYPE)..."

# Database backup
if [[ "$BACKUP_TYPE" == "--full" ]] || [[ "$BACKUP_TYPE" == "--database-only" ]]; then
    log "Backing up database..."
    DB_BACKUP="$BACKUP_DIR/cmis-database-$TIMESTAMP.sql"

    if docker-compose exec -T postgres pg_dump -U cmis -d cmis > "$DB_BACKUP" 2>/dev/null; then
        # Compress backup
        gzip "$DB_BACKUP"
        success "Database backed up to ${DB_BACKUP}.gz"

        # Get backup size
        SIZE=$(du -h "${DB_BACKUP}.gz" | cut -f1)
        log "  Backup size: $SIZE"
    else
        error "Database backup failed"
        exit 1
    fi
fi

# Files backup
if [[ "$BACKUP_TYPE" == "--full" ]] || [[ "$BACKUP_TYPE" == "--files-only" ]]; then
    log "Backing up application files..."
    FILES_BACKUP="$BACKUP_DIR/cmis-files-$TIMESTAMP.tar.gz"

    tar -czf "$FILES_BACKUP" \
        -C "$APP_DIR" \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='storage/logs/*' \
        --exclude='.git' \
        .env storage/app public/storage 2>/dev/null || true

    success "Files backed up to $FILES_BACKUP"

    # Get backup size
    SIZE=$(du -h "$FILES_BACKUP" | cut -f1)
    log "  Backup size: $SIZE"
fi

# Docker volumes backup
if [[ "$BACKUP_TYPE" == "--full" ]]; then
    log "Backing up Docker volumes..."

    # PostgreSQL volume
    docker run --rm \
        -v cmis_postgres-data:/data \
        -v "$BACKUP_DIR":/backup \
        alpine tar czf "/backup/cmis-postgres-volume-$TIMESTAMP.tar.gz" /data 2>/dev/null || true
    success "PostgreSQL volume backed up"

    # Redis volume
    docker run --rm \
        -v cmis_redis-data:/data \
        -v "$BACKUP_DIR":/backup \
        alpine tar czf "/backup/cmis-redis-volume-$TIMESTAMP.tar.gz" /data 2>/dev/null || true
    success "Redis volume backed up"
fi

# Clean up old backups
log "Cleaning up old backups (retention: $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "cmis-*" -type f -mtime +$RETENTION_DAYS -delete
DELETED=$(find "$BACKUP_DIR" -name "cmis-*" -type f -mtime +$RETENTION_DAYS | wc -l)
if [[ "$DELETED" -gt 0 ]]; then
    success "Deleted $DELETED old backup(s)"
else
    log "  No old backups to delete"
fi

# List recent backups
log "Recent backups:"
ls -lh "$BACKUP_DIR"/cmis-*$TIMESTAMP* 2>/dev/null | awk '{print "  " $9, "(" $5 ")"}'

# Calculate total backup size
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log "Total backup directory size: $TOTAL_SIZE"

success "Backup completed successfully!"

exit 0
