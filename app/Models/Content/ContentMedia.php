<?php

namespace App\Models\Content;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ContentMedia extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.content_media';
    protected $primaryKey = 'media_id';

    protected $fillable = [
        'media_id', 'content_id', 'org_id', 'url', 'media_type', 'size'
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
