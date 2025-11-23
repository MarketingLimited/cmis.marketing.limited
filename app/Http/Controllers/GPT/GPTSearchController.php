<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Models\Creative\ContentPlan;
use App\Services\KnowledgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GPT Search Controller
 *
 * Handles smart search operations across multiple resources for GPT/ChatGPT integration
 */
class GPTSearchController extends Controller
{
    use ApiResponse;

    public function __construct(
        private KnowledgeService $knowledgeService
    ) {}

    /**
     * Smart search across multiple resources
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'resources' => 'nullable|array',
            'resources.*' => 'in:campaigns,content_plans,knowledge,markets',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $query = $request->input('query');
            $resources = $request->input('resources', ['campaigns', 'content_plans', 'knowledge']);
            $limit = $request->input('limit', 10);
            $orgId = $request->user()->current_org_id;

            $results = [];

            // Search campaigns
            if (in_array('campaigns', $resources)) {
                $campaigns = Campaign::where('org_id', $orgId)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'ILIKE', "%{$query}%")
                          ->orWhere('description', 'ILIKE', "%{$query}%");
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn($c) => [
                        'type' => 'campaign',
                        'id' => $c->campaign_id,
                        'name' => $c->name,
                        'description' => $c->description,
                        'status' => $c->status,
                        'relevance_score' => $this->calculateRelevance($query, $c->name, $c->description),
                    ]);

                $results['campaigns'] = $campaigns;
            }

            // Search content plans
            if (in_array('content_plans', $resources)) {
                $contentPlans = ContentPlan::where('org_id', $orgId)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'ILIKE', "%{$query}%")
                          ->orWhere('description', 'ILIKE', "%{$query}%");
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn($p) => [
                        'type' => 'content_plan',
                        'id' => $p->plan_id,
                        'name' => $p->name,
                        'content_type' => $p->content_type,
                        'status' => $p->status,
                        'relevance_score' => $this->calculateRelevance($query, $p->name, $p->description),
                    ]);

                $results['content_plans'] = $contentPlans;
            }

            // Search knowledge base with semantic search
            if (in_array('knowledge', $resources)) {
                $knowledgeItems = $this->knowledgeService->semanticSearch(
                    $query,
                    $orgId,
                    $limit
                )->map(fn($k) => [
                    'type' => 'knowledge',
                    'id' => $k->id,
                    'title' => $k->title,
                    'content_type' => $k->content_type,
                    'relevance_score' => 1 - ($k->distance ?? 0.5),
                ]);

                $results['knowledge'] = $knowledgeItems;
            }

            // Sort all results by relevance
            $allResults = collect($results)->flatten(1)->sortByDesc('relevance_score')->take($limit);

            return $this->success([
                'query' => $query,
                'total_results' => $allResults->count(),
                'results' => $allResults->values(),
                'by_type' => array_map(fn($r) => count($r), $results),
            ]);

        } catch (\Exception $e) {
            \Log::error('Smart search failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Search failed');
        }
    }

    /**
     * Calculate text relevance score
     */
    private function calculateRelevance(string $query, string $title, ?string $description = ''): float
    {
        $query = strtolower($query);
        $title = strtolower($title);
        $description = strtolower($description ?? '');

        $score = 0.0;

        // Exact match in title
        if (strpos($title, $query) !== false) {
            $score += 1.0;
        }

        // Partial match in title
        $queryWords = explode(' ', $query);
        foreach ($queryWords as $word) {
            if (strlen($word) > 2) {
                if (strpos($title, $word) !== false) {
                    $score += 0.5;
                }
                if (strpos($description, $word) !== false) {
                    $score += 0.2;
                }
            }
        }

        return min($score, 1.0);
    }
}
