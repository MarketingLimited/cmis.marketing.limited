<?php

namespace App\Console\Commands\Traits;

use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Progress Indicators Trait
 * Issue #41: Shows progress bars for long-running operations
 *
 * Usage: use HasProgressIndicators; in your command class
 */
trait HasProgressIndicators
{
    protected ?ProgressBar $progressBar = null;

    protected function startProgress(int $total, string $message = 'Processing'): ProgressBar
    {
        $this->progressBar = $this->output->createProgressBar($total);
        $this->progressBar->setFormat(
            " %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%"
        );
        $this->progressBar->setMessage($message);
        $this->progressBar->start();

        return $this->progressBar;
    }

    protected function advanceProgress(string $message = null): void
    {
        if ($this->progressBar) {
            if ($message) {
                $this->progressBar->setMessage($message);
            }
            $this->progressBar->advance();
        }
    }

    protected function finishProgress(string $message = 'Complete'): void
    {
        if ($this->progressBar) {
            $this->progressBar->setMessage($message);
            $this->progressBar->finish();
            $this->newLine(2);
        }
    }

    protected function updateProgressMessage(string $message): void
    {
        if ($this->progressBar) {
            $this->progressBar->setMessage($message);
            $this->progressBar->display();
        }
    }

    protected function processWithProgress(iterable $items, callable $callback, string $message = 'Processing'): array
    {
        $total = is_array($items) ? count($items) : iterator_count($items);
        $this->startProgress($total, $message);

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($items as $item) {
            try {
                $callback($item);
                $results['success']++;
                $this->advanceProgress("Processed: {$results['success']}/{$total}");
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'item' => $item,
                    'error' => $e->getMessage(),
                ];
                $this->advanceProgress("Failed: {$results['failed']}");
            }
        }

        $this->finishProgress('Complete');

        return $results;
    }
}
