<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Compliance\BrandSafetyPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BrandSafetySettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of brand safety policies.
     */
    public function index(Request $request, string $org)
    {
        $policies = BrandSafetyPolicy::where('org_id', $org)
            ->with(['creator'])
            ->withCount('profileGroups')
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return $this->success($policies, 'Brand safety policies retrieved successfully');
        }

        return view('settings.brand-safety.index', [
            'policies' => $policies,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new brand safety policy.
     */
    public function create(Request $request, string $org)
    {
        return view('settings.brand-safety.create', [
            'currentOrg' => $org,
            'riskLevels' => $this->getRiskLevels(),
            'contentCategories' => $this->getContentCategories(),
        ]);
    }

    /**
     * Store a newly created brand safety policy.
     */
    public function store(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'blocked_words' => 'nullable|array',
            'blocked_topics' => 'nullable|array',
            'blocked_hashtags' => 'nullable|array',
            'blocked_mentions' => 'nullable|array',
            'sentiment_threshold' => 'nullable|string|in:negative,neutral,positive',
            'require_approval_keywords' => 'nullable|array',
            'auto_reject_patterns' => 'nullable|array',
            'content_restrictions' => 'nullable|array',
            'platform_specific_rules' => 'nullable|array',
            'risk_level' => 'required|string|in:low,medium,high',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $policy = BrandSafetyPolicy::create([
                'org_id' => $org,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'blocked_words' => $request->input('blocked_words', []),
                'blocked_topics' => $request->input('blocked_topics', []),
                'blocked_hashtags' => $request->input('blocked_hashtags', []),
                'blocked_mentions' => $request->input('blocked_mentions', []),
                'sentiment_threshold' => $request->input('sentiment_threshold'),
                'require_approval_keywords' => $request->input('require_approval_keywords', []),
                'auto_reject_patterns' => $request->input('auto_reject_patterns', []),
                'content_restrictions' => $request->input('content_restrictions', []),
                'platform_specific_rules' => $request->input('platform_specific_rules', []),
                'risk_level' => $request->input('risk_level'),
                'is_active' => $request->input('is_active', true),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return $this->created($policy, 'Brand safety policy created successfully');
            }

            return redirect()->route('orgs.settings.brand-safety.show', ['org' => $org, 'policy' => $policy->policy_id])
                ->with('success', __('settings.created_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create brand safety policy: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create brand safety policy'])->withInput();
        }
    }

    /**
     * Display the specified brand safety policy.
     */
    public function show(Request $request, string $org, string $policy)
    {
        $safetyPolicy = BrandSafetyPolicy::where('org_id', $org)
            ->where('policy_id', $policy)
            ->with(['creator', 'profileGroups'])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($safetyPolicy, 'Brand safety policy retrieved successfully');
        }

        return view('settings.brand-safety.show', [
            'policy' => $safetyPolicy,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified brand safety policy.
     */
    public function edit(Request $request, string $org, string $policy)
    {
        $safetyPolicy = BrandSafetyPolicy::where('org_id', $org)
            ->where('policy_id', $policy)
            ->firstOrFail();

        return view('settings.brand-safety.edit', [
            'policy' => $safetyPolicy,
            'currentOrg' => $org,
            'riskLevels' => $this->getRiskLevels(),
            'contentCategories' => $this->getContentCategories(),
        ]);
    }

    /**
     * Update the specified brand safety policy.
     */
    public function update(Request $request, string $org, string $policy)
    {
        $safetyPolicy = BrandSafetyPolicy::where('org_id', $org)
            ->where('policy_id', $policy)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'blocked_words' => 'nullable|array',
            'blocked_topics' => 'nullable|array',
            'blocked_hashtags' => 'nullable|array',
            'blocked_mentions' => 'nullable|array',
            'sentiment_threshold' => 'nullable|string|in:negative,neutral,positive',
            'require_approval_keywords' => 'nullable|array',
            'auto_reject_patterns' => 'nullable|array',
            'content_restrictions' => 'nullable|array',
            'platform_specific_rules' => 'nullable|array',
            'risk_level' => 'required|string|in:low,medium,high',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $safetyPolicy->update($request->only([
                'name',
                'description',
                'blocked_words',
                'blocked_topics',
                'blocked_hashtags',
                'blocked_mentions',
                'sentiment_threshold',
                'require_approval_keywords',
                'auto_reject_patterns',
                'content_restrictions',
                'platform_specific_rules',
                'risk_level',
                'is_active',
            ]));

            if ($request->wantsJson()) {
                return $this->success($safetyPolicy, 'Brand safety policy updated successfully');
            }

            return redirect()->route('orgs.settings.brand-safety.show', ['org' => $org, 'policy' => $policy])
                ->with('success', __('settings.updated_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update brand safety policy: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update brand safety policy'])->withInput();
        }
    }

    /**
     * Remove the specified brand safety policy.
     */
    public function destroy(Request $request, string $org, string $policy)
    {
        $safetyPolicy = BrandSafetyPolicy::where('org_id', $org)
            ->where('policy_id', $policy)
            ->firstOrFail();

        try {
            $safetyPolicy->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Brand safety policy deleted successfully');
            }

            return redirect()->route('orgs.settings.brand-safety.index', ['org' => $org])
                ->with('success', __('settings.deleted_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete brand safety policy: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete brand safety policy']);
        }
    }

    /**
     * Get risk level options.
     */
    private function getRiskLevels(): array
    {
        return [
            'low' => 'Low Risk - Minimal content restrictions',
            'medium' => 'Medium Risk - Standard content moderation',
            'high' => 'High Risk - Strict content guidelines',
        ];
    }

    /**
     * Get content category options.
     */
    private function getContentCategories(): array
    {
        return [
            'politics' => 'Politics & Government',
            'religion' => 'Religion & Faith',
            'adult' => 'Adult Content',
            'gambling' => 'Gambling',
            'alcohol' => 'Alcohol & Tobacco',
            'violence' => 'Violence & Gore',
            'hate_speech' => 'Hate Speech',
            'misinformation' => 'Misinformation',
            'competitor' => 'Competitor Content',
        ];
    }
}
