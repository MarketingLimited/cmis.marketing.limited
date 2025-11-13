<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Class AIGeneratedCampaignController
 * مسؤول عن إدارة الحملات التسويقية التي تم إنشاؤها تلقائيًا بواسطة الذكاء الاصطناعي.
 */
class AIGeneratedCampaignController extends Controller
{
    /**
     * عرض قائمة الحملات التي تم توليدها بالذكاء الاصطناعي.
     */
    public function index()
    {
        Gate::authorize('generateCampaign', auth()->user());

        return view('ai.generated_campaigns.index');
    }
}