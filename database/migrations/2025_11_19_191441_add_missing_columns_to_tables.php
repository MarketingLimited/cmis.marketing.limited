<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET search_path TO cmis,public');

        // Add usage_count to assets table
        if ($this->columnExists('cmis', 'assets', 'asset_id') &&
            !$this->columnExists('cmis', 'assets', 'usage_count')) {
            DB::statement("ALTER TABLE cmis.assets ADD COLUMN usage_count INTEGER DEFAULT 0");
            echo "✓ Added usage_count to assets\n";
        }

        // Add report_name to analytics_reports table
        if ($this->columnExists('cmis', 'analytics_reports', 'report_id') &&
            !$this->columnExists('cmis', 'analytics_reports', 'report_name')) {
            DB::statement("ALTER TABLE cmis.analytics_reports ADD COLUMN report_name VARCHAR(255)");
            echo "✓ Added report_name to analytics_reports\n";
        }

        // Add activity_id to activity_logs table
        if ($this->columnExists('cmis', 'activity_logs', 'log_id') &&
            !$this->columnExists('cmis', 'activity_logs', 'activity_id')) {
            DB::statement("ALTER TABLE cmis.activity_logs ADD COLUMN activity_id UUID");
            echo "✓ Added activity_id to activity_logs\n";
        }

        // Add platform to ad_accounts table (if missing)
        if ($this->columnExists('cmis', 'ad_accounts', 'ad_account_id') &&
            !$this->columnExists('cmis', 'ad_accounts', 'platform')) {
            DB::statement("ALTER TABLE cmis.ad_accounts ADD COLUMN platform VARCHAR(50)");
            echo "✓ Added platform to ad_accounts\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET search_path TO cmis,public');

        // Remove columns in reverse order
        if ($this->columnExists('cmis', 'ad_accounts', 'platform')) {
            DB::statement("ALTER TABLE cmis.ad_accounts DROP COLUMN platform");
        }

        if ($this->columnExists('cmis', 'activity_logs', 'activity_id')) {
            DB::statement("ALTER TABLE cmis.activity_logs DROP COLUMN activity_id");
        }

        if ($this->columnExists('cmis', 'analytics_reports', 'report_name')) {
            DB::statement("ALTER TABLE cmis.analytics_reports DROP COLUMN report_name");
        }

        if ($this->columnExists('cmis', 'assets', 'usage_count')) {
            DB::statement("ALTER TABLE cmis.assets DROP COLUMN usage_count");
        }
    }

    /**
     * Check if column exists in table
     */
    private function columnExists(string $schema, string $table, string $column): bool
    {
        $result = DB::select(
            "SELECT EXISTS (
                SELECT FROM information_schema.columns
                WHERE table_schema = ?
                AND table_name = ?
                AND column_name = ?
            )",
            [$schema, $table, $column]
        );

        return $result[0]->exists ?? false;
    }
};
