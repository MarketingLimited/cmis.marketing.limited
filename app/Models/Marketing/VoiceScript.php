<?php

namespace App\Models\Marketing;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class VoiceScript extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis_marketing.voice_scripts';
    protected $primaryKey = 'script_id';
    protected $fillable = [
        'script_id',
        'scenario_id',
        'task_id',
        'language',
        'voice_tone',
        'narration',
        'script_structure',
        'confidence',
    ];

    protected $casts = ['word_count' => 'integer',
        'estimated_duration_seconds' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'script_structure' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');

        }
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(VideoScenario::class, 'scenario_id', 'scenario_id');

        }
    public function scopeByLanguage($query, $language): Builder
    {
        return $query->where('language', $language);

        }
    public function scopeByStatus($query, $status): Builder
    {
        return $query->where('status', $status);

        }
    public function getDurationFormatted()
    : \Illuminate\Database\Eloquent\Relations\Relation {
        $minutes = floor($this->estimated_duration_seconds / 60);
        $seconds = $this->estimated_duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);

        }
    public function updateWordCount()
    : \Illuminate\Database\Eloquent\Relations\Relation {
        $this->word_count = str_word_count($this->script_text);
        $this->estimated_duration_seconds = ceil($this->word_count / 2.5); // Average speaking rate
        $this->save();
}
