<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatasetFile extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis.dataset_files';
    protected $primaryKey = 'file_id';
    protected $fillable = [
        'file_id',
        'pkg_id',
        'filename',
        'checksum',
        'meta',
        'provider',
    ];

    protected $casts = ['file_size_bytes' => 'integer',
        'row_count' => 'integer',
        'column_count' => 'integer',
        'file_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'meta' => 'array',
    ];

    // Relationships
    public function package()
    {
        return $this->belongsTo(DatasetPackage::class, 'package_id', 'package_id');
    }

    // Helpers
    public function getSizeFormatted()
    {
        $bytes = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function verifyChecksum()
    {
        if (!file_exists(storage_path($this->file_path))) {
            return false;
        }

        $actualChecksum = hash_file('sha256', storage_path($this->file_path));
        return $actualChecksum === $this->checksum;
    }
}
