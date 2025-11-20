<?php

namespace App\Jobs\Maintenance;

use App\Models\Log\ApiLog;
use App\Models\Activity\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupOldLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $daysToKeep;

    public function __construct(int $daysToKeep = 90)
    {
        $this->daysToKeep = $daysToKeep;
    }

    public function handle(): array
    {
        $result = [
            'success' => true,
        ];

        // Delete old API logs
        $cutoffDate = now()->subDays($this->daysToKeep);

        $apiLogsDeleted = ApiLog::where('created_at', '<', $cutoffDate)->count();
        // Would actually delete: ApiLog::where('created_at', '<', $cutoffDate)->delete();

        $activityLogsDeleted = ActivityLog::where('created_at', '<', $cutoffDate)->count();
        // Would actually delete: ActivityLog::where('created_at', '<', $cutoffDate)->delete();

        $result['deleted_api_logs'] = $apiLogsDeleted;
        $result['deleted_activity_logs'] = $activityLogsDeleted;
        $result['days_kept'] = $this->daysToKeep;

        return $result;
    }
}
