<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Super Admin Security Dashboard Controller
 *
 * Provides security monitoring, audit trails, and threat detection.
 */
class SuperAdminSecurityController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Security dashboard overview.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '24h');
        $since = $this->getPeriodStart($period);

        // Get security stats
        $stats = $this->getSecurityStats($since);

        // Get recent security events
        $recentEvents = $this->getRecentSecurityEvents(10);

        // Get login activity by hour (last 24h)
        $loginActivity = $this->getLoginActivityByHour();

        // Get top IPs with failed logins
        $suspiciousIps = $this->getSuspiciousIPs($since);

        // Get super admin actions
        $adminActions = DB::table('cmis.super_admin_actions as sa')
            ->join('cmis.users as u', 'sa.admin_user_id', '=', 'u.user_id')
            ->select([
                'sa.*',
                'u.name as admin_name',
                'u.email as admin_email',
            ])
            ->orderByDesc('sa.created_at')
            ->limit(20)
            ->get();

        if ($request->expectsJson()) {
            return $this->success(compact('stats', 'recentEvents', 'loginActivity', 'suspiciousIps', 'adminActions'));
        }

        return view('super-admin.security.index', compact(
            'stats',
            'recentEvents',
            'loginActivity',
            'suspiciousIps',
            'adminActions',
            'period'
        ));
    }

    /**
     * Audit log viewer.
     */
    public function auditLogs(Request $request)
    {
        $query = DB::table('cmis.audit_logs as al')
            ->leftJoin('cmis.users as u', 'al.user_id', '=', 'u.user_id')
            ->leftJoin('cmis.orgs as o', 'al.org_id', '=', 'o.org_id')
            ->select([
                'al.*',
                'u.name as user_name',
                'u.email as user_email',
                'o.name as org_name',
            ]);

        // Apply filters
        if ($request->filled('action')) {
            $query->where('al.action', $request->action);
        }

        if ($request->filled('entity_type')) {
            $query->where('al.entity_type', $request->entity_type);
        }

        if ($request->filled('user_id')) {
            $query->where('al.user_id', $request->user_id);
        }

        if ($request->filled('org_id')) {
            $query->where('al.org_id', $request->org_id);
        }

        if ($request->filled('date_from')) {
            $query->where('al.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('al.created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('al.action', 'ilike', "%{$search}%")
                    ->orWhere('al.entity_type', 'ilike', "%{$search}%")
                    ->orWhere('u.name', 'ilike', "%{$search}%")
                    ->orWhere('u.email', 'ilike', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('al.created_at')
            ->paginate(50)
            ->withQueryString();

        // Get filter options
        $actions = DB::table('cmis.audit_logs')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $entityTypes = DB::table('cmis.audit_logs')
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type');

        return view('super-admin.security.audit-logs', compact('logs', 'actions', 'entityTypes'));
    }

    /**
     * Security events (login attempts, suspicious activity).
     */
    public function events(Request $request)
    {
        $query = DB::table('cmis.security_events as se')
            ->leftJoin('cmis.users as u', 'se.user_id', '=', 'u.user_id')
            ->select([
                'se.*',
                'u.name as user_name',
                'u.email as user_email',
            ]);

        // Apply filters
        if ($request->filled('event_type')) {
            $query->where('se.event_type', $request->event_type);
        }

        if ($request->filled('severity')) {
            $query->where('se.severity', $request->severity);
        }

        if ($request->filled('is_resolved')) {
            $query->where('se.is_resolved', $request->is_resolved === 'true');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('se.ip_address', 'ilike', "%{$search}%")
                    ->orWhere('u.email', 'ilike', "%{$search}%");
            });
        }

        $events = $query->orderByDesc('se.created_at')
            ->paginate(50)
            ->withQueryString();

        // Get stats
        $eventStats = [
            'total' => DB::table('cmis.security_events')->count(),
            'unresolved' => DB::table('cmis.security_events')->where('is_resolved', false)->count(),
            'critical' => DB::table('cmis.security_events')->where('severity', 'critical')->where('is_resolved', false)->count(),
            'today' => DB::table('cmis.security_events')->whereDate('created_at', today())->count(),
        ];

        return view('super-admin.security.events', compact('events', 'eventStats'));
    }

    /**
     * Resolve a security event.
     */
    public function resolveEvent(Request $request, string $eventId)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $updated = DB::table('cmis.security_events')
            ->where('event_id', $eventId)
            ->update([
                'is_resolved' => true,
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
                'resolution_notes' => $request->resolution_notes,
            ]);

        if ($updated) {
            $this->logAction('security_event_resolved', 'security_event', $eventId, 'Security event resolved');

            return $this->success(['resolved' => true], __('super_admin.security.event_resolved'));
        }

        return $this->error(__('super_admin.security.event_not_found'), 404);
    }

    /**
     * IP blacklist management.
     */
    public function ipBlacklist(Request $request)
    {
        $blacklist = DB::table('cmis.ip_blacklist as bl')
            ->join('cmis.users as u', 'bl.blocked_by', '=', 'u.user_id')
            ->select([
                'bl.*',
                'u.name as blocked_by_name',
            ])
            ->orderByDesc('bl.created_at')
            ->paginate(25)
            ->withQueryString();

        // Get stats for blacklisted IPs
        $stats = [
            'total' => DB::table('cmis.ip_blacklist')->count(),
            'permanent' => DB::table('cmis.ip_blacklist')->whereNull('blocked_until')->count(),
            'temporary' => DB::table('cmis.ip_blacklist')->whereNotNull('blocked_until')->where('blocked_until', '>', now())->count(),
        ];

        return view('super-admin.security.ip-blacklist', compact('blacklist', 'stats'));
    }

    /**
     * Add IP to blacklist.
     */
    public function blockIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1', // Hours, null for permanent
        ]);

        // Check if already blocked
        $exists = DB::table('cmis.ip_blacklist')
            ->where('ip_address', $validated['ip_address'])
            ->exists();

        if ($exists) {
            return $this->error(__('super_admin.security.ip_already_blocked'), 400);
        }

        $blockedUntil = null;
        if (!empty($validated['duration'])) {
            $blockedUntil = now()->addHours($validated['duration']);
        }

        DB::table('cmis.ip_blacklist')->insert([
            'blacklist_id' => \Illuminate\Support\Str::uuid(),
            'ip_address' => $validated['ip_address'],
            'reason' => $validated['reason'],
            'blocked_by' => Auth::id(),
            'blocked_until' => $blockedUntil,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logAction('ip_blocked', 'ip_blacklist', $validated['ip_address'], $validated['ip_address'], [
            'reason' => $validated['reason'],
            'duration' => $validated['duration'] ?? 'permanent',
        ]);

        if ($request->expectsJson()) {
            return $this->created(['blocked' => true], __('super_admin.security.ip_blocked_success'));
        }

        return redirect()
            ->route('super-admin.security.ip-blacklist')
            ->with('success', __('super_admin.security.ip_blocked_success'));
    }

    /**
     * Remove IP from blacklist.
     */
    public function unblockIp(string $blacklistId)
    {
        $entry = DB::table('cmis.ip_blacklist')
            ->where('blacklist_id', $blacklistId)
            ->first();

        if (!$entry) {
            return $this->error(__('super_admin.security.ip_not_found'), 404);
        }

        DB::table('cmis.ip_blacklist')
            ->where('blacklist_id', $blacklistId)
            ->delete();

        $this->logAction('ip_unblocked', 'ip_blacklist', $entry->ip_address, $entry->ip_address);

        return redirect()
            ->route('super-admin.security.ip-blacklist')
            ->with('success', __('super_admin.security.ip_unblocked_success'));
    }

    /**
     * Super admin activity log.
     */
    public function adminActions(Request $request)
    {
        $query = DB::table('cmis.super_admin_actions as sa')
            ->join('cmis.users as u', 'sa.admin_user_id', '=', 'u.user_id')
            ->select([
                'sa.*',
                'u.name as admin_name',
                'u.email as admin_email',
            ]);

        // Apply filters
        if ($request->filled('admin_id')) {
            $query->where('sa.admin_user_id', $request->admin_id);
        }

        if ($request->filled('action_type')) {
            $query->where('sa.action_type', $request->action_type);
        }

        if ($request->filled('target_type')) {
            $query->where('sa.target_type', $request->target_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sa.action_type', 'ilike', "%{$search}%")
                    ->orWhere('sa.target_name', 'ilike', "%{$search}%")
                    ->orWhere('u.name', 'ilike', "%{$search}%");
            });
        }

        $actions = $query->orderByDesc('sa.created_at')
            ->paginate(50)
            ->withQueryString();

        // Get filter options
        $admins = DB::table('cmis.users')
            ->where('is_super_admin', true)
            ->select('user_id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $actionTypes = DB::table('cmis.super_admin_actions')
            ->select('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type');

        return view('super-admin.security.admin-actions', compact('actions', 'admins', 'actionTypes'));
    }

    /**
     * Get security statistics.
     */
    private function getSecurityStats(Carbon $since): array
    {
        // Check if security_events table exists
        $hasSecurityEvents = Schema::hasTable('cmis.security_events');

        if ($hasSecurityEvents) {
            return [
                'failed_logins' => DB::table('cmis.security_events')
                    ->where('event_type', 'login_failed')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'successful_logins' => DB::table('cmis.security_events')
                    ->where('event_type', 'login_success')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'suspicious_activities' => DB::table('cmis.security_events')
                    ->where('severity', 'critical')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'blocked_ips' => DB::table('cmis.ip_blacklist')->count(),
                'unresolved_events' => DB::table('cmis.security_events')
                    ->where('is_resolved', false)
                    ->count(),
                'admin_actions' => DB::table('cmis.super_admin_actions')
                    ->where('created_at', '>=', $since)
                    ->count(),
            ];
        }

        // Fallback stats when security_events table doesn't exist
        return [
            'failed_logins' => 0,
            'successful_logins' => 0,
            'suspicious_activities' => 0,
            'blocked_ips' => 0,
            'unresolved_events' => 0,
            'admin_actions' => DB::table('cmis.super_admin_actions')
                ->where('created_at', '>=', $since)
                ->count(),
        ];
    }

    /**
     * Get recent security events.
     */
    private function getRecentSecurityEvents(int $limit): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('cmis.security_events')) {
            return collect([]);
        }

        return DB::table('cmis.security_events as se')
            ->leftJoin('cmis.users as u', 'se.user_id', '=', 'u.user_id')
            ->select([
                'se.*',
                'u.name as user_name',
                'u.email as user_email',
            ])
            ->orderByDesc('se.created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get login activity by hour for the last 24 hours.
     */
    private function getLoginActivityByHour(): array
    {
        if (!Schema::hasTable('cmis.security_events')) {
            // Return empty data for chart
            return [];
        }

        $activity = DB::table('cmis.security_events')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereIn('event_type', ['login_success', 'login_failed'])
            ->select([
                DB::raw("DATE_TRUNC('hour', created_at) as hour"),
                DB::raw("SUM(CASE WHEN event_type = 'login_success' THEN 1 ELSE 0 END) as success"),
                DB::raw("SUM(CASE WHEN event_type = 'login_failed' THEN 1 ELSE 0 END) as failed"),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('hour', created_at)"))
            ->orderBy('hour')
            ->get();

        return $activity->map(function ($item) {
            return [
                'hour' => Carbon::parse($item->hour)->format('H:i'),
                'success' => (int) $item->success,
                'failed' => (int) $item->failed,
            ];
        })->toArray();
    }

    /**
     * Get IPs with most failed login attempts.
     */
    private function getSuspiciousIPs(Carbon $since): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('cmis.security_events')) {
            return collect([]);
        }

        return DB::table('cmis.security_events')
            ->where('event_type', 'login_failed')
            ->where('created_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->select([
                'ip_address',
                DB::raw('COUNT(*) as failed_attempts'),
                DB::raw('MAX(created_at) as last_attempt'),
            ])
            ->groupBy('ip_address')
            ->having(DB::raw('COUNT(*)'), '>=', 3)
            ->orderByDesc('failed_attempts')
            ->limit(10)
            ->get();
    }

    /**
     * Convert period string to Carbon date.
     */
    private function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };
    }
}
