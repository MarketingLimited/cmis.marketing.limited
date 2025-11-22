<?php

namespace App\Console\Commands\Traits;

/**
 * Dry-Run Mode Trait for CLI Commands
 * Issue #39: Allows preview of changes before execution
 *
 * Usage: use HasDryRunMode; in your command class
 */
trait HasDryRunMode
{
    protected bool $isDryRun = false;
    protected array $dryRunActions = [];

    protected function setupDryRun(): void
    {
        $this->isDryRun = $this->option('dry-run') ?? false;

        if ($this->isDryRun) {
            $this->warn('🔍 DRY-RUN MODE: No actual changes will be made');
            $this->newLine();
        }
    }

    protected function recordAction(string $action, array $details = []): void
    {
        if ($this->isDryRun) {
            $this->dryRunActions[] = [
                'action' => $action,
                'details' => $details,
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }

    protected function showDryRunSummary(): void
    {
        if (!$this->isDryRun || empty($this->dryRunActions)) {
            return;
        }

        $this->newLine();
        $this->info('📋 DRY-RUN SUMMARY');
        $this->info(str_repeat('=', 50));

        foreach ($this->dryRunActions as $index => $action) {
            $this->line(sprintf(
                '%d. [%s] %s',
                $index + 1,
                $action['timestamp'],
                $action['action']
            ));

            if (!empty($action['details'])) {
                foreach ($action['details'] as $key => $value) {
                    $this->line("   - {$key}: " . json_encode($value));
                }
            }
        }

        $this->newLine();
        $this->info(sprintf('Total actions that would be performed: %d', count($this->dryRunActions)));
        $this->warn('To execute these changes, run the command without --dry-run');
    }

    protected function getDryRunOption(): array
    {
        return ['dry-run', null, InputOption::VALUE_NONE, 'Preview changes without executing them'];
    }
}
