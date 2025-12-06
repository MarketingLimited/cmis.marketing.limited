<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnnouncementDismissal Model
 *
 * Tracks when users dismiss announcements.
 */
class AnnouncementDismissal extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.announcement_dismissals';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'dismissal_id';

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
        'dismissed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'dismissed_at' => 'datetime',
    ];

    /**
     * Get the announcement that was dismissed.
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id', 'announcement_id');
    }

    /**
     * Get the user who dismissed the announcement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
