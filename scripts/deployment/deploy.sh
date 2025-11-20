#!/bin/bash

###############################################################################
# CMIS Deployment Script
#
# Usage: ./deploy.sh [staging|production]
#
# This script automates the deployment process for CMIS application
###############################################################################

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Environment argument
ENVIRONMENT=${1:-staging}

# Configuration
APP_DIR="/var/www/cmis"
BACKUP_DIR="/var/backups/cmis"
LOG_FILE="/var/log/cmis-deploy-$(date +%Y%m%d-%H%M%S).log"

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✓${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}✗${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠${NC} $1" | tee -a "$LOG_FILE"
}

# Validate environment
if [[ ! "$ENVIRONMENT" =~ ^(staging|production)$ ]]; then
    error "Invalid environment. Use: staging or production"
    exit 1
fi

log "Starting deployment to $ENVIRONMENT..."

# Pre-deployment checks
log "Running pre-deployment checks..."

# Check if running as correct user
if [[ $EUID -eq 0 ]]; then
   error "This script should not be run as root for security reasons"
   exit 1
fi

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    error "Docker is not running"
    exit 1
fi

success "Pre-deployment checks passed"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Navigate to application directory
cd "$APP_DIR" || exit 1

# Backup database
log "Creating database backup..."
BACKUP_FILE="$BACKUP_DIR/cmis-db-backup-$(date +%Y%m%d-%H%M%S).sql"
docker-compose exec -T postgres pg_dump -U cmis -d cmis > "$BACKUP_FILE" 2>/dev/null || warning "Database backup failed (may not exist yet)"
if [[ -f "$BACKUP_FILE" && -s "$BACKUP_FILE" ]]; then
    success "Database backed up to $BACKUP_FILE"
else
    warning "No database to backup (fresh installation?)"
fi

# Pull latest code
log "Pulling latest code from Git..."
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
CURRENT_COMMIT=$(git rev-parse --short HEAD)

if [[ "$ENVIRONMENT" == "production" ]]; then
    DEPLOY_BRANCH="main"
else
    DEPLOY_BRANCH="develop"
fi

log "Current branch: $CURRENT_BRANCH ($CURRENT_COMMIT)"
log "Deploying from: $DEPLOY_BRANCH"

git fetch origin
git checkout "$DEPLOY_BRANCH"
git pull origin "$DEPLOY_BRANCH"

NEW_COMMIT=$(git rev-parse --short HEAD)
success "Updated to commit: $NEW_COMMIT"

# Stop services gracefully
log "Stopping services..."
docker-compose down
success "Services stopped"

# Pull latest Docker images
log "Pulling latest Docker images..."
docker-compose pull
success "Docker images updated"

# Build application
log "Building application..."
docker-compose build --build-arg BUILD_DATE=$(date -u +'%Y-%m-%dT%H:%M:%SZ') --build-arg VCS_REF=$NEW_COMMIT
success "Application built"

# Start services
log "Starting services..."
docker-compose up -d
success "Services started"

# Wait for services to be healthy
log "Waiting for services to be healthy..."
sleep 10

# Check health
for i in {1..30}; do
    if docker-compose ps | grep -q "healthy"; then
        break
    fi
    echo -n "."
    sleep 2
done
echo ""

# Run database migrations
log "Running database migrations..."
docker-compose exec -T app php artisan migrate --force
success "Migrations completed"

# Clear and optimize caches
log "Optimizing application..."
docker-compose exec -T app php artisan optimize
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
success "Application optimized"

# Restart queue workers
log "Restarting queue workers..."
docker-compose exec -T app php artisan queue:restart
success "Queue workers restarted"

# Run health check
log "Running health check..."
sleep 5
if curl -f http://localhost/health > /dev/null 2>&1; then
    success "Health check passed"
else
    error "Health check failed!"
    warning "Application may not be running correctly"
fi

# Deployment summary
log "========================================"
log "Deployment Summary:"
log "  Environment: $ENVIRONMENT"
log "  From commit: $CURRENT_COMMIT"
log "  To commit:   $NEW_COMMIT"
log "  Backup: $BACKUP_FILE"
log "  Log file: $LOG_FILE"
log "========================================"

success "Deployment completed successfully!"

# Optional: Send notification (implement as needed)
# curl -X POST https://slack.com/api/chat.postMessage ...

exit 0
