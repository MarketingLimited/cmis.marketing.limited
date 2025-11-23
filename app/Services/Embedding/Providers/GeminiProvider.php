<?php

namespace App\Services\Embedding\Providers;

use App\Services\Embedding\EmbeddingProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiProvider implements EmbeddingProviderInterface
{
    private array $config;
    private int $requestCount = 0;
    private \DateTime $lastResetTime;
    private string $apiKey;
    private string $baseUrl;
    private string $modelName;

    public function __construct(array $config = null)
    {
        $this->config = $config ?? config('cmis-embeddings.gemini', []);
        $this->apiKey = $this->config['api_key'] ?? env('GEMINI_API_KEY', '');
        $this->baseUrl = $this->config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta/';
        $this->modelName = $this->config['model_name'] ?? 'models/text-embedding-004';
        $this->lastResetTime = new \DateTime();
    }

    /**
     * Generate embedding for a single text with retry logic
     */
    public function generateEmbedding(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $retryAttempts = $this->config['retry_attempts'] ?? 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $this->checkRateLimit();

                $url = $this->baseUrl . $this->modelName . ':embedContent';

                $response = Http::timeout($this->config['timeout_seconds'] ?? 30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url . '?key=' . $this->apiKey, [
                        'model' => $this->modelName,
                        'content' => [
                            'parts' => [['text' => $text]],
                        ],
                        'taskType' => $taskType,
                    ]);

                if (!$response->successful()) {
                    $errorBody = $response->body();
                    $statusCode = $response->status();

                    // Check if error is retryable
                    if ($this->isRetryableError($statusCode) && $attempt < $retryAttempts) {
                        $backoffSeconds = pow(2, $attempt - 1); // Exponential backoff: 1, 2, 4 seconds
                        Log::warning('Gemini API error (retryable), backing off', [
                            'attempt' => $attempt,
                            'status_code' => $statusCode,
                            'backoff_seconds' => $backoffSeconds,
                            'text_length' => strlen($text)
                        ]);
                        sleep($backoffSeconds);
                        continue;
                    }

                    throw new Exception("Gemini API error (HTTP {$statusCode}): {$errorBody}");
                }

                $this->requestCount++;
                $data = $response->json();
                $embedding = $data['embedding']['values'] ?? [];

                if (empty($embedding)) {
                    throw new Exception('Gemini API returned empty embedding');
                }

                return $this->normalizeVector($embedding);

            } catch (Exception $e) {
                $lastException = $e;

                if ($attempt < $retryAttempts) {
                    $backoffSeconds = pow(2, $attempt - 1);
                    Log::warning('Embedding generation failed (retrying)', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'backoff_seconds' => $backoffSeconds,
                        'text_length' => strlen($text)
                    ]);
                    sleep($backoffSeconds);
                } else {
                    Log::error('Gemini embedding generation failed after retries', [
                        'attempts' => $retryAttempts,
                        'error' => $e->getMessage(),
                        'text_length' => strlen($text)
                    ]);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Check if HTTP status code indicates a retryable error
     */
    private function isRetryableError(int $statusCode): bool
    {
        // Retry on rate limits, server errors, and timeouts
        return in_array($statusCode, [429, 500, 502, 503, 504]);
    }

    /**
     * Generate embeddings for multiple texts
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
                    Log::warning('Failed to generate embedding for text', [
                        'text_preview' => substr($text, 0, 100),
                        'error' => $e->getMessage()
                    ]);
                    $embeddings[] = null;
                }
            }
        }

        return $embeddings;
    }

    /**
     * Get embedding dimension
     */
    public function getDimension(): int
    {
        return $this->config['embedding_dimension'] ?? 768;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'gemini';
    }

    /**
     * Rate limiting enforcement
     */
    private function checkRateLimit(): void
    {
        $currentTime = new \DateTime();
        $timeDiff = $currentTime->getTimestamp() - $this->lastResetTime->getTimestamp();

        if ($timeDiff > 60) {
            $this->requestCount = 0;
            $this->lastResetTime = $currentTime;
        }

        $rateLimit = $this->config['rate_limit_per_minute'] ?? 60;
        if ($this->requestCount >= $rateLimit) {
            $sleepTime = 60 - $timeDiff;
            if ($sleepTime > 0) {
                Log::info("Gemini rate limit reached, waiting {$sleepTime} seconds");
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
        $magnitude = sqrt(array_sum(array_map(fn($x) => $x * $x, $vector)));

        if ($magnitude == 0) {
            return $vector;
        }

        return array_map(fn($x) => $x / $magnitude, $vector);
    }
}
