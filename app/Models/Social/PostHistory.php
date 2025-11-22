<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;

class PostHistory extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.social_post_history';
    public $timestamps = false;

    protected $fillable = [
        'post_id', 'org_id', 'action', 'user_id',
        'old_status', 'new_status', 'changes', 'notes',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    }

    public function post() {
        return $this->belongsTo(SocialPost::class, 'post_id');
}
