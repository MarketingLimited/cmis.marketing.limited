<?php

namespace App\Console\Traits;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Support\Facades\DB;

trait HandlesOrgContext
{
    /**
     * Execute callback for each organization with proper context
     */
    protected function executePerOrg(\Closure $callback, ?array $orgIds = null)
    {
        // Get system user
        $systemUser = User::where('email', 'system@cmis.app')->first();

        if (!$systemUser) {
            $this->error('System user not found. Please create it first.');
            $this->info('Run: php artisan tinker');
            $this->info('Then: User::create([\'email\' => \'system@cmis.app\', \'display_name\' => \'System\', \'name\' => \'System\', \'status\' => \'active\', \'role\' => \'admin\'])');
            return;
        }

        // Get organizations
        $query = Org::query()->whereNull('deleted_at');
        if ($orgIds) {
            $query->whereIn('org_id', $orgIds);
        }
        $orgs = $query->get();

        if ($orgs->isEmpty()) {
            $this->warn('No organizations found.');
            return;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($orgs as $org) {
            $this->info("🏢 Processing: {$org->name} (ID: {$org->org_id})");

            try {
                DB::transaction(function () use ($systemUser, $org, $callback) {
                    // Set database context
                    DB::statement(
                        "SELECT cmis.init_transaction_context(?, ?)",
                        [$systemUser->user_id, $org->org_id]
                    );

                    // Execute org-specific logic
                    $callback($org);
                });

                $this->info("✅ Success: {$org->name}");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("❌ Failed: {$org->name}");
                $this->error("   Error: " . $e->getMessage());
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("📊 Summary: {$successCount} succeeded, {$failCount} failed");
    }

    /**
     * Set context for single organization
     */
    protected function setOrgContext($orgId)
    {
        $systemUser = User::where('email', 'system@cmis.app')->firstOrFail();

        DB::statement(
            "SELECT cmis.init_transaction_context(?, ?)",
            [$systemUser->user_id, $orgId]
        );
    }
}
