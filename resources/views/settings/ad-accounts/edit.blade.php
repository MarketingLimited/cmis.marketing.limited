@extends('layouts.admin')

@section('title', __('Edit') . ' ' . $account->account_name . ' - ' . __('Ad Accounts'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.ad-accounts.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Ad Accounts') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.ad-accounts.show', [$currentOrg, $account->id]) }}" class="hover:text-blue-600 transition">{{ $account->account_name }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Edit') }}</span>
        </nav>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Ad Account</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Update account settings and budget limits.
            </p>
        </div>
    </div>

    <form action="{{ route('orgs.settings.ad-accounts.update', [$currentOrg, $account->id]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Account Information (Read-only) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>Account Information
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                These details are synced from the platform and cannot be modified here.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        @php
                            $platformIcons = [
                                'meta' => 'fab fa-facebook text-blue-600',
                                'google' => 'fab fa-google text-red-500',
                                'tiktok' => 'fab fa-tiktok text-gray-900',
                                'linkedin' => 'fab fa-linkedin text-blue-700',
                                'twitter' => 'fab fa-twitter text-sky-500',
                                'snapchat' => 'fab fa-snapchat text-yellow-500',
                            ];
                        @endphp
                        <i class="{{ $platformIcons[$account->platform] ?? 'fas fa-ad text-gray-500' }}"></i>
                        <span class="text-sm text-gray-900">{{ ucfirst($account->platform) }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform Account ID</label>
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-sm font-mono text-gray-900">{{ $account->platform_account_id }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-sm text-gray-900">{{ $account->currency }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-sm text-gray-900">{{ $account->timezone }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Editable Settings --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-cog text-gray-500 mr-2"></i>Account Settings
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="account_name" class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                    <input type="text" name="account_name" id="account_name" value="{{ old('account_name', $account->account_name) }}"
                           class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500">
                    <p class="mt-1 text-xs text-gray-500">A friendly name to identify this account</p>
                    @error('account_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="profile_group_id" class="block text-sm font-medium text-gray-700 mb-1">Profile Group</label>
                    <select name="profile_group_id" id="profile_group_id"
                            class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500">
                        <option value="">No Profile Group</option>
                        @foreach($profileGroups ?? [] as $group)
                            <option value="{{ $group->group_id }}" {{ old('profile_group_id', $account->profile_group_id) == $group->group_id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Associate this ad account with a profile group</p>
                </div>

                <div>
                    <label for="monthly_budget_limit" class="block text-sm font-medium text-gray-700 mb-1">Monthly Budget Limit</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">{{ $account->currency }}</span>
                        <input type="number" name="monthly_budget_limit" id="monthly_budget_limit"
                               value="{{ old('monthly_budget_limit', $account->monthly_budget_limit) }}"
                               min="0" step="0.01"
                               class="w-full pl-14 rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500"
                               placeholder="No limit">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Maximum spend per month (leave empty for no limit)</p>
                </div>
            </div>
        </div>

        {{-- Notifications --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-bell text-yellow-500 mr-2"></i>Notifications
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Budget Alert</p>
                        <p class="text-xs text-gray-500">Notify when spending reaches a threshold</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="notify_budget_alert" value="1" {{ old('notify_budget_alert', $account->settings['notify_budget_alert'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>

                <div class="pl-4">
                    <label for="budget_alert_threshold" class="block text-sm font-medium text-gray-700 mb-1">Alert at % of Budget</label>
                    <select name="budget_alert_threshold" id="budget_alert_threshold"
                            class="w-full md:w-48 rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500">
                        <option value="50" {{ old('budget_alert_threshold', $account->settings['budget_alert_threshold'] ?? 80) == 50 ? 'selected' : '' }}>50%</option>
                        <option value="70" {{ old('budget_alert_threshold', $account->settings['budget_alert_threshold'] ?? 80) == 70 ? 'selected' : '' }}>70%</option>
                        <option value="80" {{ old('budget_alert_threshold', $account->settings['budget_alert_threshold'] ?? 80) == 80 ? 'selected' : '' }}>80%</option>
                        <option value="90" {{ old('budget_alert_threshold', $account->settings['budget_alert_threshold'] ?? 80) == 90 ? 'selected' : '' }}>90%</option>
                        <option value="95" {{ old('budget_alert_threshold', $account->settings['budget_alert_threshold'] ?? 80) == 95 ? 'selected' : '' }}>95%</option>
                    </select>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Daily Spend Report</p>
                        <p class="text-xs text-gray-500">Receive daily spending summary</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="daily_spend_report" value="1" {{ old('daily_spend_report', $account->settings['daily_spend_report'] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Performance Alerts</p>
                        <p class="text-xs text-gray-500">Alert on significant performance changes</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="performance_alerts" value="1" {{ old('performance_alerts', $account->settings['performance_alerts'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Account Status</h3>
                    <p class="text-sm text-gray-500">Enable or disable this ad account</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <form action="{{ route('orgs.settings.ad-accounts.destroy', [$currentOrg, $account->id]) }}" method="POST"
                  onsubmit="return confirm('Remove this ad account? This will not affect your ads on the platform.');">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-red-600 hover:text-red-700 text-sm font-medium">
                    <i class="fas fa-unlink mr-1"></i>Disconnect Account
                </button>
            </form>

            <div class="flex items-center gap-3">
                <a href="{{ route('orgs.settings.ad-accounts.show', [$currentOrg, $account->id]) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
