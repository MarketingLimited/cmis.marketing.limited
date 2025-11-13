<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface VerificationRepositoryInterface
{
    /**
     * Verify data integrity
     */
    public function verifyDataIntegrity(string $entityType): Collection;

    /**
     * Verify RLS policies
     */
    public function verifyRlsPolicies(): Collection;

    /**
     * Check system compliance
     */
    public function checkSystemCompliance(): ?object;
}
