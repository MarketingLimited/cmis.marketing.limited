<?php

namespace App\Models\Other;

use App\Models\Context\FieldDefinition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplateRequiredField extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.prompt_template_required_fields';
    protected $primaryKey = 'prompt_id';

    public $timestamps = false;

    public $incrementing = false;

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
    public function promptTemplate()
    {
        return $this->belongsTo(PromptTemplate::class, 'prompt_id', 'prompt_id');
    }

    /**
     * Get the field definition
     */
    public function fieldDefinition()
    {
        return $this->belongsTo(FieldDefinition::class, 'field_id', 'field_id');
    }
}
