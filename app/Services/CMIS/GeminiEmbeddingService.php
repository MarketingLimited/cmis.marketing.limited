<?php

namespace App\Services\CMIS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeminiEmbeddingService
{
    private array $config;
    private int $requestCount = 0;
    private \DateTime $lastResetTime;
    private string $apiKey;
    private string $baseUrl;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta/';
        $this->lastResetTime = new \DateTime();
    }
    
    /**
     * Generate embedding for single text
     */
    public function generateEmbedding(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        try {
            // Check rate limit
            $this->checkRateLimit();
            
            // Prepare request
            $url = $this->baseUrl . $this->config['model_name'] . ':embedContent';
            
            $response = Http::timeout($this->config['timeout_seconds'])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url . '?key=' . $this->apiKey, [
                    'model' => $this->config['model_name'],
                    'content' => [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ],
                    'taskType' => $taskType
                ]);
            
            if (!$response->successful()) {
                throw new Exception('Gemini API error: ' . $response->body());
            }
            
            $this->requestCount++;
            
            $data = $response->json();
            $embedding = $data['embedding']['values'] ?? [];
            
            // Normalize vector
            return $this->normalizeVector($embedding);
            
        } catch (Exception $e) {
            Log::error('Error generating embedding: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate embeddings for batch of texts
     */
    public function generateBatchEmbeddings(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $embeddings = [];
        $batchSize = $this->config['max_batch_size'];
        
        foreach (array_chunk($texts, $batchSize) as $batch) {
            foreach ($batch as $text) {
                try {
                    $embeddings[] = $this->generateEmbedding($text, $taskType);
                } catch (Exception $e) {
                    Log::error("Error processing text: " . substr($text, 0, 100) . "...");
                    $embeddings[] = null;
                }
            }
        }
        
        return $embeddings;
    }
    
    /**
     * Check and enforce rate limiting
     */
    private function checkRateLimit(): void
    {
        $currentTime = new \DateTime();
        $timeDiff = $currentTime->getTimestamp() - $this->lastResetTime->getTimestamp();
        
        // Reset counter every minute
        if ($timeDiff > 60) {
            $this->requestCount = 0;
            $this->lastResetTime = $currentTime;
        }
        
        // Check limit
        if ($this->requestCount >= $this->config['rate_limit_per_minute']) {
            $sleepTime = 60 - $timeDiff;
            if ($sleepTime > 0) {
                Log::info("Rate limit reached, waiting {$sleepTime} seconds");
                sleep($sleepTime);
                $this->requestCount = 0;
                $this->lastResetTime = new \DateTime();
            }
        }
    }
    
    /**
     * Normalize vector to unit length
     */
    private function normalizeVector(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $vector)));
        
        if ($norm > 0) {
            return array_map(fn($x) => $x / $norm, $vector);
        }
        
        return $vector;
    }
    
    /**
     * Generate embedding with caching
     */
    public function generateEmbeddingWithCache(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $cacheKey = 'gemini_embedding_' . md5($text . $taskType);
        
        return Cache::remember($cacheKey, 3600, function () use ($text, $taskType) {
            return $this->generateEmbedding($text, $taskType);
        });
    }
}