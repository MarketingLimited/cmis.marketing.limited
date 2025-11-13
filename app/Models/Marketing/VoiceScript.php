<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoiceScript extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis_marketing.voice_scripts';
    protected $primaryKey = 'script_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'scenario_id',
        'script_name',
        'script_text',
        'voice_type',
        'tone',
        'pace',
        'language',
        'word_count',
        'estimated_duration_seconds',
        'audio_file_url',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'word_count' => 'integer',
        'estimated_duration_seconds' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    public function scenario()
    {
        return $this->belongsTo(VideoScenario::class, 'scenario_id', 'scenario_id');
    }

    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getDurationFormatted()
    {
        $minutes = floor($this->estimated_duration_seconds / 60);
        $seconds = $this->estimated_duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function updateWordCount()
    {
        $this->word_count = str_word_count($this->script_text);
        $this->estimated_duration_seconds = ceil($this->word_count / 2.5); // Average speaking rate
        $this->save();
    }
}
