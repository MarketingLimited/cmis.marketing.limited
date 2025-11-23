<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class UserProfile extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.user_profiles';
    protected $primaryKey = 'profile_id';
    protected $fillable = [
        'user_id',
        'bio',
        'avatar_url',
        'cover_photo_url',
        'phone',
        'location',
        'website',
        'social_links',
        'skills',
        'interests',
        'notification_preferences',
        'display_preferences',
        'privacy_settings',
        'metadata',
    ];

    protected $casts = [
        'profile_id' => 'string',
        'user_id' => 'string',
        'social_links' => 'array',
        'skills' => 'array',
        'interests' => 'array',
        'notification_preferences' => 'array',
        'display_preferences' => 'array',
        'privacy_settings' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');

    }
    /**
     * Check if notifications are enabled
     */
    public function isNotificationEnabled(string $type): bool
    {
        return $this->notification_preferences[$type] ?? true;

    }
    /**
     * Get social link
     */
    public function getSocialLink(string $platform): ?string
    {
        return $this->social_links[$platform] ?? null;
}
}
