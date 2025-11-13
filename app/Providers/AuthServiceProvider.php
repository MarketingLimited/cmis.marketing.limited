<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Channel;
use App\Models\Content\ContentItem;
use App\Models\Core\Org;
use App\Models\CreativeAsset;
use App\Models\Integration;
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

        // AI gates (no specific model)
        Gate::define('ai.generate_content', [AIPolicy::class, 'generateContent']);
        Gate::define('ai.generate_campaign', [AIPolicy::class, 'generateCampaign']);
        Gate::define('ai.view_recommendations', [AIPolicy::class, 'viewRecommendations']);
        Gate::define('ai.semantic_search', [AIPolicy::class, 'useSemanticSearch']);
        Gate::define('ai.manage_knowledge', [AIPolicy::class, 'manageKnowledge']);
        Gate::define('ai.manage_prompts', [AIPolicy::class, 'managePrompts']);
        Gate::define('ai.view_insights', [AIPolicy::class, 'viewInsights']);

        // Super admin gate
        Gate::before(function ($user, $ability) {
            // Check if user has super admin role
            $currentOrgId = session('current_org_id');
            if ($currentOrgId) {
                $userOrg = $user->orgs()
                    ->where('cmis.orgs.org_id', $currentOrgId)
                    ->first();

                if ($userOrg && $userOrg->pivot && $userOrg->pivot->role) {
                    if ($userOrg->pivot->role->role_code === 'super_admin') {
                        return true; // Super admin bypasses all checks
                    }
                }
            }
        });
    }
}
