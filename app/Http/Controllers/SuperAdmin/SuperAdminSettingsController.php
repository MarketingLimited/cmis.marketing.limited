<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Core\GlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Super Admin Settings Controller
 *
 * Manages global platform settings.
 */
class SuperAdminSettingsController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display settings dashboard.
     */
    public function index(Request $request)
    {
        $groups = GlobalSetting::getGroups();
        $currentGroup = $request->get('group', 'general');

        $settings = GlobalSetting::where('group', $currentGroup)
            ->orderBy('sort_order')
            ->get();

        // Get plans for default_plan_id select
        $plans = DB::table('cmis.plans')
            ->select('plan_id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($request->expectsJson()) {
            return $this->success([
                'groups' => $groups,
                'current_group' => $currentGroup,
                'settings' => $settings,
            ]);
        }

        return view('super-admin.settings.index', compact('groups', 'currentGroup', 'settings', 'plans'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        if (empty($settings)) {
            return back()->with('error', __('super_admin.settings.no_changes'));
        }

        $updated = [];
        $errors = [];

        foreach ($settings as $key => $value) {
            $setting = GlobalSetting::where('key', $key)->first();

            if (!$setting) {
                $errors[] = __('super_admin.settings.key_not_found', ['key' => $key]);
                continue;
            }

            // Validate value based on type
            if (!$this->validateSettingValue($setting, $value)) {
                $errors[] = __('super_admin.settings.invalid_value', ['key' => $key]);
                continue;
            }

            // Format value based on type
            $formattedValue = $this->formatValue($setting, $value);

            // Check if value actually changed
            if ($setting->value !== $formattedValue) {
                $oldValue = $setting->value;
                $setting->value = $formattedValue;
                $setting->save();

                $updated[$key] = [
                    'old' => $oldValue,
                    'new' => $formattedValue,
                ];
            }
        }

        // Log the action
        if (!empty($updated)) {
            $this->logSuperAdminAction(
                'update_settings',
                'settings',
                null,
                ['updated' => $updated]
            );

            // Clear settings cache
            GlobalSetting::clearCache();
        }

        if ($request->expectsJson()) {
            if (!empty($errors)) {
                return $this->error(implode(', ', $errors));
            }
            return $this->success(['updated' => array_keys($updated)], __('super_admin.settings.updated'));
        }

        if (!empty($errors)) {
            return back()->with('error', implode('<br>', $errors))->withInput();
        }

        return back()->with('success', __('super_admin.settings.updated'));
    }

    /**
     * Reset a setting to its default value.
     */
    public function reset(Request $request, string $key)
    {
        $setting = GlobalSetting::where('key', $key)->first();

        if (!$setting) {
            return $request->expectsJson()
                ? $this->notFound(__('super_admin.settings.not_found'))
                : back()->with('error', __('super_admin.settings.not_found'));
        }

        $defaults = $this->getDefaultValues();

        if (!isset($defaults[$key])) {
            return $request->expectsJson()
                ? $this->error(__('super_admin.settings.no_default'))
                : back()->with('error', __('super_admin.settings.no_default'));
        }

        $oldValue = $setting->value;
        $setting->value = $defaults[$key];
        $setting->save();

        $this->logSuperAdminAction(
            'reset_setting',
            'settings',
            $setting->id,
            ['key' => $key, 'old' => $oldValue, 'new' => $defaults[$key]]
        );

        GlobalSetting::clearCache();

        return $request->expectsJson()
            ? $this->success(null, __('super_admin.settings.reset_success'))
            : back()->with('success', __('super_admin.settings.reset_success'));
    }

    /**
     * Export all settings as JSON.
     */
    public function export(Request $request)
    {
        $settings = GlobalSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->value];
        });

        $this->logSuperAdminAction('export_settings', 'settings', null);

        return response()->json($settings, 200, [
            'Content-Disposition' => 'attachment; filename="settings_' . date('Y-m-d') . '.json"',
        ]);
    }

    /**
     * Import settings from JSON.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $content = file_get_contents($request->file('file')->path());
            $data = json_decode($content, true);

            if (!is_array($data)) {
                throw new \Exception('Invalid JSON format');
            }

            $imported = 0;
            foreach ($data as $key => $value) {
                $setting = GlobalSetting::where('key', $key)->first();
                if ($setting) {
                    $setting->value = (string) $value;
                    $setting->save();
                    $imported++;
                }
            }

            GlobalSetting::clearCache();

            $this->logSuperAdminAction(
                'import_settings',
                'settings',
                null,
                ['imported_count' => $imported]
            );

            return back()->with('success', __('super_admin.settings.imported', ['count' => $imported]));
        } catch (\Exception $e) {
            Log::error('Settings import failed', ['error' => $e->getMessage()]);
            return back()->with('error', __('super_admin.settings.import_failed'));
        }
    }

    /**
     * Validate setting value based on type.
     */
    protected function validateSettingValue(GlobalSetting $setting, $value): bool
    {
        switch ($setting->type) {
            case 'boolean':
                return in_array($value, ['true', 'false', '1', '0', true, false, 1, 0], true);
            case 'integer':
                return is_numeric($value);
            case 'json':
                if (is_string($value)) {
                    json_decode($value);
                    return json_last_error() === JSON_ERROR_NONE;
                }
                return is_array($value);
            default:
                return true;
        }
    }

    /**
     * Format value based on setting type.
     */
    protected function formatValue(GlobalSetting $setting, $value): string
    {
        switch ($setting->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            case 'integer':
                return (string) (int) $value;
            case 'json':
                return is_string($value) ? $value : json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Get default values for settings.
     */
    protected function getDefaultValues(): array
    {
        return [
            'site_name' => 'CMIS Platform',
            'site_tagline' => 'Cognitive Marketing Intelligence Suite',
            'default_locale' => 'ar',
            'default_timezone' => 'Asia/Riyadh',
            'maintenance_mode' => 'false',
            'maintenance_message' => 'We are currently performing maintenance. Please check back soon.',
            'registration_enabled' => 'true',
            'email_verification_required' => 'true',
            'trial_days' => '14',
            'password_min_length' => '8',
            'password_require_uppercase' => 'true',
            'password_require_number' => 'true',
            'session_lifetime' => '120',
            'max_login_attempts' => '5',
            'lockout_duration' => '15',
            'two_factor_required' => 'false',
            'api_rate_limit' => '60',
            'api_burst_limit' => '10',
            'ai_rate_limit' => '30',
            'ai_hourly_limit' => '500',
            'mail_from_name' => 'CMIS Platform',
            'mail_from_address' => 'noreply@cmis.test',
            'support_email' => 'support@cmis.test',
        ];
    }
}
