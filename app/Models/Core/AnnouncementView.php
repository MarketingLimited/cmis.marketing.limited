<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnnouncementView Model
 *
 * Tracks when users view announcements for analytics.
 */
class AnnouncementView extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.announcement_views';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'view_id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'announcement_id',
        'user_id',
        'org_id',
        'viewed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the announcement that was viewed.
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id', 'announcement_id');
    }

    /**
     * Get the user who viewed the announcement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the organization context for the view.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Organization::class, 'org_id', 'org_id');
    }
}
