<?php

namespace App\Models\Social;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ScheduledSocialPost extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.scheduled_social_posts_v2';
    protected $primaryKey = 'scheduled_post_id';

    protected $fillable = [
        'scheduled_post_id', 'social_post_id', 'org_id', 'scheduled_at', 'status'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
