<?php

namespace App\Models\Automation;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTemplate extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.workflow_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'org_id',
        'created_by',
        'template_name',
        'description',
        'category',
        'tags',
        'trigger_config',
        'workflow_definition',
        'total_steps',
        'usage_count',
        'active_instances',
        'last_used_at',
        'status',
        'is_public',
        'internal_notes',
    ];

    protected $casts = [
        'tags' => 'array',
        'trigger_config' => 'array',
        'workflow_definition' => 'array',
        'total_steps' => 'integer',
        'usage_count' => 'integer',
        'active_instances' => 'integer',
        'last_used_at' => 'datetime',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'template_id', 'template_id');
    }

    // ===== Status Management =====

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function markAsUsed(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    // ===== Instance Management =====

    public function incrementActiveInstances(): void
    {
        $this->increment('active_instances');
    }

    public function decrementActiveInstances(): void
    {
        $this->decrement('active_instances');
    }

    // ===== Query Helpers =====

    public function isPublic(): bool
    {
        return $this->is_public === true;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query, int $minUsage = 5)
    {
        return $query->where('usage_count', '>=', $minUsage)
            ->orderBy('usage_count', 'desc');
    }
}
