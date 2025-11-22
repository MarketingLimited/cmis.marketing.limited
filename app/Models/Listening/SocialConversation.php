<?php

namespace App\Models\Listening;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialConversation extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.social_conversations';
    protected $primaryKey = 'conversation_id';

    protected $fillable = [
        'org_id',
        'root_mention_id',
        'platform',
        'conversation_type',
        'status',
        'assigned_to',
        'priority',
        'participants',
        'message_count',
        'unread_count',
        'overall_sentiment',
        'topics',
        'requires_escalation',
        'first_response_at',
        'last_response_at',
        'response_time_minutes',
        'resolution_time_minutes',
        'internal_notes',
        'tags',
        'last_activity_at',
    ];

    protected $casts = [
        'participants' => 'array',
        'topics' => 'array',
        'requires_escalation' => 'boolean',
        'tags' => 'array',
        'first_response_at' => 'datetime',
        'last_response_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    public function rootMention(): BelongsTo
    {
        return $this->belongsTo(SocialMention::class, 'root_mention_id', 'mention_id');

    /**
     * Status Management
     */

    public function open(): void
    {
        $this->update(['status' => 'open']);

    public function startProgress(): void
    {
        $this->update(['status' => 'in_progress']);

    public function resolve(): void
    {
        $endTime = now();
        $resolutionTime = $this->created_at->diffInMinutes($endTime);

        $this->update([
            'status' => 'resolved',
            'resolution_time_minutes' => $resolutionTime,
        ]);

    public function close(): void
    {
        $this->update(['status' => 'closed']);

    public function isOpen(): bool
    {
        return $this->status === 'open';

    public function isResolved(): bool
    {
        return $this->status === 'resolved';

    /**
     * Assignment
     */

    public function assignTo(string $userId): void
    {
        $this->update(['assigned_to' => $userId]);

    public function unassign(): void
    {
        $this->update(['assigned_to' => null]);

    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;

    /**
     * Priority Management
     */

    public function setPriority(string $priority): void
    {
        $this->update(['priority' => $priority]);

    public function markAsUrgent(): void
    {
        $this->update(['priority' => 'urgent']);

    public function markAsHigh(): void
    {
        $this->update(['priority' => 'high']);

    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';

    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['urgent', 'high']);

    /**
     * Message Tracking
     */

    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
        $this->update(['last_activity_at' => now()]);

    public function incrementUnreadCount(): void
    {
        $this->increment('unread_count');

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);

    public function hasUnreadMessages(): bool
    {
        return $this->unread_count > 0;

    /**
     * Response Tracking
     */

    public function recordResponse(): void
    {
        $now = now();

        $data = [
            'last_response_at' => $now,
        ];

        if (!$this->first_response_at) {
            $data['first_response_at'] = $now;
            $data['response_time_minutes'] = $this->created_at->diffInMinutes($now);

        $this->update($data);

    public function hasResponded(): bool
    {
        return $this->first_response_at !== null;

    public function getAverageResponseTime(): ?int
    {
        if (!$this->first_response_at || $this->message_count <= 1) {
            return null;

        $totalTime = $this->first_response_at->diffInMinutes($this->last_response_at);
        return (int) ($totalTime / ($this->message_count - 1));

    /**
     * Participant Management
     */

    public function addParticipant(string $username): void
    {
        $participants = $this->participants;
        if (!in_array($username, $participants)) {
            $participants[] = $username;
            $this->update(['participants' => $participants]);

    public function getParticipantCount(): int
    {
        return count($this->participants);

    public function getParticipantsString(): string
    {
        return implode(', ', $this->participants);

    /**
     * Escalation
     */

    public function escalate(): void
    {
        $this->update([
            'requires_escalation' => true,
            'priority' => 'urgent',
        ]);

    public function resolveEscalation(): void
    {
        $this->update(['requires_escalation' => false]);

    /**
     * Sentiment & Topics
     */

    public function updateSentiment(string $sentiment): void
    {
        $this->update(['overall_sentiment' => $sentiment]);

    public function addTopic(string $topic): void
    {
        $topics = $this->topics;
        if (!in_array($topic, $topics)) {
            $topics[] = $topic;
            $this->update(['topics' => $topics]);

    /**
     * Tags
     */

    public function addTag(string $tag): void
    {
        $tags = $this->tags;
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);

    public function removeTag(string $tag): void
    {
        $tags = array_filter($this->tags, fn($t) => $t !== $tag);
        $this->update(['tags' => array_values($tags)]);

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags);

    /**
     * Notes
     */

    public function addNote(string $note): void
    {
        $currentNotes = $this->internal_notes ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] {$note}";

        $this->update([
            'internal_notes' => $currentNotes ? "{$currentNotes}\n{$newNote}" : $newNote,
        ]);

    /**
     * Activity Tracking
     */

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);

    public function isStale(int $hoursThreshold = 48): bool
    {
        return $this->last_activity_at->lt(now()->subHours($hoursThreshold));

    /**
     * Scopes
     */

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');

    public function scopeAssignedTo($query, string $userId)
    {
        return $query->where('assigned_to', $userId);

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['urgent', 'high']);

    public function scopeWithUnread($query)
    {
        return $query->where('unread_count', '>', 0);

    public function scopeRequiringEscalation($query)
    {
        return $query->where('requires_escalation', true);

    public function scopeStale($query, int $hoursThreshold = 48)
    {
        return $query->where('last_activity_at', '<', now()->subHours($hoursThreshold));

    public function scopeOnPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);

    public function scopeRecentActivity($query)
    {
        return $query->orderBy('last_activity_at', 'desc');
}
