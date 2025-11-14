<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Core\Org;

/**
 * Trait for testing Row Level Security (RLS) policies.
 */
trait InteractsWithRLS
{
    /**
     * Assert that RLS prevents access to another organization's data.
     *
     * @param User $user
     * @param Org $userOrg
     * @param string $table
     * @param array $otherOrgData
     * @return void
     */
    protected function assertRLSPreventsAccess(
        User $user,
        Org $userOrg,
        string $table,
        array $otherOrgData
    ): void {
        $this->actingAsUserInOrg($user, $userOrg);

        $query = DB::table($table);
        foreach ($otherOrgData as $key => $value) {
            $query->where($key, $value);
        }

        $result = $query->first();

        $this->assertNull(
            $result,
            "RLS failed: User in org {$userOrg->org_id} can access data from another organization in table {$table}"
        );
    }

    /**
     * Assert that RLS allows access to own organization's data.
     *
     * @param User $user
     * @param Org $userOrg
     * @param string $table
     * @param array $ownOrgData
     * @return void
     */
    protected function assertRLSAllowsAccess(
        User $user,
        Org $userOrg,
        string $table,
        array $ownOrgData
    ): void {
        $this->actingAsUserInOrg($user, $userOrg);

        $query = DB::table($table)->where('deleted_at', null);
        foreach ($ownOrgData as $key => $value) {
            $query->where($key, $value);
        }

        $result = $query->first();

        $this->assertNotNull(
            $result,
            "RLS failed: User in org {$userOrg->org_id} cannot access their own organization's data in table {$table}"
        );
    }

    /**
     * Test multi-tenant data isolation for a given model.
     *
     * @param string $modelClass
     * @param array $createData
     * @return void
     */
    protected function testMultiTenantIsolation(string $modelClass, array $createData): void
    {
        // Create two separate organizations
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        // Create record in org1
        $this->actingAsUserInOrg($setup1['user'], $setup1['org']);

        $model = new $modelClass;
        $tableName = $model->getTable();

        $record = $modelClass::create(array_merge($createData, [
            'org_id' => $setup1['org']->org_id,
        ]));

        // Verify org1 user can see their record
        $this->assertRLSAllowsAccess(
            $setup1['user'],
            $setup1['org'],
            $tableName,
            ['org_id' => $setup1['org']->org_id]
        );

        // Verify org2 user cannot see org1's record
        $this->assertRLSPreventsAccess(
            $setup2['user'],
            $setup2['org'],
            $tableName,
            [$model->getKeyName() => $record->getKey()]
        );
    }
}
