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
        Schema::create('cmis.marketplace_apps', function (Blueprint $table) {
            $table->uuid('app_id')->primary();
            $table->string('slug', 50)->unique();
            $table->string('name_key', 100);
            $table->string('description_key', 100);
            $table->string('icon', 50);
            $table->string('category', 30);
            $table->string('route_name', 100)->nullable();
            $table->string('route_prefix', 50)->nullable();
            $table->boolean('is_core')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->integer('sort_order')->default(0);
            $table->jsonb('dependencies')->default('[]');
            $table->jsonb('required_permissions')->default('[]');
            $table->jsonb('metadata')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index('slug');
            $table->index('category');
            $table->index('is_core');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // Add foreign key to categories
        DB::statement("
            DO \$\$ BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'app_categories') THEN
                    ALTER TABLE cmis.marketplace_apps
                    ADD CONSTRAINT marketplace_apps_category_foreign
                    FOREIGN KEY (category) REFERENCES cmis.app_categories(slug) ON DELETE RESTRICT;
                END IF;
            END \$\$;
        ");

        // Enable public RLS (read-only for all, system-managed)
        $this->enablePublicRLS('cmis.marketplace_apps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS allow_all ON cmis.marketplace_apps');
        DB::statement('ALTER TABLE cmis.marketplace_apps DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('cmis.marketplace_apps');
    }
};
