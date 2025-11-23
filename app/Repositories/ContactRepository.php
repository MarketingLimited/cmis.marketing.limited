<?php

namespace App\Repositories;

use App\Models\Contact\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ContactRepository
 *
 * Data access layer for Contact operations
 */
class ContactRepository
{
    /**
     * Find contact by ID
     *
     * @param string $id
     * @param array $relations
     * @return Contact|null
     */
    public function findById(string $id, array $relations = []): ?Contact
    {
        $query = Contact::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Find contact by email
     *
     * @param string $email
     * @param string $orgId
     * @return Contact|null
     */
    public function findByEmail(string $email, string $orgId): ?Contact
    {
        return Contact::where('email', $email)
            ->where('org_id', $orgId)
            ->first();
    }

    /**
     * Create a new contact
     *
     * @param array $data
     * @return Contact
     */
    public function create(array $data): Contact
    {
        return Contact::create($data);
    }

    /**
     * Update a contact
     *
     * @param Contact $contact
     * @param array $data
     * @return Contact
     */
    public function update(Contact $contact, array $data): Contact
    {
        $contact->update($data);
        return $contact->fresh();
    }

    /**
     * Delete a contact (soft delete)
     *
     * @param Contact $contact
     * @return bool
     */
    public function delete(Contact $contact): bool
    {
        return $contact->delete();
    }

    /**
     * Get all contacts with filters and pagination
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
        $query = Contact::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('company', 'ILIKE', "%{$search}%");
            });
        }

        // Apply source filter
        if (!empty($filters['source'])) {
            $query->fromSource($filters['source']);
        }

        // Apply subscription filter
        if (isset($filters['is_subscribed'])) {
            if ($filters['is_subscribed']) {
                $query->subscribed();
            } else {
                $query->where('is_subscribed', false);
            }
        }

        // Apply segment filter
        if (!empty($filters['segment'])) {
            $query->inSegment($filters['segment']);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Find potential duplicates for a contact
     *
     * @param Contact $contact
     * @return Collection
     */
    public function findDuplicates(Contact $contact): Collection
    {
        return Contact::where('contact_id', '!=', $contact->contact_id)
            ->where(function ($query) use ($contact) {
                if ($contact->email) {
                    $query->orWhere('email', $contact->email);
                }
                if ($contact->phone) {
                    $query->orWhere('phone', $contact->phone);
                }
                if ($contact->first_name && $contact->last_name) {
                    $query->orWhere(function ($q) use ($contact) {
                        $q->where('first_name', $contact->first_name)
                          ->where('last_name', $contact->last_name);
                    });
                }
            })
            ->get();
    }

    /**
     * Get contacts by segment
     *
     * @param string $segment
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBySegment(string $segment, int $perPage = 15): LengthAwarePaginator
    {
        return Contact::inSegment($segment)->paginate($perPage);
    }

    /**
     * Get subscribed contacts
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSubscribed(int $perPage = 15): LengthAwarePaginator
    {
        return Contact::subscribed()->paginate($perPage);
    }

    /**
     * Get contacts by source
     *
     * @param string $source
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBySource(string $source, int $perPage = 15): LengthAwarePaginator
    {
        return Contact::fromSource($source)->paginate($perPage);
    }

    /**
     * Count contacts by organization
     *
     * @param string $orgId
     * @return int
     */
    public function countByOrganization(string $orgId): int
    {
        return Contact::where('org_id', $orgId)->count();
    }

    /**
     * Get recently engaged contacts
     *
     * @param int $days
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRecentlyEngaged(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        return Contact::where('last_engaged_at', '>=', now()->subDays($days))
            ->orderBy('last_engaged_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Bulk update contacts
     *
     * @param array $contactIds
     * @param array $data
     * @return int Number of updated contacts
     */
    public function bulkUpdate(array $contactIds, array $data): int
    {
        return Contact::whereIn('contact_id', $contactIds)->update($data);
    }
}
