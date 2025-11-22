<?php

namespace App\Models\Social;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class SocialAccount extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.social_accounts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'account_external_id',
        'username',
        'display_name',
        'profile_picture_url',
        'biography',
        'followers_count',
        'follows_count',
        'media_count',
        'website',
        'category',
        'fetched_at',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'followers_count' => 'integer',
        'follows_count' => 'integer',
        'media_count' => 'integer',
        'fetched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the integration that this account belongs to.
     */
    public function integration()
    {
        return $this->belongsTo(\App\Models\Integration::class, 'integration_id', 'integration_id');

    /**
     * Get all posts for this social account.
     */
    public function posts()
    {
        return $this->hasMany(SocialPost::class, 'integration_id', 'integration_id');
}
