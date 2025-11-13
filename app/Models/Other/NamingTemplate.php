<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NamingTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.naming_templates';

    protected $primaryKey = 'naming_id';

    public $timestamps = false;

    protected $fillable = [
        'naming_id',
        'scope',
        'template',
        'provider',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to filter by scope
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }
}
