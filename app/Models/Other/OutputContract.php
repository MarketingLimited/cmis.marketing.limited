<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OutputContract extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.output_contracts';

    protected $primaryKey = 'contract_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'code',
        'json_schema',
        'notes',
        'provider',
    ];

    protected $casts = [
        'contract_id' => 'string',
        'json_schema' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get prompt templates using this contract
     */
    public function promptTemplates()
    {
        return $this->belongsToMany(
            PromptTemplate::class,
            'cmis.prompt_template_contracts',
            'contract_id',
            'prompt_id'
        );
    }

    /**
     * Scope to find by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
