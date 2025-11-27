<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Channel;
use App\Models\Content\ContentItem;
use App\Models\Core\Org;
use App\Models\CreativeAsset;
use App\Models\Core\Integration;
use App\Models\Offering;
use App\Models\User;
use App\Policies\AIPolicy;
use App\Policies\AnalyticsPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\ChannelPolicy;
use App\Policies\ContentPolicy;
use App\Policies\CreativeAssetPolicy;
use App\Policies\IntegrationPolicy;
use App\Policies\OfferingPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Campaign::class => CampaignPolicy::class,
        CreativeAsset::class => CreativeAssetPolicy::class,
        ContentItem::class => ContentPolicy::class,
        Integration::class => IntegrationPolicy::class,
        Org::class => OrganizationPolicy::class,
        User::class => UserPolicy::class,
        Offering::class => OfferingPolicy::class,
        Channel::class => ChannelPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();

        // Define ability gates for non-model permissions
        $this->registerGates();
    }

    /**
     * Register authorization gates
     */
    protected function registerGates(): void
    {
        // Analytics gates (no specific model)
        Gate::define('analytics.view_dashboard', [AnalyticsPolicy::class, 'viewDashboard']);
        Gate::define('analytics.view_reports', [AnalyticsPolicy::class, 'viewReports']);
        Gate::define('analytics.create_report', [AnalyticsPolicy::class, 'createReport']);
        Gate::define('analytics.export', [AnalyticsPolicy::class, 'exportData']);
        Gate::define('analytics.view_insights', [AnalyticsPolicy::class, 'viewInsights']);
        Gate::define('analytics.view_performance', [AnalyticsPolicy::class, 'viewPerformance']);
        Gate::define('analytics.manage_dashboard', [AnalyticsPolicy::class, 'manageDashboard']);

        // AI gates (no specific model)
        Gate::define('ai.generate_content', [AIPolicy::class, 'generateContent']);
        Gate::define('ai.generate_campaign', [AIPolicy::class, 'generateCampaign']);
        Gate::define('ai.view_recommendations', [AIPolicy::class, 'viewRecommendations']);
        Gate::define('ai.semantic_search', [AIPolicy::class, 'useSemanticSearch']);
        Gate::define('ai.manage_knowledge', [AIPolicy::class, 'manageKnowledge']);
        Gate::define('ai.manage_prompts', [AIPolicy::class, 'managePrompts']);
        Gate::define('ai.view_insights', [AIPolicy::class, 'viewInsights']);

        // Backward compatibility gates (shorthand versions used by some controllers)
        // Allow all authenticated users to view insights - permission fine-tuning via roles
        Gate::define('viewInsights', function ($user) {
            return $user !== null;
        });

        // Super admin/owner gate - bypass all authorization checks
        Gate::before(function ($user, $ability) {
            // Check if user has super admin or owner role in current organization
            $currentOrgId = session('current_org_id');
            if (!$currentOrgId) {
                return null; // No org context, continue with normal authorization
            }

            // Get user-org relationship
            $userOrg = DB::table('cmis.user_orgs')
                ->where('user_id', $user->user_id)
                ->where('org_id', $currentOrgId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->first();

            if (!$userOrg || !$userOrg->role_id) {
                return null; // No role, continue with normal authorization
            }

            // Get the role
            $role = \App\Models\Core\Role::find($userOrg->role_id);

            // Super admin and owner bypass all checks
            if ($role && in_array($role->role_code, ['super_admin', 'owner'])) {
                return true; // Bypass all authorization checks
            }

            return null; // Continue with normal authorization
        });
    }
}
