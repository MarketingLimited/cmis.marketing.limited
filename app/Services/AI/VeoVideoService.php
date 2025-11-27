<?php

namespace App\Services\AI;

use App\Models\AI\GeneratedMedia;
use Google\Cloud\AIPlatform\V1\AIPlatformClient;
use Google\Cloud\AIPlatform\V1\PredictRequest;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class VeoVideoService
{
    private ?AIPlatformClient $client = null;
    private ?StorageClient $storageClient = null;
    private ?string $projectId = null;
    private string $location = 'us-central1';
    private ?string $storageBucket = null;

    public function __construct()
    {
        $this->projectId = config('services.google.project_id');
        $this->storageBucket = config('services.google.storage_bucket', 'cmis-video-ads');

        // Check if credentials are configured
        $credentialsPath = config('services.google.credentials_path');
        if (!empty($credentialsPath) && file_exists($credentialsPath)) {
            $this->initializeClients();
        } else {
            Log::warning('Google Cloud credentials not configured for VeoVideoService');
        }
    }

    /**
     * Initialize Google Cloud clients
     */
    private function initializeClients(): void
    {
        try {
            $this->client = new AIPlatformClient([
                'credentials' => config('services.google.credentials_path')
            ]);

            $this->storageClient = new StorageClient([
                'keyFilePath' => config('services.google.credentials_path')
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to initialize Veo clients', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate video from text prompt
     */
    public function generateFromText(
        string $prompt,
        int $duration = 7,
        string $aspectRatio = '16:9',
        bool $useFastModel = false,
        ?string $orgId = null
    ): array {
        if (!$this->isConfigured()) {
            throw new Exception('Veo service not configured. Set Google Cloud credentials.');
        }

        $model = $useFastModel ? 'veo-3.1-fast' : 'veo-3.1';
        $outputUri = $this->getStorageUri($orgId);

        try {
            $endpoint = $this->getEndpoint($model);

            $instance = [
                'text_prompt' => $prompt,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'output_storage_uri' => $outputUri
            ];

            $response = $this->client->predict([
                'endpoint' => $endpoint,
                'instances' => [$instance]
            ]);

            $prediction = $response->getPredictions()[0];
            $videoUri = $prediction['video_uri'] ?? $outputUri;

            // Download from GCS to local storage
            $localPath = $this->downloadFromGcs($videoUri);

            return [
                'url' => Storage::url($localPath),
                'storage_path' => $localPath,
                'gcs_uri' => $videoUri,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'model' => $model,
                'file_size' => Storage::size($localPath),
                'cost' => $this->calculateVideoCost($duration, $useFastModel),
                'metadata' => [
                    'prompt' => $prompt,
                    'model' => $model,
                    'prediction' => $prediction
                ]
            ];
        } catch (Exception $e) {
            Log::error('Veo video generation failed', [
                'prompt' => $prompt,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Convert static image to video (image-to-video)
     */
    public function imageToVideo(
        string $imagePath,
        string $animationPrompt,
        int $duration = 6,
        string $aspectRatio = '16:9',
        ?string $orgId = null
    ): array {
        if (!$this->isConfigured()) {
            throw new Exception('Veo service not configured.');
        }

        try {
            // Read and encode image
            $imageData = Storage::get($imagePath);
            $base64Image = base64_encode($imageData);

            $outputUri = $this->getStorageUri($orgId);

            $instance = [
                'text_prompt' => $animationPrompt,
                'input_image' => $base64Image,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'output_storage_uri' => $outputUri
            ];

            $endpoint = $this->getEndpoint('veo-3.1');

            $response = $this->client->predict([
                'endpoint' => $endpoint,
                'instances' => [$instance]
            ]);

            $prediction = $response->getPredictions()[0];
            $videoUri = $prediction['video_uri'] ?? $outputUri;

            // Download from GCS to local storage
            $localPath = $this->downloadFromGcs($videoUri);

            return [
                'url' => Storage::url($localPath),
                'storage_path' => $localPath,
                'gcs_uri' => $videoUri,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'source_image' => $imagePath,
                'file_size' => Storage::size($localPath),
                'cost' => $this->calculateVideoCost($duration),
                'metadata' => [
                    'animation_prompt' => $animationPrompt,
                    'source_image' => $imagePath,
                    'prediction' => $prediction
                ]
            ];
        } catch (Exception $e) {
            Log::error('Image-to-video conversion failed', [
                'image' => $imagePath,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate video with reference images for style consistency
     */
    public function generateWithReferenceImages(
        string $prompt,
        array $referenceImagePaths,
        int $duration = 7,
        string $aspectRatio = '16:9',
        ?string $orgId = null
    ): array {
        if (!$this->isConfigured()) {
            throw new Exception('Veo service not configured.');
        }

        if (count($referenceImagePaths) > 3) {
            throw new Exception('Maximum 3 reference images allowed');
        }

        try {
            $referenceImages = [];
            foreach ($referenceImagePaths as $path) {
                $imageData = Storage::get($path);
                $referenceImages[] = base64_encode($imageData);
            }

            $outputUri = $this->getStorageUri($orgId);

            $instance = [
                'text_prompt' => $prompt,
                'reference_images' => $referenceImages,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'output_storage_uri' => $outputUri
            ];

            $endpoint = $this->getEndpoint('veo-3.1');

            $response = $this->client->predict([
                'endpoint' => $endpoint,
                'instances' => [$instance]
            ]);

            $prediction = $response->getPredictions()[0];
            $videoUri = $prediction['video_uri'] ?? $outputUri;

            $localPath = $this->downloadFromGcs($videoUri);

            return [
                'url' => Storage::url($localPath),
                'storage_path' => $localPath,
                'gcs_uri' => $videoUri,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'reference_images' => $referenceImagePaths,
                'file_size' => Storage::size($localPath),
                'cost' => $this->calculateVideoCost($duration),
                'metadata' => [
                    'prompt' => $prompt,
                    'reference_images_count' => count($referenceImagePaths),
                    'prediction' => $prediction
                ]
            ];
        } catch (Exception $e) {
            Log::error('Reference image video generation failed', [
                'prompt' => $prompt,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate multiple video variations
     */
    public function batchGenerate(
        array $prompts,
        int $variationsPerPrompt = 2,
        array $options = []
    ): array {
        $jobs = [];

        foreach ($prompts as $prompt) {
            for ($i = 0; $i < $variationsPerPrompt; $i++) {
                $jobs[] = [
                    'prompt' => $prompt,
                    'variation' => $i + 1,
                    'options' => $options
                ];
            }
        }

        return $jobs;
    }

    /**
     * Get Google Cloud Storage URI for output
     */
    private function getStorageUri(?string $orgId = null): string
    {
        $orgPath = $orgId ?? 'default';
        $filename = uniqid('video_') . '.mp4';
        return "gs://{$this->storageBucket}/{$orgPath}/{$filename}";
    }

    /**
     * Get Vertex AI endpoint for model
     */
    private function getEndpoint(string $model): string
    {
        return sprintf(
            'projects/%s/locations/%s/publishers/google/models/%s',
            $this->projectId,
            $this->location,
            $model
        );
    }

    /**
     * Download video from Google Cloud Storage to local storage
     */
    private function downloadFromGcs(string $gcsUri): string
    {
        // Extract bucket and path from gs:// URI
        $uri = str_replace('gs://', '', $gcsUri);
        [$bucket, $path] = explode('/', $uri, 2);

        $bucket = $this->storageClient->bucket($bucket);
        $object = $bucket->object($path);

        $localPath = 'ai-generated/videos/' . basename($path);
        $videoData = $object->downloadAsString();

        Storage::put($localPath, $videoData);

        return $localPath;
    }

    /**
     * Calculate estimated cost for video generation
     */
    private function calculateVideoCost(int $duration, bool $isFast = false): float
    {
        // Base cost per second (estimated)
        $costPerSecond = $isFast ? 0.08 : 0.15;
        return $duration * $costPerSecond;
    }

    /**
     * Check if service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->projectId) &&
               !is_null($this->client) &&
               !is_null($this->storageClient);
    }

    /**
     * Get mock video for testing/development
     */
    public function getMockVideo(
        string $prompt,
        int $duration = 7,
        string $aspectRatio = '16:9'
    ): array {
        // Return mock data for development/testing
        $mockPath = 'ai-generated/videos/mock_' . uniqid() . '.mp4';

        // Create a placeholder file
        Storage::put($mockPath, 'MOCK VIDEO DATA');

        return [
            'url' => Storage::url($mockPath),
            'storage_path' => $mockPath,
            'gcs_uri' => "gs://{$this->storageBucket}/mock/video.mp4",
            'duration' => $duration,
            'aspect_ratio' => $aspectRatio,
            'model' => 'veo-3.1-mock',
            'file_size' => 1024,
            'cost' => 0.0,
            'is_mock' => true,
            'metadata' => [
                'prompt' => $prompt,
                'note' => 'This is a mock video for testing purposes'
            ]
        ];
    }
}
