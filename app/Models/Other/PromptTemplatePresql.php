<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplatePresql extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.prompt_template_presql';
    protected $primaryKey = 'prompt_id';

    public $timestamps = false;

    protected $fillable = [
        'prompt_id',
        'snippet_id',
        'provider',
    ];

    protected $casts = [
        'prompt_id' => 'string',
        'snippet_id' => 'string',
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
     * Get the SQL snippet
     */
    public function sqlSnippet()
    {
        return $this->belongsTo(SqlSnippet::class, 'snippet_id', 'snippet_id');
}
}
