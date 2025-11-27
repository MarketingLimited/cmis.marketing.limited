<?php

namespace App\Jobs\Social;

use App\Models\Social\BrandKnowledgeConfig;
use App\Services\Social\KnowledgeBaseConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Build Knowledge Base Job
 *
 * Asynchronously builds or updates a brand knowledge base for a profile group.
 */
class BuildKnowledgeBaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;

    private string $orgId;
    private string $profileGroupId;
    private array $options;
    private ?string $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $orgId,
        string $profileGroupId,
        array $options = [],
        ?string $userId = null
    ) {
        $this->orgId = $orgId;
        $this->profileGroupId = $profileGroupId;
        $this->options = $options;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(KnowledgeBaseConversionService $service): void
    {
        try {
            // Set org context for RLS
            \DB::statement("SET app.current_org_id = '{$this->orgId}'");

            Log::info('Building knowledge base', [
                'org_id' => $this->orgId,
                'profile_group_id' => $this->profileGroupId,
                'options' => $this->options,
            ]);

            $result = $service->buildKnowledgeBase(
                $this->orgId,
                $this->profileGroupId,
                $this->options
            );

            Log::info('Knowledge base build completed', [
                'org_id' => $this->orgId,
                'profile_group_id' => $this->profileGroupId,
                'posts_added' => $result['posts_added'],
                'core_dimensions' => $result['core_dimensions'],
                'strategy' => $result['strategy'],
            ]);

            // TODO: Send notification to user
            // if ($this->userId) {
            //     $user = User::find($this->userId);
            //     $user->notify(new KnowledgeBaseReady($result));
            // }

        } catch (\Exception $e) {
            Log::error('Knowledge base build job failed', [
                'org_id' => $this->orgId,
                'profile_group_id' => $this->profileGroupId,
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
        Log::error('Knowledge base build job failed permanently', [
            'org_id' => $this->orgId,
            'profile_group_id' => $this->profileGroupId,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Notify user of failure
    }

    /**
     * Dispatch auto-build jobs for all ready profile groups
     */
    public static function dispatchAutoBuilds(string $orgId): int
    {
        $readyConfigs = BrandKnowledgeConfig::where('org_id', $orgId)
            ->readyForAutoBuild()
            ->whereNull('kb_built_at')
            ->get();

        $dispatched = 0;

        foreach ($readyConfigs as $config) {
            self::dispatch(
                $orgId,
                $config->profile_group_id,
                ['strategy' => 'balanced'],
                null
            );
            $dispatched++;
        }

        Log::info('Dispatched auto-build jobs', [
            'org_id' => $orgId,
            'count' => $dispatched,
        ]);

        return $dispatched;
    }
}
