<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'models/text-embedding-004');
    }

    public function generateEmbedding(string $text): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:embedText", [
            'text' => $text,
        ]);

        if ($response->successful()) {
            return $response->json('embedding.values');
        }

        return null;
    }
}
