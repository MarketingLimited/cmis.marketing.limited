<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds default app assignments to subscription plans.
 *
 * This seeder assigns marketplace apps to plans based on the tier:
 * - Free: Core apps only (dashboard, basic analytics)
 * - Starter: Core + Campaigns, Content, Social (5 apps)
 * - Professional: + Advertising, Automation, AI Features (8 apps)
 * - Enterprise: All apps (20+ apps)
 */
class PlanAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all plans
        $plans = DB::table('cmis.plans')->get()->keyBy('code');

        // Get all apps
        $apps = DB::table('cmis.marketplace_apps')->where('is_active', true)->get()->keyBy('slug');

        if ($plans->isEmpty() || $apps->isEmpty()) {
            $this->command->warn('No plans or apps found. Skipping PlanAppSeeder.');
            return;
        }

        // Define which apps each plan tier gets access to
        $planAppConfig = [
            'free' => [
                'apps' => ['dashboard', 'basic-analytics', 'profile-settings'],
                'enable_core' => true,  // Also enable all core apps
            ],
            'starter' => [
                'apps' => ['dashboard', 'basic-analytics', 'profile-settings', 'campaigns', 'content-calendar', 'social-publishing', 'creative-library'],
                'enable_core' => true,
            ],
            'professional' => [
                'apps' => ['dashboard', 'basic-analytics', 'profile-settings', 'campaigns', 'content-calendar', 'social-publishing', 'creative-library', 'advertising', 'automation', 'ai-insights', 'audience-builder', 'reports'],
                'enable_core' => true,
            ],
            'enterprise' => [
                'apps' => [], // Will enable all apps
                'enable_all' => true,
            ],
        ];

        // Clear existing plan_apps
        DB::table('cmis.plan_apps')->truncate();

        $inserted = 0;

        foreach ($plans as $planCode => $plan) {
            $config = $planAppConfig[$planCode] ?? ['apps' => [], 'enable_core' => true];

            foreach ($apps as $appSlug => $app) {
                $shouldEnable = false;

                // Check if this app should be enabled for this plan
                if (isset($config['enable_all']) && $config['enable_all']) {
                    $shouldEnable = true;
                } elseif (in_array($appSlug, $config['apps'] ?? [])) {
                    $shouldEnable = true;
                } elseif (isset($config['enable_core']) && $config['enable_core'] && $app->is_core) {
                    $shouldEnable = true;
                }

                // Insert the plan_app record
                DB::table('cmis.plan_apps')->insert([
                    'plan_app_id' => Str::uuid(),
                    'plan_id' => $plan->plan_id,
                    'app_id' => $app->app_id,
                    'is_enabled' => $shouldEnable,
                    'usage_limit' => null,
                    'settings_override' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $inserted++;
            }

            $enabledCount = collect($apps)->filter(function ($app, $slug) use ($config) {
                if (isset($config['enable_all'])) return true;
                if (in_array($slug, $config['apps'] ?? [])) return true;
                if (isset($config['enable_core']) && $config['enable_core'] && $app->is_core) return true;
                return false;
            })->count();

            $this->command->info("Plan '{$plan->name}' ({$planCode}): {$enabledCount} apps enabled");
        }

        $this->command->info("Total plan_apps records created: {$inserted}");
    }
}
