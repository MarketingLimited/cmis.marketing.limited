<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ApiResponse;

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
    public function index(string $org)
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
            ", [$org]);

            return view('workflows.index', compact('workflows'));
        } catch (\Exception $e) {
            Log::error('Workflow index error: ' . $e->getMessage());
            return view('workflows.index', ['workflows' => []]);
        }
    }

    /**
     * Show workflow details
     */
    public function show(string $org, $flowId)
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
            return redirect()->route('orgs.workflows.index', ['org' => $org])->with('error', __('workflows.operation_failed'));
        }
    }

    /**
     * Initialize campaign workflow
     */
    public function initializeCampaign(Request $request, string $org)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|uuid',
            'campaign_name' => 'required|string|max:255',
        ]);

        try {
            $flowId = $this->workflowService->initializeCampaignWorkflow(
                $validated['campaign_id'],
                $validated['campaign_name'],
                $org
            );

            return response()->json([
                'success' => true,
                'flow_id' => $flowId,
                'message' => 'تم إنشاء سير العمل بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Workflow initialize error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل إنشاء سير العمل',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete workflow step
     */
    public function completeStep(Request $request, string $org, $flowId, $stepNumber)
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

            return response()->json([
                'success' => true,
                'message' => 'تم إكمال الخطوة بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Workflow complete step error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل إكمال الخطوة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign step to user
     */
    public function assignStep(Request $request, string $org, $flowId, $stepNumber)
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

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين الخطوة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل تعيين الخطوة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add comment to step
     */
    public function addComment(Request $request, string $org, $flowId, $stepNumber)
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

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة التعليق بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل إضافة التعليق',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
