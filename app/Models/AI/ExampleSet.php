<?php

namespace App\Models\AI;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class ExampleSet extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'lab.example_sets';
    protected $primaryKey = 'example_id';
    protected $fillable = [
        'example_id',
        'org_id',
        'title',
        'kind',
        'channel_id',
        'framework',
        'tone',
        'locale',
        'quality_score',
        'anchor',
        'tags',
        'body',
        'campaign_id',
    ];

    protected $casts = ['input_data' => 'array',
        'output_data' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'accuracy_score' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'body' => 'array',
    ];

    // Scopes
    public function scopeByCategory($query, $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopePassed($query): Builder
    {
        return $query->where('test_status', 'passed');
    }

    public function scopeFailed($query): Builder
    {
        return $query->where('test_status', 'failed');
    }

    public function scopeHighAccuracy($query, $threshold = 0.8): Builder
    {
        return $query->where('accuracy_score', '>=', $threshold);
    }

    // Helpers
    public function markAsPassed($actualOutput, $accuracyScore)
    {
        $this->update([
            'test_status' => 'passed',
            'actual_output' => $actualOutput,
            'accuracy_score' => $accuracyScore,
        ]);
    }

    public function markAsFailed($actualOutput, $accuracyScore = 0)
    {
        $this->update([
            'test_status' => 'failed',
            'actual_output' => $actualOutput,
            'accuracy_score' => $accuracyScore,
        ]);
    }

    public function isPassed()
    {
        return $this->test_status === 'passed';
    }
}
