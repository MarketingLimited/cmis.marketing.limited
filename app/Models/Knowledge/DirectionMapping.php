<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class DirectionMapping extends Model
{
    use HasUuids;
    protected $table = 'cmis.direction_mappings';
    protected $primaryKey = 'direction_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'direction_code',
        'direction_label',
        'description',
        'category',
        'prompt_template',
        'parameters',
        'output_format',
        'examples',
        'metadata',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'direction_id' => 'string',
        'parameters' => 'array',
        'output_format' => 'array',
        'examples' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope active directions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Find by direction code
     */
    public static function findByCode(string $code)
    {
        return self::where('direction_code', $code)->first();
    }

    /**
     * Render prompt with parameters
     */
    public function renderPrompt(array $data): string
    {
        $template = $this->prompt_template;

        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
