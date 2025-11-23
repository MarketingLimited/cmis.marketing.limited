<?php

namespace App\Models\Orchestration;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Campaign\Campaign;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CampaignOrchestration extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.campaign_orchestrations';
    protected $primaryKey = 'orchestration_id';

    protected $fillable = [
        'orchestration_id',
        'org_id',
        'template_id',
        'master_campaign_id',
        'created_by',
        'name',
        'description',
        'status',
        'platforms',
        'orchestration_config',
        'total_budget',
        'budget_allocation',
        'sync_strategy',
        'sync_frequency_minutes',
        'scheduled_start_at',
        'scheduled_end_at',
        'activated_at',
        'completed_at',
        'last_sync_at',
        'platform_count',
        'active_platform_count',
    ];

    protected $casts = [
        'platforms' => 'array',
        'orchestration_config' => 'array',
        'budget_allocation' => 'array',
        'total_budget' => 'decimal:2',
        'sync_frequency_minutes' => 'integer',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'platform_count' => 'integer',
        'active_platform_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'template_id', 'template_id');

        }
    public function masterCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'master_campaign_id', 'campaign_id');

        }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');

        }
    public function platformMappings(): HasMany
    {
        return $this->hasMany(OrchestrationPlatform::class, 'orchestration_id', 'orchestration_id');

        }
    public function workflows(): HasMany
    {
        return $this->hasMany(OrchestrationWorkflow::class, 'orchestration_id', 'orchestration_id');

        }
    public function syncLogs(): HasMany
    {
        return $this->hasMany(OrchestrationSyncLog::class, 'orchestration_id', 'orchestration_id');


        }
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

    public function pause(): void
    {
        $this->update(['status' => 'paused']);

    public function resume(): void
    {
        $this->update(['status' => 'active']);

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);

    public function schedule(string $startDate, ?string $endDate = null): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_start_at' => $startDate,
            'scheduled_end_at' => $endDate,
        ]);

    // ===== Sync Management =====

    public function markSynced(): void
    {
        $this->update(['last_sync_at' => now()]);

    public function shouldSync(): bool
    {
        if ($this->sync_strategy !== 'auto') {
            return false;



            }
    public function enableAutoSync(int $frequencyMinutes = 15): void
    {
        $this->update([
            'sync_strategy' => 'auto',
            'sync_frequency_minutes' => $frequencyMinutes,
        ]);

    public function disableAutoSync(): void
    {
        $this->update(['sync_strategy' => 'manual']);

    // ===== Platform Management =====

    public function updatePlatformCounts(): void
    {
        $platformCount = $this->platformMappings()->count();
        $activePlatformCount = $this->platformMappings()->where('status', 'active')->count();

        $this->update([
            'platform_count' => $platformCount,
            'active_platform_count' => $activePlatformCount,
        ]);

    public function getPlatformStatus(string $platform): ?string
    {
        $mapping = $this->platformMappings()->where('platform', $platform)->first();
        return $mapping?->status;

        }
    public function isActiveOnPlatform(string $platform): bool
    {
        return $this->getPlatformStatus($platform) === 'active';


        }
    public function getBudgetForPlatform(string $platform): ?float
    {
        return $this->budget_allocation[$platform] ?? null;

        }
    public function updateBudgetAllocation(array $allocation): void
    {
        $this->update(['budget_allocation' => $allocation]);

    public function getTotalAllocatedBudget(): float
    {
        return array_sum($this->budget_allocation ?? []);

        }
    public function hasUnallocatedBudget(): bool
    {
        if (!$this->total_budget) {
            return false;



            }
    public function getTotalSpend(): float
    {
        return $this->platformMappings()->sum('spend');

        }
    public function getTotalConversions(): int
    {
        return $this->platformMappings()->sum('conversions');

        }
    public function getTotalRevenue(): float
    {
        return $this->platformMappings()->sum('revenue');

        }
    public function getROAS(): float
    {
        $spend = $this->getTotalSpend();
        $revenue = $this->getTotalRevenue();

        return $spend > 0 ? $revenue / $spend : 0;

        }
    public function getBudgetUtilization(): float
    {
        if (!$this->total_budget || $this->total_budget == 0) {
            return 0;



            }
    public function isActive(): bool
    {
        return $this->status === 'active';

        }
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';

        }
    public function isDraft(): bool
    {
        return $this->status === 'draft';

        }
    public function isCompleted(): bool
    {
        return $this->status === 'completed';


        }
    public function scopeActive($query)
    {
        return $query->where('status', 'active');

        }
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');

        }
    public function scopeForPlatform($query, string $platform)
    {
        return $query->whereJsonContains('platforms', $platform);

        }
    public function scopeAutoSync($query)
    {
        return $query->where('sync_strategy', 'auto');
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
