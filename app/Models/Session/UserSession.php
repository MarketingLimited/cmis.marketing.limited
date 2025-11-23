<?php

namespace App\Models\Session;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;

class UserSession extends BaseModel
{
    use HasOrganization;
protected $table = 'cmis.user_sessions';
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'session_token',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'session_id' => 'string',
        'user_id' => 'string',
        'org_id' => 'string',
        'location_data' => 'array',
        'started_at' => 'datetime',
        'last_activity' => 'datetime',
        'ended_at' => 'datetime',
        'session_duration' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    

    /**
     * Get session contexts
     */
    public function contexts()
    {
        return $this->hasMany(SessionContext::class, 'session_id', 'session_id');

    }
    /**
     * Scope active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('ended_at');

    }
    /**
     * Scope by device type
     */
    public function scopeByDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);

    }
    /**
     * Scope by browser
     */
    public function scopeByBrowser($query, string $browser)
    {
        return $query->where('browser', $browser);

    }
    /**
     * Scope recent sessions
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));

    }
    /**
     * Scope long sessions
     */
    public function scopeLongSessions($query, int $minutes = 30)
    {
        return $query->where('session_duration', '>=', $minutes * 60);

    }
    /**
     * Update last activity
     */
    public function updateActivity(): void
    {
        $this->last_activity = now();

        if ($this->started_at) {
            $this->session_duration = $this->started_at->diffInSeconds(now());

        $this->save();

    }
    /**
     * End session
     */
    public function end(): void
    {
        $this->ended_at = now();
        $this->is_active = false;

        if ($this->started_at) {
            $this->session_duration = $this->started_at->diffInSeconds(now());

        $this->save();

    }
    /**
     * Check if session is expired
     */
    public function isExpired(int $maxInactiveMinutes = 30): bool
    {
        if (!$this->is_active) {
            return true;

        if (!$this->last_activity) {
            return false;

        return $this->last_activity->diffInMinutes(now()) > $maxInactiveMinutes;

    }
    /**
     * Get session duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        if (!$this->session_duration) {
            return 0;

        return (int) ceil($this->session_duration / 60);
}
}
}
}
}
}
}
