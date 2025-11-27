<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialPost;
use App\Services\Social\HistoricalContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Analyze Historical Post Job
 *
 * Asynchronously analyzes a historical post (success detection, visual analysis, brand DNA).
 */
class AnalyzeHistoricalPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    private string $postId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $postId)
    {
        $this->postId = $postId;
    }

    /**
     * Execute the job.
     */
    public function handle(HistoricalContentService $service): void
    {
        try {
            $post = SocialPost::findOrFail($this->postId);

            // Set org context for RLS
            \DB::statement("SET app.current_org_id = '{$post->org_id}'");

            Log::info('Analyzing historical post', [
                'post_id' => $this->postId,
                'platform' => $post->platform,
            ]);

            $result = $service->analyzeHistoricalPost($post);

            Log::info('Historical post analysis completed', [
                'post_id' => $this->postId,
                'success_score' => $result['success_analysis']['success_score'] ?? null,
                'dimensions_stored' => $result['dimensions_stored'],
                'visual_assets_analyzed' => count($result['visual_analysis']),
            ]);

        } catch (\Exception $e) {
            Log::error('Historical post analysis job failed', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Historical post analysis job failed permanently', [
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);

        // Mark post analysis as failed
        try {
            $post = SocialPost::find($this->postId);
            if ($post) {
                $post->update([
                    'analysis_status' => 'failed',
                ]);
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }
}
