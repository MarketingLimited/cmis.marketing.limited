<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class PromptTemplate extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.prompt_templates';

    protected $primaryKey = 'prompt_id';

    public $timestamps = false;

    protected $fillable = [
        'prompt_id',
        'module_id',
        'name',
        'task',
        'instructions',
        'version',
        'provider',
    ];

    protected $casts = [
        'prompt_id' => 'string',
        'module_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the module
     */
    public function module(): BelongsTo
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

    }
    /**
     * Scope to get templates for a specific module
     */
    public function scopeForModule($query, int $moduleId): Builder
    {
        return $query->where('module_id', $moduleId);
}
}
