<?php

namespace App\Models\Session;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;

class SessionContext extends BaseModel
{
    use HasOrganization;
protected $table = 'cmis.session_context';
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'active_org_id',
        'switched_at',
        'provider',
    ];

    protected $casts = [
        'session_id' => 'string',
        'context_value' => 'array',
        'set_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the session
     */
    public function session()
    {
        return $this->belongsTo(UserSession::class, 'session_id', 'session_id');

    /**
     * Scope by context key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('context_key', $key);

    /**
     * Scope by context type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('context_type', $type);

    /**
     * Scope valid contexts (not expired)
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());

    /**
     * Scope expired contexts
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

    /**
     * Check if context is valid
     */
    public function isValid(): bool
    {
        if (!$this->expires_at) {
            return true;

        return $this->expires_at->isFuture();

    /**
     * Check if context has expired
     */
    public function hasExpired(): bool
    {
        if (!$this->expires_at) {
            return false;

        return $this->expires_at->isPast();

    /**
     * Get or set context value
     */
    public static function getContextValue(string $sessionId, string $key, $default = null)
    {
        $context = self::where('session_id', $sessionId)
            ->where('context_key', $key)
            ->valid()
            ->first();

        return $context ? $context->context_value : $default;

    /**
     * Set context value
     */
    public static function setContextValue(string $sessionId, string $key, $value, string $type = 'general', ?\DateTime $expiresAt = null): self
    {
        return self::updateOrCreate(
            [
                'session_id' => $sessionId,
                'context_key' => $key,
            ],
            [
                'context_value' => $value,
                'context_type' => $type,
                'set_at' => now(),
                'expires_at' => $expiresAt,
            ]
}
