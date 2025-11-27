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
        // 1. Monitoring Keywords - Track keywords, hashtags, brands
        if (!Schema::hasTable('cmis.monitoring_keywords')) {
            Schema::create('cmis.monitoring_keywords', function (Blueprint $table) {
                $table->uuid('keyword_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->uuid('created_by')->nullable(false);
                $table->string('keyword_type', 50)->nullable(false);
                $table->string('keyword', 255)->nullable(false);
                $table->jsonb('variations')->default('[]');
                $table->boolean('case_sensitive')->default(false);
                $table->jsonb('platforms')->default('["facebook","instagram","twitter","linkedin","tiktok","youtube"]');
                $table->string('status', 50)->default('active');
                $table->boolean('enable_alerts')->default(false);
                $table->string('alert_threshold', 50)->nullable();
                $table->jsonb('alert_conditions')->default('{}');
                $table->jsonb('language_filters')->default('["en"]');
                $table->jsonb('location_filters')->default('[]');
                $table->jsonb('exclude_keywords')->default('[]');
                $table->integer('mention_count')->default(0);
                $table->timestamp('last_mention_at')->nullable();
                $table->timestamps();
                $table->index(['org_id', 'status']);
                $table->index(['keyword_type', 'status']);
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.monitoring_keywords ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.monitoring_keywords");
            DB::statement("CREATE POLICY org_isolation ON cmis.monitoring_keywords USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // 2. Social Mentions - Captured mentions from platforms
        if (!Schema::hasTable('cmis.social_mentions')) {
            Schema::create('cmis.social_mentions', function (Blueprint $table) {
                $table->uuid('mention_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->uuid('keyword_id')->nullable(false);
                $table->string('platform', 50)->nullable(false);
                $table->string('platform_post_id', 255)->nullable(false);
                $table->text('post_url')->nullable();
                $table->string('mention_type', 50)->nullable(false);
                $table->string('author_username', 255);
                $table->string('author_display_name', 255)->nullable();
                $table->text('author_profile_url')->nullable();
                $table->string('author_profile_image')->nullable();
                $table->integer('author_followers_count')->default(0);
                $table->boolean('author_is_verified')->default(false);
                $table->text('content')->nullable(false);
                $table->jsonb('media_urls')->default('[]');
                $table->jsonb('hashtags')->default('[]');
                $table->jsonb('mentioned_accounts')->default('[]');
                $table->string('language', 10)->default('en');
                $table->integer('likes_count')->default(0);
                $table->integer('comments_count')->default(0);
                $table->integer('shares_count')->default(0);
                $table->integer('views_count')->default(0);
                $table->decimal('engagement_rate', 5, 2)->default(0);
                $table->string('sentiment', 20)->nullable();
                $table->decimal('sentiment_score', 5, 4)->nullable();
                $table->integer('sentiment_confidence')->nullable();
                $table->jsonb('detected_topics')->default('[]');
                $table->jsonb('detected_entities')->default('[]');
                $table->string('status', 50)->default('new');
                $table->boolean('requires_response')->default(false);
                $table->uuid('assigned_to')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->text('internal_notes')->nullable();
                $table->timestamp('published_at')->nullable(false);
                $table->timestamp('captured_at')->default(DB::raw('NOW()'));
                $table->timestamp('last_synced_at')->nullable();
                $table->jsonb('raw_data')->default('{}');
                $table->timestamps();
                $table->index(['org_id', 'status']);
                $table->index(['keyword_id', 'published_at']);
                $table->index(['platform', 'published_at']);
                $table->index(['sentiment', 'published_at']);
                $table->index('author_username');
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
                $table->foreign('keyword_id')->references('keyword_id')->on('cmis.monitoring_keywords')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.social_mentions ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.social_mentions");
            DB::statement("CREATE POLICY org_isolation ON cmis.social_mentions USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // 3. Sentiment Analysis - AI sentiment results
        if (!Schema::hasTable('cmis.sentiment_analysis')) {
            Schema::create('cmis.sentiment_analysis', function (Blueprint $table) {
                $table->uuid('analysis_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->uuid('mention_id')->nullable(false);
                $table->string('overall_sentiment', 20)->nullable(false);
                $table->decimal('sentiment_score', 5, 4)->nullable(false);
                $table->integer('confidence', 3)->nullable(false);
                $table->decimal('positive_score', 5, 4)->default(0);
                $table->decimal('negative_score', 5, 4)->default(0);
                $table->decimal('neutral_score', 5, 4)->default(0);
                $table->decimal('mixed_score', 5, 4)->default(0);
                $table->jsonb('emotions')->default('{}');
                $table->string('primary_emotion', 50)->nullable();
                $table->jsonb('key_phrases')->default('[]');
                $table->jsonb('entities')->default('[]');
                $table->jsonb('topics')->default('[]');
                $table->string('model_used', 100)->default('gemini-pro');
                $table->string('model_version', 50)->nullable();
                $table->jsonb('model_response')->default('{}');
                $table->timestamp('analyzed_at')->default(DB::raw('NOW()'));
                $table->timestamps();
                $table->index(['org_id', 'overall_sentiment']);
                $table->index('mention_id');
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
                $table->foreign('mention_id')->references('mention_id')->on('cmis.social_mentions')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.sentiment_analysis ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.sentiment_analysis");
            DB::statement("CREATE POLICY org_isolation ON cmis.sentiment_analysis USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // 4. Competitor Profiles - Competitor social accounts
        if (!Schema::hasTable('cmis.competitor_profiles')) {
            Schema::create('cmis.competitor_profiles', function (Blueprint $table) {
                $table->uuid('competitor_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->uuid('created_by')->nullable(false);
                $table->string('competitor_name', 255)->nullable(false);
                $table->string('industry', 100)->nullable();
                $table->text('description')->nullable();
                $table->string('website')->nullable();
                $table->string('logo_url')->nullable();
                $table->jsonb('social_accounts')->default('{}');
                $table->jsonb('monitoring_settings')->default('{}');
                $table->jsonb('follower_counts')->default('{}');
                $table->jsonb('posting_frequency')->default('{}');
                $table->jsonb('engagement_stats')->default('{}');
                $table->jsonb('content_themes')->default('[]');
                $table->string('status', 50)->default('active');
                $table->boolean('enable_alerts')->default(false);
                $table->timestamp('last_analyzed_at')->nullable();
                $table->timestamps();
                $table->index(['org_id', 'status']);
                $table->index('competitor_name');
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.competitor_profiles ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.competitor_profiles");
            DB::statement("CREATE POLICY org_isolation ON cmis.competitor_profiles USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // 5. Trending Topics - Detected trends and topics
        if (!Schema::hasTable('cmis.trending_topics')) {
            Schema::create('cmis.trending_topics', function (Blueprint $table) {
                $table->uuid('trend_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->string('topic', 255)->nullable(false);
                $table->string('topic_type', 50)->nullable(false);
                $table->text('description')->nullable();
                $table->jsonb('related_keywords')->default('[]');
                $table->integer('mention_count')->default(0);
                $table->integer('mention_count_24h')->default(0);
                $table->integer('mention_count_7d')->default(0);
                $table->decimal('growth_rate', 8, 2)->default(0);
                $table->string('trend_velocity', 20)->default('normal');
                $table->jsonb('platform_distribution')->default('{}');
                $table->jsonb('geographic_distribution')->default('{}');
                $table->string('overall_sentiment', 20)->default('neutral');
                $table->decimal('avg_sentiment_score', 5, 4)->default(0);
                $table->timestamp('first_seen_at')->nullable(false);
                $table->timestamp('peak_at')->nullable();
                $table->timestamp('last_seen_at')->nullable(false);
                $table->string('status', 50)->default('active');
                $table->decimal('relevance_score', 5, 2)->default(0);
                $table->boolean('is_opportunity')->default(false);
                $table->timestamps();
                $table->index(['org_id', 'status']);
                $table->index(['relevance_score', 'status']);
                $table->index('first_seen_at');
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.trending_topics ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.trending_topics");
            DB::statement("CREATE POLICY org_isolation ON cmis.trending_topics USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // 6. Monitoring Alerts - Alert configurations and logs
        if (!Schema::hasTable('cmis.monitoring_alerts')) {
            Schema::create('cmis.monitoring_alerts', function (Blueprint $table) {
                $table->uuid('alert_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->uuid('created_by')->nullable(false);
                $table->string('alert_name', 255)->nullable(false);
                $table->string('alert_type', 50)->nullable(false);
                $table->text('description')->nullable();
                $table->jsonb('trigger_conditions')->nullable(false);
                $table->string('severity', 20)->default('medium');
                $table->integer('threshold_value')->nullable();
                $table->string('threshold_unit', 50)->nullable();
                $table->jsonb('notification_channels')->default('["email"]');
                $table->jsonb('recipients')->default('[]');
                $table->integer('notification_frequency')->default(60);
                $table->timestamp('last_notification_at')->nullable();
                $table->string('status', 50)->default('active');
                $table->integer('trigger_count')->default(0);
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamps();
                $table->index(['org_id', 'status']);
                $table->index(['alert_type', 'status']);
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.monitoring_alerts ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.monitoring_alerts");
            DB::statement("CREATE POLICY org_isolation ON cmis.monitoring_alerts USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // 7. Social Conversations - Threaded conversation tracking
        if (!Schema::hasTable('cmis.social_conversations')) {
            Schema::create('cmis.social_conversations', function (Blueprint $table) {
                $table->uuid('conversation_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->uuid('root_mention_id')->nullable(false);
                $table->string('platform', 50)->nullable(false);
                $table->string('conversation_type', 50)->default('thread');
                $table->string('status', 50)->default('open');
                $table->uuid('assigned_to')->nullable();
                $table->string('priority', 20)->default('normal');
                $table->jsonb('participants')->default('[]');
                $table->integer('message_count')->default(1);
                $table->integer('unread_count')->default(0);
                $table->string('overall_sentiment', 20)->nullable();
                $table->jsonb('topics')->default('[]');
                $table->boolean('requires_escalation')->default(false);
                $table->timestamp('first_response_at')->nullable();
                $table->timestamp('last_response_at')->nullable();
                $table->integer('response_time_minutes')->nullable();
                $table->integer('resolution_time_minutes')->nullable();
                $table->text('internal_notes')->nullable();
                $table->jsonb('tags')->default('[]');
                $table->timestamp('last_activity_at')->nullable(false);
                $table->timestamps();
                $table->index(['org_id', 'status']);
                $table->index(['assigned_to', 'status']);
                $table->index('last_activity_at');
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
                $table->foreign('root_mention_id')->references('mention_id')->on('cmis.social_mentions')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.social_conversations ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.social_conversations");
            DB::statement("CREATE POLICY org_isolation ON cmis.social_conversations USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // 8. Response Templates - Quick response templates
        if (!Schema::hasTable('cmis.response_templates')) {
            Schema::create('cmis.response_templates', function (Blueprint $table) {
                $table->uuid('template_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('org_id')->nullable(false);
                $table->uuid('created_by')->nullable(false);
                $table->string('template_name', 255)->nullable(false);
                $table->string('category', 100)->nullable();
                $table->text('template_content')->nullable(false);
                $table->text('description')->nullable();
                $table->jsonb('variables')->default('[]');
                $table->jsonb('suggested_triggers')->default('[]');
                $table->integer('usage_count')->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->decimal('effectiveness_score', 5, 2)->default(0);
                $table->jsonb('platforms')->default('["facebook","instagram","twitter","linkedin"]');
                $table->integer('character_count')->default(0);
                $table->string('status', 50)->default('active');
                $table->boolean('is_public')->default(false);
                $table->timestamps();
                $table->index(['org_id', 'status']);
                $table->index(['category', 'status']);
                $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            });

            DB::statement("ALTER TABLE cmis.response_templates ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.response_templates");
            DB::statement("CREATE POLICY org_isolation ON cmis.response_templates USING (org_id = current_setting('app.current_org_id', true)::uuid)");
        }

        // Create Performance Views
        DB::statement("DROP VIEW IF EXISTS cmis.v_listening_performance");
        DB::statement("
            CREATE VIEW cmis.v_listening_performance AS
            SELECT
                m.org_id,
                m.keyword_id,
                k.keyword,
                k.keyword_type,
                COUNT(m.mention_id) as total_mentions,
                COUNT(CASE WHEN m.sentiment = 'positive' THEN 1 END) as positive_mentions,
                COUNT(CASE WHEN m.sentiment = 'negative' THEN 1 END) as negative_mentions,
                COUNT(CASE WHEN m.sentiment = 'neutral' THEN 1 END) as neutral_mentions,
                AVG(m.sentiment_score) as avg_sentiment_score,
                SUM(m.likes_count) as total_likes,
                SUM(m.comments_count) as total_comments,
                SUM(m.shares_count) as total_shares,
                AVG(m.engagement_rate) as avg_engagement_rate,
                COUNT(CASE WHEN m.requires_response THEN 1 END) as pending_responses,
                MAX(m.published_at) as last_mention_at
            FROM cmis.social_mentions m
            JOIN cmis.monitoring_keywords k ON m.keyword_id = k.keyword_id
            GROUP BY m.org_id, m.keyword_id, k.keyword, k.keyword_type
        ");

        DB::statement("DROP VIEW IF EXISTS cmis.v_sentiment_timeline");
        DB::statement("
            CREATE VIEW cmis.v_sentiment_timeline AS
            SELECT
                m.org_id,
                m.keyword_id,
                DATE(m.published_at) as date,
                m.platform,
                COUNT(*) as mention_count,
                AVG(m.sentiment_score) as avg_sentiment,
                COUNT(CASE WHEN m.sentiment = 'positive' THEN 1 END) as positive_count,
                COUNT(CASE WHEN m.sentiment = 'negative' THEN 1 END) as negative_count,
                COUNT(CASE WHEN m.sentiment = 'neutral' THEN 1 END) as neutral_count,
                SUM(m.engagement_rate) as total_engagement
            FROM cmis.social_mentions m
            GROUP BY m.org_id, m.keyword_id, DATE(m.published_at), m.platform
            ORDER BY date DESC
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS cmis.v_sentiment_timeline');
        DB::statement('DROP VIEW IF EXISTS cmis.v_listening_performance');
        Schema::dropIfExists('cmis.response_templates');
        Schema::dropIfExists('cmis.social_conversations');
        Schema::dropIfExists('cmis.monitoring_alerts');
        Schema::dropIfExists('cmis.trending_topics');
        Schema::dropIfExists('cmis.competitor_profiles');
        Schema::dropIfExists('cmis.sentiment_analysis');
        Schema::dropIfExists('cmis.social_mentions');
        Schema::dropIfExists('cmis.monitoring_keywords');
    }
};
