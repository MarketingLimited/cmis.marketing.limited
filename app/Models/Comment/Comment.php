<?php

namespace App\Models\Comment;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Comment extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.comments';
    protected $primaryKey = 'comment_id';

    protected $fillable = [
        'comment_id',
        'org_id',
        'user_id',
        'content',
        'commentable_type',
        'commentable_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
