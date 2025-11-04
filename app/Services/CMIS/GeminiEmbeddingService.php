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

    public function __construct(array $config = null)
    {
        $this->config = $config ?? (config('cmis-embeddings.gemini') ?? []);
        $this->apiKey = $this->config['api_key'] ?? '';
        $this->baseUrl = $this->config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta/';
        $this->lastResetTime = new \DateTime();
    }

    /**
     * Generate embedding for a single text.
     */
    public function generateEmbedding(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        try {
            $this->checkRateLimit();

            $url = $this->baseUrl . ($this->config['model_name'] ?? 'models/text-embedding-004') . ':embedContent';

            $response = Http::timeout($this->config['timeout_seconds'] ?? 30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . '?key=' . $this->apiKey, [
                    'model' => $this->config['model_name'] ?? 'models/text-embedding-004',
                    'content' => [
                        'parts' => [['text' => $text]],
                    ],
                    'taskType' => $taskType,
                ]);

            if (!$response->successful()) {
                throw new Exception('Gemini API error: ' . $response->body());
            }

            $this->requestCount++;
            $data = $response->json();
            $embedding = $data['embedding']['values'] ?? [];

            return $this->normalizeVector($embedding);
        } catch (Exception $e) {
            Log::error('Error generating embedding: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate embeddings for a batch of texts.
     */
    public function generateBatchEmbeddings(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $embeddings = [];
        $batchSize = $this->config['max_batch_size'] ?? 50;

        foreach (array_chunk($texts, $batchSize) as $batch) {
            foreach ($batch as $text) {
                try {
                    $embeddings[] = $this->generateEmbedding($text, $taskType);
                } catch (Exception $e) {
                    Log::error('Error processing text: ' . substr($text, 0, 100) . '...');
                    $embeddings[] = null;
                }
            }
        }

        return $embeddings;
    }

    /**
     * Rate limiting enforcement.
     */
    private function checkRateLimit(): void
    {
        $currentTime = new \DateTime();
        $timeDiff = $currentTime->getTimestamp() - $this->lastResetTime->getTimestamp();

        if ($timeDiff > 60) {
            $this->requestCount = 0;
            $this->lastResetTime = $currentTime;
        }

        if ($this->requestCount >= ($this->config['rate_limit_per_minute'] ?? 60)) {
            $sleepTime = 60 - $timeDiff;
            if ($sleepTime > 0) {
                Log::info("Gemini rate limit reached, waiting {$sleepTime} seconds...");
                sleep($sleepTime);
                $this->requestCount = 0;
                $this->lastResetTime = new \DateTime();
            }
        }
    }

    /**
     * Normalize vector.
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
     * Cached embedding generation.
     */
    public function generateEmbeddingWithCache(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $cacheKey = 'gemini_embedding_' . md5($text . $taskType);
        return Cache::remember($cacheKey, 3600, fn() => $this->generateEmbedding($text, $taskType));
    }
}
