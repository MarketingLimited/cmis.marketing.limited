<?php

namespace App\Console\Commands\Traits;

/**
 * Helpful Error Messages Trait
 * Issue #42: Error messages suggest solutions
 *
 * Usage: use HasHelpfulErrors; in your command class
 */
trait HasHelpfulErrors
{
    protected array $errorSolutions = [];

    protected function registerErrorSolution(string $errorPattern, string $solution): void
    {
        $this->errorSolutions[$errorPattern] = $solution;
    }

    protected function handleErrorWithSolution(\Exception $e, string $context = ''): void
    {
        $this->error("❌ Error: {$e->getMessage()}");

        if ($context) {
            $this->line("Context: {$context}");
        }

        // Try to find a solution
        $solution = $this->findSolution($e->getMessage());
        if ($solution) {
            $this->newLine();
            $this->info("💡 Suggested Solution:");
            $this->line("   {$solution}");
        }

        $this->newLine();
        $this->line("For more help, run: php artisan help {$this->getName()}");
    }

    protected function findSolution(string $errorMessage): ?string
    {
        foreach ($this->errorSolutions as $pattern => $solution) {
            if (stripos($errorMessage, $pattern) !== false) {
                return $solution;
            }
        }

        // Common error solutions
        return $this->getCommonSolution($errorMessage);
    }

    protected function getCommonSolution(string $errorMessage): ?string
    {
        $commonSolutions = [
            'connection refused' => 'Check if the database/service is running and accessible.',
            'access denied' => 'Verify your credentials in the .env file.',
            'file not found' => 'Make sure the file path is correct and the file exists.',
            'permission denied' => 'Check file/directory permissions. You may need sudo.',
            'class not found' => 'Run "composer dump-autoload" to regenerate autoload files.',
            'table' => 'Run "php artisan migrate" to create missing tables.',
            'column' => 'Run "php artisan migrate:fresh" or update your database schema.',
            'timeout' => 'The operation took too long. Try increasing timeout limits or checking network.',
            'rate limit' => 'You\'ve hit a rate limit. Wait a moment and try again.',
            'invalid argument' => 'Check the command syntax with "php artisan help {command}".',
            'org_id' => 'Make sure you\'ve specified a valid organization ID with --org option.',
        ];

        foreach ($commonSolutions as $keyword => $solution) {
            if (stripos($errorMessage, $keyword) !== false) {
                return $solution;
            }
        }

        return null;
    }

    protected function showDetailedError(\Exception $e, bool $includeTrace = false): void
    {
        $this->error('❌ Detailed Error Information:');
        $this->line("Message: {$e->getMessage()}");
        $this->line("File: {$e->getFile()}:{$e->getLine()}");

        if ($includeTrace) {
            $this->newLine();
            $this->line("Stack Trace:");
            $this->line($e->getTraceAsString());
        }

        // Find and show solution
        $solution = $this->findSolution($e->getMessage());
        if ($solution) {
            $this->newLine();
            $this->info("💡 Suggested Solution: {$solution}");
        }
    }
}
