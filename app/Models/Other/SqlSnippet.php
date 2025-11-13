<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SqlSnippet extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.sql_snippets';

    protected $primaryKey = 'snippet_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'snippet_id',
        'name',
        'sql',
        'description',
        'provider',
    ];

    protected $casts = [
        'snippet_id' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get prompt templates using this snippet
     */
    public function promptTemplates()
    {
        return $this->belongsToMany(
            PromptTemplate::class,
            'cmis.prompt_template_presql',
            'snippet_id',
            'prompt_id'
        );
    }

    /**
     * Scope to find by name
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }
}
