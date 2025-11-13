<?php

namespace App\Jobs;

use App\Services\CMIS\GeminiEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1800; // 30 minutes for large batches
    public $backoff = [300, 900];

    protected string $orgId;
    protected ?int $limit;
    protected ?string $contentType;

    public function __construct(string $orgId, ?int $limit = null, ?string $contentType = null)
    {
        $this->orgId = $orgId;
        $this->limit = $limit;
        $this->contentType = $contentType;
        $this->onQueue('embeddings');
    }

    public function handle(GeminiEmbeddingService $embeddingService): void
    {
        Log::info('Starting embeddings generation job', [
            'org_id' => $this->orgId,
            'limit' => $this->limit,
            'content_type' => $this->contentType,
        ]);

        try {
            DB::transaction(function () use ($embeddingService) {
                // Set database context for RLS
                DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                    config('cmis.system_user_id'),
                    $this->orgId
                ]);

                // Get content that needs embeddings
                $query = DB::table('cmis.social_posts')
                    ->where('org_id', $this->orgId)
                    ->whereNull('embedding')
                    ->whereNotNull('caption');

                if ($this->contentType) {
                    $query->where('media_type', $this->contentType);
                }

                if ($this->limit) {
                    $query->limit($this->limit);
                }

                $posts = $query->get();

                $processedCount = 0;
                $errorCount = 0;

                foreach ($posts as $post) {
                    try {
                        // Generate embedding for post caption
                        $embedding = $embeddingService->generateEmbedding($post->caption);

                        if ($embedding) {
                            // Store embedding in database
                            DB::table('cmis.social_posts')
                                ->where('id', $post->id)
                                ->update([
                                    'embedding' => DB::raw("'" . json_encode($embedding) . "'::vector"),
                                    'embedding_generated_at' => now(),
                                    'updated_at' => now(),
                                ]);

                            $processedCount++;
                        }

                        // Rate limiting - sleep between API calls
                        usleep(100000); // 100ms delay

                    } catch (\Exception $e) {
                        Log::warning('Failed to generate embedding for post', [
                            'post_id' => $post->id,
                            'error' => $e->getMessage(),
                        ]);
                        $errorCount++;
                    }
                }

                Log::info('Embeddings generation completed', [
                    'org_id' => $this->orgId,
                    'processed' => $processedCount,
                    'errors' => $errorCount,
                ]);

                // Log sync success
                DB::table('cmis.sync_logs')->insert([
                    'org_id' => $this->orgId,
                    'source' => 'embeddings',
                    'status' => 'success',
                    'message' => "Generated {$processedCount} embeddings ({$errorCount} errors)",
                    'created_at' => now(),
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Embeddings generation job failed', [
                'org_id' => $this->orgId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            DB::table('cmis.sync_logs')->insert([
                'org_id' => $this->orgId,
                'source' => 'embeddings',
                'status' => 'failed',
                'message' => 'Embeddings generation failed: ' . $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Embeddings generation job failed permanently', [
            'org_id' => $this->orgId,
            'error' => $exception->getMessage(),
        ]);
    }
}
