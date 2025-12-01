<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Social\QueueSlotLabelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QueueSlotLabelController extends Controller
{
    use ApiResponse;

    protected QueueSlotLabelService $labelService;

    public function __construct(QueueSlotLabelService $labelService)
    {
        $this->labelService = $labelService;
    }

    /**
     * Get all queue slot labels for the organization.
     *
     * @param Request $request
     * @param string $org Organization UUID
     * @return JsonResponse
     */
    public function index(Request $request, string $org): JsonResponse
    {
        try {
            // Initialize RLS context
            $user = $request->user();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$user->user_id, $org]);

            $search = $request->get('search');
            $labels = $this->labelService->getLabels($org, $search);

            $formattedLabels = $labels->map(function ($label) {
                return [
                    'id' => $label->id,
                    'name' => $label->name,
                    'slug' => $label->slug,
                    'background_color' => $label->background_color,
                    'text_color' => $label->text_color,
                    'color_type' => $label->color_type,
                    'gradient_start' => $label->gradient_start,
                    'gradient_end' => $label->gradient_end,
                    'computed_background' => $label->computed_background,
                    'sort_order' => $label->sort_order,
                    'created_at' => $label->created_at,
                    'updated_at' => $label->updated_at,
                ];
            });

            return $this->success($formattedLabels, __('profiles.labels_retrieved'));
        } catch (\Exception $e) {
            return $this->error(__('profiles.labels_load_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Create a new queue slot label.
     *
     * @param Request $request
     * @param string $org Organization UUID
     * @return JsonResponse
     */
    public function store(Request $request, string $org): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'background_color' => 'nullable|string|max:100',
            'text_color' => 'nullable|string|max:20',
            'color_type' => 'nullable|in:solid,gradient',
            'gradient_start' => 'nullable|string|max:20',
            'gradient_end' => 'nullable|string|max:20',
        ]);

        try {
            // Initialize RLS context
            $user = $request->user();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$user->user_id, $org]);

            $label = $this->labelService->createLabel($org, $validated);

            return $this->created([
                'id' => $label->id,
                'name' => $label->name,
                'slug' => $label->slug,
                'background_color' => $label->background_color,
                'text_color' => $label->text_color,
                'color_type' => $label->color_type,
                'gradient_start' => $label->gradient_start,
                'gradient_end' => $label->gradient_end,
                'computed_background' => $label->computed_background,
                'sort_order' => $label->sort_order,
            ], __('profiles.label_created'));
        } catch (\Exception $e) {
            return $this->error(__('profiles.label_create_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Update an existing queue slot label.
     *
     * @param Request $request
     * @param string $org Organization UUID
     * @param string $labelId Label UUID
     * @return JsonResponse
     */
    public function update(Request $request, string $org, string $labelId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'background_color' => 'nullable|string|max:100',
            'text_color' => 'nullable|string|max:20',
            'color_type' => 'nullable|in:solid,gradient',
            'gradient_start' => 'nullable|string|max:20',
            'gradient_end' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            // Initialize RLS context
            $user = $request->user();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$user->user_id, $org]);

            $label = $this->labelService->updateLabel($org, $labelId, $validated);

            if (!$label) {
                return $this->notFound(__('profiles.label_not_found'));
            }

            return $this->success([
                'id' => $label->id,
                'name' => $label->name,
                'slug' => $label->slug,
                'background_color' => $label->background_color,
                'text_color' => $label->text_color,
                'color_type' => $label->color_type,
                'gradient_start' => $label->gradient_start,
                'gradient_end' => $label->gradient_end,
                'computed_background' => $label->computed_background,
                'sort_order' => $label->sort_order,
            ], __('profiles.label_updated'));
        } catch (\Exception $e) {
            return $this->error(__('profiles.label_update_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Delete a queue slot label.
     *
     * @param Request $request
     * @param string $org Organization UUID
     * @param string $labelId Label UUID
     * @return JsonResponse
     */
    public function destroy(Request $request, string $org, string $labelId): JsonResponse
    {
        try {
            // Initialize RLS context
            $user = $request->user();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$user->user_id, $org]);

            $deleted = $this->labelService->deleteLabel($org, $labelId);

            if (!$deleted) {
                return $this->notFound(__('profiles.label_not_found'));
            }

            return $this->deleted(__('profiles.label_deleted'));
        } catch (\Exception $e) {
            return $this->error(__('profiles.label_delete_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get color presets for the UI.
     *
     * @param string $org Organization UUID
     * @return JsonResponse
     */
    public function presets(string $org): JsonResponse
    {
        try {
            $presets = $this->labelService->getColorPresets();

            return $this->success($presets, __('profiles.presets_retrieved'));
        } catch (\Exception $e) {
            return $this->error(__('profiles.presets_load_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Reorder labels.
     *
     * @param Request $request
     * @param string $org Organization UUID
     * @return JsonResponse
     */
    public function reorder(Request $request, string $org): JsonResponse
    {
        $validated = $request->validate([
            'label_ids' => 'required|array',
            'label_ids.*' => 'required|uuid',
        ]);

        try {
            // Initialize RLS context
            $user = $request->user();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$user->user_id, $org]);

            $this->labelService->reorderLabels($org, $validated['label_ids']);

            return $this->success(null, __('profiles.labels_reordered'));
        } catch (\Exception $e) {
            return $this->error(__('profiles.labels_reorder_failed') . ': ' . $e->getMessage());
        }
    }
}
