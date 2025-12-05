# Organization Backup & Restore Application

**Date:** 2025-12-05
**Author:** Claude Code Agent
**App ID:** `org-backup-restore`
**Category:** Administration Tools

## Summary

A comprehensive backup and restore system for CMIS organizations, supporting manual and scheduled backups, selective/full restores with conflict resolution, plan-based limits, and cloud storage options.

## Features

### Backup Capabilities
- **Manual Backups**: On-demand organization data backup
- **Scheduled Backups**: Hourly, daily, weekly, or monthly schedules
- **Category Selection**: Backup specific data categories
- **Cloud Storage**: Support for local, Google Drive, OneDrive, and Dropbox
- **Encryption**: AES-256-GCM encryption with custom keys (Enterprise)
- **Checksum Verification**: SHA-256 checksums for integrity

### Restore Capabilities
- **Selective Restore**: Choose specific categories to restore
- **Full Restore**: Complete organization data replacement
- **Merge Restore**: Merge backup data with existing data
- **Conflict Resolution**: Skip, replace, merge, or per-record decisions
- **Schema Reconciliation**: Validate backup compatibility with current schema
- **Rollback Support**: 24-hour rollback window after restore

### Plan-Based Limits
| Plan | Monthly Backups | Max Size | Retention | Schedules |
|------|-----------------|----------|-----------|-----------|
| Free | 2 | 500 MB | 7 days | - |
| Basic | 10 | 5 GB | 30 days | Daily |
| Pro | Unlimited | 50 GB | 90 days | All |
| Enterprise | Unlimited | 500 GB | 365 days | All + Custom |

## Database Tables

All tables in `cmis` schema with RLS policies:

| Table | Purpose |
|-------|---------|
| `organization_backups` | Backup records and metadata |
| `backup_schedules` | Scheduled backup configurations |
| `backup_restores` | Restore operation records |
| `backup_audit_logs` | Audit trail for all operations |
| `backup_settings` | Per-organization notification settings |
| `backup_encryption_keys` | Custom encryption keys (Enterprise) |

## File Structure

```
app/
├── Models/Backup/
│   ├── OrganizationBackup.php
│   ├── BackupSchedule.php
│   ├── BackupRestore.php
│   ├── BackupAuditLog.php
│   ├── BackupSetting.php
│   └── BackupEncryptionKey.php
├── Apps/Backup/Services/
│   ├── BackupOrchestrator.php
│   ├── Discovery/
│   │   ├── SchemaDiscoveryService.php
│   │   ├── FileDiscoveryService.php
│   │   └── DependencyResolver.php
│   ├── Extraction/
│   │   ├── DataExtractorService.php
│   │   ├── FileCollectorService.php
│   │   └── ChunkedExtractor.php
│   ├── Packaging/
│   │   ├── BackupPackagerService.php
│   │   ├── BackupEncryptionService.php
│   │   └── ChecksumService.php
│   ├── Export/
│   │   └── ExportMapperService.php
│   ├── Restore/
│   │   ├── RestoreOrchestrator.php
│   │   ├── SchemaReconcilerService.php
│   │   ├── ConflictResolverService.php
│   │   ├── RestoreExecutorService.php
│   │   └── RollbackService.php
│   └── Limits/
│       └── PlanLimitsService.php
├── Http/Controllers/Backup/
│   ├── BackupController.php
│   ├── RestoreController.php
│   ├── BackupScheduleController.php
│   ├── BackupSettingsController.php
│   └── BackupAuditController.php
├── Jobs/Backup/
│   ├── ProcessBackupJob.php
│   ├── ProcessRestoreJob.php
│   ├── ScheduledBackupJob.php
│   ├── CleanupExpiredBackupsJob.php
│   └── SendBackupNotificationJob.php
└── Notifications/Backup/
    ├── BackupCompletedNotification.php
    ├── BackupFailedNotification.php
    ├── RestoreStartedNotification.php
    ├── RestoreCompletedNotification.php
    ├── RestoreFailedNotification.php
    ├── RestoreRolledBackNotification.php
    ├── BackupExpiringNotification.php
    └── StorageQuotaWarningNotification.php

resources/views/apps/backup/
├── index.blade.php          # Dashboard
├── create.blade.php         # Create backup
├── show.blade.php           # Backup details
├── progress.blade.php       # Progress tracking
├── restore/
│   ├── index.blade.php      # Restore list
│   ├── upload.blade.php     # External upload
│   ├── analyze.blade.php    # Compatibility analysis
│   ├── select.blade.php     # Category selection
│   ├── conflicts.blade.php  # Conflict resolution
│   ├── confirm.blade.php    # Multi-level confirmation
│   └── progress.blade.php   # Restore progress
├── schedule/
│   └── index.blade.php      # Schedule management
├── settings/
│   └── index.blade.php      # Notification settings
└── logs/
    └── index.blade.php      # Audit logs

tests/Feature/Backup/
├── BackupCreationTest.php
├── BackupDownloadTest.php
├── RestoreWorkflowTest.php
├── ConflictResolutionTest.php
├── ScheduleManagementTest.php
├── PlanLimitsTest.php
├── ApiEndpointsTest.php
├── SchemaDiscoveryTest.php
└── MultiTenancyTest.php
```

