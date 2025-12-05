<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Backup\BackupSchedule;
use App\Models\Backup\BackupAuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Backup Schedule Controller
 *
 * Manages automated backup schedules for organizations.
 */
class BackupScheduleController extends Controller
{
    use ApiResponse;

    /**
     * Display schedule management page
     */
    public function index(Request $request, string $org)
    {
        $schedules = BackupSchedule::where('org_id', $org)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        if ($request->wantsJson()) {
            return $this->paginated($schedules);
        }

        return view('apps.backup.schedule.index', compact('schedules', 'org'));
    }

    /**
     * Show schedule creation form
     */
    public function create(Request $request, string $org)
    {
        $timezones = timezone_identifiers_list();
        $frequencies = ['hourly', 'daily', 'weekly', 'monthly'];
        $daysOfWeek = [
            0 => __('common.sunday'),
            1 => __('common.monday'),
            2 => __('common.tuesday'),
            3 => __('common.wednesday'),
            4 => __('common.thursday'),
            5 => __('common.friday'),
            6 => __('common.saturday'),
        ];

        if ($request->wantsJson()) {
            return $this->success([
                'timezones' => $timezones,
                'frequencies' => $frequencies,
                'days_of_week' => $daysOfWeek,
            ]);
        }

        return view('apps.backup.schedule.create', compact('timezones', 'frequencies', 'daysOfWeek', 'org'));
    }

