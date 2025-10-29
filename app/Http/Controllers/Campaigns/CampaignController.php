<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class CampaignController
 * مسؤول عن إدارة الحملات التسويقية داخل النظام (إنشاء، عرض، تعديل، تحليل)
 */
class CampaignController extends Controller
{
    /**
     * عرض قائمة الحملات التسويقية.
     */
    public function index()
    {
        return view('campaigns.index');
    }
}