<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class WorkflowController extends Controller
{
    use ApiResponse;

    protected $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->middleware('auth');
        $this->workflowService = $workflowService;
    }

    /**
     * Display workflow list
     */
    public function index(): View
    {
        try {
            $workflows = \DB::select("
                SELECT w.*,
                       (SELECT COUNT(*) FROM cmis.flow_steps WHERE flow_id = w.flow_id) as total_steps,
                       (SELECT COUNT(*) FROM cmis.flow_steps WHERE flow_id = w.flow_id AND status = 'completed') as completed_steps
                FROM cmis.flows w
                WHERE w.org_id = ?
                ORDER BY w.created_at DESC
                LIMIT 50
            ", [Auth::user()->current_org_id]);

            return view('workflows.index', compact('workflows'));
        } catch (\Exception $e) {
            Log::error('Workflow index error: ' . $e->getMessage());
            return view('workflows.index', ['workflows' => []]);
        }
    }

    /**
     * Show workflow details
     */
    public function show($flowId): View
    {
        try {
            $status = $this->workflowService->getWorkflowStatus($flowId);

            return view('workflows.show', [
                'workflow' => $status['workflow'],
                'steps' => $status['steps'],
                'progress' => $status['progress']
            ]);
        } catch (\Exception $e) {
            Log::error('Workflow show error: ' . $e->getMessage());
            return redirect()->route('workflows.index')->with('error', 'فشل تحميل سير العمل');
        }
    }

    /**
     * Initialize campaign workflow
     */
    public function initializeCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'required|uuid',
            'campaign_name' => 'required|string|max:255',
        ]);

        try {
            $flowId = $this->workflowService->initializeCampaignWorkflow(
                $validated['campaign_id'],
                $validated['campaign_name'],
                Auth::user()->current_org_id
            );

            return $this->success(['flow_id' => $flowId,
                'message' => 'تم إنشاء سير العمل بنجاح'], 'Operation completed successfully');
        } catch (\Exception $e) {
            Log::error('Workflow initialize error: ' . $e->getMessage());
            return $this->serverError('فشل إنشاء سير العمل' . ': ' . $e->getMessage());
        }
    }

    /**
     * Complete workflow step
     */
    public function completeStep(Request $request, $flowId, $stepNumber): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $this->workflowService->completeStep(
                $flowId,
                $stepNumber,
                $validated['notes'] ?? null,
                $validated['metadata'] ?? []
            );

            return $this->success(null, 'تم إكمال الخطوة بنجاح');
        } catch (\Exception $e) {
            Log::error('Workflow complete step error: ' . $e->getMessage());
            return $this->serverError('فشل إكمال الخطوة' . ': ' . $e->getMessage());
        }
    }

    /**
     * Assign step to user
     */
    public function assignStep(Request $request, $flowId, $stepNumber): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid',
        ]);

        try {
            $this->workflowService->assignStep(
                $flowId,
                $stepNumber,
                $validated['user_id']
            );

            return $this->success(null, 'تم تعيين الخطوة بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('فشل تعيين الخطوة' . ': ' . $e->getMessage());
        }
    }

    /**
     * Add comment to step
     */
    public function addComment(Request $request, $flowId, $stepNumber): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $this->workflowService->addComment(
                $flowId,
                $stepNumber,
                Auth::id(),
                $validated['comment']
            );

            return $this->success(null, 'تم إضافة التعليق بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('فشل إضافة التعليق' . ': ' . $e->getMessage());
        }
    }
}
