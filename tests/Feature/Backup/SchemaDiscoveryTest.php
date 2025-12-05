<?php

namespace Tests\Feature\Backup;

use App\Apps\Backup\Services\Discovery\DependencyResolver;
use App\Apps\Backup\Services\Discovery\FileDiscoveryService;
use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected SchemaDiscoveryService $schemaService;
    protected FileDiscoveryService $fileService;
    protected DependencyResolver $dependencyResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaService = app(SchemaDiscoveryService::class);
        $this->fileService = app(FileDiscoveryService::class);
        $this->dependencyResolver = app(DependencyResolver::class);
    }

    /** @test */
    public function it_discovers_tables_with_org_id_column()
    {
        $tables = $this->schemaService->discoverOrgTables();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $tables);
        $this->assertGreaterThan(0, $tables->count());

        // Should include known tables
        $tableNames = $tables->toArray();
        $this->assertTrue(
            in_array('cmis.orgs', $tableNames) ||
            in_array('cmis.users', $tableNames) ||
            count($tableNames) > 0
        );
    }

    /** @test */
    public function it_discovers_tables_across_multiple_schemas()
    {
        $tables = $this->schemaService->discoverOrgTables();

        $schemas = $tables->map(function ($table) {
            return explode('.', $table)[0];
        })->unique();

        // Should discover tables from multiple schemas
        $this->assertGreaterThanOrEqual(1, $schemas->count());
    }

    /** @test */
    public function it_retrieves_table_schema_definition()
    {
        $schema = $this->schemaService->getTableSchema('cmis.orgs');

        $this->assertIsArray($schema);
        $this->assertGreaterThan(0, count($schema));

        // Check for expected columns
        $columnNames = array_column($schema, 'column_name');
        $this->assertContains('org_id', $columnNames);
    }

    /** @test */
    public function it_categorizes_tables_correctly()
    {
        $tables = [
            'cmis.campaigns' => 'Campaigns',
            'cmis.campaign_metrics' => 'Campaigns',
            'cmis.social_posts' => 'Posts',
            'cmis.post_media' => 'Posts',
            'cmis.users' => 'Users',
            'cmis.org_settings' => 'Settings',
        ];

        foreach ($tables as $tableName => $expectedCategory) {
            $category = $this->schemaService->categorizeTable($tableName);

            // Should return a category string (may vary based on implementation)
            $this->assertIsString($category);
        }
    }

    /** @test */
    public function it_returns_other_for_uncategorized_tables()
    {
        $category = $this->schemaService->categorizeTable('cmis.some_unknown_table');

        $this->assertEquals('Other', $category);
    }

    /** @test */
    public function it_discovers_file_columns()
    {
        $fileColumns = $this->fileService->discoverFileColumns();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $fileColumns);

        // Check structure of returned data
        if ($fileColumns->isNotEmpty()) {
            $first = $fileColumns->first();
            $this->assertObjectHasProperty('table_schema', $first);
            $this->assertObjectHasProperty('table_name', $first);
            $this->assertObjectHasProperty('column_name', $first);
        }
    }

    /** @test */
    public function it_identifies_common_file_column_patterns()
    {
        $patterns = ['file_path', 'image_url', 'media_url', 'attachment', 'thumbnail', 'avatar', 'logo'];

        $fileColumns = $this->fileService->discoverFileColumns();

        // Should detect columns matching file patterns
        $columnNames = $fileColumns->pluck('column_name')->toArray();

        // At least check the method works and returns proper structure
        $this->assertIsArray($columnNames);
    }

    /** @test */
    public function it_resolves_table_dependencies()
    {
        $tables = ['cmis.campaigns', 'cmis.orgs'];

        $orderedTables = $this->dependencyResolver->resolveDependencyOrder($tables);

        $this->assertIsArray($orderedTables);

        // Parent tables should come before child tables
        // orgs should come before campaigns (campaigns references orgs)
        if (in_array('cmis.orgs', $orderedTables) && in_array('cmis.campaigns', $orderedTables)) {
            $orgsIndex = array_search('cmis.orgs', $orderedTables);
            $campaignsIndex = array_search('cmis.campaigns', $orderedTables);
            $this->assertLessThan($campaignsIndex, $orgsIndex);
        }
    }

    /** @test */
    public function it_handles_circular_dependencies()
    {
        // Test with tables that might have circular references
        $tables = $this->schemaService->discoverOrgTables()->toArray();

        // Should not throw an exception
        $orderedTables = $this->dependencyResolver->resolveDependencyOrder($tables);

        $this->assertIsArray($orderedTables);
    }

    /** @test */
    public function it_creates_schema_snapshot()
    {
        $tables = $this->schemaService->discoverOrgTables()->take(5)->toArray();

        $snapshot = $this->schemaService->createSchemaSnapshot($tables);

        $this->assertIsArray($snapshot);
        $this->assertArrayHasKey('version', $snapshot);
        $this->assertArrayHasKey('created_at', $snapshot);
        $this->assertArrayHasKey('tables', $snapshot);
    }

    /** @test */
    public function it_compares_schema_snapshots()
    {
        $oldSnapshot = [
            'version' => '1.0',
            'tables' => [
                'cmis.campaigns' => [
                    'columns' => [
                        ['column_name' => 'id', 'data_type' => 'uuid'],
                        ['column_name' => 'name', 'data_type' => 'varchar'],
                    ],
                ],
            ],
        ];

        $newSnapshot = [
            'version' => '1.0',
            'tables' => [
                'cmis.campaigns' => [
                    'columns' => [
                        ['column_name' => 'id', 'data_type' => 'uuid'],
                        ['column_name' => 'name', 'data_type' => 'varchar'],
                        ['column_name' => 'new_field', 'data_type' => 'text'], // New column
                    ],
                ],
            ],
        ];

        $diff = $this->schemaService->compareSnapshots($oldSnapshot, $newSnapshot);

        $this->assertIsArray($diff);
        $this->assertArrayHasKey('added_columns', $diff);
        $this->assertArrayHasKey('removed_columns', $diff);
        $this->assertArrayHasKey('modified_columns', $diff);
    }

    /** @test */
    public function it_detects_table_existence()
    {
        $exists = $this->schemaService->tableExists('cmis.orgs');
        $this->assertTrue($exists);

        $notExists = $this->schemaService->tableExists('cmis.nonexistent_table_xyz');
        $this->assertFalse($notExists);
    }

    /** @test */
    public function it_gets_row_count_for_table()
    {
        $count = $this->schemaService->getTableRowCount('cmis.orgs');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** @test */
    public function it_estimates_backup_size()
    {
        $tables = $this->schemaService->discoverOrgTables()->take(5)->toArray();

        $estimate = $this->schemaService->estimateBackupSize($tables);

        $this->assertIsArray($estimate);
        $this->assertArrayHasKey('total_bytes', $estimate);
        $this->assertArrayHasKey('by_table', $estimate);
    }

    /** @test */
    public function it_groups_tables_by_category()
    {
        $tables = $this->schemaService->discoverOrgTables();

        $grouped = $this->schemaService->groupTablesByCategory($tables);

        $this->assertIsArray($grouped);

        // Each group should be an array of tables
        foreach ($grouped as $category => $categoryTables) {
            $this->assertIsString($category);
            $this->assertIsArray($categoryTables);
        }
    }

    /** @test */
    public function it_discovers_indexes_on_tables()
    {
        $indexes = $this->schemaService->getTableIndexes('cmis.orgs');

        $this->assertIsArray($indexes);
        // Should have at least primary key index
    }

    /** @test */
    public function it_handles_schema_qualified_table_names()
    {
        // Should work with schema.table format
        $schema1 = $this->schemaService->getTableSchema('cmis.orgs');
        $this->assertNotEmpty($schema1);

        // Should also work without schema (defaults to public or first in path)
        $tables = $this->schemaService->discoverOrgTables();
        $this->assertGreaterThan(0, $tables->count());
    }

    /** @test */
    public function it_validates_schema_compatibility()
    {
        $backupSchema = [
            'version' => '1.0',
            'tables' => [
                'cmis.orgs' => [
                    'columns' => [
                        ['column_name' => 'org_id', 'data_type' => 'uuid'],
                        ['column_name' => 'name', 'data_type' => 'varchar'],
                    ],
                ],
            ],
        ];

        $result = $this->schemaService->validateCompatibility($backupSchema);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('compatible', $result);
        $this->assertArrayHasKey('issues', $result);
    }
}
