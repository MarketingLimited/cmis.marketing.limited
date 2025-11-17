<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Core\Org;
use App\Models\Core\Role;
use App\Models\Core\UserOrg;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = false;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize test logging
        $this->initializeTestLogging();
    }

    /**
     * Initialize transaction context for multi-tenancy testing.
     *
     * @param string $userId
     * @param string $orgId
     * @return void
     */
    protected function initTransactionContext(string $userId, string $orgId): void
    {
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$userId, $orgId]);
    }

    /**
     * Clear transaction context.
     *
     * @return void
     */
    protected function clearTransactionContext(): void
    {
        DB::statement('SELECT cmis.clear_transaction_context()');
    }

    /**
     * Create a test user with organization and role.
     *
     * @param array $userData
     * @param array $orgData
     * @param string|null $roleCode
     * @return array ['user' => User, 'org' => Org, 'role' => Role]
     */
    protected function createUserWithOrg(
        array $userData = [],
        array $orgData = [],
        ?string $roleCode = 'admin'
    ): array {
        // Create organization
        $org = Org::create(array_merge([
            'org_id' => Str::uuid(),
            'name' => 'Test Organization ' . Str::random(8),
            'default_locale' => 'ar-BH',
            'currency' => 'BHD',
        ], $orgData));

        // Create user
        $user = User::create(array_merge([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'test' . Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ], $userData));

        // Create or find role
        $role = Role::firstOrCreate(
            [
                'org_id' => $org->org_id,
                'role_code' => $roleCode,
            ],
            [
                'role_id' => Str::uuid(),
                'role_name' => ucfirst($roleCode),
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // Associate user with org and role
        UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'org_id' => $org->org_id,
            'role_id' => $role->role_id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return [
            'user' => $user->fresh(),
            'org' => $org->fresh(),
            'role' => $role->fresh(),
        ];
    }

    /**
     * Authenticate as a user and set organization context.
     *
     * @param User $user
     * @param Org $org
     * @return $this
     */
    protected function actingAsUserInOrg(User $user, Org $org): static
    {
        $this->actingAs($user, 'sanctum');
        $this->initTransactionContext($user->user_id, $org->org_id);

        return $this;
    }

    /**
     * Initialize test logging to dev_logs.
     *
     * @return void
     */
    protected function initializeTestLogging(): void
    {
        $testName = method_exists($this, 'name') ? $this->name() : (method_exists($this, 'getName') ? $this->getName() : 'unknown');
        $testClass = static::class;

        try {
            DB::table('cmis_dev.dev_logs')->insert([
                'log_id' => Str::uuid(),
                'task_id' => $this->getTestTaskId(),
                'event' => 'test_started',
                'details' => json_encode([
                    'test_class' => $testClass,
                    'test_method' => $testName,
                    'timestamp' => now()->toIso8601String(),
                ]),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if dev_logs table doesn't exist
        }
    }

    /**
     * Log test result to dev_logs.
     *
     * @param string $status
     * @param array $details
     * @return void
     */
    protected function logTestResult(string $status, array $details = []): void
    {
        $testName = method_exists($this, 'name') ? $this->name() : (method_exists($this, 'getName') ? $this->getName() : 'unknown');
        $testClass = static::class;

        try {
            DB::table('cmis_dev.dev_logs')->insert([
                'log_id' => Str::uuid(),
                'task_id' => $this->getTestTaskId(),
                'event' => 'test_completed',
                'details' => json_encode(array_merge([
                    'test_class' => $testClass,
                    'test_method' => $testName,
                    'status' => $status,
                    'timestamp' => now()->toIso8601String(),
                ], $details)),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if dev_logs table doesn't exist
        }
    }

    /**
     * Get or create a task ID for this test run.
     *
     * @return string
     */
    protected function getTestTaskId(): string
    {
        static $taskId = null;

        if ($taskId === null) {
            $taskId = Str::uuid();
        }

        return $taskId;
    }

    /**
     * Assert that a database record exists with RLS context.
     *
     * @param string $table
     * @param array $data
     * @return $this
     */
    protected function assertDatabaseHasWithRLS(string $table, array $data): static
    {
        $this->assertDatabaseHas($table, array_merge($data, [
            'deleted_at' => null,
        ]));

        return $this;
    }

    /**
     * Assert that a record is soft deleted.
     *
     * @param string $table
     * @param array $data
     * @param string|null $connection
     * @param string $deletedAtColumn
     * @return $this
     */
    protected function assertSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at'): static
    {
        $query = $connection ? DB::connection($connection)->table($table) : DB::table($table);

        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }

        $record = $query->first();

        $this->assertNotNull($record, "Record not found in {$table}");
        $this->assertNotNull($record->{$deletedAtColumn}, "Record is not soft deleted");

        return $this;
    }

    /**
     * Customize migration options for test runs to use lightweight schemas.
     */
    protected function migrateFreshUsing()
    {
        $options = [
            '--database' => config('database.default'),
            '--drop-views' => $this->shouldDropViews(),
            '--drop-types' => $this->shouldDropTypes(),
            '--path' => $this->testingMigrationPath(),
        ];

        $seeder = method_exists($this, 'seeder') ? $this->seeder() : null;

        return array_merge(
            $options,
            $seeder ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()]
        );
    }

    /**
     * Resolve the migration path for the current test environment.
     */
    protected function testingMigrationPath(): string
    {
        $configuredPath = env('DB_MIGRATIONS_PATH', 'testing-migrations');
        $fullPath = database_path($configuredPath);

        return file_exists($fullPath)
            ? 'database/' . trim($configuredPath, '/\\')
            : 'database/migrations';
    }

    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        try {
            $this->clearTransactionContext();
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }

        parent::tearDown();
    }
}
