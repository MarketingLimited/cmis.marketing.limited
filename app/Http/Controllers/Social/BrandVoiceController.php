<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Creative\BrandVoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * BrandVoiceController
 *
 * Manages brand voice profiles for consistent AI-powered content generation.
 * Includes tone, personality traits, content guidelines, and AI configuration.
 */
class BrandVoiceController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all brand voices for an organization
     *
     * GET /api/orgs/{org_id}/brand-voices
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'tone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'profile_group_id' => 'nullable|uuid',
            'org_wide' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $query = BrandVoice::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->filled('tone')) {
                $query->byTone($request->input('tone'));
            }

            if ($request->filled('language')) {
                $query->byLanguage($request->input('language'));
            }

            if ($request->filled('profile_group_id')) {
                $query->forGroup($request->input('profile_group_id'));
            }

            if ($request->boolean('org_wide')) {
                $query->orgWide();
            }

            $query->with(['creator', 'profileGroup']);

            $perPage = $request->input('per_page', 15);
            $voices = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->paginated($voices, 'Brand voices retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve brand voices: ' . $e->getMessage());
        }
    }

    /**
     * Create a new brand voice
     *
     * POST /api/orgs/{org_id}/brand-voices
     */
    public function store(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'tone' => 'required|string|max:50',
            'personality_traits' => 'nullable|array',
            'personality_traits.*' => 'string|max:100',
            'inspired_by' => 'nullable|array',
            'inspired_by.*' => 'string|max:255',
            'target_audience' => 'nullable|string',
            'keywords_to_use' => 'nullable|array',
            'keywords_to_use.*' => 'string|max:100',
            'keywords_to_avoid' => 'nullable|array',
            'keywords_to_avoid.*' => 'string|max:100',
            'emojis_preference' => 'nullable|string|in:none,minimal,moderate,frequent',
            'hashtag_strategy' => 'nullable|string|in:none,minimal,moderate,extensive',
            'example_posts' => 'nullable|array',
            'example_posts.*' => 'string',
            'primary_language' => 'nullable|string|max:10',
            'secondary_languages' => 'nullable|array',
            'secondary_languages.*' => 'string|max:10',
            'dialect_preference' => 'nullable|string|max:50',
            'ai_system_prompt' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $voice = BrandVoice::create([
                'org_id' => $orgId,
                'profile_group_id' => $request->input('profile_group_id'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'tone' => $request->input('tone'),
                'personality_traits' => $request->input('personality_traits', []),
                'inspired_by' => $request->input('inspired_by', []),
                'target_audience' => $request->input('target_audience'),
                'keywords_to_use' => $request->input('keywords_to_use', []),
                'keywords_to_avoid' => $request->input('keywords_to_avoid', []),
                'emojis_preference' => $request->input('emojis_preference', 'moderate'),
                'hashtag_strategy' => $request->input('hashtag_strategy', 'moderate'),
                'example_posts' => $request->input('example_posts', []),
                'primary_language' => $request->input('primary_language', 'ar'),
                'secondary_languages' => $request->input('secondary_languages', []),
                'dialect_preference' => $request->input('dialect_preference'),
                'ai_system_prompt' => $request->input('ai_system_prompt'),
                'temperature' => $request->input('temperature', 0.7),
                'created_by' => Auth::id(),
            ]);

            $voice->load(['creator', 'profileGroup']);

            return $this->created($voice, 'Brand voice created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create brand voice: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific brand voice
     *
     * GET /api/orgs/{org_id}/brand-voices/{voice_id}
     */
    public function show(string $orgId, string $voiceId): JsonResponse
    {
        try {
            $voice = BrandVoice::with(['creator', 'profileGroup', 'profileGroups'])
                ->findOrFail($voiceId);

            return $this->success($voice, 'Brand voice retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Brand voice not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve brand voice: ' . $e->getMessage());
        }
    }

    /**
     * Update a brand voice
     *
     * PUT /api/orgs/{org_id}/brand-voices/{voice_id}
     */
    public function update(string $orgId, string $voiceId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'tone' => 'sometimes|required|string|max:50',
            'personality_traits' => 'nullable|array',
            'inspired_by' => 'nullable|array',
            'target_audience' => 'nullable|string',
            'keywords_to_use' => 'nullable|array',
            'keywords_to_avoid' => 'nullable|array',
            'emojis_preference' => 'nullable|string|in:none,minimal,moderate,frequent',
            'hashtag_strategy' => 'nullable|string|in:none,minimal,moderate,extensive',
            'example_posts' => 'nullable|array',
            'primary_language' => 'nullable|string|max:10',
            'secondary_languages' => 'nullable|array',
            'dialect_preference' => 'nullable|string|max:50',
            'ai_system_prompt' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $voice = BrandVoice::findOrFail($voiceId);

            $voice->update($request->only([
                'name', 'description', 'profile_group_id', 'tone',
                'personality_traits', 'inspired_by', 'target_audience',
                'keywords_to_use', 'keywords_to_avoid', 'emojis_preference',
                'hashtag_strategy', 'example_posts', 'primary_language',
                'secondary_languages', 'dialect_preference', 'ai_system_prompt',
                'temperature',
            ]));

            $voice->load(['creator', 'profileGroup']);

            return $this->success($voice, 'Brand voice updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Brand voice not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update brand voice: ' . $e->getMessage());
        }
    }

    /**
     * Delete a brand voice (soft delete)
     *
     * DELETE /api/orgs/{org_id}/brand-voices/{voice_id}
     */
    public function destroy(string $orgId, string $voiceId): JsonResponse
    {
        try {
            $voice = BrandVoice::findOrFail($voiceId);

            // Check if voice is in use by profile groups
            $usageCount = $voice->profileGroups()->count();
            if ($usageCount > 0) {
                return $this->error(
                    "Cannot delete brand voice that is in use by {$usageCount} profile group(s). Unassign it first.",
                    400
                );
            }

            $voice->delete();

            return $this->deleted('Brand voice deleted successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Brand voice not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete brand voice: ' . $e->getMessage());
        }
    }
}
