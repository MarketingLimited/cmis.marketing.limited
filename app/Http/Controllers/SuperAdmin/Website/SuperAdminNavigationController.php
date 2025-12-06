<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\NavigationMenu;
use App\Models\Website\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Navigation Controller
 *
 * Manages website navigation menus and items.
 */
class SuperAdminNavigationController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display navigation menus.
     */
    public function index(Request $request)
    {
        $menus = NavigationMenu::withCount('items')->get();

        if ($request->expectsJson()) {
            return $this->success(['menus' => $menus]);
        }

        return view('super-admin.website.navigation.index', compact('menus'));
    }

    /**
     * Show a specific menu with its items.
     */
    public function show(string $id)
    {
        $menu = NavigationMenu::with(['items' => function ($q) {
            $q->whereNull('parent_id')
              ->with(['children' => function ($q) {
                  $q->orderBy('sort_order');
              }])
              ->orderBy('sort_order');
        }])->findOrFail($id);

        return view('super-admin.website.navigation.show', compact('menu'));
    }

    /**
     * Create a new menu.
     */
    public function storeMenu(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|in:header,footer,mobile,sidebar',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);

            $menu = NavigationMenu::create($validated);

            $this->logAction('navigation_menu_created', 'navigation_menu', $menu->id, $menu->name);

            if ($request->expectsJson()) {
                return $this->created($menu, __('super_admin.website.navigation.menu_created_success'));
            }

            return redirect()
                ->route('super-admin.website.navigation.show', $menu->id)
                ->with('success', __('super_admin.website.navigation.menu_created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create navigation menu', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.navigation.menu_create_failed'));
        }
    }

    /**
     * Update a menu.
     */
    public function updateMenu(Request $request, string $id)
    {
        $menu = NavigationMenu::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|in:header,footer,mobile,sidebar',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $menu->update($validated);

            $this->logAction('navigation_menu_updated', 'navigation_menu', $menu->id, $menu->name);

            if ($request->expectsJson()) {
                return $this->success($menu, __('super_admin.website.navigation.menu_updated_success'));
            }

            return redirect()
                ->route('super-admin.website.navigation.show', $menu->id)
                ->with('success', __('super_admin.website.navigation.menu_updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update navigation menu', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.navigation.menu_update_failed'));
        }
    }

    /**
     * Delete a menu and all its items.
     */
    public function destroyMenu(string $id)
    {
        $menu = NavigationMenu::findOrFail($id);

        $this->logAction('navigation_menu_deleted', 'navigation_menu', $menu->id, $menu->name);
        $menu->delete();

        return redirect()
            ->route('super-admin.website.navigation.index')
            ->with('success', __('super_admin.website.navigation.menu_deleted_success'));
    }

    /**
     * Add an item to a menu.
     */
    public function storeItem(Request $request, string $menuId)
    {
        $menu = NavigationMenu::findOrFail($menuId);

        $validated = $request->validate([
            'parent_id' => 'nullable|uuid|exists:cmis_website.navigation_items,id',
            'label_en' => 'required|string|max:255',
            'label_ar' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:500',
            'route_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'target' => 'nullable|string|in:_self,_blank',
            'type' => 'nullable|string|in:link,dropdown,mega',
            'attributes' => 'nullable|array',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'highlight_color' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['menu_id'] = $menu->id;
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_highlighted'] = $request->boolean('is_highlighted', false);
            $validated['target'] = $validated['target'] ?? '_self';
            $validated['type'] = $validated['type'] ?? 'link';

            // Get max sort order for this parent
            $maxOrder = NavigationItem::where('menu_id', $menu->id)
                ->where('parent_id', $validated['parent_id'] ?? null)
                ->max('sort_order');
            $validated['sort_order'] = $validated['sort_order'] ?? ($maxOrder + 1);

            $item = NavigationItem::create($validated);

            $this->logAction('navigation_item_created', 'navigation_item', $item->id, $item->label_en);

            if ($request->expectsJson()) {
                return $this->created($item, __('super_admin.website.navigation.item_created_success'));
            }

            return redirect()
                ->route('super-admin.website.navigation.show', $menu->id)
                ->with('success', __('super_admin.website.navigation.item_created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create navigation item', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.navigation.item_create_failed'));
        }
    }

    /**
     * Update an item.
     */
    public function updateItem(Request $request, string $itemId)
    {
        $item = NavigationItem::findOrFail($itemId);

        $validated = $request->validate([
            'parent_id' => 'nullable|uuid|exists:cmis_website.navigation_items,id',
            'label_en' => 'required|string|max:255',
            'label_ar' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:500',
            'route_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'target' => 'nullable|string|in:_self,_blank',
            'type' => 'nullable|string|in:link,dropdown,mega',
            'attributes' => 'nullable|array',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'highlight_color' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_highlighted'] = $request->boolean('is_highlighted', false);
            $item->update($validated);

            $this->logAction('navigation_item_updated', 'navigation_item', $item->id, $item->label_en);

            if ($request->expectsJson()) {
                return $this->success($item, __('super_admin.website.navigation.item_updated_success'));
            }

            return redirect()
                ->route('super-admin.website.navigation.show', $item->menu_id)
                ->with('success', __('super_admin.website.navigation.item_updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update navigation item', ['id' => $itemId, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.navigation.item_update_failed'));
        }
    }

    /**
     * Delete an item.
     */
    public function destroyItem(string $itemId)
    {
        $item = NavigationItem::findOrFail($itemId);
        $menuId = $item->menu_id;

        $this->logAction('navigation_item_deleted', 'navigation_item', $item->id, $item->label_en);
        $item->delete();

        return redirect()
            ->route('super-admin.website.navigation.show', $menuId)
            ->with('success', __('super_admin.website.navigation.item_deleted_success'));
    }

    /**
     * Reorder items.
     */
    public function reorderItems(Request $request, string $menuId)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid',
            'items.*.parent_id' => 'nullable|uuid',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                foreach ($validated['items'] as $item) {
                    NavigationItem::where('id', $item['id'])->update([
                        'parent_id' => $item['parent_id'],
                        'sort_order' => $item['sort_order'],
                    ]);
                }
            });

            $this->logAction('navigation_items_reordered', 'navigation_menu', $menuId, 'Items reordered');

            return $this->success(null, __('super_admin.website.navigation.reordered_success'));
        } catch (\Exception $e) {
            Log::error('Failed to reorder navigation items', ['error' => $e->getMessage()]);
            return $this->serverError(__('super_admin.website.navigation.reorder_failed'));
        }
    }

    /**
     * Toggle item active status.
     */
    public function toggleItemActive(string $itemId)
    {
        $item = NavigationItem::findOrFail($itemId);
        $item->is_active = !$item->is_active;
        $item->save();

        return $this->success(['is_active' => $item->is_active]);
    }
}
