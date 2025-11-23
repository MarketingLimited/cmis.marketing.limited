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
     */
    public function up(): void
    {
        // 1. Monitoring Keywords - Track keywords, hashtags, brands
        Schema::create('cmis.monitoring_keywords', function (Blueprint $table) {
            $table->uuid('keyword_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Keyword Configuration
            $table->string('keyword_type', 50)->nullable(false); // brand, hashtag, keyword, phrase, mention
            $table->string('keyword', 255)->nullable(false);
            $table->jsonb('variations')->default('[]'); // Alternative spellings, misspellings
            $table->boolean('case_sensitive')->default(false);

            // Monitoring Settings
            $table->jsonb('platforms')->default('["facebook","instagram","twitter","linkedin","tiktok","youtube"]');
            $table->string('status', 50)->default('active'); // active, paused, archived
            $table->boolean('enable_alerts')->default(false);
            $table->string('alert_threshold', 50)->nullable(); // high, medium, low
            $table->jsonb('alert_conditions')->default('{}'); // Sentiment, volume thresholds

            // Filters
            $table->jsonb('language_filters')->default('["en"]');
            $table->jsonb('location_filters')->default('[]');
            $table->jsonb('exclude_keywords')->default('[]'); // Keywords to exclude

            // Metadata
            $table->integer('mention_count')->default(0);
            $table->timestamp('last_mention_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['keyword_type', 'status']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.monitoring_keywords');

        // 2. Social Mentions - Captured mentions from platforms
        Schema::create('cmis.social_mentions', function (Blueprint $table) {
            $table->uuid('mention_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('keyword_id')->nullable(false);

            // Source Information
            $table->string('platform', 50)->nullable(false);
            $table->string('platform_post_id', 255)->nullable(false);
            $table->text('post_url')->nullable();
            $table->string('mention_type', 50)->nullable(false); // direct_mention, hashtag, keyword, comment, reply

            // Author Information
            $table->string('author_username', 255);
            $table->string('author_display_name', 255)->nullable();
            $table->text('author_profile_url')->nullable();
            $table->string('author_profile_image')->nullable();
            $table->integer('author_followers_count')->default(0);
            $table->boolean('author_is_verified')->default(false);

            // Content
            $table->text('content')->nullable(false);
            $table->jsonb('media_urls')->default('[]');
            $table->jsonb('hashtags')->default('[]');
            $table->jsonb('mentioned_accounts')->default('[]');
            $table->string('language', 10)->default('en');

            // Engagement Metrics
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);

            // Sentiment & Analysis
            $table->string('sentiment', 20)->nullable(); // positive, negative, neutral, mixed
            $table->decimal('sentiment_score', 5, 4)->nullable(); // -1.0 to 1.0
            $table->integer('sentiment_confidence')->nullable(); // 0-100
            $table->jsonb('detected_topics')->default('[]');
            $table->jsonb('detected_entities')->default('[]'); // People, places, products

            // Status & Actions
            $table->string('status', 50)->default('new'); // new, reviewed, responded, archived, flagged
            $table->boolean('requires_response')->default(false);
            $table->uuid('assigned_to')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('internal_notes')->nullable();

            // Metadata
            $table->timestamp('published_at')->nullable(false);
            $table->timestamp('captured_at')->default(DB::raw('NOW()'));
            $table->timestamp('last_synced_at')->nullable();
            $table->jsonb('raw_data')->default('{}'); // Full platform response

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['keyword_id', 'published_at']);
            $table->index(['platform', 'published_at']);
            $table->index(['sentiment', 'published_at']);
            $table->index('author_username');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('keyword_id')->references('keyword_id')->on('cmis.monitoring_keywords')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.social_mentions');

        // 3. Sentiment Analysis - AI sentiment results
        Schema::create('cmis.sentiment_analysis', function (Blueprint $table) {
            $table->uuid('analysis_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('mention_id')->nullable(false);

            // Sentiment Scores
            $table->string('overall_sentiment', 20)->nullable(false); // positive, negative, neutral, mixed
            $table->decimal('sentiment_score', 5, 4)->nullable(false); // -1.0 to 1.0
            $table->integer('confidence', 3)->nullable(false); // 0-100

            // Detailed Sentiment Breakdown
            $table->decimal('positive_score', 5, 4)->default(0);
            $table->decimal('negative_score', 5, 4)->default(0);
            $table->decimal('neutral_score', 5, 4)->default(0);
            $table->decimal('mixed_score', 5, 4)->default(0);

            // Emotion Detection
            $table->jsonb('emotions')->default('{}'); // joy, sadness, anger, fear, surprise
            $table->string('primary_emotion', 50)->nullable();

            // Topic & Entity Analysis
            $table->jsonb('key_phrases')->default('[]');
            $table->jsonb('entities')->default('[]'); // People, organizations, locations, products
            $table->jsonb('topics')->default('[]');

            // AI Model Information
            $table->string('model_used', 100)->default('gemini-pro');
            $table->string('model_version', 50)->nullable();
            $table->jsonb('model_response')->default('{}');

            $table->timestamp('analyzed_at')->default(DB::raw('NOW()'));
            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'overall_sentiment']);
            $table->index('mention_id');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('mention_id')->references('mention_id')->on('cmis.social_mentions')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.sentiment_analysis');

        // 4. Competitor Profiles - Competitor social accounts
        Schema::create('cmis.competitor_profiles', function (Blueprint $table) {
            $table->uuid('competitor_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Competitor Information
            $table->string('competitor_name', 255)->nullable(false);
            $table->string('industry', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();

            // Social Media Accounts
            $table->jsonb('social_accounts')->default('{}'); // {platform: {username, url, account_id}}
            $table->jsonb('monitoring_settings')->default('{}');

            // Tracking Metrics
            $table->jsonb('follower_counts')->default('{}'); // Per platform
            $table->jsonb('posting_frequency')->default('{}'); // Posts per day per platform
            $table->jsonb('engagement_stats')->default('{}'); // Avg engagement per platform
            $table->jsonb('content_themes')->default('[]'); // Most common topics

            // Status
            $table->string('status', 50)->default('active'); // active, paused, archived
            $table->boolean('enable_alerts')->default(false);
            $table->timestamp('last_analyzed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index('competitor_name');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.competitor_profiles');

        // 5. Trending Topics - Detected trends and topics
        Schema::create('cmis.trending_topics', function (Blueprint $table) {
            $table->uuid('trend_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);

            // Topic Information
            $table->string('topic', 255)->nullable(false);
            $table->string('topic_type', 50)->nullable(false); // hashtag, keyword, phrase, event
            $table->text('description')->nullable();
            $table->jsonb('related_keywords')->default('[]');

            // Trend Metrics
            $table->integer('mention_count')->default(0);
            $table->integer('mention_count_24h')->default(0);
            $table->integer('mention_count_7d')->default(0);
            $table->decimal('growth_rate', 8, 2)->default(0); // Percentage growth
            $table->string('trend_velocity', 20)->default('normal'); // viral, rising, normal, declining

            // Platform Distribution
            $table->jsonb('platform_distribution')->default('{}'); // Mentions per platform
            $table->jsonb('geographic_distribution')->default('{}'); // Mentions by location

            // Sentiment
            $table->string('overall_sentiment', 20)->default('neutral');
            $table->decimal('avg_sentiment_score', 5, 4)->default(0);

            // Timing
            $table->timestamp('first_seen_at')->nullable(false);
            $table->timestamp('peak_at')->nullable();
            $table->timestamp('last_seen_at')->nullable(false);
            $table->string('status', 50)->default('active'); // active, declining, expired

            // Relevance Score
            $table->decimal('relevance_score', 5, 2)->default(0); // 0-100
            $table->boolean('is_opportunity')->default(false); // Marketing opportunity flag

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['relevance_score', 'status']);
            $table->index('first_seen_at');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.trending_topics');

        // 6. Monitoring Alerts - Alert configurations and logs
        Schema::create('cmis.monitoring_alerts', function (Blueprint $table) {
            $table->uuid('alert_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Alert Configuration
            $table->string('alert_name', 255)->nullable(false);
            $table->string('alert_type', 50)->nullable(false); // mention, sentiment, volume, competitor, trend
            $table->text('description')->nullable();

            // Trigger Conditions
            $table->jsonb('trigger_conditions')->nullable(false); // {keyword_ids, sentiment, threshold, etc.}
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->integer('threshold_value')->nullable();
            $table->string('threshold_unit', 50)->nullable(); // mentions, sentiment_score, growth_rate

            // Notification Settings
            $table->jsonb('notification_channels')->default('["email"]'); // email, sms, slack, webhook
            $table->jsonb('recipients')->default('[]'); // User IDs or external contacts
            $table->integer('notification_frequency')->default(60); // Minutes between notifications
            $table->timestamp('last_notification_at')->nullable();

            // Status
            $table->string('status', 50)->default('active'); // active, paused, archived
            $table->integer('trigger_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['alert_type', 'status']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.monitoring_alerts');

        // 7. Social Conversations - Threaded conversation tracking
        Schema::create('cmis.social_conversations', function (Blueprint $table) {
            $table->uuid('conversation_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('root_mention_id')->nullable(false); // Original post/mention

            // Conversation Metadata
            $table->string('platform', 50)->nullable(false);
            $table->string('conversation_type', 50)->default('thread'); // thread, dm, comment_chain
            $table->string('status', 50)->default('open'); // open, in_progress, resolved, closed
            $table->uuid('assigned_to')->nullable();
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent

            // Participants
            $table->jsonb('participants')->default('[]'); // List of usernames involved
            $table->integer('message_count')->default(1);
            $table->integer('unread_count')->default(0);

            // Sentiment & Topics
            $table->string('overall_sentiment', 20)->nullable();
            $table->jsonb('topics')->default('[]');
            $table->boolean('requires_escalation')->default(false);

            // Response Tracking
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('last_response_at')->nullable();
            $table->integer('response_time_minutes')->nullable(); // Time to first response
            $table->integer('resolution_time_minutes')->nullable(); // Time to resolution

            // Metadata
            $table->text('internal_notes')->nullable();
            $table->jsonb('tags')->default('[]');
            $table->timestamp('last_activity_at')->nullable(false);

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('last_activity_at');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('root_mention_id')->references('mention_id')->on('cmis.social_mentions')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.social_conversations');

        // 8. Response Templates - Quick response templates
        Schema::create('cmis.response_templates', function (Blueprint $table) {
            $table->uuid('template_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Template Information
            $table->string('template_name', 255)->nullable(false);
            $table->string('category', 100)->nullable(); // support, feedback, complaint, inquiry
            $table->text('template_content')->nullable(false);
            $table->text('description')->nullable();

            // Template Variables
            $table->jsonb('variables')->default('[]'); // Placeholders like {customer_name}, {product}
            $table->jsonb('suggested_triggers')->default('[]'); // Keywords that suggest this template

            // Usage Tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->decimal('effectiveness_score', 5, 2)->default(0); // Based on response success

            // Platform Compatibility
            $table->jsonb('platforms')->default('["facebook","instagram","twitter","linkedin"]');
            $table->integer('character_count')->default(0);

            // Status
            $table->string('status', 50)->default('active'); // active, archived
            $table->boolean('is_public')->default(false); // Shared across team

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['category', 'status']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        $this->enableRLS('cmis.response_templates');

        // Create Performance Views

        // View 1: Listening Performance Dashboard
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_listening_performance AS
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
            GROUP BY m.org_id, m.keyword_id, k.keyword, k.keyword_type;
        ");

        // View 2: Sentiment Timeline
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_sentiment_timeline AS
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
            ORDER BY date DESC;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement('DROP VIEW IF EXISTS cmis.v_sentiment_timeline');
        DB::statement('DROP VIEW IF EXISTS cmis.v_listening_performance');

        // Drop tables in reverse order
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
