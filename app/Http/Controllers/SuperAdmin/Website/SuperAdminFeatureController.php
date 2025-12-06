<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\Feature;
use App\Models\Website\FeatureCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Feature Controller
 *
 * Manages platform features displayed on the features page.
 */
class SuperAdminFeatureController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $query = Feature::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title_en', 'ilike', "%{$search}%")
                  ->orWhere('title_ar', 'ilike', "%{$search}%");
            });
        }

        $features = $query->orderBy('sort_order')->paginate(20)->withQueryString();
        $categories = FeatureCategory::orderBy('sort_order')->get();

        if ($request->expectsJson()) {
            return $this->success(['features' => $features, 'categories' => $categories]);
        }

        return view('super-admin.website.features.index', compact('features', 'categories'));
    }

    public function create()
    {
        $categories = FeatureCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('super-admin.website.features.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:cmis_website.feature_categories,id',
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:2000',
            'description_ar' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:100',
            'image_url' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'badge_text_en' => 'nullable|string|max:50',
            'badge_text_ar' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_highlighted'] = $request->boolean('is_highlighted', false);
            $validated['sort_order'] = $validated['sort_order'] ?? Feature::max('sort_order') + 1;

            $feature = Feature::create($validated);

            $this->logAction('feature_created', 'feature', $feature->id, $feature->title_en);

            if ($request->expectsJson()) {
                return $this->created($feature, __('super_admin.website.features.created_success'));
            }

            return redirect()
                ->route('super-admin.website.features.index')
                ->with('success', __('super_admin.website.features.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create feature', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.features.create_failed'));
        }
    }

    public function edit(string $id)
    {
        $feature = Feature::findOrFail($id);
        $categories = FeatureCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('super-admin.website.features.edit', compact('feature', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $feature = Feature::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:cmis_website.feature_categories,id',
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:2000',
            'description_ar' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:100',
            'image_url' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'badge_text_en' => 'nullable|string|max:50',
            'badge_text_ar' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_highlighted'] = $request->boolean('is_highlighted', false);
            $feature->update($validated);

            $this->logAction('feature_updated', 'feature', $feature->id, $feature->title_en);

            if ($request->expectsJson()) {
                return $this->success($feature, __('super_admin.website.features.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.features.index')
                ->with('success', __('super_admin.website.features.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update feature', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.features.update_failed'));
        }
    }

    public function toggleActive(string $id)
    {
        $feature = Feature::findOrFail($id);
        $feature->is_active = !$feature->is_active;
        $feature->save();

        $this->logAction($feature->is_active ? 'feature_activated' : 'feature_deactivated', 'feature', $feature->id, $feature->title_en);

        return $this->success(['is_active' => $feature->is_active]);
    }

    public function destroy(string $id)
    {
        $feature = Feature::findOrFail($id);
        $this->logAction('feature_deleted', 'feature', $feature->id, $feature->title_en);
        $feature->delete();

        return redirect()
            ->route('super-admin.website.features.index')
            ->with('success', __('super_admin.website.features.deleted_success'));
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            Feature::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return $this->success(null, __('super_admin.website.features.reordered_success'));
    }
}
