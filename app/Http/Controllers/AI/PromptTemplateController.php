<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        return view('ai.prompts.index');
    }
}