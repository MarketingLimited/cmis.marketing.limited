<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\AdAccount;
use App\Models\Social\ProfileGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdAccountSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of ad accounts.
     */
    public function index(Request $request, string $org)
    {
        $adAccounts = AdAccount::where('org_id', $org)
            ->with(['profileGroup'])
            ->orderBy('provider')
            ->orderBy('name')
            ->get();

        // Group by provider for easier display
        $accountsByPlatform = $adAccounts->groupBy('provider');

        if ($request->wantsJson()) {
            return $this->success($adAccounts, 'Ad accounts retrieved successfully');
        }

        return view('settings.ad-accounts.index', [
            'adAccounts' => $adAccounts,
            'accountsByPlatform' => $accountsByPlatform,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new ad account.
     */
    public function create(Request $request, string $org)
    {
        $profileGroups = ProfileGroup::where('org_id', $org)->get();

        return view('settings.ad-accounts.create', [
            'currentOrg' => $org,
            'profileGroups' => $profileGroups,
            'platforms' => $this->getPlatforms(),
            'currencies' => $this->getCurrencies(),
        ]);
    }

    /**
     * Store a newly created ad account.
     */
    public function store(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|max:50',
            'platform_account_id' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'currency' => 'required|string|max:3',
            'timezone' => 'required|string|max:50',
            'daily_budget_limit' => 'nullable|numeric|min:0',
            'monthly_budget_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $adAccount = AdAccount::create([
                'org_id' => $org,
                'platform' => $request->input('platform'),
                'platform_account_id' => $request->input('platform_account_id'),
                'account_name' => $request->input('account_name'),
                'profile_group_id' => $request->input('profile_group_id'),
                'currency' => $request->input('currency'),
                'timezone' => $request->input('timezone'),
                'daily_budget_limit' => $request->input('daily_budget_limit'),
                'monthly_budget_limit' => $request->input('monthly_budget_limit'),
                'is_active' => $request->input('is_active', true),
                'status' => 'active',
            ]);

            if ($request->wantsJson()) {
                return $this->created($adAccount, 'Ad account created successfully');
            }

            return redirect()->route('orgs.settings.ad-accounts.show', ['org' => $org, 'account' => $adAccount->account_id])
                ->with('success', __('settings.created_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create ad account: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create ad account'])->withInput();
        }
    }

    /**
     * Display the specified ad account.
     */
    public function show(Request $request, string $org, string $account)
    {
        $adAccount = AdAccount::where('org_id', $org)
            ->where('id', $account)
            ->with(['profileGroup', 'boostRules'])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($adAccount, 'Ad account retrieved successfully');
        }

        return view('settings.ad-accounts.show', [
            'account' => $adAccount,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified ad account.
     */
    public function edit(Request $request, string $org, string $account)
    {
        $adAccount = AdAccount::where('org_id', $org)
            ->where('id', $account)
            ->firstOrFail();

        $profileGroups = ProfileGroup::where('org_id', $org)->get();

        return view('settings.ad-accounts.edit', [
            'account' => $adAccount,
            'currentOrg' => $org,
            'profileGroups' => $profileGroups,
            'platforms' => $this->getPlatforms(),
            'currencies' => $this->getCurrencies(),
        ]);
    }

    /**
     * Update the specified ad account.
     */
    public function update(Request $request, string $org, string $account)
    {
        $adAccount = AdAccount::where('org_id', $org)
            ->where('id', $account)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string|max:255',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'currency' => 'required|string|max:3',
            'timezone' => 'required|string|max:50',
            'daily_budget_limit' => 'nullable|numeric|min:0',
            'monthly_budget_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $adAccount->update($request->only([
                'account_name',
                'profile_group_id',
                'currency',
                'timezone',
                'daily_budget_limit',
                'monthly_budget_limit',
                'is_active',
            ]));

            if ($request->wantsJson()) {
                return $this->success($adAccount, 'Ad account updated successfully');
            }

            return redirect()->route('orgs.settings.ad-accounts.show', ['org' => $org, 'account' => $account])
                ->with('success', __('settings.updated_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update ad account: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update ad account'])->withInput();
        }
    }

    /**
     * Remove the specified ad account.
     */
    public function destroy(Request $request, string $org, string $account)
    {
        $adAccount = AdAccount::where('org_id', $org)
            ->where('id', $account)
            ->firstOrFail();

        try {
            $adAccount->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Ad account deleted successfully');
            }

            return redirect()->route('orgs.settings.ad-accounts.index', ['org' => $org])
                ->with('success', __('settings.deleted_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete ad account: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete ad account']);
        }
    }

    /**
     * Get available platforms.
     */
    private function getPlatforms(): array
    {
        return [
            'meta' => 'Meta (Facebook/Instagram)',
            'google' => 'Google Ads',
            'tiktok' => 'TikTok Ads',
            'linkedin' => 'LinkedIn Ads',
            'twitter' => 'Twitter/X Ads',
            'snapchat' => 'Snapchat Ads',
            'pinterest' => 'Pinterest Ads',
        ];
    }

    /**
     * Get available currencies.
     */
    private function getCurrencies(): array
    {
        return [
            'USD' => 'US Dollar (USD)',
            'EUR' => 'Euro (EUR)',
            'GBP' => 'British Pound (GBP)',
            'CAD' => 'Canadian Dollar (CAD)',
            'AUD' => 'Australian Dollar (AUD)',
            'JPY' => 'Japanese Yen (JPY)',
            'CNY' => 'Chinese Yuan (CNY)',
            'INR' => 'Indian Rupee (INR)',
            'AED' => 'UAE Dirham (AED)',
            'SAR' => 'Saudi Riyal (SAR)',
        ];
    }

    /**
     * Sync ad account data from the platform.
     */
    public function sync(Request $request, string $org, string $account)
    {
        $adAccount = AdAccount::where('org_id', $org)
            ->where('id', $account)
            ->firstOrFail();

        try {
            // Get the related integration for this ad account
            $integration = \App\Models\Integration::where('org_id', $org)
                ->where('platform', $adAccount->platform)
                ->where('is_active', true)
                ->first();

            if ($integration) {
                // Use the platform-specific service to sync account data
                $platformService = \App\Services\AdPlatforms\AdPlatformFactory::make($integration);
                $syncResult = $platformService->syncAccount();

                if ($syncResult['success']) {
                    // Update ad account with synced data
                    $adAccount->update([
                        'account_name' => $syncResult['data']['name'] ?? $adAccount->account_name,
                        'currency' => $syncResult['data']['currency'] ?? $adAccount->currency,
                        'timezone' => $syncResult['data']['timezone'] ?? $adAccount->timezone,
                        'status' => $syncResult['data']['status'] ?? $adAccount->status,
                        'metadata' => array_merge($adAccount->metadata ?? [], [
                            'spend_cap' => $syncResult['data']['spend_cap'] ?? null,
                            'amount_spent' => $syncResult['data']['amount_spent'] ?? null,
                            'balance' => $syncResult['data']['balance'] ?? null,
                        ]),
                        'last_synced_at' => now(),
                    ]);
                } else {
                    throw new \Exception($syncResult['error'] ?? 'Sync failed');
                }
            } else {
                // No integration found, just update sync timestamp
                $adAccount->update(['last_synced_at' => now()]);
            }

            if ($request->wantsJson()) {
                return $this->success($adAccount->fresh(), 'Ad account synced successfully');
            }

            return redirect()->route('orgs.settings.ad-accounts.show', ['org' => $org, 'account' => $account])
                ->with('success', __('settings.ad_account_synced_successfully'));
        } catch (\App\Exceptions\FeatureDisabledException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 403);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to sync ad account: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to sync ad account: ' . $e->getMessage()]);
        }
    }

    /**
     * Import ad accounts from an existing platform connection.
     */
    public function import(Request $request, string $org, string $connection)
    {
        try {
            // Find the platform connection/integration
            $integration = \App\Models\Integration::where('org_id', $org)
                ->where('integration_id', $connection)
                ->firstOrFail();

            // Use the platform-specific service to fetch ad accounts
            $platformService = \App\Services\AdPlatforms\AdPlatformFactory::make($integration);

            // Test connection first
            $testResult = $platformService->testConnection();
            if (!$testResult['success']) {
                throw new \Exception(__('settings.platform_connection_failed', ['error' => ($testResult['error'] ?? 'Unknown error')]));
            }

            // Sync account to get ad account list
            $syncResult = $platformService->syncAccount();

            if (!$syncResult['success']) {
                throw new \Exception(__('settings.ad_accounts_fetch_failed', ['error' => ($syncResult['error'] ?? 'Unknown error')]));
            }

            $importedAccounts = [];
            $adAccountsData = $syncResult['data']['ad_accounts'] ?? [$syncResult['data']];

            foreach ($adAccountsData as $accountData) {
                // Check if account already exists
                $existingAccount = AdAccount::where('org_id', $org)
                    ->where('platform', $integration->platform)
                    ->where('platform_account_id', $accountData['id'] ?? $accountData['account_id'] ?? '')
                    ->first();

                if ($existingAccount) {
                    // Update existing account
                    $existingAccount->update([
                        'account_name' => $accountData['name'] ?? $existingAccount->account_name,
                        'currency' => $accountData['currency'] ?? $existingAccount->currency,
                        'timezone' => $accountData['timezone'] ?? $existingAccount->timezone,
                        'status' => 'active',
                        'last_synced_at' => now(),
                    ]);
                    $importedAccounts[] = $existingAccount;
                } else {
                    // Create new account
                    $newAccount = AdAccount::create([
                        'org_id' => $org,
                        'platform' => $integration->platform,
                        'platform_account_id' => $accountData['id'] ?? $accountData['account_id'] ?? '',
                        'account_name' => $accountData['name'] ?? 'Imported Account',
                        'currency' => $accountData['currency'] ?? 'USD',
                        'timezone' => $accountData['timezone'] ?? 'UTC',
                        'status' => 'active',
                        'is_active' => true,
                        'last_synced_at' => now(),
                        'metadata' => [
                            'imported_from' => $integration->integration_id,
                            'imported_at' => now()->toIso8601String(),
                        ],
                    ]);
                    $importedAccounts[] = $newAccount;
                }
            }

            if ($request->wantsJson()) {
                return $this->success([
                    'imported' => count($importedAccounts),
                    'accounts' => $importedAccounts,
                ], count($importedAccounts) . ' ad account(s) imported successfully');
            }

            return redirect()->route('orgs.settings.ad-accounts.index', ['org' => $org])
                ->with('success', count($importedAccounts) . ' ad account(s) imported successfully');
        } catch (\App\Exceptions\FeatureDisabledException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 403);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return $this->notFound('Platform connection not found');
            }
            return back()->withErrors(['error' => 'Platform connection not found']);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to import ad accounts: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to import ad accounts: ' . $e->getMessage()]);
        }
    }
}
