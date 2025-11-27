@extends('layouts.admin')

@section('title', __('Boost Rules') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Boost Rules') }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Boost Rules</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Automatically boost high-performing organic posts based on engagement metrics.
            </p>
        </div>
        <a href="{{ route('orgs.settings.boost-rules.create', $currentOrg) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700">
            <i class="fas fa-plus mr-2"></i>Create Rule
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex"><i class="fas fa-check-circle text-green-400 mr-3"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($boostRules->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($boostRules as $rule)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                                <i class="fas fa-rocket text-orange-600"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-base font-semibold text-gray-900">{{ $rule->name }}</h3>
                                <p class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $rule->trigger_type)) }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full {{ $rule->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $rule->is_active ? 'Active' : 'Paused' }}
                        </span>
                    </div>

                    {{-- Trigger Info --}}
                    <div class="bg-gray-50 rounded-md p-3 mb-4 text-xs">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-500">Trigger:</span>
                            <span class="font-medium text-gray-700">{{ $rule->trigger_threshold }} {{ $rule->trigger_metric }}</span>
                        </div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-500">Budget:</span>
                            <span class="font-medium text-gray-700">{{ number_format($rule->budget_amount, 2) }} {{ $rule->budget_currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Duration:</span>
                            <span class="font-medium text-gray-700">{{ $rule->duration_hours }}h</span>
                        </div>
                    </div>

                    @if($rule->profileGroup)
                        <div class="text-xs text-gray-500 mb-4">
                            <i class="fas fa-layer-group mr-1"></i>{{ $rule->profileGroup->name }}
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                        <a href="{{ route('orgs.settings.boost-rules.show', [$currentOrg, $rule->boost_rule_id]) }}"
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                            View
                        </a>
                        <a href="{{ route('orgs.settings.boost-rules.edit', [$currentOrg, $rule->boost_rule_id]) }}"
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-xs font-medium text-white bg-orange-600 hover:bg-orange-700">
                            Edit
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-rocket text-orange-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Boost Rules Yet</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                Create rules to automatically promote posts that perform well organically.
            </p>
            <a href="{{ route('orgs.settings.boost-rules.create', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700">
                <i class="fas fa-plus mr-2"></i>Create First Rule
            </a>
        </div>
    @endif
</div>
@endsection
