<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Listening\ResponseTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Response Template Controller
 *
 * Manages response templates for social media conversations
 */
class ResponseTemplateController extends Controller
{
    use ApiResponse;

    /**
     * Get response templates
     *
     * GET /api/listening/templates
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $templates = ResponseTemplate::where('org_id', $orgId);

        if ($request->has('category')) {
            $templates->where('category', $request->category);
        }

        if ($request->has('platform')) {
            $templates->forPlatform($request->platform);
        }

        $templates = $templates->active()->orderBy('usage_count', 'desc')->get();

        return $this->success($templates, 'Templates retrieved successfully');
    }

    /**
     * Create response template
     *
     * POST /api/listening/templates
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'category' => 'string|max:100',
            'template_content' => 'required|string',
            'description' => 'string|nullable',
            'variables' => 'array',
            'suggested_triggers' => 'array',
            'platforms' => 'array',
        ]);

        $template = ResponseTemplate::create([
            'org_id' => $request->user()->org_id,
            'created_by' => $request->user()->id,
            ...$validated,
        ]);

        return $this->created($template, 'Template created successfully');
    }

    /**
     * Show single template
     *
     * GET /api/listening/templates/{id}
     */
    public function show(string $id): JsonResponse
    {
        $template = ResponseTemplate::findOrFail($id);

        return $this->success($template, 'Template retrieved successfully');
    }

    /**
     * Update response template
     *
     * PUT/PATCH /api/listening/templates/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = ResponseTemplate::findOrFail($id);

        $validated = $request->validate([
            'template_name' => 'string|max:255',
            'category' => 'string|max:100',
            'template_content' => 'string',
            'description' => 'string|nullable',
            'variables' => 'array',
            'suggested_triggers' => 'array',
            'platforms' => 'array',
            'status' => 'in:active,inactive',
        ]);

        $template->update($validated);

        return $this->success($template->fresh(), 'Template updated successfully');
    }

    /**
     * Delete response template
     *
     * DELETE /api/listening/templates/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $template = ResponseTemplate::findOrFail($id);
        $template->delete();

        return $this->deleted('Template deleted successfully');
    }
}
