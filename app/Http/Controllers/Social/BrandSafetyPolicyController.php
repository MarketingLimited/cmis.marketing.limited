<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Compliance\BrandSafetyPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * BrandSafetyPolicyController
 *
 * Manages brand safety and compliance policies for automated content validation.
 * Includes prohibited content rules, disclosure requirements, and enforcement settings.
 */
class BrandSafetyPolicyController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all brand safety policies for an organization
     *
     * GET /api/orgs/{org_id}/brand-safety-policies
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'enforcement_level' => 'nullable|string|in:warn,block,review',
            'profile_group_id' => 'nullable|uuid',
            'org_wide' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $query = BrandSafetyPolicy::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('enforcement_level')) {
                $query->byEnforcementLevel($request->input('enforcement_level'));
            }

            if ($request->filled('profile_group_id')) {
                $query->forGroup($request->input('profile_group_id'));
            }

            if ($request->boolean('org_wide')) {
                $query->orgWide();
            }

            $query->with(['creator', 'profileGroup']);

            $perPage = $request->input('per_page', 15);
            $policies = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->paginated($policies, 'Brand safety policies retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve brand safety policies: ' . $e->getMessage());
        }
    }

    /**
     * Create a new brand safety policy
     *
     * POST /api/orgs/{org_id}/brand-safety-policies
     */
    public function store(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'is_active' => 'nullable|boolean',
            'prohibit_derogatory_language' => 'nullable|boolean',
            'prohibit_profanity' => 'nullable|boolean',
            'prohibit_offensive_content' => 'nullable|boolean',
            'custom_banned_words' => 'nullable|array',
            'custom_banned_words.*' => 'string|max:100',
            'custom_banned_phrases' => 'nullable|array',
            'custom_banned_phrases.*' => 'string|max:500',
            'custom_requirements' => 'nullable|string',
            'require_disclosure' => 'nullable|boolean',
            'disclosure_text' => 'nullable|string|max:500',
            'require_fact_checking' => 'nullable|boolean',
            'require_source_citation' => 'nullable|boolean',
            'industry_regulations' => 'nullable|array',
            'industry_regulations.*' => 'string|max:50',
            'compliance_regions' => 'nullable|array',
            'compliance_regions.*' => 'string|max:10',
            'enforcement_level' => 'nullable|string|in:warn,block,review',
            'auto_reject_violations' => 'nullable|boolean',
            'use_default_template' => 'nullable|boolean',
            'template_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $policy = BrandSafetyPolicy::create([
                'org_id' => $orgId,
                'profile_group_id' => $request->input('profile_group_id'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true),
                'prohibit_derogatory_language' => $request->input('prohibit_derogatory_language', true),
                'prohibit_profanity' => $request->input('prohibit_profanity', true),
                'prohibit_offensive_content' => $request->input('prohibit_offensive_content', true),
                'custom_banned_words' => $request->input('custom_banned_words', []),
                'custom_banned_phrases' => $request->input('custom_banned_phrases', []),
                'custom_requirements' => $request->input('custom_requirements'),
                'require_disclosure' => $request->input('require_disclosure', false),
                'disclosure_text' => $request->input('disclosure_text'),
                'require_fact_checking' => $request->input('require_fact_checking', false),
                'require_source_citation' => $request->input('require_source_citation', false),
                'industry_regulations' => $request->input('industry_regulations', []),
                'compliance_regions' => $request->input('compliance_regions', []),
                'enforcement_level' => $request->input('enforcement_level', 'warn'),
                'auto_reject_violations' => $request->input('auto_reject_violations', false),
                'use_default_template' => $request->input('use_default_template', false),
                'template_name' => $request->input('template_name'),
                'created_by' => Auth::id(),
            ]);

            $policy->load(['creator', 'profileGroup']);

            return $this->created($policy, 'Brand safety policy created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create brand safety policy: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific brand safety policy
     *
     * GET /api/orgs/{org_id}/brand-safety-policies/{policy_id}
     */
    public function show(string $orgId, string $policyId): JsonResponse
    {
        try {
            $policy = BrandSafetyPolicy::with(['creator', 'profileGroup', 'profileGroups'])
                ->findOrFail($policyId);

            return $this->success($policy, 'Brand safety policy retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Brand safety policy not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve brand safety policy: ' . $e->getMessage());
        }
    }

    /**
     * Update a brand safety policy
     *
     * PUT /api/orgs/{org_id}/brand-safety-policies/{policy_id}
     */
    public function update(string $orgId, string $policyId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'is_active' => 'nullable|boolean',
            'prohibit_derogatory_language' => 'nullable|boolean',
            'prohibit_profanity' => 'nullable|boolean',
            'prohibit_offensive_content' => 'nullable|boolean',
            'custom_banned_words' => 'nullable|array',
            'custom_banned_phrases' => 'nullable|array',
            'custom_requirements' => 'nullable|string',
            'require_disclosure' => 'nullable|boolean',
            'disclosure_text' => 'nullable|string|max:500',
            'require_fact_checking' => 'nullable|boolean',
            'require_source_citation' => 'nullable|boolean',
            'industry_regulations' => 'nullable|array',
            'compliance_regions' => 'nullable|array',
            'enforcement_level' => 'nullable|string|in:warn,block,review',
            'auto_reject_violations' => 'nullable|boolean',
            'use_default_template' => 'nullable|boolean',
            'template_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $policy = BrandSafetyPolicy::findOrFail($policyId);

            $policy->update($request->only([
                'name', 'description', 'profile_group_id', 'is_active',
                'prohibit_derogatory_language', 'prohibit_profanity', 'prohibit_offensive_content',
                'custom_banned_words', 'custom_banned_phrases', 'custom_requirements',
                'require_disclosure', 'disclosure_text', 'require_fact_checking',
                'require_source_citation', 'industry_regulations', 'compliance_regions',
                'enforcement_level', 'auto_reject_violations', 'use_default_template',
                'template_name',
            ]));

            $policy->load(['creator', 'profileGroup']);

            return $this->success($policy, 'Brand safety policy updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Brand safety policy not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update brand safety policy: ' . $e->getMessage());
        }
    }

    /**
     * Delete a brand safety policy (soft delete)
     *
     * DELETE /api/orgs/{org_id}/brand-safety-policies/{policy_id}
     */
    public function destroy(string $orgId, string $policyId): JsonResponse
    {
        try {
            $policy = BrandSafetyPolicy::findOrFail($policyId);

            // Check if policy is in use by profile groups
            $usageCount = $policy->profileGroups()->count();
            if ($usageCount > 0) {
                return $this->error(
                    "Cannot delete brand safety policy that is in use by {$usageCount} profile group(s). Unassign it first.",
                    400
                );
            }

            $policy->delete();

            return $this->deleted('Brand safety policy deleted successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Brand safety policy not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete brand safety policy: ' . $e->getMessage());
        }
    }

    /**
     * Validate content against a policy
     *
     * POST /api/orgs/{org_id}/brand-safety-policies/{policy_id}/validate
     */
    public function validateContent(string $orgId, string $policyId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $policy = BrandSafetyPolicy::findOrFail($policyId);

            $result = $policy->validateContent($request->input('content'));

            return $this->success($result, 'Content validation completed');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Brand safety policy not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to validate content: ' . $e->getMessage());
        }
    }
}
