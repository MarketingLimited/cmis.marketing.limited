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
        // 1. Influencer Profiles - Influencer database
        Schema::create('cmis.influencer_profiles', function (Blueprint $table) {
            $table->uuid('influencer_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('added_by')->nullable(false);

            // Basic Information
            $table->string('full_name', 255)->nullable(false);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('location', 255)->nullable();
            $table->jsonb('languages')->default('["en"]');

            // Social Media Accounts
            $table->jsonb('social_accounts')->nullable(false); // {platform: {username, url, followers, engagement_rate}}
            $table->integer('total_followers')->default(0);
            $table->decimal('avg_engagement_rate', 5, 2)->default(0);

            // Niche & Categories
            $table->jsonb('niches')->default('[]'); // fashion, tech, beauty, fitness, etc.
            $table->jsonb('content_types')->default('[]'); // video, photo, story, blog, etc.
            $table->string('tier', 20)->default('micro'); // nano, micro, mid, macro, mega, celebrity

            // Audience Demographics
            $table->jsonb('audience_demographics')->default('{}'); // age, gender, location, interests
            $table->jsonb('audience_quality_score')->default('{}'); // authenticity, engagement quality

            // Performance Metrics
            $table->decimal('authenticity_score', 5, 2)->default(0); // 0-100
            $table->decimal('reliability_score', 5, 2)->default(0); // 0-100 based on past performance
            $table->integer('completed_campaigns')->default(0);
            $table->integer('total_campaigns')->default(0);
            $table->decimal('avg_roi', 8, 2)->default(0);

            // Rates & Availability
            $table->jsonb('rates')->default('{}'); // Per platform and content type
            $table->boolean('available_for_partnerships')->default(true);
            $table->string('preferred_collaboration_type', 50)->nullable(); // paid, barter, affiliate, gifting

            // Contract Information
            $table->boolean('exclusive_partnership')->default(false);
            $table->jsonb('blacklisted_brands')->default('[]');
            $table->jsonb('preferred_brands')->default('[]');

            // Status & Tags
            $table->string('status', 50)->default('active'); // active, inactive, blacklisted, pending
            $table->jsonb('tags')->default('[]');
            $table->text('internal_notes')->nullable();

            // Discovery Information
            $table->string('source', 50)->default('manual'); // manual, discovery, application, referral
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('last_campaign_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index('tier');
            $table->index('total_followers');
            $table->index(['avg_engagement_rate', 'status']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("
            ALTER TABLE cmis.influencer_profiles ENABLE ROW LEVEL SECURITY;
            CREATE POLICY org_isolation ON cmis.influencer_profiles
            USING (org_id = current_setting('app.current_org_id', true)::uuid);
        ");

        // 2. Influencer Partnerships - Partnership agreements
        Schema::create('cmis.influencer_partnerships', function (Blueprint $table) {
            $table->uuid('partnership_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('influencer_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Partnership Details
            $table->string('partnership_name', 255)->nullable(false);
            $table->string('partnership_type', 50)->nullable(false); // one-time, ongoing, ambassador, affiliate
            $table->text('description')->nullable();

            // Contract Information
            $table->string('contract_status', 50)->default('draft'); // draft, sent, signed, active, completed, terminated
            $table->text('contract_url')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->boolean('auto_renew')->default(false);

            // Compensation
            $table->string('compensation_type', 50)->nullable(false); // fixed, per_post, commission, barter, hybrid
            $table->decimal('fixed_amount', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0); // Percentage for affiliate
            $table->string('currency', 3)->default('USD');
            $table->jsonb('payment_terms')->default('{}'); // Net 30, 50% upfront, etc.

            // Deliverables & Terms
            $table->jsonb('deliverables')->default('[]'); // Expected content/posts
            $table->integer('min_posts_per_month')->default(0);
            $table->jsonb('content_guidelines')->default('{}');
            $table->jsonb('approval_requirements')->default('{}');
            $table->boolean('requires_exclusivity')->default(false);
            $table->jsonb('exclusivity_terms')->default('{}');

            // Performance Requirements
            $table->jsonb('kpi_targets')->default('{}'); // Reach, engagement, conversions
            $table->jsonb('performance_bonuses')->default('[]');

            // Status & Management
            $table->string('status', 50)->default('active'); // active, paused, completed, cancelled
            $table->uuid('account_manager')->nullable();
            $table->text('internal_notes')->nullable();
            $table->jsonb('tags')->default('[]');

            // Tracking
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->integer('campaigns_completed')->default(0);
            $table->timestamp('last_payment_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['influencer_id', 'status']);
            $table->index('contract_status');
            $table->index('contract_end_date');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('influencer_id')->references('influencer_id')->on('cmis.influencer_profiles')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("
            ALTER TABLE cmis.influencer_partnerships ENABLE ROW LEVEL SECURITY;
            CREATE POLICY org_isolation ON cmis.influencer_partnerships
            USING (org_id = current_setting('app.current_org_id', true)::uuid);
        ");

        // 3. Influencer Campaigns - Campaigns with influencers
        Schema::create('cmis.influencer_campaigns', function (Blueprint $table) {
            $table->uuid('campaign_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('partnership_id')->nullable(false);
            $table->uuid('created_by')->nullable(false);

            // Campaign Details
            $table->string('campaign_name', 255)->nullable(false);
            $table->text('campaign_brief')->nullable();
            $table->string('campaign_objective', 50)->nullable(false); // awareness, engagement, conversions, traffic
            $table->jsonb('target_audience')->default('{}');

            // Timeline
            $table->date('start_date')->nullable(false);
            $table->date('end_date')->nullable(false);
            $table->timestamp('launch_date')->nullable();

            // Content Requirements
            $table->integer('required_posts')->default(1);
            $table->jsonb('content_requirements')->default('{}'); // platforms, formats, hashtags, mentions
            $table->jsonb('brand_guidelines')->default('{}');
            $table->boolean('content_approval_required')->default(true);

            // Budget & Compensation
            $table->decimal('budget', 12, 2)->nullable(false);
            $table->decimal('influencer_payment', 12, 2)->nullable(false);
            $table->string('payment_status', 50)->default('pending'); // pending, partial, paid
            $table->decimal('amount_paid', 12, 2)->default(0);

            // Tracking
            $table->string('tracking_link')->nullable();
            $table->string('promo_code')->nullable();
            $table->string('utm_parameters')->nullable();

            // Performance Metrics
            $table->integer('posts_delivered')->default(0);
            $table->integer('total_reach')->default(0);
            $table->integer('total_impressions')->default(0);
            $table->integer('total_engagement')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_value', 12, 2)->default(0);
            $table->decimal('roi', 8, 2)->default(0);
            $table->decimal('cpe', 8, 2)->default(0); // Cost per engagement
            $table->decimal('cpc', 8, 2)->default(0); // Cost per click
            $table->decimal('cpa', 8, 2)->default(0); // Cost per acquisition

            // Status
            $table->string('status', 50)->default('draft'); // draft, active, in_review, completed, cancelled
            $table->text('internal_notes')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['partnership_id', 'status']);
            $table->index(['start_date', 'end_date']);

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('partnership_id')->references('partnership_id')->on('cmis.influencer_partnerships')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("
            ALTER TABLE cmis.influencer_campaigns ENABLE ROW LEVEL SECURITY;
            CREATE POLICY org_isolation ON cmis.influencer_campaigns
            USING (org_id = current_setting('app.current_org_id', true)::uuid);
        ");

        // 4. Campaign Deliverables - Content deliverables and approvals
        Schema::create('cmis.campaign_deliverables', function (Blueprint $table) {
            $table->uuid('deliverable_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('campaign_id')->nullable(false);

            // Deliverable Details
            $table->string('deliverable_type', 50)->nullable(false); // post, story, reel, video, blog, etc.
            $table->string('platform', 50)->nullable(false);
            $table->text('content_description')->nullable();
            $table->date('due_date')->nullable(false);

            // Content Submission
            $table->text('draft_content')->nullable();
            $table->jsonb('draft_media_urls')->default('[]');
            $table->timestamp('submitted_at')->nullable();

            // Approval Workflow
            $table->string('approval_status', 50)->default('pending'); // pending, approved, rejected, revision_requested
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('feedback')->nullable();
            $table->integer('revision_count')->default(0);

            // Publishing
            $table->string('post_url')->nullable();
            $table->string('platform_post_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_published')->default(false);

            // Performance Metrics
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('saves')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->timestamp('metrics_last_synced_at')->nullable();

            // Status
            $table->string('status', 50)->default('pending'); // pending, in_progress, submitted, approved, published, failed
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['campaign_id', 'status']);
            $table->index(['platform', 'is_published']);
            $table->index('due_date');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('campaign_id')->references('campaign_id')->on('cmis.influencer_campaigns')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("
            ALTER TABLE cmis.campaign_deliverables ENABLE ROW LEVEL SECURITY;
            CREATE POLICY org_isolation ON cmis.campaign_deliverables
            USING (org_id = current_setting('app.current_org_id', true)::uuid);
        ");

        // 5. Influencer Payments - Payment tracking and history
        Schema::create('cmis.influencer_payments', function (Blueprint $table) {
            $table->uuid('payment_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('partnership_id')->nullable(false);
            $table->uuid('campaign_id')->nullable();

            // Payment Details
            $table->string('payment_type', 50)->nullable(false); // campaign, bonus, affiliate, monthly
            $table->decimal('amount', 12, 2)->nullable(false);
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();

            // Payment Information
            $table->string('payment_method', 50)->nullable(); // bank_transfer, paypal, stripe, check
            $table->string('payment_reference')->nullable();
            $table->date('payment_date')->nullable();
            $table->date('due_date')->nullable();

            // Status
            $table->string('status', 50)->default('pending'); // pending, processing, paid, failed, cancelled
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Tracking
            $table->string('invoice_number')->nullable();
            $table->text('invoice_url')->nullable();
            $table->text('receipt_url')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['partnership_id', 'status']);
            $table->index('campaign_id');
            $table->index('payment_date');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('partnership_id')->references('partnership_id')->on('cmis.influencer_partnerships')->onDelete('cascade');
            $table->foreign('campaign_id')->references('campaign_id')->on('cmis.influencer_campaigns')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("
            ALTER TABLE cmis.influencer_payments ENABLE ROW LEVEL SECURITY;
            CREATE POLICY org_isolation ON cmis.influencer_payments
            USING (org_id = current_setting('app.current_org_id', true)::uuid);
        ");

        // 6. Influencer Applications - Applications to campaigns
        Schema::create('cmis.influencer_applications', function (Blueprint $table) {
            $table->uuid('application_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('influencer_id')->nullable(false);
            $table->uuid('campaign_id')->nullable()->comment('Null if general application');

            // Application Details
            $table->string('application_type', 50)->default('campaign'); // campaign, partnership, ambassador
            $table->text('cover_letter')->nullable();
            $table->jsonb('portfolio_links')->default('[]');
            $table->jsonb('relevant_stats')->default('{}');
            $table->decimal('proposed_rate', 12, 2)->nullable();

            // Review
            $table->string('status', 50)->default('pending'); // pending, reviewed, accepted, rejected
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Follow-up
            $table->timestamp('contacted_at')->nullable();
            $table->string('contact_method', 50)->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'status']);
            $table->index(['influencer_id', 'status']);
            $table->index('campaign_id');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('influencer_id')->references('influencer_id')->on('cmis.influencer_profiles')->onDelete('cascade');
            $table->foreign('campaign_id')->references('campaign_id')->on('cmis.influencer_campaigns')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("
            ALTER TABLE cmis.influencer_applications ENABLE ROW LEVEL SECURITY;
            CREATE POLICY org_isolation ON cmis.influencer_applications
            USING (org_id = current_setting('app.current_org_id', true)::uuid);
        ");

        // 7. Influencer Performance History - Historical performance data
        Schema::create('cmis.influencer_performance', function (Blueprint $table) {
            $table->uuid('performance_id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('org_id')->nullable(false);
            $table->uuid('influencer_id')->nullable(false);
            $table->uuid('campaign_id')->nullable();

            // Time Period
            $table->date('period_start')->nullable(false);
            $table->date('period_end')->nullable(false);
            $table->string('period_type', 20)->default('campaign'); // campaign, monthly, quarterly

            // Metrics
            $table->integer('total_posts')->default(0);
            $table->integer('total_reach')->default(0);
            $table->integer('total_impressions')->default(0);
            $table->integer('total_engagement')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('avg_engagement_rate', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);

            // Financial
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('revenue_generated', 12, 2)->default(0);
            $table->decimal('roi', 8, 2)->default(0);

            // Quality Scores
            $table->decimal('content_quality_score', 5, 2)->default(0); // 0-100
            $table->decimal('timeliness_score', 5, 2)->default(0); // 0-100
            $table->decimal('collaboration_score', 5, 2)->default(0); // 0-100

            $table->timestamps();

            // Indexes
            $table->index(['org_id', 'influencer_id']);
            $table->index(['period_start', 'period_end']);
            $table->index('campaign_id');

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('influencer_id')->references('influencer_id')->on('cmis.influencer_profiles')->onDelete('cascade');
            $table->foreign('campaign_id')->references('campaign_id')->on('cmis.influencer_campaigns')->onDelete('cascade');
        });

        // RLS Policy
        DB::statement("
            ALTER TABLE cmis.influencer_performance ENABLE ROW LEVEL SECURITY;
            CREATE POLICY org_isolation ON cmis.influencer_performance
            USING (org_id = current_setting('app.current_org_id', true)::uuid);
        ");

        // Create Performance Views

        // View 1: Partnership Performance Dashboard
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_partnership_performance AS
            SELECT
                p.org_id,
                p.partnership_id,
                p.partnership_name,
                i.full_name as influencer_name,
                i.tier as influencer_tier,
                COUNT(DISTINCT c.campaign_id) as total_campaigns,
                COUNT(DISTINCT d.deliverable_id) as total_deliverables,
                SUM(c.total_reach) as total_reach,
                SUM(c.total_engagement) as total_engagement,
                SUM(c.conversions) as total_conversions,
                SUM(c.influencer_payment) as total_paid,
                SUM(c.conversion_value) as total_revenue,
                CASE
                    WHEN SUM(c.influencer_payment) > 0
                    THEN (SUM(c.conversion_value) / SUM(c.influencer_payment)) * 100
                    ELSE 0
                END as overall_roi
            FROM cmis.influencer_partnerships p
            JOIN cmis.influencer_profiles i ON p.influencer_id = i.influencer_id
            LEFT JOIN cmis.influencer_campaigns c ON p.partnership_id = c.partnership_id
            LEFT JOIN cmis.campaign_deliverables d ON c.campaign_id = d.campaign_id
            GROUP BY p.org_id, p.partnership_id, p.partnership_name, i.full_name, i.tier;
        ");

        // View 2: Influencer Leaderboard
        DB::statement("
            CREATE OR REPLACE VIEW cmis.v_influencer_leaderboard AS
            SELECT
                i.org_id,
                i.influencer_id,
                i.full_name,
                i.tier,
                i.total_followers,
                i.avg_engagement_rate,
                i.completed_campaigns,
                i.avg_roi,
                i.reliability_score,
                COUNT(DISTINCT c.campaign_id) as active_campaigns,
                SUM(c.total_engagement) as lifetime_engagement,
                SUM(c.conversions) as lifetime_conversions
            FROM cmis.influencer_profiles i
            LEFT JOIN cmis.influencer_partnerships p ON i.influencer_id = p.influencer_id
            LEFT JOIN cmis.influencer_campaigns c ON p.partnership_id = c.partnership_id
            WHERE i.status = 'active'
            GROUP BY i.org_id, i.influencer_id, i.full_name, i.tier,
                     i.total_followers, i.avg_engagement_rate, i.completed_campaigns,
                     i.avg_roi, i.reliability_score
            ORDER BY i.avg_roi DESC, i.reliability_score DESC;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement('DROP VIEW IF EXISTS cmis.v_influencer_leaderboard');
        DB::statement('DROP VIEW IF EXISTS cmis.v_partnership_performance');

        // Drop tables in reverse order
        Schema::dropIfExists('cmis.influencer_performance');
        Schema::dropIfExists('cmis.influencer_applications');
        Schema::dropIfExists('cmis.influencer_payments');
        Schema::dropIfExists('cmis.campaign_deliverables');
        Schema::dropIfExists('cmis.influencer_campaigns');
        Schema::dropIfExists('cmis.influencer_partnerships');
        Schema::dropIfExists('cmis.influencer_profiles');
    }
};
