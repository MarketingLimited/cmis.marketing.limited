<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Class PromptTemplateController
 * مسؤول عن إدارة القوالب النصية (Prompts) التي يستخدمها الذكاء الاصطناعي في إنشاء المحتوى.
 */
class PromptTemplateController extends Controller
{
    /**
     * عرض قائمة القوالب النصية.
     */
    public function index()
    {
        Gate::authorize('managePrompts', auth()->user());

        return view('ai.prompts.index');
    }
}