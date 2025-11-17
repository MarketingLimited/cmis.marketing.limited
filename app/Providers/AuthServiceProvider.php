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

        // Legacy analytics abilities used across controllers
        Gate::define('viewDashboard', [AnalyticsPolicy::class, 'viewDashboard']);
        Gate::define('viewReports', [AnalyticsPolicy::class, 'viewReports']);
        Gate::define('createReport', [AnalyticsPolicy::class, 'createReport']);
        Gate::define('exportData', [AnalyticsPolicy::class, 'exportData']);
        Gate::define('viewPerformance', [AnalyticsPolicy::class, 'viewPerformance']);
        Gate::define('manageDashboard', [AnalyticsPolicy::class, 'manageDashboard']);

        // AI gates (no specific model)
        Gate::define('ai.generate_content', [AIPolicy::class, 'generateContent']);
        Gate::define('ai.generate_campaign', [AIPolicy::class, 'generateCampaign']);
        Gate::define('ai.view_recommendations', [AIPolicy::class, 'viewRecommendations']);
        Gate::define('ai.semantic_search', [AIPolicy::class, 'useSemanticSearch']);
        Gate::define('ai.manage_knowledge', [AIPolicy::class, 'manageKnowledge']);
        Gate::define('ai.manage_prompts', [AIPolicy::class, 'managePrompts']);
        Gate::define('ai.view_insights', [AIPolicy::class, 'viewInsights']);

        // Legacy AI abilities used across controllers
        Gate::define('generateContent', [AIPolicy::class, 'generateContent']);
        Gate::define('generateCampaign', [AIPolicy::class, 'generateCampaign']);
        Gate::define('viewRecommendations', [AIPolicy::class, 'viewRecommendations']);
        Gate::define('useSemanticSearch', [AIPolicy::class, 'useSemanticSearch']);
        Gate::define('manageKnowledge', [AIPolicy::class, 'manageKnowledge']);
        Gate::define('managePrompts', [AIPolicy::class, 'managePrompts']);

        // Shared legacy ability name across analytics and AI contexts
        Gate::define('viewInsights', function ($user) {
            return app(AnalyticsPolicy::class)->viewInsights($user)
                || app(AIPolicy::class)->viewInsights($user);
        });

        // Super admin/admin gate
        Gate::before(function ($user, $ability) {
            // Check if user has a bypass role in current organization
            $currentOrgId = session('current_org_id')
                ?? ($user->current_org_id ?? null)
                ?? $user->org_id;

            if ($currentOrgId) {
                $userOrg = $user->orgs()
                    ->where('cmis.orgs.org_id', $currentOrgId)
                    ->first();

                if ($userOrg && $userOrg->pivot && $userOrg->pivot->role) {
                    $roleCode = $userOrg->pivot->role->role_code;

                    if (in_array($roleCode, ['super_admin', 'admin'], true)) {
                        return true; // Admin and super admin bypass all checks
                    }
                }
            }
        });
    }
}
