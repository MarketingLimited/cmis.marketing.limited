<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class UserActivity extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.user_activities';
    protected $primaryKey = 'activity_id';
            const UPDATED_AT = null; // user_activities table doesn't have updated_at

    protected $fillable = [
        'user_id',
        'org_id',
        'session_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'ip_address',
        'provider',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the activity
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');

    /**
     * Get the organization associated with the activity
     */
    public function organization()
    {
        return $this->belongsTo(Core\Org::class, 'org_id', 'org_id');

    /**
     * Scope to get activities for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);

    /**
     * Scope to get activities for a specific org
     */
    public function scopeForOrg(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);

    /**
     * Get formatted activity description
     */
    public function getDescriptionAttribute()
    {
        $action = $this->action;
        $entityType = $this->entity_type;

        $descriptions = [
            'login' => 'قام بتسجيل الدخول إلى النظام',
            'logout' => 'قام بتسجيل الخروج من النظام',
            'create' => "قام بإنشاء {$entityType} جديد",
            'update' => "قام بتحديث {$entityType}",
            'delete' => "قام بحذف {$entityType}",
            'view' => "قام بعرض {$entityType}",
            'export' => "قام بتصدير بيانات {$entityType}",
        ];

        return $descriptions[$action] ?? $action;

    /**
     * Get activity type for icons
     */
    public function getTypeAttribute()
    {
        $action = $this->action;

        $types = [
            'login' => 'login',
            'logout' => 'logout',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            'view' => 'access',
            'export' => 'access',
        ];

        return $types[$action] ?? 'access';

    /**
     * Create activity log entry
     */
    public static function log(
        string $userId,
        string $orgId,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $details = null
    ) {
        return static::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
}
