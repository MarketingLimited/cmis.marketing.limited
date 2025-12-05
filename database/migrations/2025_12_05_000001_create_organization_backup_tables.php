<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

/**
 * Organization Backup System Tables
 *
 * Creates all tables required for the backup and restore application:
 * 1. organization_backups - Stores backup records
 * 2. backup_schedules - Automatic backup scheduling
 * 3. backup_restores - Restore operation tracking
 * 4. backup_audit_logs - Audit trail for all operations
 * 5. backup_settings - Per-organization settings
 * 6. backup_encryption_keys - Custom encryption keys (enterprise)
 *
 * Design Principles:
 * - Schema-agnostic: Works with any table structure
 * - RLS-enabled: Full multi-tenancy support
 * - No database structure exposure to users
 */
return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. Organization Backups Table
        // ═══════════════════════════════════════════════════════════════
        if (!Schema::hasTable('cmis.organization_backups')) {
            Schema::create('cmis.organization_backups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id');

                // Backup identification
                $table->string('backup_number', 30)->comment('Human-readable ID: BKUP-2024-001');
                $table->string('backup_type', 20)->comment('full, data_only, files_only');
                $table->string('trigger_type', 20)->comment('manual, scheduled, pre_restore');

                // Status tracking
                $table->string('status', 20)->default('pending')
                    ->comment('pending, processing, completed, failed, expired');
                $table->unsignedInteger('progress_percent')->default(0);
                $table->text('status_message')->nullable();

                // File information
                $table->text('file_path')->nullable()->comment('Encrypted storage path');
                $table->unsignedBigInteger('file_size_bytes')->nullable();
                $table->string('checksum_sha256', 64)->nullable();
                $table->uuid('encryption_key_id')->nullable()->comment('Reference to custom encryption key');

                // Summary statistics (user-friendly, no schema exposure)
                $table->jsonb('summary')->default('{}')
                    ->comment('Categories with counts and sizes, no internal structure');

                // Schema snapshot for restore compatibility
                $table->jsonb('schema_snapshot')->nullable()
                    ->comment('Schema state at backup time for reconciliation');

                // Version control
                $table->string('backup_version', 10)->default('1.0');
                $table->string('cmis_version', 20)->nullable()->comment('Platform version at backup time');

                // User and timing
                $table->uuid('requested_by')->nullable();
                $table->timestampTz('started_at')->nullable();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampTz('expires_at')->nullable();
                $table->timestampTz('downloaded_at')->nullable();
                $table->unsignedInteger('download_count')->default(0);

                // Standard timestamps
                $table->timestampsTz();
                $table->softDeletesTz();

                // Indexes
                $table->index('org_id');
                $table->index('backup_number');
                $table->index('status');
                $table->index('backup_type');
                $table->index('trigger_type');
                $table->index('expires_at');
                $table->index(['org_id', 'status']);
                $table->index(['org_id', 'created_at']);
            });

            // Foreign keys
            $this->addForeignKeyIfTableExists(
                'cmis.organization_backups',
                'org_id',
                'cmis.orgs',
                'org_id',
                'CASCADE'
            );

            $this->addForeignKeyIfTableExists(
                'cmis.organization_backups',
                'requested_by',
                'cmis.users',
                'user_id',
                'SET NULL'
            );

            // Enable RLS
            $this->enableRLS('cmis.organization_backups');
        }

        // ═══════════════════════════════════════════════════════════════
        // 2. Backup Schedules Table
        // ═══════════════════════════════════════════════════════════════
        if (!Schema::hasTable('cmis.backup_schedules')) {
            Schema::create('cmis.backup_schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id');

                // Schedule settings
                $table->boolean('is_active')->default(true);
                $table->string('frequency', 20)->comment('hourly, daily, weekly, monthly');
                $table->string('backup_type', 20)->default('full');

                // Timing preferences
                $table->time('preferred_time')->default('03:00:00')->comment('Preferred execution time');
                $table->unsignedSmallInteger('preferred_day')->nullable()
                    ->comment('Day of week (0-6) or month (1-31)');
                $table->string('timezone', 50)->default('UTC');

                // Retention settings
                $table->unsignedInteger('retention_days')->default(30);
                $table->unsignedInteger('max_backups')->default(10);

                // Execution tracking
                $table->timestampTz('last_run_at')->nullable();
                $table->uuid('last_backup_id')->nullable();
                $table->timestampTz('next_run_at')->nullable();
                $table->unsignedInteger('consecutive_failures')->default(0);
                $table->text('last_error')->nullable();

                // Management
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestampsTz();
                $table->softDeletesTz();

                // Indexes
                $table->index('org_id');
                $table->index('is_active');
                $table->index('frequency');
                $table->index('next_run_at');
                $table->index(['is_active', 'next_run_at']);
            });

            // Foreign keys
            $this->addForeignKeyIfTableExists(
                'cmis.backup_schedules',
                'org_id',
                'cmis.orgs',
                'org_id',
                'CASCADE'
            );

            $this->addForeignKeyIfTableExists(
                'cmis.backup_schedules',
                'created_by',
                'cmis.users',
                'user_id',
                'SET NULL'
            );

            // Enable RLS
            $this->enableRLS('cmis.backup_schedules');
        }

        // ═══════════════════════════════════════════════════════════════
        // 3. Backup Restores Table
        // ═══════════════════════════════════════════════════════════════
        if (!Schema::hasTable('cmis.backup_restores')) {
            Schema::create('cmis.backup_restores', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id');
                $table->uuid('backup_id');

                // Restore configuration
                $table->string('restore_type', 20)->comment('full, selective, merge');
                $table->string('conflict_mode', 20)->nullable()->comment('skip, replace, merge, ask');
                $table->jsonb('selected_categories')->nullable()->comment('Categories chosen for selective restore');

                // Status tracking
                $table->string('status', 20)->default('pending')
                    ->comment('pending, analyzing, confirming, processing, completed, failed, rolled_back');
                $table->unsignedInteger('progress_percent')->default(0);
                $table->text('status_message')->nullable();

                // Safety backup (created before restore)
                $table->uuid('safety_backup_id')->nullable();

                // Analysis and reports
                $table->jsonb('reconciliation_report')->nullable()
                    ->comment('Schema compatibility analysis');
                $table->jsonb('result_report')->nullable()
                    ->comment('Final restore results: restored, skipped, errors');

                // Conflict handling
                $table->jsonb('conflicts')->nullable()->comment('Detected conflicts');
                $table->jsonb('conflict_resolutions')->nullable()->comment('User decisions on conflicts');

                // Rollback capability
                $table->boolean('can_rollback')->default(true);
                $table->timestampTz('rollback_expires_at')->nullable()
                    ->comment('Rollback available for 24 hours');
                $table->timestampTz('rolled_back_at')->nullable();
                $table->uuid('rolled_back_by')->nullable();

                // Confirmation (multi-level security)
                $table->uuid('requested_by')->nullable();
                $table->uuid('confirmed_by')->nullable();
                $table->string('confirmation_code', 10)->nullable()->comment('Required for full restore');
                $table->timestampTz('confirmed_at')->nullable();

                // Timing
                $table->timestampTz('started_at')->nullable();
                $table->timestampTz('completed_at')->nullable();

                // Standard timestamps
                $table->timestampsTz();
                $table->softDeletesTz();

                // Indexes
                $table->index('org_id');
                $table->index('backup_id');
                $table->index('status');
                $table->index('restore_type');
                $table->index('can_rollback');
                $table->index('rollback_expires_at');
                $table->index(['org_id', 'status']);
            });

            // Foreign keys
            $this->addForeignKeyIfTableExists(
                'cmis.backup_restores',
                'org_id',
                'cmis.orgs',
                'org_id',
                'CASCADE'
            );

            $this->addForeignKeyIfTableExists(
                'cmis.backup_restores',
                'requested_by',
                'cmis.users',
                'user_id',
                'SET NULL'
            );

            $this->addForeignKeyIfTableExists(
                'cmis.backup_restores',
                'confirmed_by',
                'cmis.users',
                'user_id',
                'SET NULL'
            );

            // Enable RLS
            $this->enableRLS('cmis.backup_restores');
        }

        // ═══════════════════════════════════════════════════════════════
        // 4. Backup Audit Logs Table
        // ═══════════════════════════════════════════════════════════════
        if (!Schema::hasTable('cmis.backup_audit_logs')) {
            Schema::create('cmis.backup_audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id');

                // Action details
                $table->string('action', 50)
                    ->comment('backup.created, backup.downloaded, restore.started, etc.');
                $table->string('entity_type', 20)->comment('backup, restore, schedule, settings');
                $table->uuid('entity_id');

                // Context
                $table->jsonb('details')->default('{}');
                $table->jsonb('changes')->nullable()->comment('Before/after for updates');
                $table->inet('ip_address')->nullable();
                $table->text('user_agent')->nullable();

                // User and timing
                $table->uuid('performed_by')->nullable();
                $table->timestampTz('performed_at')->useCurrent();

                // No updated_at - audit logs are immutable
                $table->timestampTz('created_at')->useCurrent();

                // Indexes
                $table->index('org_id');
                $table->index('action');
                $table->index('entity_type');
                $table->index('entity_id');
                $table->index('performed_by');
                $table->index('performed_at');
                $table->index(['org_id', 'performed_at']);
                $table->index(['entity_type', 'entity_id']);
            });

            // Foreign keys
            $this->addForeignKeyIfTableExists(
                'cmis.backup_audit_logs',
                'org_id',
                'cmis.orgs',
                'org_id',
                'CASCADE'
            );

            $this->addForeignKeyIfTableExists(
                'cmis.backup_audit_logs',
                'performed_by',
                'cmis.users',
                'user_id',
                'SET NULL'
            );

            // Enable RLS
            $this->enableRLS('cmis.backup_audit_logs');
        }

        // ═══════════════════════════════════════════════════════════════
        // 5. Backup Settings Table
        // ═══════════════════════════════════════════════════════════════
        if (!Schema::hasTable('cmis.backup_settings')) {
            Schema::create('cmis.backup_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->unique();

                // Notification settings
                $table->boolean('notifications_enabled')->default(true);
                $table->boolean('notify_on_backup_complete')->default(true);
                $table->boolean('notify_on_backup_failed')->default(true);
                $table->boolean('notify_on_restore')->default(true);
                $table->boolean('notify_on_expiring')->default(true);
                $table->jsonb('notification_emails')->default('[]')
                    ->comment('Additional emails to notify');

                // Encryption settings (enterprise feature)
                $table->boolean('use_custom_encryption_key')->default(false);
                $table->uuid('active_encryption_key_id')->nullable();

                // Retention override (within plan limits)
                $table->unsignedInteger('default_retention_days')->nullable();

                // Auto-cleanup
                $table->boolean('auto_delete_expired')->default(true);

                // Standard timestamps
                $table->timestampsTz();

                // Indexes
                $table->index('org_id');
            });

            // Foreign keys
            $this->addForeignKeyIfTableExists(
                'cmis.backup_settings',
                'org_id',
                'cmis.orgs',
                'org_id',
                'CASCADE'
            );

            // Enable RLS
            $this->enableRLS('cmis.backup_settings');
        }

        // ═══════════════════════════════════════════════════════════════
        // 6. Backup Encryption Keys Table (Enterprise Feature)
        // ═══════════════════════════════════════════════════════════════
        if (!Schema::hasTable('cmis.backup_encryption_keys')) {
            Schema::create('cmis.backup_encryption_keys', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id');

                // Key identification
                $table->string('key_name', 100);
                $table->string('key_hash', 64)->comment('SHA-256 hash for verification only');
                $table->string('algorithm', 30)->default('AES-256-GCM');

                // Status
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('backup_count')->default(0)
                    ->comment('Number of backups using this key');

                // Management
                $table->uuid('created_by')->nullable();
                $table->timestampTz('rotated_at')->nullable();
                $table->uuid('rotated_by')->nullable();

                // Standard timestamps
                $table->timestampsTz();
                $table->softDeletesTz();

                // Indexes
                $table->index('org_id');
                $table->index('is_active');
                $table->index(['org_id', 'is_active']);
            });

            // Foreign keys
            $this->addForeignKeyIfTableExists(
                'cmis.backup_encryption_keys',
                'org_id',
                'cmis.orgs',
                'org_id',
                'CASCADE'
            );

            $this->addForeignKeyIfTableExists(
                'cmis.backup_encryption_keys',
                'created_by',
                'cmis.users',
                'user_id',
                'SET NULL'
            );

            // Enable RLS
            $this->enableRLS('cmis.backup_encryption_keys');
        }

        // ═══════════════════════════════════════════════════════════════
        // Add cross-table foreign keys
        // ═══════════════════════════════════════════════════════════════
        $this->addCrossTableForeignKeys();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order due to foreign keys
        $tables = [
            'cmis.backup_encryption_keys',
            'cmis.backup_settings',
            'cmis.backup_audit_logs',
            'cmis.backup_restores',
            'cmis.backup_schedules',
            'cmis.organization_backups',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->disableRLS($table);
                Schema::dropIfExists($table);
            }
        }
    }

    /**
     * Helper: Add foreign key if referenced table exists
     */
    private function addForeignKeyIfTableExists(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete = 'CASCADE'
    ): void {
        [$schema, $tableName] = explode('.', $table);
        [$refSchema, $refTableName] = explode('.', $referencedTable);

        $constraintName = "{$tableName}_{$column}_foreign";

        DB::statement("
            DO \$\$ BEGIN
                IF EXISTS (
                    SELECT 1 FROM information_schema.tables
                    WHERE table_schema = '{$refSchema}'
                    AND table_name = '{$refTableName}'
                ) THEN
                    IF NOT EXISTS (
                        SELECT 1 FROM information_schema.table_constraints
                        WHERE constraint_name = '{$constraintName}'
                        AND table_schema = '{$schema}'
                    ) THEN
                        ALTER TABLE {$table}
                        ADD CONSTRAINT {$constraintName}
                        FOREIGN KEY ({$column})
                        REFERENCES {$referencedTable}({$referencedColumn})
                        ON DELETE {$onDelete};
                    END IF;
                END IF;
            END \$\$;
        ");
    }

    /**
     * Add foreign keys between backup tables
     */
    private function addCrossTableForeignKeys(): void
    {
        // backup_schedules.last_backup_id -> organization_backups.id
        DB::statement("
            DO \$\$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'backup_schedules_last_backup_id_foreign'
                ) THEN
                    ALTER TABLE cmis.backup_schedules
                    ADD CONSTRAINT backup_schedules_last_backup_id_foreign
                    FOREIGN KEY (last_backup_id)
                    REFERENCES cmis.organization_backups(id)
                    ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");

        // backup_restores.backup_id -> organization_backups.id
        DB::statement("
            DO \$\$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'backup_restores_backup_id_foreign'
                ) THEN
                    ALTER TABLE cmis.backup_restores
                    ADD CONSTRAINT backup_restores_backup_id_foreign
                    FOREIGN KEY (backup_id)
                    REFERENCES cmis.organization_backups(id)
                    ON DELETE CASCADE;
                END IF;
            END \$\$;
        ");

        // backup_restores.safety_backup_id -> organization_backups.id
        DB::statement("
            DO \$\$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'backup_restores_safety_backup_id_foreign'
                ) THEN
                    ALTER TABLE cmis.backup_restores
                    ADD CONSTRAINT backup_restores_safety_backup_id_foreign
                    FOREIGN KEY (safety_backup_id)
                    REFERENCES cmis.organization_backups(id)
                    ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");

        // organization_backups.encryption_key_id -> backup_encryption_keys.id
        DB::statement("
            DO \$\$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'organization_backups_encryption_key_id_foreign'
                ) THEN
                    ALTER TABLE cmis.organization_backups
                    ADD CONSTRAINT organization_backups_encryption_key_id_foreign
                    FOREIGN KEY (encryption_key_id)
                    REFERENCES cmis.backup_encryption_keys(id)
                    ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");

        // backup_settings.active_encryption_key_id -> backup_encryption_keys.id
        DB::statement("
            DO \$\$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'backup_settings_active_encryption_key_id_foreign'
                ) THEN
                    ALTER TABLE cmis.backup_settings
                    ADD CONSTRAINT backup_settings_active_encryption_key_id_foreign
                    FOREIGN KEY (active_encryption_key_id)
                    REFERENCES cmis.backup_encryption_keys(id)
                    ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");
    }
};
