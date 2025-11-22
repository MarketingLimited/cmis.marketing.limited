<?php

namespace App\Services;

/**
 * SQL Validator Service
 * Issue #40: Validates SQL content before execution
 *
 * Detects potentially destructive SQL operations
 */
class SQLValidator
{
    protected array $destructivePatterns = [
        'DROP\s+TABLE',
        'DROP\s+DATABASE',
        'DROP\s+SCHEMA',
        'TRUNCATE\s+TABLE',
        'DELETE\s+FROM.*WITHOUT.*WHERE', // DELETE without WHERE clause
        'UPDATE.*SET.*WITHOUT.*WHERE',    // UPDATE without WHERE clause
        'ALTER\s+TABLE.*DROP',
        'DROP\s+COLUMN',
        'DROP\s+INDEX',
        'REVOKE',
        'GRANT.*WITH\s+GRANT\s+OPTION', // Privilege escalation
    ];

    protected array $dangerousPatterns = [
        'EXEC',
        'EXECUTE',
        'xp_cmdshell', // SQL Server command execution
        '\$\$', // PostgreSQL dollar-quoted function definitions
        'CREATE\s+FUNCTION',
        'CREATE\s+PROCEDURE',
        'CREATE\s+TRIGGER',
        'COPY.*FROM\s+PROGRAM', // PostgreSQL COPY from program
        'pg_read_file', // PostgreSQL file reading
        'pg_ls_dir', // PostgreSQL directory listing
    ];

    public function validate(string $sql): array
    {
        $errors = [];
        $warnings = [];
        $isDestructive = false;
        $isDangerous = false;

        // Normalize SQL (remove extra whitespace, comments)
        $normalizedSQL = $this->normalizeSQL($sql);

        // Check for destructive operations
        foreach ($this->destructivePatterns as $pattern) {
            if (preg_match('/\b' . $pattern . '\b/i', $normalizedSQL)) {
                $isDestructive = true;
                $warnings[] = "Detected destructive operation: {$pattern}";
            }
        }

        // Check for dangerous operations
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $normalizedSQL)) {
                $isDangerous = true;
                $errors[] = "Detected dangerous operation: {$pattern}";
            }
        }

        // Check for missing WHERE clause in DELETE/UPDATE
        if (preg_match('/\b(DELETE|UPDATE)\b/i', $normalizedSQL)) {
            if (!preg_match('/\bWHERE\b/i', $normalizedSQL)) {
                $warnings[] = "DELETE/UPDATE without WHERE clause detected - will affect all rows!";
                $isDestructive = true;
            }
        }

        // Check for SELECT *
        if (preg_match('/SELECT\s+\*/i', $normalizedSQL)) {
            $warnings[] = "SELECT * detected - consider specifying columns explicitly";
        }

        return [
            'valid' => empty($errors),
            'is_destructive' => $isDestructive,
            'is_dangerous' => $isDangerous,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function normalizeSQL(string $sql): string
    {
        // Remove single-line comments
        $sql = preg_replace('/--[^\n]*/', '', $sql);

        // Remove multi-line comments
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Collapse whitespace
        $sql = preg_replace('/\s+/', ' ', $sql);

        return trim($sql);
    }

    public function getDestructiveOperationDescription(string $pattern): string
    {
        $descriptions = [
            'DROP\s+TABLE' => 'Permanently deletes a table and all its data',
            'DROP\s+DATABASE' => 'Permanently deletes an entire database',
            'DROP\s+SCHEMA' => 'Permanently deletes a schema and all its objects',
            'TRUNCATE\s+TABLE' => 'Deletes all rows from a table (cannot be rolled back in some databases)',
            'DELETE\s+FROM' => 'Deletes rows from a table',
            'UPDATE.*SET' => 'Modifies existing rows in a table',
            'ALTER\s+TABLE.*DROP' => 'Removes a column or constraint from a table',
        ];

        foreach ($descriptions as $pat => $desc) {
            if (stripos($pattern, str_replace('\\s+', ' ', $pat)) !== false) {
                return $desc;
            }
        }

        return 'Potentially destructive operation';
    }
}
