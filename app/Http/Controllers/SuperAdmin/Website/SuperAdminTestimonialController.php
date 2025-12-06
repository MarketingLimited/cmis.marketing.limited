<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Testimonial Controller
 *
 * Manages customer testimonials.
 */
class SuperAdminTestimonialController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $query = Testimonial::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured === 'yes');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('author_name_en', 'ilike', "%{$search}%")
                  ->orWhere('company_name_en', 'ilike', "%{$search}%");
            });
        }

        $testimonials = $query->orderBy('sort_order')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return $this->success(['testimonials' => $testimonials]);
        }

        return view('super-admin.website.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        return view('super-admin.website.testimonials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'author_name_en' => 'required|string|max:255',
            'author_name_ar' => 'nullable|string|max:255',
            'author_title_en' => 'nullable|string|max:255',
            'author_title_ar' => 'nullable|string|max:255',
            'company_name_en' => 'nullable|string|max:255',
            'company_name_ar' => 'nullable|string|max:255',
            'quote_en' => 'required|string|max:2000',
            'quote_ar' => 'nullable|string|max:2000',
            'author_image_url' => 'nullable|url|max:500',
            'company_logo_url' => 'nullable|url|max:500',
            'rating' => 'nullable|integer|min:1|max:5',
            'video_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['sort_order'] = $validated['sort_order'] ?? Testimonial::max('sort_order') + 1;

            $testimonial = Testimonial::create($validated);

            $this->logAction('testimonial_created', 'testimonial', $testimonial->id, $testimonial->author_name_en);

            if ($request->expectsJson()) {
                return $this->created($testimonial, __('super_admin.website.testimonials.created_success'));
            }

            return redirect()
                ->route('super-admin.website.testimonials.index')
                ->with('success', __('super_admin.website.testimonials.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create testimonial', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.testimonials.create_failed'));
        }
    }

    public function edit(string $id)
    {
        $testimonial = Testimonial::findOrFail($id);
        return view('super-admin.website.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, string $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validated = $request->validate([
            'author_name_en' => 'required|string|max:255',
            'author_name_ar' => 'nullable|string|max:255',
            'author_title_en' => 'nullable|string|max:255',
            'author_title_ar' => 'nullable|string|max:255',
            'company_name_en' => 'nullable|string|max:255',
            'company_name_ar' => 'nullable|string|max:255',
            'quote_en' => 'required|string|max:2000',
            'quote_ar' => 'nullable|string|max:2000',
            'author_image_url' => 'nullable|url|max:500',
            'company_logo_url' => 'nullable|url|max:500',
            'rating' => 'nullable|integer|min:1|max:5',
            'video_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $testimonial->update($validated);

            $this->logAction('testimonial_updated', 'testimonial', $testimonial->id, $testimonial->author_name_en);

            if ($request->expectsJson()) {
                return $this->success($testimonial, __('super_admin.website.testimonials.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.testimonials.index')
                ->with('success', __('super_admin.website.testimonials.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update testimonial', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.testimonials.update_failed'));
        }
    }

    public function toggleActive(string $id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->is_active = !$testimonial->is_active;
        $testimonial->save();

        $this->logAction($testimonial->is_active ? 'testimonial_activated' : 'testimonial_deactivated', 'testimonial', $testimonial->id, $testimonial->author_name_en);

        return $this->success(['is_active' => $testimonial->is_active]);
    }

    public function toggleFeatured(string $id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->is_featured = !$testimonial->is_featured;
        $testimonial->save();

        return $this->success(['is_featured' => $testimonial->is_featured]);
    }

    public function destroy(string $id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $this->logAction('testimonial_deleted', 'testimonial', $testimonial->id, $testimonial->author_name_en);
        $testimonial->delete();

        return redirect()
            ->route('super-admin.website.testimonials.index')
            ->with('success', __('super_admin.website.testimonials.deleted_success'));
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            Testimonial::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return $this->success(null, __('super_admin.website.testimonials.reordered_success'));
    }
}
