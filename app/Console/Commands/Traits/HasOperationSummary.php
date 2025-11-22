<?php

namespace App\Console\Commands\Traits;

/**
 * Operation Summary Trait
 * Issue #43: Shows detailed summary after bulk operations
 *
 * Usage: use HasOperationSummary; in your command class
 */
trait HasOperationSummary
{
    protected array $operationStats = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => [],
        'warnings' => [],
        'start_time' => null,
        'end_time' => null,
    ];

    protected function initSummary(): void
    {
        $this->operationStats['start_time'] = now();
    }

    protected function recordSuccess(string $item = null): void
    {
        $this->operationStats['total']++;
        $this->operationStats['success']++;
    }

    protected function recordFailure(string $item, string $error): void
    {
        $this->operationStats['total']++;
        $this->operationStats['failed']++;
        $this->operationStats['errors'][] = [
            'item' => $item,
            'error' => $error,
        ];
    }

    protected function recordSkipped(string $item, string $reason): void
    {
        $this->operationStats['total']++;
        $this->operationStats['skipped']++;
        $this->operationStats['warnings'][] = [
            'item' => $item,
            'reason' => $reason,
        ];
    }

    protected function showSummary(string $operationName = 'Operation'): void
    {
        $this->operationStats['end_time'] = now();
        $duration = $this->operationStats['start_time']->diffInSeconds($this->operationStats['end_time']);

        $this->newLine();
        $this->info("📊 {$operationName} Summary");
        $this->info(str_repeat('=', 60));

        // Statistics
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $this->operationStats['total']],
                ['✅ Successful', $this->operationStats['success']],
                ['❌ Failed', $this->operationStats['failed']],
                ['⏭️  Skipped', $this->operationStats['skipped']],
            ]
        );

        // Success Rate
        $successRate = $this->operationStats['total'] > 0
            ? round(($this->operationStats['success'] / $this->operationStats['total']) * 100, 2)
            : 0;

        if ($successRate >= 90) {
            $this->info("✨ Success Rate: {$successRate}%");
        } elseif ($successRate >= 50) {
            $this->warn("⚠️  Success Rate: {$successRate}%");
        } else {
            $this->error("❌ Success Rate: {$successRate}%");
        }

        // Duration
        $this->info("⏱️  Duration: {$duration} seconds");

        // Errors
        if (!empty($this->operationStats['errors'])) {
            $this->newLine();
            $this->error('❌ Errors:');
            foreach ($this->operationStats['errors'] as $error) {
                $this->line("  - {$error['item']}: {$error['error']}");
            }
        }

        // Warnings
        if (!empty($this->operationStats['warnings'])) {
            $this->newLine();
            $this->warn('⚠️  Warnings:');
            foreach ($this->operationStats['warnings'] as $warning) {
                $this->line("  - {$warning['item']}: {$warning['reason']}");
            }
        }

        $this->newLine();
    }

    protected function getExitCode(): int
    {
        // Issue #49: Exit with proper code on partial failure
        if ($this->operationStats['failed'] > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
