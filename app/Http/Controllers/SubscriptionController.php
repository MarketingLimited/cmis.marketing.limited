<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    use ApiResponse;

    /**
     * Display subscription plans.
     */
    public function plans()
    {
        $plans = [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'price' => 49,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    '5 campaigns per month',
                    '1,000 AI generations',
                    'Basic analytics',
                    'Email support',
                ],
                'ai_rate_limit' => 10, // 10 req/min
            ],
            [
                'id' => 'professional',
                'name' => 'Professional',
                'price' => 149,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'Unlimited campaigns',
                    '10,000 AI generations',
                    'Advanced analytics',
                    'Priority support',
                    'Team collaboration',
                ],
                'ai_rate_limit' => 30, // 30 req/min
                'popular' => true,
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'price' => null, // Custom pricing
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'Unlimited everything',
                    'Unlimited AI generations',
                    'Custom analytics dashboards',
                    'Dedicated account manager',
                    'Advanced team controls',
                    'SLA guarantee',
                ],
                'ai_rate_limit' => 100, // 100 req/min
            ],
        ];

        return view('subscription.plans', compact('plans'));
    }

    /**
     * Show upgrade form.
     */
    public function upgrade()
    {
        $user = Auth::user();
        $currentPlan = $user->organization->subscription_plan ?? 'starter';

        $plans = [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'price' => 49,
            ],
            [
                'id' => 'professional',
                'name' => 'Professional',
                'price' => 149,
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'price' => null,
            ],
        ];

        return view('subscription.upgrade', compact('currentPlan', 'plans'));
    }

    /**
     * Process subscription upgrade.
     *
     * NOTE: This is a simplified implementation. In production, integrate with
     * payment providers like Stripe, Paddle, or LemonSqueezy.
     */
    public function processUpgrade(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|in:starter,professional,enterprise',
            'billing_cycle' => 'required|in:monthly,annual',
        ]);

        $user = Auth::user();
        $organization = $user->organization;
        $requestedPlan = $validated['plan'];
        $currentPlan = $organization->subscription_plan ?? 'starter';

        // Prevent downgrade to same plan
        if ($requestedPlan === $currentPlan) {
            return redirect()->back()->with('warning', __('common.already_on_plan', ['plan' => ucfirst($currentPlan)]));
        }

        // For enterprise plan, redirect to contact sales
        if ($requestedPlan === 'enterprise') {
            return redirect()->back()->with('info',
                'Thank you for your interest in our Enterprise plan. ' .
                'Our sales team will contact you within 24 hours to discuss your needs. ' .
                'In the meantime, you can email us at sales@cmis.marketing.'
            );
        }

        // Log the upgrade attempt
        Log::info('Subscription upgrade requested', [
            'user_id' => $user->id,
            'org_id' => $organization->id,
            'from_plan' => $currentPlan,
            'to_plan' => $requestedPlan,
            'billing_cycle' => $validated['billing_cycle'],
        ]);

        // In production, this would:
        // 1. Create Stripe/Paddle checkout session
        // 2. Redirect to payment page
        // 3. Handle webhook for successful payment
        // 4. Update org subscription plan
        // 5. Apply new rate limits

        // For now, display clear message that this requires payment integration
        return redirect()->back()->with('info',
            '⚙️ Subscription upgrades require payment integration. ' .
            'This feature is currently under development. ' .
            'Please contact sales@cmis.marketing to upgrade your plan manually. ' .
            'We will set up your ' . ucfirst($requestedPlan) . ' plan within 1 business day.'
        );
    }

    /**
     * Show current subscription status.
     */
    public function status()
    {
        $user = Auth::user();
        $organization = $user->organization;

        $subscription = [
            'plan' => $organization->subscription_plan ?? 'starter',
            'status' => 'active', // In production, check actual payment status
            'current_period_end' => now()->addMonth()->format('Y-m-d'),
            'ai_rate_limit' => $this->getAIRateLimitForPlan($organization->subscription_plan ?? 'starter'),
        ];

        return view('subscription.status', compact('subscription'));
    }

    /**
     * Get AI rate limit for a given plan.
     */
    protected function getAIRateLimitForPlan(string $plan): int
    {
        return match($plan) {
            'professional' => 30,
            'enterprise' => 100,
            default => 10, // starter
        };
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $organization = $user->organization;

        // Require confirmation
        $request->validate([
            'confirm_cancellation' => 'required|accepted',
        ]);

        Log::info('Subscription cancellation requested', [
            'user_id' => $user->id,
            'org_id' => $organization->id,
            'plan' => $organization->subscription_plan,
        ]);

        // In production: Cancel with payment provider, schedule downgrade at period end

        return redirect()->route('subscription.status')->with('info',
            'We\'re sorry to see you go! Your subscription will remain active until the end of your billing period. ' .
            'You can reactivate anytime before then.'
        );
    }
}
