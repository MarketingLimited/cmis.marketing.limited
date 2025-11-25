<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiGeneratedCampaign;
use App\Models\AiModel;
use App\Models\AiRecommendation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class AIDashboardController extends Controller
{
    public function index(string $org)
    {
        Gate::authorize('viewInsights', auth()->user());

        $stats = Cache::remember("ai.stats.{$org}", now()->addMinutes(5), function () use ($org) {
            return [
                'campaigns' => AiGeneratedCampaign::where('org_id', $org)->count(),
                'recommendations' => AiRecommendation::where('org_id', $org)->count(),
                'models' => AiModel::where('org_id', $org)->count(),
            ];
        });

        $recentCampaigns = AiGeneratedCampaign::query()
            ->where('org_id', $org)
            ->select('campaign_id', 'objective_code', 'ai_summary', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentRecommendations = AiRecommendation::query()
            ->where('org_id', $org)
            ->select('prediction_id', 'prediction_summary', 'predicted_ctr', 'confidence_level', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $models = AiModel::query()
            ->where('org_id', $org)
            ->select('model_id', 'model_name', 'model_family', 'status', 'trained_at')
            ->orderBy('model_name')
            ->get();

        return view('ai.index', [
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
            'recentRecommendations' => $recentRecommendations,
            'models' => $models,
        ]);
    }
}
