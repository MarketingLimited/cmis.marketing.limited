<?php

namespace App\Models\Other;

use App\Models\Context\FieldDefinition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplateRequiredField extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.prompt_template_required_fields';
    protected $primaryKey = 'prompt_id';

    public $timestamps = false;

    protected $fillable = [
        'prompt_id',
        'field_id',
        'provider',
    ];

    protected $casts = [
        'prompt_id' => 'string',
        'field_id' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the prompt template
     */
    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class, 'prompt_id', 'prompt_id');

    }
    /**
     * Get the field definition
     */
    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(FieldDefinition::class, 'field_id', 'field_id');
}
}
