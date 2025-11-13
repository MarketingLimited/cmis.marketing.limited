<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Audience Templates (Sprint 4.2)
 * Enables reusable audience targeting across campaigns
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql')->create('cmis.audience_templates', function (Blueprint $table) {
            $table->uuid('template_id')->primary();
            $table->uuid('org_id')->index();

            // Template details
            $table->string('name', 255);
            $table->text('description')->nullable();

            // Targeting criteria (platform-agnostic format)
            $table->jsonb('targeting_criteria')->default('{}');

            // Platform support
            $table->jsonb('platforms')->default('["meta","google"]'); // Array of supported platforms

            // Usage tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // Timestamps
            $table->uuid('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users')->onDelete('cascade');

            // Indexes
            $table->index(['org_id', 'name'], 'audience_templates_org_name_idx');
        });

        DB::statement("COMMENT ON TABLE cmis.audience_templates IS 'Reusable audience targeting templates for multi-platform campaigns'");
        DB::statement("COMMENT ON COLUMN cmis.audience_templates.targeting_criteria IS 'Platform-agnostic targeting configuration'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('cmis.audience_templates');
    }
};
