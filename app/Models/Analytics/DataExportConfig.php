<?php

namespace App\Models\Analytics;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataExportConfig extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.data_export_configs';
    protected $primaryKey = 'config_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id', 'created_by', 'name', 'description', 'export_type',
        'format', 'delivery_method', 'data_config', 'delivery_config',
        'schedule', 'is_active', 'last_export_at', 'export_count'
    ];

    protected $casts = [
        'data_config' => 'array',
        'delivery_config' => 'array',
        'schedule' => 'array',
        'is_active' => 'boolean',
        'last_export_at' => 'datetime',
        'export_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DataExportLog::class, 'config_id', 'config_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('schedule');
    }

    public function markExported(): void
    {
        $this->update([
            'last_export_at' => now(),
            'export_count' => $this->export_count + 1
        ]);
    }
}
