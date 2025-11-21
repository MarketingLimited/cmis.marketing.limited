<?php
namespace App\Services\CMIS;

class SemanticSearchService
{
    public function generateEmbedding($text)
    {
        \Log::info("SemanticSearchService::generateEmbedding", ['text_length' => strlen($text)]);
        
        // Return 768-dimensional vector for Gemini
        return array_fill(0, 768, 0.1);
    }
    
    public function search($query, $limit = 10)
    {
        \Log::info("SemanticSearchService::search", ['query' => $query, 'limit' => $limit]);
        
        return [
            'success' => true,
            'results' => []
        ];
    }
}
