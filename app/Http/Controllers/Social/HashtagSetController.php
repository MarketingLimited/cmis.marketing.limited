<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\HashtagSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HashtagSetController extends Controller
{
    use ApiResponse;

    /**
     * Get all hashtag sets for the organization
     */
    public function index(Request $request, $orgId)
    {
        try {
            // Initialize RLS context
            DB::statement("SELECT init_transaction_context(?)", [$orgId]);

            $hashtagSets = HashtagSet::where('org_id', $orgId)
                ->orderBy('name')
                ->get()
                ->map(function ($set) {
                    return [
                        'id' => $set->id,
                        'name' => $set->name,
                        'hashtags' => $set->hashtags,
                        'usage_count' => $set->usage_count ?? 0,
                        'created_at' => $set->created_at,
                        'updated_at' => $set->updated_at,
                    ];
                });

            return $this->success($hashtagSets, 'Hashtag sets retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to load hashtag sets: ' . $e->getMessage());
        }
    }

    /**
     * Create a new hashtag set
     */
    public function store(Request $request, $orgId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hashtags' => 'required|array|min:1',
            'hashtags.*' => 'required|string|max:100',
        ]);

        try {
            // Initialize RLS context
            DB::statement("SELECT init_transaction_context(?)", [$orgId]);

            // Clean hashtags (remove # if present, lowercase)
            $hashtags = array_map(function ($tag) {
                return strtolower(ltrim($tag, '#'));
            }, $validated['hashtags']);

            $hashtagSet = HashtagSet::create([
                'org_id' => $orgId,
                'name' => $validated['name'],
                'hashtags' => $hashtags,
                'usage_count' => 0,
            ]);

            return $this->created($hashtagSet, 'Hashtag set created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create hashtag set: ' . $e->getMessage());
        }
    }

    /**
     * Update a hashtag set
     */
    public function update(Request $request, $orgId, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'hashtags' => 'sometimes|array|min:1',
            'hashtags.*' => 'required_with:hashtags|string|max:100',
        ]);

        try {
            // Initialize RLS context
            DB::statement("SELECT init_transaction_context(?)", [$orgId]);

            $hashtagSet = HashtagSet::where('org_id', $orgId)->findOrFail($id);

            if (isset($validated['name'])) {
                $hashtagSet->name = $validated['name'];
            }

            if (isset($validated['hashtags'])) {
                // Clean hashtags
                $hashtags = array_map(function ($tag) {
                    return strtolower(ltrim($tag, '#'));
                }, $validated['hashtags']);
                $hashtagSet->hashtags = $hashtags;
            }

            $hashtagSet->save();

            return $this->success($hashtagSet, 'Hashtag set updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update hashtag set: ' . $e->getMessage());
        }
    }

    /**
     * Delete a hashtag set
     */
    public function destroy($orgId, $id)
    {
        try {
            // Initialize RLS context
            DB::statement("SELECT init_transaction_context(?)", [$orgId]);

            $hashtagSet = HashtagSet::where('org_id', $orgId)->findOrFail($id);
            $hashtagSet->delete();

            return $this->deleted('Hashtag set deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete hashtag set: ' . $e->getMessage());
        }
    }

    /**
     * Increment usage count when hashtags are used
     */
    public function incrementUsage(Request $request, $orgId, $id)
    {
        try {
            // Initialize RLS context
            DB::statement("SELECT init_transaction_context(?)", [$orgId]);

            $hashtagSet = HashtagSet::where('org_id', $orgId)->findOrFail($id);
            $hashtagSet->increment('usage_count');

            return $this->success(['usage_count' => $hashtagSet->usage_count], 'Usage count updated');
        } catch (\Exception $e) {
            return $this->error('Failed to update usage count: ' . $e->getMessage());
        }
    }

    /**
     * Get trending hashtags (mock implementation - would need analytics data)
     */
    public function trending(Request $request, $orgId)
    {
        try {
            // Initialize RLS context
            DB::statement("SELECT init_transaction_context(?)", [$orgId]);

            // Mock trending hashtags - in production, this would come from analytics
            $trending = [
                'marketing',
                'business',
                'socialmedia',
                'digitalmarketing',
                'contentmarketing',
                'branding',
                'entrepreneur',
                'success',
                'motivation',
                'leadership'
            ];

            return $this->success($trending, 'Trending hashtags retrieved');
        } catch (\Exception $e) {
            return $this->error('Failed to get trending hashtags: ' . $e->getMessage());
        }
    }
}
