<?php

namespace App\Models\Orchestration;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
class OrchestrationWorkflow extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.orchestration_workflows';
    protected $primaryKey = 'workflow_id';

    protected $fillable = [
        'workflow_id',
        'org_id',
        'orchestration_id',
        'workflow_type',
        'status',
        'steps',
        'current_step',
        'total_steps',
        'started_at',
        'completed_at',
        'duration_seconds',
        'execution_log',
        'error_message',
        'rollback_data',
    ];

    protected $casts = [
        'steps' => 'array',
        'execution_log' => 'array',
        'rollback_data' => 'array',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function orchestration(): BelongsTo
    {
        return $this->belongsTo(CampaignOrchestration::class, 'orchestration_id', 'orchestration_id');


        }
    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
            'current_step' => 0,
        ]);

    public function advanceStep(): void
    {
        $this->increment('current_step');

    public function logStep(string $stepName, string $status, ?array $details = null): void
    {
        $log = $this->execution_log ?? [];
        $log[] = [
            'step' => $this->current_step,
            'name' => $stepName,
            'status' => $status,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ];

        $this->update(['execution_log' => $log]);

    public function complete(): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'current_step' => $this->total_steps,
        ]);

    public function fail(string $errorMessage): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'error_message' => $errorMessage,
        ]);

    public function getProgress(): float
    {
        if ($this->total_steps === 0) {
            return 0;


            }
    public function isRunning(): bool
    {
        return $this->status === 'running';

        }
    public function isCompleted(): bool
    {
        return $this->status === 'completed';

        }
    public function isFailed(): bool
    {
        return $this->status === 'failed';


        }
    public function scopeRunning($query): Builder
    {
        return $query->where('status', 'running');

        }
    public function scopeCompleted($query): Builder
    {
        return $query->where('status', 'completed');

        }
    public function scopeForType($query, string $type): Builder
    {
        return $query->where('workflow_type', $type);
}
}
}
}
}
}
}
}