## Routes

### Web Routes (authenticated)
- `GET /{org}/backup` - Dashboard
- `GET/POST /{org}/backup/create` - Create backup
- `GET /{org}/backup/{backup}` - Backup details
- `GET /{org}/backup/{backup}/download` - Download backup
- `GET /{org}/backup/{backup}/progress` - Progress tracking
- `DELETE /{org}/backup/{backup}` - Delete backup

### Restore Routes
- `GET /{org}/backup/restore` - Restore selection
- `GET/POST /{org}/backup/restore/upload` - External upload
- `GET /{org}/backup/restore/{backup}/analyze` - Analyze compatibility
- `GET/POST /{org}/backup/restore/{backup}/select` - Select categories
- `GET/POST /{org}/backup/restore/{restore}/conflicts` - Resolve conflicts
- `GET /{org}/backup/restore/{restore}/confirm` - Confirm restore
- `POST /{org}/backup/restore/{restore}/process` - Start restore
- `POST /{org}/backup/restore/{restore}/rollback` - Rollback

### API Routes (v1)
- `POST /api/v1/backup/create`
- `GET /api/v1/backup/list`
- `GET /api/v1/backup/{id}`
- `GET /api/v1/backup/{id}/download`
- `DELETE /api/v1/backup/{id}`
- `POST /api/v1/restore/analyze`
- `POST /api/v1/restore/start`
- `GET /api/v1/restore/{id}/status`
- `POST /api/v1/restore/{id}/rollback`

## Permissions

| Permission | Description |
|------------|-------------|
| `backup.create` | Create manual backups |
| `backup.download` | Download backup files |
| `backup.restore` | Selective/merge restore |
| `backup.restore_full` | Full restore (Super Admin) |
| `backup.schedule` | Manage schedules |
| `backup.upload` | Upload external backups |
| `backup.view_logs` | View audit logs |

## Translations

- **Arabic (ar)**: 341 translation keys
- **English (en)**: 341 translation keys

## Usage

### Creating a Backup
```php
use App\Jobs\Backup\ProcessBackupJob;

// Queue a backup job
ProcessBackupJob::dispatch($orgId, $userId, [
    'name' => 'Manual Backup',
    'type' => 'manual',
    'categories' => ['campaigns', 'posts', 'settings'],
]);
```

### Restoring from Backup
```php
use App\Jobs\Backup\ProcessRestoreJob;

// Queue a restore job
ProcessRestoreJob::dispatch($restoreId, [
    'conflict_resolution' => 'merge',
    'selected_categories' => ['campaigns', 'posts'],
]);
```

### Schema Discovery
```php
use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;

$service = app(SchemaDiscoveryService::class);

// Discover all org-scoped tables
$tables = $service->discoverOrgTables();

// Get table schema
$schema = $service->getTableSchema('cmis.campaigns');

// Categorize table
$category = $service->categorizeTable('cmis.campaigns'); // 'Campaigns'
```

## Testing

```bash
# Run all backup tests
vendor/bin/phpunit tests/Feature/Backup/

# Run specific test
vendor/bin/phpunit tests/Feature/Backup/SchemaDiscoveryTest.php
```

## Configuration

See `config/backup.php` for:
- Storage disk configuration
- Plan-based limits
- Encryption settings
- Category mappings
- Retention policies

## Related Documentation

- [Multi-Tenancy Patterns](../../.claude/knowledge/MULTI_TENANCY_PATTERNS.md)
- [RLS Policies](../../.claude/knowledge/RLS_POLICIES.md)
- [Implementation Plan](../../.claude/plans/eventual-kindling-nebula.md)