    /**
     * Store a new schedule
     */
    public function store(Request $request, string $org)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'timezone' => 'required|timezone',
            'retention_days' => 'required|integer|min:1|max:365',
            'categories' => 'nullable|array',
            'storage_disk' => 'nullable|string|in:local,google,onedrive,dropbox',
        ]);

        $schedule = BackupSchedule::create([
            'org_id' => $org,
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'time' => $validated['time'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'timezone' => $validated['timezone'],
            'retention_days' => $validated['retention_days'],
            'categories' => $validated['categories'] ?? null,
            'storage_disk' => $validated['storage_disk'] ?? config('backup.storage.default', 'local'),
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun($validated),
            'created_by' => auth()->id(),
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'schedule_created',
            'entity_id' => $schedule->id,
            'entity_type' => 'backup_schedule',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'name' => $schedule->name,
                'frequency' => $schedule->frequency,
            ],
        ]);

        if ($request->wantsJson()) {
            return $this->created($schedule, __('backup.schedule_created'));
        }

        return redirect()
            ->route('backup.schedule.index', ['org' => $org])
            ->with('success', __('backup.schedule_created'));
    }

    /**
     * Show schedule details
     */
    public function show(Request $request, string $org, string $schedule)
    {
        $schedule = BackupSchedule::where('org_id', $org)
            ->findOrFail($schedule);

        if ($request->wantsJson()) {
            return $this->success($schedule);
        }

        return view('apps.backup.schedule.show', compact('schedule', 'org'));
    }

    /**
     * Show schedule edit form
     */
    public function edit(Request $request, string $org, string $schedule)
    {
        $schedule = BackupSchedule::where('org_id', $org)
            ->findOrFail($schedule);

        $timezones = timezone_identifiers_list();
        $frequencies = ['hourly', 'daily', 'weekly', 'monthly'];
        $daysOfWeek = [
            0 => __('common.sunday'),
            1 => __('common.monday'),
            2 => __('common.tuesday'),
            3 => __('common.wednesday'),
            4 => __('common.thursday'),
            5 => __('common.friday'),
            6 => __('common.saturday'),
        ];

        return view('apps.backup.schedule.edit', compact('schedule', 'timezones', 'frequencies', 'daysOfWeek', 'org'));
    }

    /**
     * Update a schedule
     */
    public function update(Request $request, string $org, string $schedule)
    {
        $schedule = BackupSchedule::where('org_id', $org)
            ->findOrFail($schedule);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'timezone' => 'required|timezone',
            'retention_days' => 'required|integer|min:1|max:365',
            'categories' => 'nullable|array',
            'storage_disk' => 'nullable|string|in:local,google,onedrive,dropbox',
            'is_active' => 'boolean',
        ]);

        $schedule->update([
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'time' => $validated['time'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'timezone' => $validated['timezone'],
            'retention_days' => $validated['retention_days'],
            'categories' => $validated['categories'] ?? null,
            'storage_disk' => $validated['storage_disk'] ?? config('backup.storage.default', 'local'),
            'is_active' => $validated['is_active'] ?? $schedule->is_active,
            'next_run_at' => $this->calculateNextRun($validated),
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'schedule_updated',
            'entity_id' => $schedule->id,
            'entity_type' => 'backup_schedule',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'name' => $schedule->name,
                'changes' => $schedule->getChanges(),
            ],
        ]);

        if ($request->wantsJson()) {
            return $this->success($schedule, __('backup.schedule_updated'));
        }

        return redirect()
            ->route('backup.schedule.index', ['org' => $org])
            ->with('success', __('backup.schedule_updated'));
    }

    /**
     * Toggle schedule active status
     */
    public function toggle(Request $request, string $org, string $schedule)
    {
        $schedule = BackupSchedule::where('org_id', $org)
            ->findOrFail($schedule);

        $schedule->update([
            'is_active' => !$schedule->is_active,
            'next_run_at' => !$schedule->is_active ? $this->calculateNextRun([
                'frequency' => $schedule->frequency,
                'time' => $schedule->time,
                'day_of_week' => $schedule->day_of_week,
                'day_of_month' => $schedule->day_of_month,
                'timezone' => $schedule->timezone,
            ]) : null,
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'schedule_updated',
            'entity_id' => $schedule->id,
            'entity_type' => 'backup_schedule',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'name' => $schedule->name,
                'is_active' => $schedule->is_active,
            ],
        ]);

        if ($request->wantsJson()) {
            return $this->success($schedule, $schedule->is_active
                ? __('backup.schedule_activated')
                : __('backup.schedule_deactivated'));
        }

        return back()->with('success', $schedule->is_active
            ? __('backup.schedule_activated')
            : __('backup.schedule_deactivated'));
    }

    /**
     * Delete a schedule
     */
    public function destroy(Request $request, string $org, string $schedule)
    {
        $schedule = BackupSchedule::where('org_id', $org)
            ->findOrFail($schedule);

        // Create audit log before deletion
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'schedule_deleted',
            'entity_id' => $schedule->id,
            'entity_type' => 'backup_schedule',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'name' => $schedule->name,
                'frequency' => $schedule->frequency,
            ],
        ]);

        $schedule->delete();

        if ($request->wantsJson()) {
            return $this->deleted(__('backup.schedule_deleted'));
        }

        return redirect()
            ->route('backup.schedule.index', ['org' => $org])
            ->with('success', __('backup.schedule_deleted'));
    }

    /**
     * Calculate the next run time for a schedule
     */
    protected function calculateNextRun(array $config): Carbon
    {
        $now = Carbon::now($config['timezone']);
        $time = explode(':', $config['time']);
        $hour = (int) $time[0];
        $minute = (int) $time[1];

        switch ($config['frequency']) {
            case 'hourly':
                $next = $now->copy()->minute($minute)->second(0);
                if ($next->lte($now)) {
                    $next->addHour();
                }
                break;

            case 'daily':
                $next = $now->copy()->setTime($hour, $minute, 0);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                $next = $now->copy()->setTime($hour, $minute, 0);
                $targetDay = $config['day_of_week'] ?? 0;
                $daysUntil = ($targetDay - $now->dayOfWeek + 7) % 7;
                if ($daysUntil === 0 && $next->lte($now)) {
                    $daysUntil = 7;
                }
                $next->addDays($daysUntil);
                break;

            case 'monthly':
                $targetDay = min($config['day_of_month'] ?? 1, $now->daysInMonth);
                $next = $now->copy()->day($targetDay)->setTime($hour, $minute, 0);
                if ($next->lte($now)) {
                    $next->addMonth();
                    $next->day(min($targetDay, $next->daysInMonth));
                }
                break;

            default:
                $next = $now->copy()->addDay();
        }

        return $next->setTimezone('UTC');
    }
}
