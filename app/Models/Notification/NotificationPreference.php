<?php

namespace App\Models\Notification;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class NotificationPreference extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.notification_preferences';
    protected $primaryKey = 'preference_id';

    protected $fillable = [
        'preference_id',
        'user_id',
        'org_id',
        'channel',
        'event_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
