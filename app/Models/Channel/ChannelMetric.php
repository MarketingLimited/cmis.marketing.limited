<?php

namespace App\Models\Channel;

use App\Models\BaseModel;

class ChannelMetric extends BaseModel
{
    
    protected $table = 'cmis.channel_metrics';
    protected $primaryKey = 'metric_id';
    public $timestamps = false;

    protected $fillable = [
        'channel_id',
        'date',
        'followers',
        'following',
        'posts_count',
        'engagement_rate',
        'average_likes',
        'average_comments',
        'average_shares',
        'reach',
        'impressions',
        'profile_views',
        'website_clicks',
        'custom_metrics',
        'recorded_at',
    ];

    protected $casts = [
        'metric_id' => 'string',
        'channel_id' => 'integer',
        'date' => 'date',
        'followers' => 'integer',
        'following' => 'integer',
        'posts_count' => 'integer',
        'engagement_rate' => 'float',
        'average_likes' => 'float',
        'average_comments' => 'float',
        'average_shares' => 'float',
        'reach' => 'integer',
        'impressions' => 'integer',
        'profile_views' => 'integer',
        'website_clicks' => 'integer',
        'custom_metrics' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the channel
     */
    public function channel()
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);

    /**
     * Scope recent metrics
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));

    /**
     * Get follower growth
     */
    public static function getFollowerGrowth(int $channelId, int $days = 30): array
    {
        $metrics = self::where('channel_id', $channelId)
            ->recent($days)
            ->orderBy('date')
            ->get(['date', 'followers']);

        if ($metrics->count() < 2) {
            return ['growth' => 0, 'percentage' => 0];

        $first = $metrics->first();
        $last = $metrics->last();

        $growth = $last->followers - $first->followers;
        $percentage = $first->followers > 0
            ? ($growth / $first->followers) * 100
            : 0;

        return [
            'growth' => $growth,
            'percentage' => round($percentage, 2),
        ];
}
