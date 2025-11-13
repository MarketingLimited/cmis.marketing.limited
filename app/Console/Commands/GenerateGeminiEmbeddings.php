<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Gemini\EmbeddingService;

class GenerateGeminiEmbeddings extends Command
{
    protected $signature = 'gemini:embeddings {--limit=100}';
    protected $description = 'Generate text embeddings using Google Gemini API and store them in the database';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $this->info("Starting Gemini Embeddings generation (limit: {$limit})...");

        $embeddingService = new EmbeddingService();
        $records = DB::select('SELECT id, content FROM cmis_knowledge.text_chunks WHERE embedding IS NULL LIMIT ?', [$limit]);

        foreach ($records as $record) {
            $embedding = $embeddingService->generateEmbedding($record->content);

            if ($embedding) {
                DB::update('UPDATE cmis_knowledge.text_chunks SET embedding = ? WHERE id = ?', [json_encode($embedding), $record->id]);
                $this->info("✅ Updated record ID: {$record->id}");
            } else {
                $this->error("❌ Failed to generate embedding for record ID: {$record->id}");
            }

            usleep(500000); // احترام معدل الطلبات
        }

        $this->info('✨ All embeddings processed successfully.');
    }
}
