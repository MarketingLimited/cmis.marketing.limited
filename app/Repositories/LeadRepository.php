<?php

namespace App\Repositories;

use App\Models\Lead\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * LeadRepository
 *
 * Data access layer for Lead operations
 */
class LeadRepository
{
    /**
     * Find lead by ID
     *
     * @param string $id
     * @param array $relations
     * @return Lead|null
     */
    public function findById(string $id, array $relations = []): ?Lead
    {
        $query = Lead::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Find lead by email
     *
     * @param string $email
     * @param string $orgId
     * @return Lead|null
     */
    public function findByEmail(string $email, string $orgId): ?Lead
    {
        return Lead::where('email', $email)
            ->where('org_id', $orgId)
            ->first();
    }

    /**
     * Create a new lead
     *
     * @param array $data
     * @return Lead
     */
    public function create(array $data): Lead
    {
        return Lead::create($data);
    }

    /**
     * Update a lead
     *
     * @param Lead $lead
     * @param array $data
     * @return Lead
     */
    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);
        return $lead->fresh();
    }

    /**
     * Delete a lead (soft delete)
     *
     * @param Lead $lead
     * @return bool
     */
    public function delete(Lead $lead): bool
    {
        return $lead->delete();
    }

    /**
     * Get all leads with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortOrder
     * @return LengthAwarePaginator
     */
    public function getAll(
        array $filters = [],
        int $perPage = 15,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator {
        $query = Lead::with(['campaign', 'contact', 'assignedTo']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('phone', 'ILIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        // Apply source filter
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // Apply campaign filter
        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        // Apply assigned user filter
        if (!empty($filters['assigned_to'])) {
            $query->assignedTo($filters['assigned_to']);
        }

        // Apply min score filter
        if (isset($filters['min_score'])) {
            $query->where('score', '>=', $filters['min_score']);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get leads by status
     *
     * @param string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Lead::withStatus($status)
            ->with(['campaign', 'contact', 'assignedTo'])
            ->paginate($perPage);
    }

    /**
     * Get qualified leads
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getQualified(int $perPage = 15): LengthAwarePaginator
    {
        return Lead::qualified()
            ->with(['campaign', 'contact', 'assignedTo'])
            ->paginate($perPage);
    }

    /**
     * Get converted leads
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getConverted(int $perPage = 15): LengthAwarePaginator
    {
        return Lead::converted()
            ->with(['campaign', 'contact', 'assignedTo'])
            ->paginate($perPage);
    }

    /**
     * Get high-scoring leads
     *
     * @param int $minScore
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getHighScore(int $minScore = 70, int $perPage = 15): LengthAwarePaginator
    {
        return Lead::highScore($minScore)
            ->with(['campaign', 'contact', 'assignedTo'])
            ->paginate($perPage);
    }

    /**
     * Get leads assigned to a user
     *
     * @param string $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAssignedUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Lead::assignedTo($userId)
            ->with(['campaign', 'contact'])
            ->paginate($perPage);
    }

    /**
     * Get leads by campaign
     *
     * @param string $campaignId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCampaign(string $campaignId, int $perPage = 15): LengthAwarePaginator
    {
        return Lead::where('campaign_id', $campaignId)
            ->with(['contact', 'assignedTo'])
            ->paginate($perPage);
    }

    /**
     * Count leads by status
     *
     * @param string $orgId
     * @return array
     */
    public function countByStatus(string $orgId): array
    {
        $counts = Lead::where('org_id', $orgId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'new' => $counts[Lead::STATUS_NEW] ?? 0,
            'contacted' => $counts[Lead::STATUS_CONTACTED] ?? 0,
            'qualified' => $counts[Lead::STATUS_QUALIFIED] ?? 0,
            'converted' => $counts[Lead::STATUS_CONVERTED] ?? 0,
            'lost' => $counts[Lead::STATUS_LOST] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    /**
     * Get recently created leads
     *
     * @param int $days
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRecent(int $days = 7, int $perPage = 15): LengthAwarePaginator
    {
        return Lead::where('created_at', '>=', now()->subDays($days))
            ->with(['campaign', 'contact', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recently contacted leads
     *
     * @param int $days
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRecentlyContacted(int $days = 7, int $perPage = 15): LengthAwarePaginator
    {
        return Lead::whereNotNull('last_contacted_at')
            ->where('last_contacted_at', '>=', now()->subDays($days))
            ->with(['campaign', 'contact', 'assignedTo'])
            ->orderBy('last_contacted_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get leads needing follow-up (not contacted recently)
     *
     * @param int $days
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getNeedingFollowUp(int $days = 7, int $perPage = 15): LengthAwarePaginator
    {
        return Lead::where(function ($query) use ($days) {
                $query->whereNull('last_contacted_at')
                    ->orWhere('last_contacted_at', '<', now()->subDays($days));
            })
            ->whereIn('status', [Lead::STATUS_NEW, Lead::STATUS_CONTACTED])
            ->with(['campaign', 'contact', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Update lead status
     *
     * @param Lead $lead
     * @param string $status
     * @return Lead
     */
    public function updateStatus(Lead $lead, string $status): Lead
    {
        $data = ['status' => $status];

        if ($status === Lead::STATUS_CONVERTED) {
            $data['converted_at'] = now();
        }

        return $this->update($lead, $data);
    }

    /**
     * Update lead score
     *
     * @param Lead $lead
     * @param int $score
     * @return Lead
     */
    public function updateScore(Lead $lead, int $score): Lead
    {
        return $this->update($lead, ['score' => $score]);
    }

    /**
     * Assign lead to user
     *
     * @param Lead $lead
     * @param string $userId
     * @return Lead
     */
    public function assignToUser(Lead $lead, string $userId): Lead
    {
        return $this->update($lead, ['assigned_to' => $userId]);
    }

    /**
     * Mark lead as contacted
     *
     * @param Lead $lead
     * @return Lead
     */
    public function markAsContacted(Lead $lead): Lead
    {
        return $this->update($lead, [
            'last_contacted_at' => now(),
            'status' => Lead::STATUS_CONTACTED,
        ]);
    }

    /**
     * Get average score by source
     *
     * @param string $orgId
     * @return array
     */
    public function getAverageScoreBySource(string $orgId): array
    {
        return Lead::where('org_id', $orgId)
            ->selectRaw('source, AVG(score) as avg_score, COUNT(*) as count')
            ->groupBy('source')
            ->orderBy('avg_score', 'desc')
            ->get()
            ->mapWithKeys(fn($item) => [$item->source => [
                'avg_score' => round($item->avg_score, 2),
                'count' => $item->count,
            ]])
            ->toArray();
    }

    /**
     * Bulk update leads
     *
     * @param array $leadIds
     * @param array $data
     * @return int Number of updated leads
     */
    public function bulkUpdate(array $leadIds, array $data): int
    {
        return Lead::whereIn('lead_id', $leadIds)->update($data);
    }

    /**
     * Find potential duplicate leads
     *
     * @param string $email
     * @param string $orgId
     * @return Collection
     */
    public function findDuplicates(string $email, string $orgId): Collection
    {
        return Lead::where('email', $email)
            ->where('org_id', $orgId)
            ->with(['campaign', 'contact'])
            ->get();
    }
}
