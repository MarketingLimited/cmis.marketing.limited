<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DocsSearch extends Command
{
    protected $signature = 'docs:search {keyword} {--context=3 : Number of lines of context} {--case-sensitive : Case-sensitive search}';

    protected $description = 'Search documentation for keyword - ALWAYS use this before starting any work!';

    public function handle(): int
    {
        $keyword = $this->argument('keyword');
        $context = $this->option('context');
        $caseSensitive = $this->option('case-sensitive');

        $docsPath = base_path('docs');

        if (!File::isDirectory($docsPath)) {
            $this->error('❌ Documentation directory not found: ' . $docsPath);
            return Command::FAILURE;
        }

        $this->info("🔍 Searching documentation for: \"{$keyword}\"");
        $this->newLine();

        // Build grep command
        $grepFlags = '-n'; // Line numbers
        if (!$caseSensitive) {
            $grepFlags .= 'i'; // Case insensitive
        }
        if ($context > 0) {
            $grepFlags .= " -C {$context}"; // Context lines
        }

        // Search
        $command = "grep {$grepFlags} --include=\"*.md\" -r " . escapeshellarg($keyword) . " {$docsPath} 2>/dev/null";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || empty($output)) {
            $this->warn("⚠️  No matches found for \"{$keyword}\"");
            $this->newLine();
            $this->comment('💡 Tips:');
            $this->line('   - Try different keywords');
            $this->line('   - Use --case-sensitive if needed');
            $this->line('   - List all docs: find docs/ -name "*.md"');
            return Command::WARNING;
        }

        // Parse and display results
        $this->displayResults($output, $docsPath, $keyword);

        return Command::SUCCESS;
    }

    protected function displayResults(array $output, string $docsPath, string $keyword): void
    {
        $currentFile = null;
        $fileMatches = [];

        foreach ($output as $line) {
            // Extract filename and line number
            if (preg_match('/^([^:]+):(\d+):(.*)$/', $line, $matches)) {
                $file = $matches[1];
                $lineNum = $matches[2];
                $content = $matches[3];

                if ($currentFile !== $file) {
                    if ($currentFile !== null) {
                        $this->newLine();
                    }
                    $relativePath = str_replace($docsPath . '/', '', $file);
                    $this->line("<fg=cyan>📄 {$relativePath}</>");
                    $currentFile = $file;
                }

                // Highlight keyword in content
                $highlightedContent = $this->highlightKeyword($content, $keyword);
                $this->line("   <fg=gray>{$lineNum}:</> {$highlightedContent}");

                // Track file matches
                if (!isset($fileMatches[$file])) {
                    $fileMatches[$file] = 0;
                }
                $fileMatches[$file]++;
            } else {
                // Context line (no match, just context)
                $this->line("   <fg=gray>{$line}</>");
            }
        }

        // Summary
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $totalFiles = count($fileMatches);
        $totalMatches = array_sum($fileMatches);

        $this->info("✅ Found {$totalMatches} matches across {$totalFiles} files");

        // Show top files
        arsort($fileMatches);
        $topFiles = array_slice($fileMatches, 0, 5, true);

        if (!empty($topFiles)) {
            $this->newLine();
            $this->comment('📊 Top files:');
            foreach ($topFiles as $file => $count) {
                $relativePath = str_replace($docsPath . '/', '', $file);
                $this->line("   {$count} matches - {$relativePath}");
            }
        }

        $this->newLine();
    }

    protected function highlightKeyword(string $text, string $keyword): string
    {
        // Highlight keyword in yellow
        $pattern = '/' . preg_quote($keyword, '/') . '/i';
        return preg_replace($pattern, '<fg=yellow>$0</>', $text);
    }
}
