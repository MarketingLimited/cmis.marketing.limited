<?php

namespace App\Apps\Backup\Services\Discovery;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Dependency Resolver
 *
 * Resolves table dependencies based on foreign key relationships.
 * Uses topological sorting to determine correct order for:
 * - Backup extraction (children before parents for referential integrity)
 * - Restore insertion (parents before children)
 */
class DependencyResolver
{
    /**
     * Cache TTL for dependency graph (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Schemas to scan for dependencies
     */
    protected array $schemas;

    public function __construct()
    {
        $this->schemas = config('backup.discovery.schemas', [
            'cmis',
            'cmis_ai',
            'cmis_analytics',
            'cmis_creative',
            'cmis_platform',
            'cmis_google',
            'cmis_meta',
            'cmis_tiktok',
            'cmis_linkedin',
            'cmis_twitter',
            'cmis_snapchat',
        ]);
    }

    /**
     * Get the dependency graph for all tables
     *
     * @return array ['dependencies' => [...], 'dependents' => [...]]
     */
    public function buildDependencyGraph(): array
    {
        $cacheKey = 'backup:dependency_graph:' . md5(implode(',', $this->schemas));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            // Convert PHP array to PostgreSQL array literal format
            $schemaArrayLiteral = '{' . implode(',', $this->schemas) . '}';

            // Query foreign key relationships from PostgreSQL catalog
            $foreignKeys = DB::select("
                SELECT
                    tc.table_schema || '.' || tc.table_name AS table_name,
                    ccu.table_schema || '.' || ccu.table_name AS referenced_table,
                    kcu.column_name AS column_name,
                    ccu.column_name AS referenced_column
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage ccu
                    ON ccu.constraint_name = tc.constraint_name
                WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = ANY(?::text[])
                    AND ccu.table_schema = ANY(?::text[])
            ", [$schemaArrayLiteral, $schemaArrayLiteral]);

            // Build dependency maps
            $dependencies = []; // table => [tables it depends on]
            $dependents = [];   // table => [tables that depend on it]

            foreach ($foreignKeys as $fk) {
                $table = $fk->table_name;
                $referenced = $fk->referenced_table;

                // Skip self-references
                if ($table === $referenced) {
                    continue;
                }

                // This table depends on the referenced table
                if (!isset($dependencies[$table])) {
                    $dependencies[$table] = [];
                }
                if (!in_array($referenced, $dependencies[$table])) {
                    $dependencies[$table][] = $referenced;
                }

                // The referenced table has this table as a dependent
                if (!isset($dependents[$referenced])) {
                    $dependents[$referenced] = [];
                }
                if (!in_array($table, $dependents[$referenced])) {
                    $dependents[$referenced][] = $table;
                }
            }

            return [
                'dependencies' => $dependencies,
                'dependents' => $dependents,
                'foreign_keys' => array_map(fn($fk) => (array) $fk, $foreignKeys),
            ];
        });
    }

    /**
     * Get tables that the given table depends on
     *
     * @param string $tableName Fully qualified table name
     * @return array Tables this table references
     */
    public function getDependencies(string $tableName): array
    {
        $graph = $this->buildDependencyGraph();
        return $graph['dependencies'][$tableName] ?? [];
    }

    /**
     * Get tables that depend on the given table
     *
     * @param string $tableName Fully qualified table name
     * @return array Tables that reference this table
     */
    public function getDependents(string $tableName): array
    {
        $graph = $this->buildDependencyGraph();
        return $graph['dependents'][$tableName] ?? [];
    }

    /**
     * Get all dependencies recursively (transitive closure)
     *
     * @param string $tableName Fully qualified table name
     * @return array All tables this table depends on (directly or indirectly)
     */
    public function getAllDependencies(string $tableName): array
    {
        $graph = $this->buildDependencyGraph();
        $allDeps = [];
        $toProcess = [$tableName];
        $processed = [];

        while (!empty($toProcess)) {
            $current = array_shift($toProcess);

            if (in_array($current, $processed)) {
                continue;
            }
            $processed[] = $current;

            $deps = $graph['dependencies'][$current] ?? [];
            foreach ($deps as $dep) {
                if (!in_array($dep, $allDeps)) {
                    $allDeps[] = $dep;
                    $toProcess[] = $dep;
                }
            }
        }

        return $allDeps;
    }

    /**
     * Order tables by dependencies for backup extraction
     * Returns tables in order where children come before parents
     * (extract data from leaf tables first)
     *
     * @param array|Collection $tables Tables to order
     * @return array Ordered table list
     */
    public function resolveExtractionOrder($tables): array
    {
        $tables = $tables instanceof Collection ? $tables->toArray() : $tables;

        // Reverse of restore order - children first
        return array_reverse($this->resolveRestoreOrder($tables));
    }

    /**
     * Order tables by dependencies for restore
     * Returns tables in order where parents come before children
     * (insert into parent tables first)
     *
     * @param array|Collection $tables Tables to order
     * @return array Ordered table list (parents first)
     */
    public function resolveRestoreOrder($tables): array
    {
        $tables = $tables instanceof Collection ? $tables->toArray() : $tables;
        $graph = $this->buildDependencyGraph();

        // Build a subset graph for only the tables we're interested in
        $subsetDeps = [];
        foreach ($tables as $table) {
            $subsetDeps[$table] = array_intersect(
                $graph['dependencies'][$table] ?? [],
                $tables
            );
        }

        // Topological sort using Kahn's algorithm
        return $this->topologicalSort($subsetDeps);
    }

    /**
     * Perform topological sort using Kahn's algorithm
     *
     * @param array $dependencies Map of node => dependencies
     * @return array Sorted nodes (parents before children)
     */
    protected function topologicalSort(array $dependencies): array
    {
        $nodes = array_keys($dependencies);
        $inDegree = [];
        $sorted = [];

        // Initialize in-degree for all nodes
        foreach ($nodes as $node) {
            $inDegree[$node] = 0;
        }

        // Calculate in-degree (number of incoming edges)
        foreach ($dependencies as $node => $deps) {
            foreach ($deps as $dep) {
                if (isset($inDegree[$dep])) {
                    $inDegree[$node]++;
                }
            }
        }

        // Start with nodes that have no dependencies (in-degree = 0)
        $queue = array_filter($nodes, fn($node) => $inDegree[$node] === 0);
        $queue = array_values($queue);

        while (!empty($queue)) {
            $node = array_shift($queue);
            $sorted[] = $node;

            // For each node that depends on this node, decrease its in-degree
            foreach ($nodes as $dependent) {
                if (in_array($node, $dependencies[$dependent] ?? [])) {
                    $inDegree[$dependent]--;
                    if ($inDegree[$dependent] === 0) {
                        $queue[] = $dependent;
                    }
                }
            }
        }

        // If sorted doesn't contain all nodes, there's a cycle
        if (count($sorted) !== count($nodes)) {
            // Return best effort order with remaining nodes at the end
            $remaining = array_diff($nodes, $sorted);
            return array_merge($sorted, $remaining);
        }

        return $sorted;
    }

    /**
     * Detect circular dependencies in a set of tables
     *
     * @param array|Collection $tables Tables to check
     * @return array Circular dependency chains found
     */
    public function detectCircularDependencies($tables): array
    {
        $tables = $tables instanceof Collection ? $tables->toArray() : $tables;
        $graph = $this->buildDependencyGraph();
        $cycles = [];

        foreach ($tables as $table) {
            $visited = [];
            $path = [];
            $this->findCycles($table, $graph['dependencies'], $tables, $visited, $path, $cycles);
        }

        return array_values(array_unique($cycles, SORT_REGULAR));
    }

    /**
     * Recursive helper to find cycles using DFS
     */
    protected function findCycles(
        string $node,
        array $dependencies,
        array $validNodes,
        array &$visited,
        array &$path,
        array &$cycles
    ): void {
        if (in_array($node, $path)) {
            // Found a cycle
            $cycleStart = array_search($node, $path);
            $cycle = array_slice($path, $cycleStart);
            $cycle[] = $node; // Complete the cycle
            $cycles[] = $cycle;
            return;
        }

        if (in_array($node, $visited)) {
            return;
        }

        $visited[] = $node;
        $path[] = $node;

        $deps = array_intersect($dependencies[$node] ?? [], $validNodes);
        foreach ($deps as $dep) {
            $this->findCycles($dep, $dependencies, $validNodes, $visited, $path, $cycles);
        }

        array_pop($path);
    }

    /**
     * Get foreign key constraints for a table
     *
     * @param string $tableName Fully qualified table name
     * @return array Foreign key definitions
     */
    public function getForeignKeys(string $tableName): array
    {
        $graph = $this->buildDependencyGraph();

        return collect($graph['foreign_keys'])
            ->where('table_name', $tableName)
            ->values()
            ->toArray();
    }

    /**
     * Get tables that can be safely processed in parallel
     * (no dependencies between them)
     *
     * @param array|Collection $tables Tables to analyze
     * @return array Groups of tables that can be processed in parallel
     */
    public function getParallelGroups($tables): array
    {
        $tables = $tables instanceof Collection ? $tables->toArray() : $tables;
        $ordered = $this->resolveRestoreOrder($tables);
        $graph = $this->buildDependencyGraph();

        $groups = [];
        $assigned = [];

        foreach ($ordered as $table) {
            // Find which group this table can go in
            $deps = array_intersect($graph['dependencies'][$table] ?? [], $tables);

            // Find the minimum group that all dependencies are in
            $minGroup = 0;
            foreach ($deps as $dep) {
                if (isset($assigned[$dep])) {
                    $minGroup = max($minGroup, $assigned[$dep] + 1);
                }
            }

            // Add to appropriate group
            if (!isset($groups[$minGroup])) {
                $groups[$minGroup] = [];
            }
            $groups[$minGroup][] = $table;
            $assigned[$table] = $minGroup;
        }

        return array_values($groups);
    }

    /**
     * Clear dependency cache
     */
    public function clearCache(): void
    {
        $cacheKey = 'backup:dependency_graph:' . md5(implode(',', $this->schemas));
        Cache::forget($cacheKey);
    }
}
