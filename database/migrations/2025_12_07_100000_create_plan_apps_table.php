<?php

use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('cmis.plan_apps')) {
            return;
        }

        // Create plan_apps table to track which apps are available for each plan
        Schema::create('cmis.plan_apps', function (Blueprint $table) {
            $table->uuid('plan_app_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('plan_id');
            $table->uuid('app_id');
            $table->boolean('is_enabled')->default(true);
            $table->integer('usage_limit')->nullable()->comment('Optional usage limit per billing period');
            $table->jsonb('settings_override')->default('{}')->comment('Plan-specific app settings');
            $table->timestamps();

            // Foreign keys
            $table->foreign('plan_id')
                ->references('plan_id')
                ->on('cmis.plans')
                ->onDelete('cascade');

            $table->foreign('app_id')
                ->references('app_id')
                ->on('cmis.marketplace_apps')
                ->onDelete('cascade');

            // Unique constraint - each plan can only have each app once
            $table->unique(['plan_id', 'app_id'], 'plan_apps_unique');

            // Index for quick lookups
            $table->index('plan_id', 'idx_plan_apps_plan');
            $table->index('app_id', 'idx_plan_apps_app');
            $table->index('is_enabled', 'idx_plan_apps_enabled');
        });

        // Enable RLS but with public read access
        $this->enablePublicRLS('cmis.plan_apps');

        // Add comment to table
        DB::statement("COMMENT ON TABLE cmis.plan_apps IS 'Maps which marketplace apps are available for each subscription plan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->disableRLS('cmis.plan_apps');
        Schema::dropIfExists('cmis.plan_apps');
    }
};
