<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplateContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.prompt_template_contracts';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'prompt_id',
        'contract_id',
    ];

    protected $casts = [
        'prompt_id' => 'string',
        'contract_id' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the prompt template
     */
    public function promptTemplate()
    {
        return $this->belongsTo(PromptTemplate::class, 'prompt_id', 'prompt_id');
    }

    /**
     * Get the output contract
     */
    public function outputContract()
    {
        return $this->belongsTo(OutputContract::class, 'contract_id', 'contract_id');
    }
}
