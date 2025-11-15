<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

/**
 * AI & Vector Embeddings Policy
 *
 * يحدد صلاحيات الوصول لجميع ميزات الذكاء الاصطناعي والـ Vector Embeddings
 */
class AIPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    // ========================================
    // AI Content Generation
    // ========================================

    public function generateContent(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_content');
    }

    public function generateCampaign(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_campaign');
    }

    public function generateContentPlan(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_content_plan');
    }

    public function optimizeContent(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.optimize_content');
    }

    public function translateContent(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.translate_content');
    }

    // ========================================
    // AI Video & Scripts
    // ========================================

    public function generateVideoScript(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_video_script');
    }

    public function generateVideoPrompt(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_video_prompt');
    }

    public function analyzeVideoPerformance(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.analyze_video_performance');
    }

    public function generateVideoThumbnail(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_video_thumbnail');
    }

    // ========================================
    // AI Image & Design
    // ========================================

    public function generateImagePrompt(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_image_prompt');
    }

    public function generateDesignDescription(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_design_description');
    }

    public function analyzeImagePerformance(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.analyze_image_performance');
    }

    public function generateAdCopy(User $user): bool
    {
        return $this->permissionService->check($user, 'ai.generate_ad_copy');
    }

    // ========================================
    // Content Planning
    // ========================================

    public function generateContentCalendar(User $user): bool
    {
        return $this->permissionService->check($user, 'planning.generate_calendar');
    }

    public function suggestTopics(User $user): bool
    {
        return $this->permissionService->check($user, 'planning.suggest_topics');
    }

    public function analyzeTrends(User $user): bool
    {
        return $this->permissionService->check($user, 'planning.analyze_trends');
    }

    public function optimizeTiming(User $user): bool
    {
        return $this->permissionService->check($user, 'planning.optimize_timing');
    }

    // ========================================
    // Vector Embeddings
    // ========================================

    public function useSemanticSearch(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.semantic_search');
    }

    public function useHybridSearch(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.hybrid_search');
    }

    public function loadContext(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.load_context');
    }

    public function processQueue(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.process_queue');
    }

    public function viewStatus(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.view_status');
    }

    public function viewAnalytics(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.view_analytics');
    }

    public function manageSystem(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.manage_system');
    }

    public function manageEmbeddings(User $user): bool
    {
        return $this->permissionService->check($user, 'vector.manage_embeddings');
    }

    // ========================================
    // Knowledge Management
    // ========================================

    public function manageKnowledge(User $user): bool
    {
        return $this->permissionService->check($user, 'knowledge.manage');
    }

    public function registerKnowledge(User $user): bool
    {
        return $this->permissionService->check($user, 'knowledge.register');
    }

    public function updateKnowledge(User $user): bool
    {
        return $this->permissionService->check($user, 'knowledge.update');
    }

    public function deleteKnowledge(User $user): bool
    {
        return $this->permissionService->check($user, 'knowledge.delete');
    }

    public function exportKnowledge(User $user): bool
    {
        return $this->permissionService->check($user, 'knowledge.export');
    }

    // ========================================
    // AI Prompts
    // ========================================

    public function managePrompts(User $user): bool
    {
        return $this->permissionService->check($user, 'prompts.manage');
    }

    public function createPrompt(User $user): bool
    {
        return $this->permissionService->check($user, 'prompts.create');
    }

    public function editPrompt(User $user): bool
    {
        return $this->permissionService->check($user, 'prompts.edit');
    }

    public function usePromptTemplates(User $user): bool
    {
        return $this->permissionService->check($user, 'prompts.use_templates');
    }

    public function sharePrompts(User $user): bool
    {
        return $this->permissionService->check($user, 'prompts.share');
    }

    // ========================================
    // AI Insights
    // ========================================

    public function viewInsights(User $user): bool
    {
        return $this->permissionService->check($user, 'insights.view');
    }

    public function generateReports(User $user): bool
    {
        return $this->permissionService->check($user, 'insights.generate_reports');
    }

    public function viewPredictions(User $user): bool
    {
        return $this->permissionService->check($user, 'insights.view_predictions');
    }

    public function exportAnalytics(User $user): bool
    {
        return $this->permissionService->check($user, 'insights.export_analytics');
    }

    // ========================================
    // Scheduling Integration
    // ========================================

    public function createSchedule(User $user): bool
    {
        return $this->permissionService->check($user, 'schedule.create');
    }

    public function editSchedule(User $user): bool
    {
        return $this->permissionService->check($user, 'schedule.edit');
    }

    public function deleteSchedule(User $user): bool
    {
        return $this->permissionService->check($user, 'schedule.delete');
    }

    public function publishNow(User $user): bool
    {
        return $this->permissionService->check($user, 'schedule.publish_now');
    }

    public function bulkSchedule(User $user): bool
    {
        return $this->permissionService->check($user, 'schedule.bulk_schedule');
    }

    // ========================================
    // Paid Ads Management
    // ========================================

    public function createAd(User $user): bool
    {
        return $this->permissionService->check($user, 'ads.create');
    }

    public function editAd(User $user): bool
    {
        return $this->permissionService->check($user, 'ads.edit');
    }

    public function manageBudgets(User $user): bool
    {
        return $this->permissionService->check($user, 'ads.manage_budgets');
    }

    public function viewAdAnalytics(User $user): bool
    {
        return $this->permissionService->check($user, 'ads.view_analytics');
    }

    public function optimizeAdCampaigns(User $user): bool
    {
        return $this->permissionService->check($user, 'ads.optimize_campaigns');
    }

    // ========================================
    // Workflow & Automation
    // ========================================

    public function createWorkflow(User $user): bool
    {
        return $this->permissionService->check($user, 'workflow.create');
    }

    public function editWorkflow(User $user): bool
    {
        return $this->permissionService->check($user, 'workflow.edit');
    }

    public function executeWorkflow(User $user): bool
    {
        return $this->permissionService->check($user, 'workflow.execute');
    }

    public function viewWorkflowLogs(User $user): bool
    {
        return $this->permissionService->check($user, 'workflow.view_logs');
    }

    // ========================================
    // Legacy Compatibility (for old code)
    // ========================================

    public function viewRecommendations(User $user): bool
    {
        return $this->viewInsights($user);
    }
}
