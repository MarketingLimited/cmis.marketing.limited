<?php

namespace App\Services;

class EmbeddingService
{
    public function getOrGenerateEmbedding($text, $type = 'content')
    {
        \Log::info("EmbeddingService::getOrGenerateEmbedding", ['text_length' => strlen($text), 'type' => $type]);
        
        // Return mock embedding vector (768 dimensions for Gemini)
        return array_fill(0, 768, 0.1);
    }

    public function batchGenerateEmbeddings(array $items)
    {
        \Log::info("EmbeddingService::batchGenerateEmbeddings", ['count' => count($items)]);
        
        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'id' => $item['id'] ?? uniqid(),
                'embedding' => array_fill(0, 768, 0.1)
            ];
        }
        
        return $results;
    }
}

    public function generateEmbedding($text)
    {
        \Log::info("EmbeddingService::generateEmbedding", ['text_length' => strlen($text)]);
        return array_fill(0, 768, 0.1);
    }
}
