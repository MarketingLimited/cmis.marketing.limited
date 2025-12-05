<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

/**
 * Create Backup System Tables Migration
 *
 * Creates 6 tables for the Organization Backup & Restore application:
 * 1. organization_backups - Backup records with status, file info, summary
 * 2. backup_schedules - Automatic backup scheduling
 * 3. backup_restores - Restore operations with reconciliation reports
 * 4. backup_audit_logs - Complete audit trail
 * 5. backup_settings - Per-organization notification and storage settings
 * 6. backup_encryption_keys - Custom encryption keys (enterprise)
 *
 * All tables use RLS for multi-tenancy isolation.
 */
return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Organization Backups - Main backup records
        if (!Schema::hasTable('cmis.organization_backups')) {
            Schema::create('cmis.organization_backups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->index();
                $table->string('backup_code', 20)->unique(); // BKUP-2024-0001
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type', 20)->default('manual'); // manual, scheduled, pre_restore
                $table->string('status', 20)->default('pending'); // pending, processing, completed, failed, expired
                $table->string('file_path')->nullable();
                $table->string('storage_disk', 50)->default('local'); // local, google, onedrive, dropbox
                $table->bigInteger('file_size')->default(0);
                $table->string('checksum_sha256', 64)->nullable();
                $table->boolean('is_encrypted')->default(false);
                $table->uuid('encryption_key_id')->nullable();
                $table->jsonb('summary')->nullable(); // {categories: {Campaigns: {count, size_kb}}, total_records, etc}
                $table->jsonb('schema_snapshot')->nullable(); // For restore compatibility checking
                $table->jsonb('metadata')->nullable(); // Additional metadata
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->text('error_message')->nullable();
                $table->uuid('created_by');
                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.orgs')
                    ->onDelete('cascade');

                $table->foreign('created_by')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('cascade');

                // Indexes for common queries
                $table->index(['org_id', 'status']);
                $table->index(['org_id', 'created_at']);
                $table->index(['org_id', 'type']);
                $table->index('expires_at');
            });

            // Enable RLS for organization isolation
            $this->enableRLS('cmis.organization_backups');
        }

        // 2. Backup Schedules - Automatic backup scheduling
        if (!Schema::hasTable('cmis.backup_schedules')) {
            Schema::create('cmis.backup_schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->index();
                $table->string('name');
                $table->string('frequency', 20)->default('daily'); // hourly, daily, weekly, monthly
                $table->string('time', 5)->default('02:00'); // HH:MM format
                $table->tinyInteger('day_of_week')->nullable(); // 0-6 for weekly (0 = Sunday)
                $table->tinyInteger('day_of_month')->nullable(); // 1-31 for monthly
                $table->string('timezone', 50)->default('UTC');
                $table->boolean('is_active')->default(true);
                $table->integer('retention_days')->default(30);
                $table->jsonb('categories')->nullable(); // null = all categories, or specific array
                $table->string('storage_disk', 50)->default('local');
                $table->boolean('encrypt_backup')->default(false);
                $table->uuid('encryption_key_id')->nullable();
                $table->timestamp('last_run_at')->nullable();
                $table->timestamp('next_run_at')->nullable();
                $table->string('last_status', 20)->nullable(); // completed, failed
                $table->text('last_error')->nullable();
                $table->uuid('created_by');
                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.orgs')
                    ->onDelete('cascade');

                $table->foreign('created_by')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('cascade');

                // Indexes
                $table->index(['org_id', 'is_active']);
                $table->index('next_run_at');
            });

            $this->enableRLS('cmis.backup_schedules');
        }

        // 3. Backup Restores - Restore operations tracking
        if (!Schema::hasTable('cmis.backup_restores')) {
            Schema::create('cmis.backup_restores', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->index();
                $table->uuid('backup_id')->nullable(); // null if external upload
                $table->string('restore_code', 20)->unique(); // REST-2024-0001
                $table->string('type', 20)->default('selective'); // full, selective, merge
                $table->string('status', 30)->default('pending');
                // Status: pending, analyzing, awaiting_confirmation, processing, completed, failed, rolled_back
                $table->string('source_type', 20)->default('internal'); // internal, external_upload
                $table->string('external_file_path')->nullable(); // For uploaded backup files
                $table->jsonb('selected_categories')->nullable(); // Categories selected for restore
                $table->jsonb('conflict_resolution')->nullable();
                // {strategy: 'skip'|'replace'|'merge'|'ask', decisions: {record_id: 'skip'|'replace'|'merge'}}
                $table->jsonb('reconciliation_report')->nullable();
                // {compatible: [...], partially_compatible: [...], incompatible: [...]}
                $table->jsonb('execution_report')->nullable();
                // {records_restored: N, records_skipped: N, records_merged: N, errors: [...]}
                $table->uuid('safety_backup_id')->nullable(); // Auto-created backup before restore
                $table->integer('total_records')->default(0);
                $table->integer('processed_records')->default(0);
                $table->integer('progress_percent')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('rollback_expires_at')->nullable(); // 24 hours from completion
                $table->boolean('rollback_available')->default(false);
                $table->text('error_message')->nullable();
                $table->uuid('created_by');
                $table->uuid('confirmed_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->string('confirmation_method', 30)->nullable(); // simple, org_name, email_code
                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.orgs')
                    ->onDelete('cascade');

                $table->foreign('backup_id')
                    ->references('id')
                    ->on('cmis.organization_backups')
                    ->onDelete('set null');

                $table->foreign('safety_backup_id')
                    ->references('id')
                    ->on('cmis.organization_backups')
                    ->onDelete('set null');

                $table->foreign('created_by')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('cascade');

                $table->foreign('confirmed_by')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('set null');

                // Indexes
                $table->index(['org_id', 'status']);
                $table->index(['org_id', 'created_at']);
                $table->index('rollback_expires_at');
            });

            $this->enableRLS('cmis.backup_restores');
        }

        // 4. Backup Audit Logs - Complete audit trail
        if (!Schema::hasTable('cmis.backup_audit_logs')) {
            Schema::create('cmis.backup_audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->index();
                $table->string('action', 50);
                // Actions: backup_created, backup_started, backup_completed, backup_failed,
                // backup_downloaded, backup_deleted, backup_expired,
                // restore_started, restore_completed, restore_failed, restore_rolled_back,
                // schedule_created, schedule_updated, schedule_deleted, schedule_triggered,
                // settings_updated, external_upload, encryption_key_created, encryption_key_deleted
                $table->uuid('entity_id')->nullable(); // backup_id, restore_id, schedule_id
                $table->string('entity_type', 50)->nullable(); // backup, restore, schedule, settings
                $table->uuid('user_id');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->jsonb('details')->nullable(); // Action-specific details
                $table->jsonb('changes')->nullable(); // Before/after for updates
                $table->timestamps();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.orgs')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('cascade');

                // Indexes for audit queries
                $table->index(['org_id', 'action']);
                $table->index(['org_id', 'created_at']);
                $table->index(['org_id', 'entity_type', 'entity_id']);
                $table->index(['org_id', 'user_id']);
            });

            $this->enableRLS('cmis.backup_audit_logs');
        }

        // 5. Backup Settings - Per-organization settings
        if (!Schema::hasTable('cmis.backup_settings')) {
            Schema::create('cmis.backup_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->unique(); // One settings record per org

                // Email notification settings
                $table->boolean('email_on_backup_complete')->default(true);
                $table->boolean('email_on_backup_failed')->default(true);
                $table->boolean('email_on_restore_started')->default(true);
                $table->boolean('email_on_restore_complete')->default(true);
                $table->boolean('email_on_restore_failed')->default(true);
                $table->boolean('email_on_backup_expiring')->default(true);
                $table->boolean('email_on_storage_warning')->default(true);
                $table->boolean('notify_all_admins')->default(false);
                $table->jsonb('notification_emails')->nullable(); // Additional email addresses

                // In-app notification settings
                $table->boolean('inapp_notifications')->default(true);

                // Storage settings
                $table->string('default_storage_disk', 50)->default('local');
                $table->jsonb('storage_credentials')->nullable(); // Encrypted cloud credentials
                // {google: {client_id, refresh_token}, onedrive: {...}, dropbox: {...}}

                // Encryption settings
                $table->boolean('encrypt_by_default')->default(false);
                $table->uuid('default_encryption_key_id')->nullable();

                // Retention settings
                $table->integer('default_retention_days')->default(30);
                $table->boolean('auto_delete_expired')->default(true);

                // Storage quota tracking
                $table->bigInteger('storage_used_bytes')->default(0);
                $table->bigInteger('storage_quota_bytes')->nullable(); // null = unlimited
                $table->integer('storage_warning_percent')->default(80);

                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.orgs')
                    ->onDelete('cascade');
            });

            $this->enableRLS('cmis.backup_settings');
        }

        // 6. Backup Encryption Keys - Enterprise feature
        if (!Schema::hasTable('cmis.backup_encryption_keys')) {
            Schema::create('cmis.backup_encryption_keys', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->text('encrypted_key'); // AES key encrypted with master key
                $table->string('key_hash', 64); // SHA-256 hash for verification
                $table->string('algorithm', 30)->default('aes-256-gcm');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('usage_count')->default(0); // Number of backups using this key
                $table->timestamp('last_used_at')->nullable();
                $table->uuid('created_by');
                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.orgs')
                    ->onDelete('cascade');

                $table->foreign('created_by')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('cascade');

                // Indexes
                $table->index(['org_id', 'is_active']);
                $table->index(['org_id', 'is_default']);
            });

            $this->enableRLS('cmis.backup_encryption_keys');
        }

        // Add foreign key for encryption_key_id in organization_backups after encryption_keys table exists
        if (Schema::hasTable('cmis.organization_backups') && Schema::hasTable('cmis.backup_encryption_keys')) {
            // Check if foreign key already exists (Laravel adds 'cmis_' prefix to schema-qualified tables)
            $fkExists = DB::select("
                SELECT 1 FROM information_schema.table_constraints
                WHERE constraint_name IN (
                    'organization_backups_encryption_key_id_foreign',
                    'cmis_organization_backups_encryption_key_id_foreign'
                )
                AND table_schema = 'cmis'
                AND table_name = 'organization_backups'
            ");

            if (empty($fkExists)) {
                Schema::table('cmis.organization_backups', function (Blueprint $table) {
                    $table->foreign('encryption_key_id')
                        ->references('id')
                        ->on('cmis.backup_encryption_keys')
                        ->onDelete('set null');
                });
            }
        }

        // Add foreign key for encryption_key_id in backup_schedules
        if (Schema::hasTable('cmis.backup_schedules') && Schema::hasTable('cmis.backup_encryption_keys')) {
            $fkExists = DB::select("
                SELECT 1 FROM information_schema.table_constraints
                WHERE constraint_name IN (
                    'backup_schedules_encryption_key_id_foreign',
                    'cmis_backup_schedules_encryption_key_id_foreign'
                )
                AND table_schema = 'cmis'
                AND table_name = 'backup_schedules'
            ");

            if (empty($fkExists)) {
                Schema::table('cmis.backup_schedules', function (Blueprint $table) {
                    $table->foreign('encryption_key_id')
                        ->references('id')
                        ->on('cmis.backup_encryption_keys')
                        ->onDelete('set null');
                });
            }
        }

        // Add foreign key for default_encryption_key_id in backup_settings
        if (Schema::hasTable('cmis.backup_settings') && Schema::hasTable('cmis.backup_encryption_keys')) {
            $fkExists = DB::select("
                SELECT 1 FROM information_schema.table_constraints
                WHERE constraint_name IN (
                    'backup_settings_default_encryption_key_id_foreign',
                    'cmis_backup_settings_default_encryption_key_id_foreign'
                )
                AND table_schema = 'cmis'
                AND table_name = 'backup_settings'
            ");

            if (empty($fkExists)) {
                Schema::table('cmis.backup_settings', function (Blueprint $table) {
                    $table->foreign('default_encryption_key_id')
                        ->references('id')
                        ->on('cmis.backup_encryption_keys')
                        ->onDelete('set null');
                });
            }
        }

        // Create indexes for JSONB columns
        DB::statement("CREATE INDEX IF NOT EXISTS idx_organization_backups_summary_gin ON cmis.organization_backups USING GIN (summary)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_backup_restores_conflict_resolution_gin ON cmis.backup_restores USING GIN (conflict_resolution)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_backup_restores_execution_report_gin ON cmis.backup_restores USING GIN (execution_report)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_backup_audit_logs_details_gin ON cmis.backup_audit_logs USING GIN (details)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first (in reverse order of creation)
        if (Schema::hasTable('cmis.backup_settings')) {
            Schema::table('cmis.backup_settings', function (Blueprint $table) {
                $table->dropForeignIfExists('backup_settings_default_encryption_key_id_foreign');
            });
        }

        if (Schema::hasTable('cmis.backup_schedules')) {
            Schema::table('cmis.backup_schedules', function (Blueprint $table) {
                $table->dropForeignIfExists('backup_schedules_encryption_key_id_foreign');
            });
        }

        if (Schema::hasTable('cmis.organization_backups')) {
            Schema::table('cmis.organization_backups', function (Blueprint $table) {
                $table->dropForeignIfExists('organization_backups_encryption_key_id_foreign');
            });
        }

        // Drop JSONB indexes
        DB::statement("DROP INDEX IF EXISTS cmis.idx_organization_backups_summary_gin");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_backup_restores_conflict_resolution_gin");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_backup_restores_execution_report_gin");
        DB::statement("DROP INDEX IF EXISTS cmis.idx_backup_audit_logs_details_gin");

        // Disable RLS and drop tables in reverse order
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
};
