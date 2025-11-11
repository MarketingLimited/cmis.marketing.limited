#!/bin/bash
# ============================================================================
# CMIS Refactoring Execution Script v2.2
# Purpose: Safely execute the refactoring with view-to-table conversion
# ============================================================================

set -e  # Exit on error

# Configuration
DB_NAME="${DB_NAME:-cmis}"
DB_USER="${DB_USER:-begin}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
WORK_DIR="${WORK_DIR:-/home/cmis/public_html/upgrade_database}"
BACKUP_DIR="${WORK_DIR}/backup"
LOG_DIR="${WORK_DIR}/logs"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create directories
mkdir -p "${BACKUP_DIR}"
mkdir -p "${LOG_DIR}"

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check PostgreSQL connection
check_connection() {
    log_info "Checking database connection..."
    
    if psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -c "SELECT 1" > /dev/null 2>&1; then
        log_info "Database connection successful"
        return 0
    else
        log_error "Cannot connect to database"
        return 1
    fi
}

# Create backup
create_backup() {
    log_info "Creating backup..."
    BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_v22_${TIMESTAMP}.dump"
    
    if pg_dump -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" \
              -F c -Z 9 -f "$BACKUP_FILE" 2>/dev/null; then
        log_info "Backup created: $BACKUP_FILE"
        echo "$BACKUP_FILE"
        return 0
    else
        log_error "Backup failed"
        return 1
    fi
}

# Check current state
check_current_state() {
    log_info "Checking current database state..."
    
    psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" << EOF
\echo 'Current state of key objects:'
\echo '-----------------------------'
SELECT 
    n.nspname as schema,
    c.relname as name,
    CASE c.relkind 
        WHEN 'r' THEN 'TABLE'
        WHEN 'v' THEN 'VIEW'
        ELSE c.relkind::text
    END as type
FROM pg_class c
JOIN pg_namespace n ON n.oid = c.relnamespace
WHERE n.nspname IN ('cmis', 'cmis_refactored')
    AND c.relname IN ('campaigns', 'integrations', 'orgs', 'users', 'creative_assets')
ORDER BY n.nspname, c.relname;
EOF
}

# Run refactoring
run_refactoring() {
    log_info "Starting refactoring process v2.2..."
    
    MIGRATION_LOG="${LOG_DIR}/refactoring_v22_${TIMESTAMP}.log"
    
    if psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" \
           -v ON_ERROR_STOP=1 \
           -f cmis_refactoring_v2.2.sql \
           2>&1 | tee "$MIGRATION_LOG"; then
        log_info "Refactoring completed successfully"
        return 0
    else
        log_error "Refactoring failed"
        log_error "Check log file: $MIGRATION_LOG"
        return 1
    fi
}

# Verify migration
verify_migration() {
    log_info "Running verification..."
    
    VERIFY_LOG="${LOG_DIR}/verify_v22_${TIMESTAMP}.log"
    
    if psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" \
           -f verify_refactoring_v2.2.sql \
           -o "$VERIFY_LOG" 2>&1; then
        
        # Check for critical failures in verification
        if grep -q "❌ FAILED" "$VERIFY_LOG"; then
            log_warn "Some verification checks failed:"
            grep "❌ FAILED" "$VERIFY_LOG"
            return 1
        else
            log_info "All verification checks passed"
            return 0
        fi
    else
        log_error "Verification script failed"
        return 1
    fi
}

# Rollback function
rollback() {
    log_error "Rolling back changes..."
    
    local backup_file="$1"
    
    if [ -z "$backup_file" ] || [ ! -f "$backup_file" ]; then
        log_error "Backup file not found: $backup_file"
        return 1
    fi
    
    # Drop current database
    dropdb -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" --if-exists "$DB_NAME"
    
    # Create new database
    createdb -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" "$DB_NAME"
    
    # Restore from backup
    if pg_restore -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" "$backup_file" 2>/dev/null; then
        log_info "Rollback completed successfully"
        return 0
    else
        log_error "Rollback failed"
        return 1
    fi
}

# Main execution
main() {
    echo "========================================"
    echo "CMIS Database Refactoring v2.2"
    echo "========================================"
    echo ""
    
    # Pre-flight checks
    if ! check_connection; then
        exit 1
    fi
    
    # Check current state
    check_current_state
    
    echo ""
    log_warn "This will convert views to tables and migrate schema!"
    read -p "Do you want to continue? (yes/no): " CONFIRM
    
    if [ "$CONFIRM" != "yes" ]; then
        log_info "Operation cancelled"
        exit 0
    fi
    
    # Create backup
    BACKUP_FILE=$(create_backup)
    if [ $? -ne 0 ]; then
        exit 1
    fi
    
    # Run refactoring
    if run_refactoring; then
        log_info "Refactoring completed"
        
        # Verify
        if verify_migration; then
            log_info "✅ Migration verified successfully!"
            echo ""
            echo "========================================"
            echo "✅ REFACTORING COMPLETED SUCCESSFULLY!"
            echo "========================================"
            echo ""
            echo "Backup saved as: $BACKUP_FILE"
            echo ""
            echo "Next steps:"
            echo "1. Test your application"
            echo "2. Monitor for any issues"
            echo "3. Keep the backup for at least 7 days"
        else
            log_warn "Verification found issues"
            
            read -p "Do you want to rollback? (yes/no): " ROLLBACK_CONFIRM
            
            if [ "$ROLLBACK_CONFIRM" = "yes" ]; then
                rollback "$BACKUP_FILE"
            fi
        fi
    else
        log_error "Refactoring failed!"
        
        read -p "Do you want to rollback? (yes/no): " ROLLBACK_CONFIRM
        
        if [ "$ROLLBACK_CONFIRM" = "yes" ]; then
            rollback "$BACKUP_FILE"
        else
            log_warn "Manual intervention required"
            log_warn "Backup available at: $BACKUP_FILE"
        fi
        
        exit 1
    fi
}

# Check for required files
if [ ! -f "cmis_refactoring_v2.2.sql" ]; then
    log_error "Required file missing: cmis_refactoring_v2.2.sql"
    exit 1
fi

if [ ! -f "verify_refactoring_v2.2.sql" ]; then
    log_error "Required file missing: verify_refactoring_v2.2.sql"
    exit 1
fi

# Run main function
main
