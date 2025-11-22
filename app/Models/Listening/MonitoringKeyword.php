<?php

namespace App\Models\Listening;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringKeyword extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.monitoring_keywords';
    protected $primaryKey = 'keyword_id';

    protected $fillable = [
        'org_id',
        'created_by',
        'keyword_type',
        'keyword',
        'variations',
        'case_sensitive',
        'platforms',
        'status',
        'enable_alerts',
        'alert_threshold',
        'alert_conditions',
        'language_filters',
        'location_filters',
        'exclude_keywords',
        'mention_count',
        'last_mention_at',
    ];

    protected $casts = [
        'variations' => 'array',
        'case_sensitive' => 'boolean',
        'platforms' => 'array',
        'enable_alerts' => 'boolean',
        'alert_conditions' => 'array',
        'language_filters' => 'array',
        'location_filters' => 'array',
        'exclude_keywords' => 'array',
        'last_mention_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    public function mentions(): HasMany
    {
        return $this->hasMany(SocialMention::class, 'keyword_id', 'keyword_id');

    public function alerts(): HasMany
    {
        return $this->hasMany(MonitoringAlert::class, 'keyword_id', 'keyword_id');

    /**
     * Status Management
     */

    public function activate(): void
    {
        $this->update(['status' => 'active']);

    public function pause(): void
    {
        $this->update(['status' => 'paused']);

    public function archive(): void
    {
        $this->update(['status' => 'archived']);

    public function isActive(): bool
    {
        return $this->status === 'active';

    /**
     * Mention Tracking
     */

    public function incrementMentionCount(): void
    {
        $this->increment('mention_count');
        $this->update(['last_mention_at' => now()]);

    public function resetMentionCount(): void
    {
        $this->update(['mention_count' => 0]);

    /**
     * Platform Management
     */

    public function enablePlatform(string $platform): void
    {
        $platforms = $this->platforms;
        if (!in_array($platform, $platforms)) {
            $platforms[] = $platform;
            $this->update(['platforms' => $platforms]);

    public function disablePlatform(string $platform): void
    {
        $platforms = array_filter($this->platforms, fn($p) => $p !== $platform);
        $this->update(['platforms' => array_values($platforms)]);

    public function isMonitoringPlatform(string $platform): bool
    {
        return in_array($platform, $this->platforms);

    /**
     * Alert Management
     */

    public function enableAlerts(string $threshold = 'medium'): void
    {
        $this->update([
            'enable_alerts' => true,
            'alert_threshold' => $threshold,
        ]);

    public function disableAlerts(): void
    {
        $this->update(['enable_alerts' => false]);

    public function shouldTriggerAlert(array $mentionData): bool
    {
        if (!$this->enable_alerts) {
            return false;

        $conditions = $this->alert_conditions;

        // Check sentiment condition
        if (isset($conditions['sentiment']) && isset($mentionData['sentiment'])) {
            if (!in_array($mentionData['sentiment'], $conditions['sentiment'])) {
                return false;

        // Check volume threshold
        if (isset($conditions['volume_threshold'])) {
            $recentMentions = $this->mentions()
                ->where('published_at', '>=', now()->subHours($conditions['time_window'] ?? 24))
                ->count();

            if ($recentMentions < $conditions['volume_threshold']) {
                return false;

        return true;

    /**
     * Keyword Matching
     */

    public function matches(string $text): bool
    {
        $keyword = $this->case_sensitive ? $this->keyword : strtolower($this->keyword);
        $searchText = $this->case_sensitive ? $text : strtolower($text);

        // Check main keyword
        if (str_contains($searchText, $keyword)) {
            return true;

        // Check variations
        foreach ($this->variations as $variation) {
            $var = $this->case_sensitive ? $variation : strtolower($variation);
            if (str_contains($searchText, $var)) {
                return true;

        return false;

    public function matchesWithExclusions(string $text): bool
    {
        // First check if it matches
        if (!$this->matches($text)) {
            return false;

        // Check exclusions
        $searchText = $this->case_sensitive ? $text : strtolower($text);
        foreach ($this->exclude_keywords as $exclude) {
            $excl = $this->case_sensitive ? $exclude : strtolower($exclude);
            if (str_contains($searchText, $excl)) {
                return false;

        return true;

    /**
     * Scopes
     */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');

    public function scopeForPlatform($query, string $platform)
    {
        return $query->whereJsonContains('platforms', $platform);

    public function scopeWithAlerts($query)
    {
        return $query->where('enable_alerts', true);

    public function scopeOfType($query, string $type)
    {
        return $query->where('keyword_type', $type);
}
