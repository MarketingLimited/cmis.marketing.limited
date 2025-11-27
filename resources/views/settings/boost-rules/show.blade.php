@extends('layouts.admin')

@section('title', $rule->name . ' - ' . __('Boost Rules'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.boost-rules.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Boost Rules') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $rule->name }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
                <i class="fas fa-rocket text-orange-600 text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $rule->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $rule->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $rule->is_active ? 'Active' : 'Paused' }}
                    </span>
                    <span class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $rule->trigger_type)) }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('orgs.settings.boost-rules.toggle', [$currentOrg, $rule->boost_rule_id]) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 border {{ $rule->is_active ? 'border-yellow-300 text-yellow-600 hover:bg-yellow-50' : 'border-green-300 text-green-600 hover:bg-green-50' }} rounded-lg transition text-sm font-medium">
                    <i class="fas {{ $rule->is_active ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                    {{ $rule->is_active ? 'Pause' : 'Activate' }}
                </button>
            </form>
            <a href="{{ route('orgs.settings.boost-rules.edit', [$currentOrg, $rule->boost_rule_id]) }}"
               class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm font-medium">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Total Boosts</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_boosts'] ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-rocket text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Total Spend</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($stats['total_spend'] ?? 0, 2) }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Avg. Reach</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['avg_reach'] ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['this_month'] ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Trigger Configuration --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-bolt text-yellow-500 mr-2"></i>Trigger Configuration
            </h3>

            <div class="space-y-4">
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Trigger Type</span>
                    <span class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $rule->trigger_type)) }}</span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Metric</span>
                    <span class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $rule->trigger_metric)) }}</span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Threshold</span>
                    <span class="text-sm font-medium text-gray-900">{{ $rule->trigger_threshold }}{{ $rule->trigger_metric === 'engagement_rate' ? '%' : '' }}</span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Evaluation Period</span>
                    <span class="text-sm font-medium text-gray-900">{{ $rule->settings['evaluation_period_hours'] ?? 24 }} hours</span>
                </div>
            </div>
        </div>

        {{-- Budget Configuration --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-dollar-sign text-green-500 mr-2"></i>Budget Configuration
            </h3>

            <div class="space-y-4">
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Budget Per Boost</span>
                    <span class="text-sm font-medium text-gray-900">{{ number_format($rule->budget_amount, 2) }} {{ $rule->budget_currency }}</span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Duration</span>
                    <span class="text-sm font-medium text-gray-900">{{ $rule->duration_hours }} hours</span>
                </div>
                @if($rule->settings['daily_budget_cap'] ?? null)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">Daily Cap</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($rule->settings['daily_budget_cap'], 2) }} {{ $rule->budget_currency }}</span>
                    </div>
                @endif
                @if($rule->settings['monthly_budget_cap'] ?? null)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">Monthly Cap</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($rule->settings['monthly_budget_cap'], 2) }} {{ $rule->budget_currency }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Targeting --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-bullseye text-red-500 mr-2"></i>Targeting
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-xs text-gray-500 mb-1">Audience Type</p>
                <p class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $rule->targeting['type'] ?? 'page_engagers')) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Age Range</p>
                <p class="text-sm font-medium text-gray-900">{{ $rule->targeting['min_age'] ?? 18 }} - {{ $rule->targeting['max_age'] ?? 65 }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Locations</p>
                @if(!empty($rule->targeting['locations']))
                    <div class="flex flex-wrap gap-1">
                        @foreach($rule->targeting['locations'] as $location)
                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded-full">{{ $location }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-900">Worldwide</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Associated Entities --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($rule->profileGroup)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">
                    <i class="fas fa-layer-group text-indigo-500 mr-2"></i>Profile Group
                </h3>
                <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $rule->profileGroup->group_id]) }}"
                   class="inline-flex items-center gap-3 p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition w-full">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-layer-group text-indigo-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $rule->profileGroup->name }}</p>
                        <p class="text-xs text-gray-500">{{ $rule->profileGroup->profiles_count ?? 0 }} profiles</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        @endif

        @if($rule->adAccount)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">
                    <i class="fas fa-ad text-green-500 mr-2"></i>Ad Account
                </h3>
                <a href="{{ route('orgs.settings.ad-accounts.show', [$currentOrg, $rule->adAccount->account_id]) }}"
                   class="inline-flex items-center gap-3 p-3 bg-green-50 rounded-lg hover:bg-green-100 transition w-full">
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                        <i class="fas fa-ad text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $rule->adAccount->account_name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($rule->adAccount->platform) }}</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        @endif
    </div>

    {{-- Recent Boosts --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">
                <i class="fas fa-history text-gray-500 mr-2"></i>Recent Boosts
            </h3>
        </div>

        @if(($recentBoosts ?? collect())->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Post</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Triggered</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Spend</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reach</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentBoosts as $boost)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ Str::limit($boost->post_content ?? 'N/A', 40) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $boost->created_at->format('M d, H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">${{ number_format($boost->spend ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($boost->reach ?? 0) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $boost->status === 'active' ? 'bg-green-100 text-green-700' : ($boost->status === 'completed' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ ucfirst($boost->status ?? 'pending') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-8">No boosts triggered yet</p>
        @endif
    </div>

    {{-- Metadata --}}
    <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-500">
        <div class="flex flex-wrap gap-6">
            <div>
                <span class="text-gray-400">Created:</span>
                <span class="ml-1">{{ $rule->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Last Updated:</span>
                <span class="ml-1">{{ $rule->updated_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Rule ID:</span>
                <span class="ml-1 font-mono">{{ $rule->boost_rule_id }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
