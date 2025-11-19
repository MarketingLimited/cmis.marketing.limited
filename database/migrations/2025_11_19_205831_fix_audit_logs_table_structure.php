<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if log_id already exists
        $hasLogId = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'audit_logs' AND column_name = 'log_id'");
        $hasAuditId = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'audit_logs' AND column_name = 'audit_id'");

        // If log_id exists and audit_id doesn't, the migration already ran
        if (!empty($hasLogId) && empty($hasAuditId)) {
            // Just add missing columns if they don't exist
            Schema::table('cmis.audit_logs', function (Blueprint $table) {
                $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'audit_logs'");
                $columnNames = array_column($columns, 'column_name');

                if (!in_array('old_values', $columnNames)) {
                    $table->jsonb('old_values')->nullable();
                }
                if (!in_array('new_values', $columnNames)) {
                    $table->jsonb('new_values')->nullable();
                }
                if (!in_array('ip_address', $columnNames)) {
                    $table->string('ip_address', 45)->nullable();
                }
                if (!in_array('user_agent', $columnNames)) {
                    $table->text('user_agent')->nullable();
                }
                if (!in_array('metadata', $columnNames)) {
                    $table->jsonb('metadata')->nullable();
                }
                if (!in_array('updated_at', $columnNames)) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
            return;
        }

        // Original migration logic for when audit_id exists
        if (empty($hasLogId)) {
            Schema::table('cmis.audit_logs', function (Blueprint $table) {
                $table->uuid('log_id')->nullable();
            });
        }

        Schema::table('cmis.audit_logs', function (Blueprint $table) {
            $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'audit_logs'");
            $columnNames = array_column($columns, 'column_name');

            if (!in_array('old_values', $columnNames)) {
                $table->jsonb('old_values')->nullable();
            }
            if (!in_array('new_values', $columnNames)) {
                $table->jsonb('new_values')->nullable();
            }
            if (!in_array('ip_address', $columnNames)) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!in_array('user_agent', $columnNames)) {
                $table->text('user_agent')->nullable();
            }
            if (!in_array('metadata', $columnNames)) {
                $table->jsonb('metadata')->nullable();
            }
            if (!in_array('updated_at', $columnNames)) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        // Only rename audit_id to log_id if audit_id exists and log_id was just created
        if (!empty($hasAuditId) && empty($hasLogId)) {
            // Copy audit_id to log_id for existing records
            DB::statement("UPDATE cmis.audit_logs SET log_id = audit_id WHERE log_id IS NULL");

            // Check if primary key exists before dropping
            $hasPK = DB::select("SELECT constraint_name FROM information_schema.table_constraints WHERE table_schema = 'cmis' AND table_name = 'audit_logs' AND constraint_type = 'PRIMARY KEY' AND constraint_name = 'audit_logs_pkey'");
            if (!empty($hasPK)) {
                DB::statement("ALTER TABLE cmis.audit_logs DROP CONSTRAINT audit_logs_pkey");
            }

            // Make log_id NOT NULL and set as primary key
            DB::statement("ALTER TABLE cmis.audit_logs ALTER COLUMN log_id SET NOT NULL");
            DB::statement("ALTER TABLE cmis.audit_logs ADD PRIMARY KEY (log_id)");

            // Drop the old audit_id column
            Schema::table('cmis.audit_logs', function (Blueprint $table) {
                $table->dropColumn('audit_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cmis.audit_logs', function (Blueprint $table) {
            // Add back audit_id
            $table->uuid('audit_id')->nullable();
        });

        // Copy log_id to audit_id
        DB::statement("UPDATE cmis.audit_logs SET audit_id = log_id WHERE audit_id IS NULL");

        // Drop the log_id primary key
        DB::statement("ALTER TABLE cmis.audit_logs DROP CONSTRAINT audit_logs_pkey");

        // Make audit_id the primary key again
        DB::statement("ALTER TABLE cmis.audit_logs ALTER COLUMN audit_id SET NOT NULL");
        DB::statement("ALTER TABLE cmis.audit_logs ADD PRIMARY KEY (audit_id)");

        // Drop new columns
        Schema::table('cmis.audit_logs', function (Blueprint $table) {
            $table->dropColumn(['log_id', 'old_values', 'new_values', 'ip_address', 'user_agent', 'metadata', 'updated_at']);
        });
    }
};
