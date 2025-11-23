<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Models\Creative\ContentPlan;
use App\Services\ContentPlanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GPT Bulk Operations Controller
 *
 * Handles bulk operations on resources for GPT/ChatGPT integration
 */
class GPTBulkOperationsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ContentPlanService $contentPlanService
    ) {}

    /**
     * Execute bulk operations on resources
     */
    public function execute(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'operation' => 'required|in:approve,reject,publish,archive',
            'resource_type' => 'required|in:content_plans,campaigns',
            'resource_ids' => 'required|array|min:1|max:50',
            'reason' => 'sometimes|required_if:operation,reject|string',
            'confirmed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        // SAFETY CHECK: Require explicit confirmation for operations affecting >10 items
        $resourceCount = count($request->input('resource_ids'));
        $confirmed = $request->input('confirmed', false);

        if ($resourceCount > 10 && !$confirmed) {
            return response()->json([
                'success' => false,
                'confirmation_required' => true,
                'message' => "This operation will affect {$resourceCount} resources. Please confirm by setting 'confirmed' to true.",
                'operation' => $request->input('operation'),
                'resource_type' => $request->input('resource_type'),
                'resource_count' => $resourceCount,
                'warning' => 'This action cannot be easily undone. Please verify the resource IDs before confirming.'
            ], 422);
        }

        try {
            $results = [];
            $errors = [];

            foreach ($request->input('resource_ids') as $resourceId) {
                try {
                    $result = $this->executeSingleOperation(
                        $request->input('resource_type'),
                        $resourceId,
                        $request->input('operation'),
                        $request->input('reason'),
                        $request->user()
                    );

                    $results[] = [
                        'id' => $resourceId,
                        'status' => 'success',
                        'result' => $result,
                    ];
                } catch (\Exception $e) {
                    \Log::warning('Bulk operation failed for resource', [
                        'resource_id' => $resourceId,
                        'operation' => $request->input('operation'),
                        'error' => $e->getMessage(),
                    ]);

                    $errors[] = [
                        'id' => $resourceId,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return $this->success([
                'successful' => count($results),
                'failed' => count($errors),
                'results' => $results,
                'errors' => $errors,
            ], 'Bulk operation completed');

        } catch (\Exception $e) {
            \Log::error('Bulk operation failed', [
                'operation' => $request->input('operation'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Bulk operation failed');
        }
    }

    /**
     * Execute single bulk operation
     */
    private function executeSingleOperation(
        string $resourceType,
        string $resourceId,
        string $operation,
        ?string $reason = null,
        $user = null
    ): array {
        if ($resourceType === 'content_plans') {
            $plan = ContentPlan::where('plan_id', $resourceId)
                ->where('org_id', $user->current_org_id)
                ->firstOrFail();

            switch ($operation) {
                case 'approve':
                    $this->contentPlanService->approve($plan);
                    break;
                case 'reject':
                    $this->contentPlanService->reject($plan, $reason);
                    break;
                case 'publish':
                    $this->contentPlanService->publish($plan);
                    break;
                case 'archive':
                    $plan->update(['status' => 'archived']);
                    break;
            }

            return [
                'id' => $plan->plan_id,
                'name' => $plan->name,
                'status' => $plan->status,
            ];
        }

        if ($resourceType === 'campaigns') {
            $campaign = Campaign::where('campaign_id', $resourceId)
                ->where('org_id', $user->current_org_id)
                ->firstOrFail();

            switch ($operation) {
                case 'archive':
                    $campaign->update(['status' => 'archived']);
                    break;
                default:
                    throw new \Exception("Operation {$operation} not supported for campaigns");
            }

            return [
                'id' => $campaign->campaign_id,
                'name' => $campaign->name,
                'status' => $campaign->status,
            ];
        }

        throw new \Exception("Unsupported resource type: {$resourceType}");
    }
}
