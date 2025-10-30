<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\SocialAccount;
use App\Services\Social\InstagramSyncService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class InstagramApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:api
        {account : Instagram account identifier (ID, username, or integration id)}
        {operation : Operation to perform (profile|insights|media|media-insights|sync|all)}
        {--by=id : Identifier type: id, username, or integration}
        {--media_id= : Instagram media id (required for media-insights operation)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute Instagram Graph API operations for a specific account using stored credentials.';

    public function __construct(private readonly InstagramSyncService $instagramSyncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $accountIdentifier = (string) $this->argument('account');
        $operation = strtolower((string) $this->argument('operation'));
        $identifierType = strtolower((string) $this->option('by'));

        $integration = $this->resolveIntegration($accountIdentifier, $identifierType);

        if (! $integration) {
            $this->error(sprintf('Unable to locate Instagram integration for "%s" using "%s" lookup.', $accountIdentifier, $identifierType));

            return self::FAILURE;
        }

        if (empty($integration->access_token)) {
            $this->error('Integration does not contain an access token.');

            return self::FAILURE;
        }

        try {
            return $this->performOperation($operation, $integration);
        } catch (Throwable $exception) {
            $this->error(sprintf('Instagram API operation failed: %s', $exception->getMessage()));

            return self::FAILURE;
        }
    }

    protected function resolveIntegration(string $identifier, string $type): ?Integration
    {
        return match ($type) {
            'username' => $this->findIntegrationByUsername($identifier),
            'integration' => $this->findIntegrationByIntegrationId($identifier),
            default => $this->findIntegrationByAccountId($identifier),
        };
    }

    protected function findIntegrationByUsername(string $username): ?Integration
    {
        $socialAccount = SocialAccount::query()
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
            ->orderByDesc('fetched_at')
            ->first();

        if (! $socialAccount) {
            return null;
        }

        return Integration::query()
            ->platform('instagram')
            ->where('integration_id', $socialAccount->integration_id)
            ->first();
    }

    protected function findIntegrationByIntegrationId(string $integrationId): ?Integration
    {
        return Integration::query()
            ->platform('instagram')
            ->where('integration_id', $integrationId)
            ->first();
    }

    protected function findIntegrationByAccountId(string $accountId): ?Integration
    {
        return Integration::query()
            ->platform('instagram')
            ->where(function (Builder $query) use ($accountId) {
                $query
                    ->where('account_id', $accountId)
                    ->orWhere('integration_id', $accountId);
            })
            ->first();
    }

    protected function performOperation(string $operation, Integration $integration): int
    {
        return match ($operation) {
            'profile' => $this->displayJson(
                $this->instagramSyncService->getAccountProfileData($integration)
            ),
            'insights' => $this->displayJson(
                $this->instagramSyncService->getAccountInsightsData($integration)
            ),
            'media' => $this->displayJson(
                $this->instagramSyncService->getAccountMediaData($integration)
            ),
            'media-insights' => $this->runMediaInsights($integration),
            'sync' => $this->runSync($integration),
            'all' => $this->runAll($integration),
            default => $this->unknownOperation($operation),
        };
    }

    protected function runMediaInsights(Integration $integration): int
    {
        $mediaId = (string) $this->option('media_id');

        if ($mediaId === '') {
            $this->error('The --media_id option is required for the media-insights operation.');

            return self::FAILURE;
        }

        return $this->displayJson(
            $this->instagramSyncService->getMediaInsightsData($integration, $mediaId)
        );
    }

    protected function runSync(Integration $integration): int
    {
        $this->instagramSyncService->syncIntegration($integration);
        $this->info(sprintf('Integration %s synced successfully.', $integration->integration_id));

        return self::SUCCESS;
    }

    protected function runAll(Integration $integration): int
    {
        $results = [
            'profile' => $this->instagramSyncService->getAccountProfileData($integration),
            'insights' => $this->instagramSyncService->getAccountInsightsData($integration),
            'media' => $this->instagramSyncService->getAccountMediaData($integration),
        ];

        $this->displayJson($results);

        return self::SUCCESS;
    }

    protected function unknownOperation(string $operation): int
    {
        $this->error(sprintf('Unknown operation "%s". Use one of: profile, insights, media, media-insights, sync, all.', $operation));

        return self::FAILURE;
    }

    protected function displayJson(array $payload): int
    {
        if (empty($payload)) {
            $this->info('No data returned from Instagram API.');

            return self::SUCCESS;
        }

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
