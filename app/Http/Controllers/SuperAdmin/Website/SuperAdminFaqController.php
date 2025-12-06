<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\FaqItem;
use App\Models\Website\FaqCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin FAQ Controller
 *
 * Manages FAQ items.
 */
class SuperAdminFaqController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $query = FaqItem::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question_en', 'ilike', "%{$search}%")
                  ->orWhere('answer_en', 'ilike', "%{$search}%");
            });
        }

        $faqs = $query->orderBy('sort_order')->paginate(20)->withQueryString();
        $categories = FaqCategory::orderBy('sort_order')->get();

        if ($request->expectsJson()) {
            return $this->success(['faqs' => $faqs, 'categories' => $categories]);
        }

        return view('super-admin.website.faqs.index', compact('faqs', 'categories'));
    }

    public function create()
    {
        $categories = FaqCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('super-admin.website.faqs.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:cmis_website.faq_categories,id',
            'question_en' => 'required|string|max:500',
            'question_ar' => 'nullable|string|max:500',
            'answer_en' => 'required|string',
            'answer_ar' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['sort_order'] = $validated['sort_order'] ?? FaqItem::max('sort_order') + 1;

            $faq = FaqItem::create($validated);

            $this->logAction('faq_created', 'faq', $faq->id, substr($faq->question_en, 0, 50));

            if ($request->expectsJson()) {
                return $this->created($faq, __('super_admin.website.faqs.created_success'));
            }

            return redirect()
                ->route('super-admin.website.faqs.index')
                ->with('success', __('super_admin.website.faqs.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create FAQ', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.faqs.create_failed'));
        }
    }

    public function edit(string $id)
    {
        $faq = FaqItem::findOrFail($id);
        $categories = FaqCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('super-admin.website.faqs.edit', compact('faq', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $faq = FaqItem::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:cmis_website.faq_categories,id',
            'question_en' => 'required|string|max:500',
            'question_ar' => 'nullable|string|max:500',
            'answer_en' => 'required|string',
            'answer_ar' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $faq->update($validated);

            $this->logAction('faq_updated', 'faq', $faq->id, substr($faq->question_en, 0, 50));

            if ($request->expectsJson()) {
                return $this->success($faq, __('super_admin.website.faqs.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.faqs.index')
                ->with('success', __('super_admin.website.faqs.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update FAQ', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.faqs.update_failed'));
        }
    }

    public function toggleActive(string $id)
    {
        $faq = FaqItem::findOrFail($id);
        $faq->is_active = !$faq->is_active;
        $faq->save();

        return $this->success(['is_active' => $faq->is_active]);
    }

    public function destroy(string $id)
    {
        $faq = FaqItem::findOrFail($id);
        $this->logAction('faq_deleted', 'faq', $faq->id, substr($faq->question_en, 0, 50));
        $faq->delete();

        return redirect()
            ->route('super-admin.website.faqs.index')
            ->with('success', __('super_admin.website.faqs.deleted_success'));
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            FaqItem::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return $this->success(null, __('super_admin.website.faqs.reordered_success'));
    }
}
