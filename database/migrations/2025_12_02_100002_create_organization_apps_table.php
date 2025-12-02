<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('cmis.organization_apps')) {
            return;
        }

        Schema::create('cmis.organization_apps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->uuid('app_id');
            $table->boolean('is_enabled')->default(false);
            $table->timestampTz('enabled_at')->nullable();
            $table->uuid('enabled_by')->nullable();
            $table->timestampTz('disabled_at')->nullable();
            $table->uuid('disabled_by')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['org_id', 'app_id']);
            $table->index('org_id');
            $table->index('app_id');
            $table->index('is_enabled');
        });

        // Add foreign keys with existence checks
        DB::statement("
            DO \$\$ BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'orgs') THEN
                    ALTER TABLE cmis.organization_apps
                    ADD CONSTRAINT organization_apps_org_id_foreign
                    FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
                END IF;
            END \$\$;
        ");

        DB::statement("
            DO \$\$ BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'marketplace_apps') THEN
                    ALTER TABLE cmis.organization_apps
                    ADD CONSTRAINT organization_apps_app_id_foreign
                    FOREIGN KEY (app_id) REFERENCES cmis.marketplace_apps(app_id) ON DELETE CASCADE;
                END IF;
            END \$\$;
        ");

        DB::statement("
            DO \$\$ BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'users') THEN
                    ALTER TABLE cmis.organization_apps
                    ADD CONSTRAINT organization_apps_enabled_by_foreign
                    FOREIGN KEY (enabled_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;

                    ALTER TABLE cmis.organization_apps
                    ADD CONSTRAINT organization_apps_disabled_by_foreign
                    FOREIGN KEY (disabled_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");

        // Enable RLS with organization isolation
        $this->enableRLS('cmis.organization_apps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->disableRLS('cmis.organization_apps');
        Schema::dropIfExists('cmis.organization_apps');
    }
};
