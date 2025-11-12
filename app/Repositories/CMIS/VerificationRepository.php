<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Verification Functions
 * Encapsulates PostgreSQL functions related to system verification and testing
 */
class VerificationRepository
{
    /**
     * Verify optional improvements
     * Corresponds to: cmis.verify_optional_improvements()
     *
     * @return string Verification report text
     */
    public function verifyOptionalImprovements(): string
    {
        $result = DB::select('SELECT cmis.verify_optional_improvements() as report');

        return $result[0]->report ?? '';
    }

    /**
     * Verify phase 1 fixes
     * Corresponds to: cmis.verify_phase1_fixes()
     *
     * @return Collection Collection of verification results
     */
    public function verifyPhase1Fixes(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.verify_phase1_fixes()');

        return collect($results);
    }

    /**
     * Verify phase 2 permissions
     * Corresponds to: cmis.verify_phase2_permissions()
     *
     * @return Collection Collection of permission verification results
     */
    public function verifyPhase2Permissions(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.verify_phase2_permissions()');

        return collect($results);
    }

    /**
     * Verify RBAC policies
     * Corresponds to: cmis.verify_rbac_policies()
     *
     * @return Collection Collection of RBAC policy verification results
     */
    public function verifyRbacPolicies(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.verify_rbac_policies()');

        return collect($results);
    }

    /**
     * Verify RLS (Row Level Security) fixes
     * Corresponds to: cmis.verify_rls_fixes()
     *
     * @return Collection Collection of RLS verification results
     */
    public function verifyRlsFixes(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.verify_rls_fixes()');

        return collect($results);
    }

    /**
     * Analyze table sizes
     * Corresponds to: cmis.analyze_table_sizes()
     *
     * @return Collection Collection of table names and their sizes
     */
    public function analyzeTableSizes(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.analyze_table_sizes()');

        return collect($results);
    }
}
