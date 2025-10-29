<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiGeneratedCampaign;
use App\Models\AiModel;
use App\Models\AiRecommendation;
use Illuminate\Support\Facades\Cache;

class AIDashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('ai.stats', now()->addMinutes(5), function () {
            return [
                'campaigns' => AiGeneratedCampaign::count(),
                'recommendations' => AiRecommendation::count(),
                'models' => AiModel::count(),
            ];
        });

        $recentCampaigns = AiGeneratedCampaign::query()
            ->select('campaign_id', 'objective_code', 'ai_summary', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentRecommendations = AiRecommendation::query()
            ->select('prediction_id', 'prediction_summary', 'predicted_ctr', 'confidence_level', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $models = AiModel::query()
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