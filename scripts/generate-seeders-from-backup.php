<?php

/**
 * Generate Laravel Seeders from PostgreSQL backup file
 *
 * This script parses a PostgreSQL dump file and creates Laravel seeder classes
 * for each table with data.
 */

$backupFile = __DIR__ . '/../database/backup-db-for-seeds.sql';
$seedersDir = __DIR__ . '/../database/seeders';

if (!file_exists($backupFile)) {
    die("Backup file not found: $backupFile\n");
}

if (!is_dir($seedersDir)) {
    mkdir($seedersDir, 0755, true);
}

echo "Parsing backup file...\n";

// Read the file line by line
$handle = fopen($backupFile, 'r');
if (!$handle) {
    die("Could not open backup file\n");
}

$currentTable = null;
$currentColumns = [];
$tableData = [];
$inCopyBlock = false;

while (($line = fgets($handle)) !== false) {
    // Match COPY statement
    if (preg_match('/^COPY cmis\.(\w+) \((.*?)\) FROM stdin;$/', $line, $matches)) {
        $currentTable = $matches[1];
        $currentColumns = array_map('trim', explode(',', $matches[2]));
        $tableData[$currentTable] = [
            'columns' => $currentColumns,
            'rows' => []
        ];
        $inCopyBlock = true;
        echo "Found table: $currentTable with " . count($currentColumns) . " columns\n";
        continue;
    }

    // End of COPY block
    if ($inCopyBlock && trim($line) === '\.') {
        $inCopyBlock = false;
        $currentTable = null;
        $currentColumns = [];
        continue;
    }

    // Parse data rows
    if ($inCopyBlock && $currentTable) {
        $tableData[$currentTable]['rows'][] = $line;
    }
}

fclose($handle);

echo "\nFound " . count($tableData) . " tables with data\n";

// Filter tables with actual data
$tablesWithData = array_filter($tableData, function($table) {
    return count($table['rows']) > 0;
});

echo "Tables with data: " . count($tablesWithData) . "\n\n";

// Generate seeders for each table
foreach ($tablesWithData as $tableName => $data) {
    $rowCount = count($data['rows']);
    echo "Generating seeder for $tableName ($rowCount rows)...\n";

    $className = str_replace('_', '', ucwords($tableName, '_')) . 'Seeder';
    $seederFile = $seedersDir . '/' . $className . '.php';

    // Parse rows into PHP arrays
    $parsedRows = [];
    foreach ($data['rows'] as $row) {
        $parsedRow = parsePostgresRow(trim($row), $data['columns']);
        if ($parsedRow) {
            $parsedRows[] = $parsedRow;
        }
    }

    if (empty($parsedRows)) {
        echo "  Skipping $tableName - no valid rows\n";
        continue;
    }

    // Generate seeder content
    $seederContent = generateSeederClass($className, $tableName, $parsedRows);

    file_put_contents($seederFile, $seederContent);
    echo "  Created: $seederFile\n";
}

echo "\n✓ Seeder generation complete!\n";
echo "\nNext steps:\n";
echo "1. Update database/seeders/DatabaseSeeder.php to call these seeders\n";
echo "2. Run: php artisan db:seed\n";

/**
 * Parse a PostgreSQL COPY data row into a PHP array
 */
function parsePostgresRow($line, $columns) {
    if (empty($line)) {
        return null;
    }

    $values = [];
    $current = '';
    $inQuotes = false;
    $i = 0;

    while ($i < strlen($line)) {
        $char = $line[$i];

        if ($char === "\t" && !$inQuotes) {
            $values[] = parseValue($current);
            $current = '';
            $i++;
            continue;
        }

        $current .= $char;
        $i++;
    }

    // Add the last value
    if ($current !== '') {
        $values[] = parseValue($current);
    }

    // Combine columns with values
    $row = [];
    foreach ($columns as $index => $column) {
        $row[$column] = $values[$index] ?? null;
    }

    return $row;
}

/**
 * Parse a PostgreSQL value
 */
function parseValue($value) {
    $value = trim($value);

    // NULL
    if ($value === '\\N') {
        return null;
    }

    // Boolean
    if ($value === 't') {
        return true;
    }
    if ($value === 'f') {
        return false;
    }

    // Unescape PostgreSQL escapes
    $value = str_replace('\\\\', '\\', $value);
    $value = str_replace('\\t', "\t", $value);
    $value = str_replace('\\n', "\n", $value);
    $value = str_replace('\\r', "\r", $value);

    return $value;
}

/**
 * Generate seeder class content
 */
function generateSeederClass($className, $tableName, $rows) {
    $rowCount = count($rows);

    // Split into chunks if too many rows
    $chunkSize = 500;
    $chunks = array_chunk($rows, $chunkSize);

    $content = "<?php\n\n";
    $content .= "namespace Database\\Seeders;\n\n";
    $content .= "use Illuminate\\Database\\Seeder;\n";
    $content .= "use Illuminate\\Support\\Facades\\DB;\n\n";
    $content .= "class $className extends Seeder\n";
    $content .= "{\n";
    $content .= "    /**\n";
    $content .= "     * Run the database seeds.\n";
    $content .= "     * Total rows: $rowCount\n";
    $content .= "     */\n";
    $content .= "    public function run(): void\n";
    $content .= "    {\n";
    $content .= "        // Disable foreign key checks\n";
    $content .= "        DB::statement('SET CONSTRAINTS ALL DEFERRED');\n\n";

    foreach ($chunks as $chunkIndex => $chunk) {
        $chunkNum = $chunkIndex + 1;
        $content .= "        // Chunk $chunkNum\n";
        $content .= "        DB::table('cmis.$tableName')->insert(\n";
        $content .= "            " . var_export($chunk, true) . "\n";
        $content .= "        );\n\n";
    }

    $content .= "    }\n";
    $content .= "}\n";

    return $content;
}
