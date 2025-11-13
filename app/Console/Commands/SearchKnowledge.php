<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CMIS\SemanticSearchService;

class SearchKnowledge extends Command
{
    protected $signature = 'cmis:search 
                            {query : The search query}
                            {--intent= : Search intent}
                            {--direction= : Search direction}
                            {--purpose= : Search purpose}
                            {--limit=10 : Number of results}
                            {--threshold=0.7 : Similarity threshold}';
    
    protected $description = 'Search CMIS knowledge using semantic search';
    
    private SemanticSearchService $searchService;
    
    public function __construct(SemanticSearchService $searchService)
    {
        parent::__construct();
        $this->searchService = $searchService;
    }
    
    public function handle(): int
    {
        $query = $this->argument('query');
        $intent = $this->option('intent');
        $direction = $this->option('direction');
        $purpose = $this->option('purpose');
        $limit = (int) $this->option('limit');
        $threshold = (float) $this->option('threshold');
        
        $this->info("Searching for: {$query}");
        
        $results = $this->searchService->search(
            $query,
            $intent,
            $direction,
            $purpose,
            $limit,
            $threshold
        );
        
        if (empty($results)) {
            $this->warn('No results found.');
            return Command::SUCCESS;
        }
        
        $this->info("Found " . count($results) . " results:");
        
        $tableData = [];
        foreach ($results as $index => $result) {
            $tableData[] = [
                $index + 1,
                $result->knowledge_id ?? 'N/A',
                substr($result->topic ?? '', 0, 50),
                round($result->similarity ?? 0, 4),
                $result->category ?? 'N/A'
            ];
        }
        
        $this->table(
            ['#', 'ID', 'Topic', 'Similarity', 'Category'],
            $tableData
        );
        
        return Command::SUCCESS;
    }
}