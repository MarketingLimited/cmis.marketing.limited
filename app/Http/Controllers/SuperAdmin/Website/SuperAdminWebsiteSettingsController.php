<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Website Settings Controller
 *
 * Manages global website settings.
 */
class SuperAdminWebsiteSettingsController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display website settings grouped by category.
     */
    public function index(Request $request)
    {
        $settings = WebsiteSetting::orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $groups = [
            'general' => __('super_admin.website.settings.groups.general'),
            'branding' => __('super_admin.website.settings.groups.branding'),
            'social' => __('super_admin.website.settings.groups.social'),
            'seo' => __('super_admin.website.settings.groups.seo'),
            'contact' => __('super_admin.website.settings.groups.contact'),
            'analytics' => __('super_admin.website.settings.groups.analytics'),
            'advanced' => __('super_admin.website.settings.groups.advanced'),
        ];

        if ($request->expectsJson()) {
            return $this->success(['settings' => $settings, 'groups' => $groups]);
        }

        return view('super-admin.website.settings.index', compact('settings', 'groups'));
    }

    /**
     * Update a specific setting.
     */
    public function update(Request $request, string $key)
    {
        $setting = WebsiteSetting::where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'value_en' => 'nullable|string',
            'value_ar' => 'nullable|string',
        ]);

        try {
            $setting->update($validated);

            // Clear website settings cache
            Cache::forget('website_settings');

            $this->logAction('website_setting_updated', 'website_setting', $setting->id, $setting->key);

            if ($request->expectsJson()) {
                return $this->success($setting, __('super_admin.website.settings.updated_success'));
            }

            return back()->with('success', __('super_admin.website.settings.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update website setting', ['key' => $key, 'error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.website.settings.update_failed'));
            }

            return back()->with('error', __('super_admin.website.settings.update_failed'));
        }
    }

    /**
     * Bulk update settings.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value_en' => 'nullable|string',
            'settings.*.value_ar' => 'nullable|string',
        ]);

        try {
            foreach ($validated['settings'] as $item) {
                WebsiteSetting::where('key', $item['key'])->update([
                    'value_en' => $item['value_en'] ?? null,
                    'value_ar' => $item['value_ar'] ?? null,
                ]);
            }

            // Clear website settings cache
            Cache::forget('website_settings');

            $this->logAction('website_settings_bulk_updated', 'website_setting', null, 'Bulk update', [
                'count' => count($validated['settings']),
            ]);

            if ($request->expectsJson()) {
                return $this->success(null, __('super_admin.website.settings.bulk_updated_success'));
            }

            return back()->with('success', __('super_admin.website.settings.bulk_updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to bulk update website settings', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.website.settings.bulk_update_failed'));
            }

            return back()->with('error', __('super_admin.website.settings.bulk_update_failed'));
        }
    }

    /**
     * Create a new setting.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:cmis_website.website_settings,key',
            'value_en' => 'nullable|string',
            'value_ar' => 'nullable|string',
            'group' => 'required|string|max:50',
            'type' => 'nullable|string|in:text,textarea,url,email,image,boolean,json',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_public'] = $request->boolean('is_public', false);
            $validated['type'] = $validated['type'] ?? 'text';
            $validated['sort_order'] = $validated['sort_order'] ?? WebsiteSetting::where('group', $validated['group'])->max('sort_order') + 1;

            $setting = WebsiteSetting::create($validated);

            // Clear website settings cache
            Cache::forget('website_settings');

            $this->logAction('website_setting_created', 'website_setting', $setting->id, $setting->key);

            if ($request->expectsJson()) {
                return $this->created($setting, __('super_admin.website.settings.created_success'));
            }

            return back()->with('success', __('super_admin.website.settings.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create website setting', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.website.settings.create_failed'));
            }

            return back()->withInput()->with('error', __('super_admin.website.settings.create_failed'));
        }
    }

    /**
     * Delete a setting.
     */
    public function destroy(string $key)
    {
        $setting = WebsiteSetting::where('key', $key)->firstOrFail();

        $this->logAction('website_setting_deleted', 'website_setting', $setting->id, $setting->key);
        $setting->delete();

        // Clear website settings cache
        Cache::forget('website_settings');

        return redirect()
            ->route('super-admin.website.settings.index')
            ->with('success', __('super_admin.website.settings.deleted_success'));
    }

    /**
     * Reset all settings to defaults (from seeders).
     */
    public function resetToDefaults(Request $request)
    {
        // This would typically re-run the WebsiteSettingsSeeder
        // For safety, we just clear the cache and inform the user
        Cache::forget('website_settings');

        $this->logAction('website_settings_cache_cleared', 'website_setting', null, 'Cache cleared');

        if ($request->expectsJson()) {
            return $this->success(null, __('super_admin.website.settings.cache_cleared'));
        }

        return back()->with('success', __('super_admin.website.settings.cache_cleared'));
    }

    /**
     * Export settings as JSON.
     */
    public function export()
    {
        $settings = WebsiteSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => [
                'value_en' => $setting->value_en,
                'value_ar' => $setting->value_ar,
                'group' => $setting->group,
                'type' => $setting->type,
            ]];
        });

        $this->logAction('website_settings_exported', 'website_setting', null, 'Settings exported');

        return response()->json($settings)
            ->header('Content-Disposition', 'attachment; filename="website-settings.json"');
    }

    /**
     * Import settings from JSON.
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $content = json_decode(file_get_contents($validated['file']->path()), true);

            if (!is_array($content)) {
                return back()->with('error', __('super_admin.website.settings.invalid_json'));
            }

            foreach ($content as $key => $data) {
                WebsiteSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value_en' => $data['value_en'] ?? null,
                        'value_ar' => $data['value_ar'] ?? null,
                        'group' => $data['group'] ?? 'general',
                        'type' => $data['type'] ?? 'text',
                    ]
                );
            }

            // Clear website settings cache
            Cache::forget('website_settings');

            $this->logAction('website_settings_imported', 'website_setting', null, 'Settings imported', [
                'count' => count($content),
            ]);

            return back()->with('success', __('super_admin.website.settings.imported_success', ['count' => count($content)]));
        } catch (\Exception $e) {
            Log::error('Failed to import website settings', ['error' => $e->getMessage()]);
            return back()->with('error', __('super_admin.website.settings.import_failed'));
        }
    }
}
