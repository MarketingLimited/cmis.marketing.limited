<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates tables for tracking AI usage and enforcing quotas
     * to control costs and prevent abuse.
     *
     * Tables:
     * - cmis_ai.usage_quotas: Defines limits per tier/org/user
     * - cmis_ai.usage_tracking: Records every AI API call
     * - cmis_ai.usage_summary: Aggregated usage stats
     *
     * @return void
     */
    public function up()
    {
        // 1. Create usage quotas table
        Schema::create('cmis_ai.usage_quotas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->nullable()->comment('Organization ID (null = system default)');
            $table->uuid('user_id')->nullable()->comment('User ID (null = org-level quota)');

            $table->string('tier')->default('free')->comment('free, pro, enterprise, custom');
            $table->string('ai_service')->comment('gpt, gemini, embeddings, image_gen');

            // Quota limits
            $table->integer('daily_limit')->default(5)->comment('Requests per day');
            $table->integer('monthly_limit')->default(150)->comment('Requests per month');
            $table->decimal('cost_limit_monthly', 10, 2)->nullable()->comment('USD limit per month');

            // Current usage
            $table->integer('daily_used')->default(0);
            $table->integer('monthly_used')->default(0);
            $table->decimal('cost_used_monthly', 10, 2)->default(0.00);

            // Reset tracking
            $table->date('last_daily_reset')->nullable();
            $table->date('last_monthly_reset')->nullable();

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Additional settings');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['org_id', 'ai_service'], 'idx_org_service');
            $table->index(['user_id', 'ai_service'], 'idx_user_service');
            $table->index('tier');
        });

        // 2. Create usage tracking table (detailed log)
        Schema::create('cmis_ai.usage_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->uuid('user_id')->nullable();

            $table->string('ai_service')->comment('gpt, gemini, embeddings');
            $table->string('operation')->comment('generate_content, create_embedding, etc');
            $table->string('model_used')->nullable()->comment('gpt-4, text-embedding-004');

            // Request details
            $table->integer('tokens_used')->nullable()->comment('For LLM calls');
            $table->integer('input_length')->nullable()->comment('Characters/tokens');
            $table->integer('output_length')->nullable();
            $table->decimal('estimated_cost', 8, 4)->default(0)->comment('USD');

            // Performance
            $table->integer('response_time_ms')->nullable();
            $table->boolean('cached')->default(false)->comment('Was response from cache?');

            // Status
            $table->string('status')->default('success')->comment('success, error, rate_limited');
            $table->text('error_message')->nullable();

            // Context
            $table->uuid('campaign_id')->nullable()->comment('Related campaign if applicable');
            $table->uuid('content_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->json('request_metadata')->nullable();
            $table->json('response_metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['org_id', 'created_at'], 'idx_org_time');
            $table->index(['user_id', 'created_at'], 'idx_user_time');
            $table->index(['ai_service', 'created_at'], 'idx_service_time');
            $table->index('status');
        });

        // 3. Create usage summary table (aggregated for fast queries)
        Schema::create('cmis_ai.usage_summary', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->uuid('user_id')->nullable();

            $table->string('ai_service');
            $table->date('summary_date');
            $table->string('period_type')->comment('daily, weekly, monthly');

            // Aggregated stats
            $table->integer('total_requests')->default(0);
            $table->integer('successful_requests')->default(0);
            $table->integer('failed_requests')->default(0);
            $table->integer('cached_requests')->default(0);

            $table->bigInteger('total_tokens')->default(0);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->integer('avg_response_time_ms')->nullable();

            $table->timestamps();

            // Indexes and unique constraint
            $table->unique(['org_id', 'ai_service', 'summary_date', 'period_type'], 'idx_summary_unique');
            $table->index(['org_id', 'period_type', 'summary_date'], 'idx_org_period');
        });

        // 4. Enable Row-Level Security on all tables
        DB::statement("ALTER TABLE cmis_ai.usage_quotas ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis_ai.usage_quotas
            USING (
                org_id = current_setting('app.current_org_id', true)::uuid
                OR org_id IS NULL
            )
        ");

        DB::statement("ALTER TABLE cmis_ai.usage_tracking ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis_ai.usage_tracking
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");

        DB::statement("ALTER TABLE cmis_ai.usage_summary ENABLE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY org_isolation ON cmis_ai.usage_summary
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");

        // 5. Insert default system-level quotas
        DB::table('cmis_ai.usage_quotas')->insert([
            // Free tier - GPT
            [
                'id' => DB::raw('gen_random_uuid()'),
                'org_id' => null,
                'user_id' => null,
                'tier' => 'free',
                'ai_service' => 'gpt',
                'daily_limit' => 5,
                'monthly_limit' => 100,
                'cost_limit_monthly' => 10.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Pro tier - GPT
            [
                'id' => DB::raw('gen_random_uuid()'),
                'org_id' => null,
                'user_id' => null,
                'tier' => 'pro',
                'ai_service' => 'gpt',
                'daily_limit' => 50,
                'monthly_limit' => 1000,
                'cost_limit_monthly' => 100.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Enterprise tier - GPT
            [
                'id' => DB::raw('gen_random_uuid()'),
                'org_id' => null,
                'user_id' => null,
                'tier' => 'enterprise',
                'ai_service' => 'gpt',
                'daily_limit' => 999999,
                'monthly_limit' => 999999,
                'cost_limit_monthly' => 1000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Free tier - Embeddings (more generous as cheaper)
            [
                'id' => DB::raw('gen_random_uuid()'),
                'org_id' => null,
                'user_id' => null,
                'tier' => 'free',
                'ai_service' => 'embeddings',
                'daily_limit' => 20,
                'monthly_limit' => 500,
                'cost_limit_monthly' => 5.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmis_ai.usage_summary');
        Schema::dropIfExists('cmis_ai.usage_tracking');
        Schema::dropIfExists('cmis_ai.usage_quotas');
    }
};
