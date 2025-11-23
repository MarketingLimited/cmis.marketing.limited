<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     *
     * Create missing tables identified in test failures
     */
    public function up(): void
    {
        // Create cmis.templates table
        if (!Schema::hasTable('cmis.templates')) {
            Schema::create('cmis.templates', function (Blueprint $table) {
                $table->uuid('template_id')->primary();
                $table->uuid('org_id');
                $table->string('template_name');
                $table->string('template_type')->nullable();
                $table->text('content')->nullable();
                $table->integer('usage_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index('org_id');
            });

            // Add RLS policy
            
            $this->enableRLS('cmis.templates');
            echo "✓ Created cmis.templates table with RLS\n";
        }

        // Create cmis.comments table
        if (!Schema::hasTable('cmis.comments')) {
            Schema::create('cmis.comments', function (Blueprint $table) {
                $table->uuid('comment_id')->primary();
                $table->uuid('org_id');
                $table->uuid('post_id')->nullable();
                $table->uuid('user_id')->nullable();
                $table->text('body');
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->softDeletes();

                $table->index('org_id');
                $table->index('post_id');
            });

            // Add RLS policy
            
            $this->enableRLS('cmis.comments');
            echo "✓ Created cmis.comments table with RLS\n";
        }

        // Create cmis.scheduled_posts table
        if (!Schema::hasTable('cmis.scheduled_posts')) {
            Schema::create('cmis.scheduled_posts', function (Blueprint $table) {
                $table->uuid('post_id')->primary();
                $table->uuid('org_id');
                $table->uuid('campaign_id')->nullable();
                $table->text('content');
                $table->string('platform')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->string('status')->default('pending');
                $table->integer('retry_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index('org_id');
                $table->index('campaign_id');
                $table->index('scheduled_at');
            });

            // Add RLS policy
            
            $this->enableRLS('cmis.scheduled_posts');
            echo "✓ Created cmis.scheduled_posts table with RLS\n";
        }

        // Create cmis.ad_campaigns_v2 table
        if (!Schema::hasTable('cmis.ad_campaigns_v2')) {
            Schema::create('cmis.ad_campaigns_v2', function (Blueprint $table) {
                $table->uuid('campaign_id')->primary();
                $table->uuid('org_id');
                $table->string('campaign_name');
                $table->string('platform')->nullable();
                $table->string('status')->default('draft');
                $table->decimal('budget', 15, 2)->nullable();
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->jsonb('targeting')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('org_id');
                $table->index('platform');
            });

            // Add RLS policy
            
            $this->enableRLS('cmis.ad_campaigns_v2');
            echo "✓ Created cmis.ad_campaigns_v2 table with RLS\n";
        }

        // Create cmis.semantic_search_log table
        if (!Schema::hasTable('cmis.semantic_search_log')) {
            Schema::create('cmis.semantic_search_log', function (Blueprint $table) {
                $table->uuid('log_id')->primary();
                $table->uuid('org_id');
                $table->uuid('user_id')->nullable();
                $table->text('query');
                $table->integer('results_count')->default(0);
                $table->decimal('search_time_ms', 10, 2)->nullable();
                $table->timestamps();

                $table->index('org_id');
                $table->index('created_at');
            });

            // Add RLS policy
            
            $this->enableRLS('cmis.semantic_search_log');
            echo "✓ Created cmis.semantic_search_log table with RLS\n";
        }

        // Create cmis.knowledge_indexes table (for AI knowledge base)
        if (!Schema::hasTable('cmis.knowledge_indexes')) {
            Schema::create('cmis.knowledge_indexes', function (Blueprint $table) {
                $table->uuid('index_id')->primary();
                $table->uuid('org_id');
                $table->string('index_name');
                $table->text('description')->nullable();
                $table->string('index_type')->default('semantic');
                $table->integer('document_count')->default(0);
                $table->timestamps();

                $table->index('org_id');
            });

            // Add RLS policy
            
            $this->enableRLS('cmis.knowledge_indexes');
            echo "✓ Created cmis.knowledge_indexes table with RLS\n";
        }

        // Create cmis.ads table (generic ads table)
        if (!Schema::hasTable('cmis.ads')) {
            Schema::create('cmis.ads', function (Blueprint $table) {
                $table->uuid('ad_id')->primary();
                $table->uuid('org_id');
                $table->uuid('campaign_id')->nullable();
                $table->string('ad_name');
                $table->string('platform')->nullable();
                $table->text('ad_copy')->nullable();
                $table->string('creative_url')->nullable();
                $table->string('status')->default('draft');
                $table->timestamps();
                $table->softDeletes();

                $table->index('org_id');
                $table->index('campaign_id');
                $table->index('platform');
            });

            // Add RLS policy
            
            $this->enableRLS('cmis.ads');
            echo "✓ Created cmis.ads table with RLS\n";
        }

        echo "\n✅ All missing tables created successfully with RLS policies!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.ads');
        Schema::dropIfExists('cmis.knowledge_indexes');
        Schema::dropIfExists('cmis.semantic_search_log');
        Schema::dropIfExists('cmis.ad_campaigns_v2');
        Schema::dropIfExists('cmis.scheduled_posts');
        Schema::dropIfExists('cmis.comments');
        Schema::dropIfExists('cmis.templates');
    }
};
