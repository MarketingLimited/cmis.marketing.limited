<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatasetFile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis.dataset_files';
    protected $primaryKey = 'file_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'package_id',
        'file_name',
        'file_path',
        'file_size_bytes',
        'mime_type',
        'checksum',
        'row_count',
        'column_count',
        'file_metadata',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'row_count' => 'integer',
        'column_count' => 'integer',
        'file_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
