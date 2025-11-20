<?php

namespace App\Models\Social;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'cmis.social_posts';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'post_external_id',
        'caption',
        'media_url',
        'permalink',
        'media_type',
        'posted_at',
        'metrics',
        'fetched_at',
        'video_url',
        'thumbnail_url',
        'children_media',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'metrics' => 'array',
        'children_media' => 'array',
        'posted_at' => 'datetime',
        'fetched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this post.
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the integration (social account) that this post belongs to.
     */
    public function integration()
    {
        return $this->belongsTo(\App\Models\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get the social account for this post.
     */
    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class, 'integration_id', 'integration_id');
    }
}
