<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.prompt_templates';

    protected $primaryKey = 'prompt_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'name',
        'task',
        'instructions',
        'version',
    ];

    protected $casts = [
        'prompt_id' => 'string',
        'module_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the module
     */
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id', 'module_id');
    }

    /**
     * Get output contracts
     */
    public function outputContracts()
    {
        return $this->belongsToMany(
            OutputContract::class,
            'cmis.prompt_template_contracts',
            'prompt_id',
            'contract_id'
        );
    }

    /**
     * Get SQL snippets
     */
    public function sqlSnippets()
    {
        return $this->belongsToMany(
            SqlSnippet::class,
            'cmis.prompt_template_presql',
            'prompt_id',
            'snippet_id'
        );
    }

    /**
     * Scope to get templates for a specific module
     */
    public function scopeForModule($query, int $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }
}
