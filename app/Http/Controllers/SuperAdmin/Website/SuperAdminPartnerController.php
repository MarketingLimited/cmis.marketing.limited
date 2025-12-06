<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Partner Controller
 *
 * Manages partner/client logos displayed on the website.
 */
class SuperAdminPartnerController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $query = Partner::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured === 'yes');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%");
            });
        }

        $partners = $query->orderBy('sort_order')->paginate(15)->withQueryString();

        $types = [
            'client' => __('super_admin.website.partners.types.client'),
            'partner' => __('super_admin.website.partners.types.partner'),
            'sponsor' => __('super_admin.website.partners.types.sponsor'),
            'integration' => __('super_admin.website.partners.types.integration'),
        ];

        if ($request->expectsJson()) {
            return $this->success(['partners' => $partners, 'types' => $types]);
        }

        return view('super-admin.website.partners.index', compact('partners', 'types'));
    }

    public function create()
    {
        $types = [
            'client' => __('super_admin.website.partners.types.client'),
            'partner' => __('super_admin.website.partners.types.partner'),
            'sponsor' => __('super_admin.website.partners.types.sponsor'),
            'integration' => __('super_admin.website.partners.types.integration'),
        ];
        return view('super-admin.website.partners.create', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'logo_url' => 'required|url|max:500',
            'logo_dark_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'type' => 'required|string|in:client,partner,sponsor,integration',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['sort_order'] = $validated['sort_order'] ?? Partner::max('sort_order') + 1;

            $partner = Partner::create($validated);

            $this->logAction('partner_created', 'partner', $partner->id, $partner->name_en);

            if ($request->expectsJson()) {
                return $this->created($partner, __('super_admin.website.partners.created_success'));
            }

            return redirect()
                ->route('super-admin.website.partners.index')
                ->with('success', __('super_admin.website.partners.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create partner', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.partners.create_failed'));
        }
    }

    public function edit(string $id)
    {
        $partner = Partner::findOrFail($id);
        $types = [
            'client' => __('super_admin.website.partners.types.client'),
            'partner' => __('super_admin.website.partners.types.partner'),
            'sponsor' => __('super_admin.website.partners.types.sponsor'),
            'integration' => __('super_admin.website.partners.types.integration'),
        ];
        return view('super-admin.website.partners.edit', compact('partner', 'types'));
    }

    public function update(Request $request, string $id)
    {
        $partner = Partner::findOrFail($id);

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'logo_url' => 'required|url|max:500',
            'logo_dark_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'type' => 'required|string|in:client,partner,sponsor,integration',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $partner->update($validated);

            $this->logAction('partner_updated', 'partner', $partner->id, $partner->name_en);

            if ($request->expectsJson()) {
                return $this->success($partner, __('super_admin.website.partners.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.partners.index')
                ->with('success', __('super_admin.website.partners.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update partner', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.partners.update_failed'));
        }
    }

    public function toggleActive(string $id)
    {
        $partner = Partner::findOrFail($id);
        $partner->is_active = !$partner->is_active;
        $partner->save();

        return $this->success(['is_active' => $partner->is_active]);
    }

    public function toggleFeatured(string $id)
    {
        $partner = Partner::findOrFail($id);
        $partner->is_featured = !$partner->is_featured;
        $partner->save();

        return $this->success(['is_featured' => $partner->is_featured]);
    }

    public function destroy(string $id)
    {
        $partner = Partner::findOrFail($id);
        $this->logAction('partner_deleted', 'partner', $partner->id, $partner->name_en);
        $partner->delete();

        return redirect()
            ->route('super-admin.website.partners.index')
            ->with('success', __('super_admin.website.partners.deleted_success'));
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            Partner::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return $this->success(null, __('super_admin.website.partners.reordered_success'));
    }
}
