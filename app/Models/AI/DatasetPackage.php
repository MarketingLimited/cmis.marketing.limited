<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class DatasetPackage extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis.dataset_packages';
    protected $primaryKey = 'pkg_id';
    protected $fillable = [
        'pkg_id',
        'code',
        'version',
        'notes',
        'provider',
    ];

    protected $casts = [
        'total_files' => 'integer',
        'total_size_bytes' => 'integer',
        'schema' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'download_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function files(): HasMany
    {
        return $this->hasMany(DatasetFile::class, 'package_id', 'package_id');
    }

    // Scopes
    public function scopePublic($query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type): Builder
    {
        return $query->where('package_type', $type);
    }

    public function scopePopular($query, $minDownloads = 10): Builder
    {
        return $query->where('download_count', '>=', $minDownloads)
            ->orderByDesc('download_count');
    }

    // Helpers
    public function incrementDownloads()
    {
        $this->increment('download_count');
    }

    public function getSizeFormatted()
    {
        $bytes = $this->total_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
