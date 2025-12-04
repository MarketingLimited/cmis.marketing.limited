<?php

namespace App\Observers;

use App\Models\Core\Integration;
use App\Models\Platform\BoostRule;
use Illuminate\Support\Facades\Log;

/**
 * Observer for Integration model.
 *
 * Handles cascade operations when integrations (profiles) are soft deleted.
 */
class IntegrationObserver
{
    /**
     * Handle the Integration "deleting" event (before soft delete).
     * Cascade soft delete to related entities.
     *
     * @param Integration $integration
     * @return void
     */
    public function deleting(Integration $integration): void
    {
        // Soft delete queue settings
        if ($integration->queueSettings) {
            $integration->queueSettings->delete();

            Log::debug('Cascade deleted queue settings for integration', [
                'integration_id' => $integration->integration_id,
            ]);
        }

        // Remove from boost rules' apply_to_social_profiles array
        $this->removeFromBoostRules($integration);
    }

    /**
     * Handle the Integration "restoring" event.
     * Cascade restore to related entities.
     *
     * @param Integration $integration
     * @return void
     */
    public function restoring(Integration $integration): void
    {
        // Restore queue settings if they were soft deleted
        $integration->queueSettings()->withTrashed()->restore();

        Log::debug('Cascade restored queue settings for integration', [
            'integration_id' => $integration->integration_id,
        ]);

        // Note: Boost rules are not automatically restored to prevent
        // unintended re-activation of automation rules
    }

    /**
     * Remove integration from boost rules' apply_to_social_profiles array.
     *
     * @param Integration $integration
     * @return void
     */
    protected function removeFromBoostRules(Integration $integration): void
    {
        $updatedRulesCount = 0;

        BoostRule::where('org_id', $integration->org_id)
            ->whereJsonContains('apply_to_social_profiles', $integration->integration_id)
            ->each(function ($rule) use ($integration, &$updatedRulesCount) {
                $profiles = collect($rule->apply_to_social_profiles ?? [])
                    ->reject(fn($id) => $id === $integration->integration_id)
                    ->values()
                    ->toArray();

                $rule->update(['apply_to_social_profiles' => $profiles]);
                $updatedRulesCount++;
            });

        if ($updatedRulesCount > 0) {
            Log::debug('Removed integration from boost rules', [
                'integration_id' => $integration->integration_id,
                'updated_rules_count' => $updatedRulesCount,
            ]);
        }
    }
}
