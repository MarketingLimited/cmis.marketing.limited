@extends('layouts.admin')

@section('title', $account->account_name . ' - ' . __('Ad Accounts'))

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
            <span class="text-gray-900 font-medium">{{ $account->account_name }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            @php
                $platformIcons = [
                    'meta' => 'fab fa-facebook text-blue-600',
                    'google' => 'fab fa-google text-red-500',
                    'tiktok' => 'fab fa-tiktok text-gray-900',
                    'linkedin' => 'fab fa-linkedin text-blue-700',
                    'twitter' => 'fab fa-twitter text-sky-500',
                    'snapchat' => 'fab fa-snapchat text-yellow-500',
                ];
                $platformBgColors = [
                    'meta' => 'bg-blue-100',
                    'google' => 'bg-red-100',
                    'tiktok' => 'bg-gray-100',
                    'linkedin' => 'bg-blue-100',
                    'twitter' => 'bg-sky-100',
                    'snapchat' => 'bg-yellow-100',
                ];
            @endphp
            <div class="w-12 h-12 rounded-lg {{ $platformBgColors[$account->platform] ?? 'bg-gray-100' }} flex items-center justify-center">
                <i class="{{ $platformIcons[$account->platform] ?? 'fas fa-ad text-gray-500' }} text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $account->account_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="text-xs text-gray-500">{{ ucfirst($account->platform) }}</span>
                    <span class="text-xs text-gray-400">&bull;</span>
                    <span class="text-xs text-gray-500 font-mono">{{ $account->platform_account_id }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('orgs.settings.ad-accounts.sync', [$currentOrg, $account->id]) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                    <i class="fas fa-sync mr-1"></i>Sync Data
                </button>
            </form>
            <a href="{{ route('orgs.settings.ad-accounts.edit', [$currentOrg, $account->id]) }}"
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
        </div>
    </div>

    {{-- Account Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Total Spend</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $account->currency }} {{ number_format($stats['total_spend'] ?? 0, 2) }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">This month</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Active Campaigns</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['active_campaigns'] ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bullhorn text-blue-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ $stats['total_campaigns'] ?? 0 }} total</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Total Reach</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_reach'] ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-purple-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Last 30 days</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Avg. CPM</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $account->currency }} {{ number_format($stats['avg_cpm'] ?? 0, 2) }}</p>
                </div>
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-orange-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Cost per 1000 impressions</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Account Details --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>Account Details
            </h3>

            <div class="space-y-4">
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Platform Account ID</span>
                    <span class="text-sm font-mono text-gray-900">{{ $account->platform_account_id }}</span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Currency</span>
                    <span class="text-sm font-medium text-gray-900">{{ $account->currency }}</span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Timezone</span>
                    <span class="text-sm font-medium text-gray-900">{{ $account->timezone }}</span>
                </div>
                @if($account->monthly_budget_limit)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">Monthly Budget Limit</span>
                        <span class="text-sm font-medium text-gray-900">{{ $account->currency }} {{ number_format($account->monthly_budget_limit, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Last Synced</span>
                    <span class="text-sm text-gray-900">{{ $account->last_synced_at ? $account->last_synced_at->diffForHumans() : 'Never' }}</span>
                </div>
            </div>
        </div>

        {{-- Budget Usage --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-pie text-green-500 mr-2"></i>Budget Usage
            </h3>

            @if($account->monthly_budget_limit)
                @php
                    $spentThisMonth = $stats['monthly_spend'] ?? 0;
                    $budgetLimit = $account->monthly_budget_limit;
                    $usagePercent = $budgetLimit > 0 ? min(100, ($spentThisMonth / $budgetLimit) * 100) : 0;
                    $remaining = max(0, $budgetLimit - $spentThisMonth);
                @endphp
                <div class="space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Monthly Limit</span>
                        <span class="font-medium text-gray-900">{{ $account->currency }} {{ number_format($budgetLimit, 2) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $usagePercent > 90 ? 'bg-red-500' : ($usagePercent > 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                             style="width: {{ $usagePercent }}%"></div>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Spent: {{ $account->currency }} {{ number_format($spentThisMonth, 2) }}</span>
                        <span class="text-gray-600">Remaining: {{ $account->currency }} {{ number_format($remaining, 2) }}</span>
                    </div>
                    <p class="text-center text-lg font-bold {{ $usagePercent > 90 ? 'text-red-600' : ($usagePercent > 70 ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ number_format($usagePercent, 1) }}% used
                    </p>
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-infinity text-4xl text-gray-300 mb-3"></i>
                    <p class="text-sm text-gray-500">No budget limit set</p>
                    <a href="{{ route('orgs.settings.ad-accounts.edit', [$currentOrg, $account->id]) }}"
                       class="text-sm text-green-600 hover:text-green-700 mt-2 inline-block">Set a limit</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Associated Profile Group --}}
    @if($account->profileGroup)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-layer-group text-indigo-500 mr-2"></i>Associated Profile Group
            </h3>
            <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $account->profileGroup->group_id]) }}"
               class="inline-flex items-center gap-3 p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $account->profileGroup->name }}</p>
                    <p class="text-xs text-gray-500">{{ $account->profileGroup->profiles_count ?? 0 }} profiles</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 ml-auto"></i>
            </a>
        </div>
    @endif

    {{-- Linked Boost Rules --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">
                <i class="fas fa-rocket text-orange-500 mr-2"></i>Linked Boost Rules
            </h3>
            <a href="{{ route('orgs.settings.boost-rules.create', $currentOrg) }}?ad_account={{ $account->id }}"
               class="text-sm text-orange-600 hover:text-orange-700">
                <i class="fas fa-plus mr-1"></i>Create Rule
            </a>
        </div>

        @if(($boostRules ?? collect())->count() > 0)
            <div class="space-y-3">
                @foreach($boostRules as $rule)
                    <a href="{{ route('orgs.settings.boost-rules.show', [$currentOrg, $rule->rule_id]) }}"
                       class="flex items-center justify-between p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-rocket text-orange-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $rule->name }}</p>
                                <p class="text-xs text-gray-500">{{ $rule->trigger_threshold }}{{ $rule->trigger_metric === 'engagement_rate' ? '%' : '' }} {{ str_replace('_', ' ', $rule->trigger_metric) }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $rule->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $rule->is_active ? 'Active' : 'Paused' }}
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-4">No boost rules linked to this account</p>
        @endif
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-history text-gray-500 mr-2"></i>Recent Activity
        </h3>

        @if(($recentActivity ?? collect())->count() > 0)
            <div class="space-y-3">
                @foreach($recentActivity as $activity)
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-{{ $activity->type === 'spend' ? 'green' : 'blue' }}-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-{{ $activity->type === 'spend' ? 'dollar-sign' : 'bullhorn' }} text-{{ $activity->type === 'spend' ? 'green' : 'blue' }}-600 text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-4">No recent activity</p>
        @endif
    </div>

    {{-- Metadata --}}
    <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-500">
        <div class="flex flex-wrap gap-6">
            <div>
                <span class="text-gray-400">Connected:</span>
                <span class="ml-1">{{ $account->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Last Updated:</span>
                <span class="ml-1">{{ $account->updated_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Account ID:</span>
                <span class="ml-1 font-mono">{{ $account->id }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
