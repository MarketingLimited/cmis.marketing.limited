<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Super Admin Blog Category Controller
 *
 * Manages blog categories.
 */
class SuperAdminBlogCategoryController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $categories = BlogCategory::withCount('posts')
            ->orderBy('sort_order')
            ->get();

        if ($request->expectsJson()) {
            return $this->success(['categories' => $categories]);
        }

        return view('super-admin.website.blog-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('super-admin.website.blog-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255|unique:cmis_website.blog_categories,slug',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['sort_order'] = $validated['sort_order'] ?? BlogCategory::max('sort_order') + 1;

            $category = BlogCategory::create($validated);

            $this->logAction('blog_category_created', 'blog_category', $category->id, $category->name_en);

            if ($request->expectsJson()) {
                return $this->created($category, __('super_admin.website.blog_categories.created_success'));
            }

            return redirect()
                ->route('super-admin.website.blog-categories.index')
                ->with('success', __('super_admin.website.blog_categories.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create blog category', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.blog_categories.create_failed'));
        }
    }

    public function edit(string $id)
    {
        $category = BlogCategory::findOrFail($id);
        return view('super-admin.website.blog-categories.edit', compact('category'));
    }

    public function update(Request $request, string $id)
    {
        $category = BlogCategory::findOrFail($id);

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('cmis_website.blog_categories', 'slug')->ignore($category->id)],
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['slug']);
            $validated['is_active'] = $request->boolean('is_active', true);
            $category->update($validated);

            $this->logAction('blog_category_updated', 'blog_category', $category->id, $category->name_en);

            if ($request->expectsJson()) {
                return $this->success($category, __('super_admin.website.blog_categories.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.blog-categories.index')
                ->with('success', __('super_admin.website.blog_categories.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update blog category', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.blog_categories.update_failed'));
        }
    }

    public function destroy(string $id)
    {
        $category = BlogCategory::findOrFail($id);

        if ($category->posts()->exists()) {
            return back()->with('error', __('super_admin.website.blog_categories.has_posts'));
        }

        $this->logAction('blog_category_deleted', 'blog_category', $category->id, $category->name_en);
        $category->delete();

        return redirect()
            ->route('super-admin.website.blog-categories.index')
            ->with('success', __('super_admin.website.blog_categories.deleted_success'));
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            BlogCategory::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return $this->success(null, __('super_admin.website.blog_categories.reordered_success'));
    }
}
