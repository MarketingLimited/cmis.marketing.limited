@extends('layouts.admin')

@section('title', __('Feature Flags'))

@section('content')
<div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                {{ __('Feature Flags') }}
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('Manage feature flags, rollouts, and A/B tests') }}
            </p>
        </div>
        <a href="{{ route('feature-flags.create') }}"
           class="btn btn-primary inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700">
            <svg class="w-5 h-5 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Create Flag') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('feature-flags.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('Search') }}
                </label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('Search by name or key') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Type Filter -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('Type') }}
                </label>
                <select id="type"
                        name="type"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="boolean" {{ request('type') === 'boolean' ? 'selected' : '' }}>{{ __('Boolean') }}</option>
                    <option value="multivariate" {{ request('type') === 'multivariate' ? 'selected' : '' }}>{{ __('A/B Test') }}</option>
                    <option value="kill_switch" {{ request('type') === 'kill_switch' ? 'selected' : '' }}>{{ __('Kill Switch') }}</option>
                </select>
            </div>

            <!-- Active Only -->
            <div class="flex items-end">
                <label class="inline-flex items-center">
                    <input type="checkbox"
                           name="active_only"
                           value="1"
                           {{ request('active_only') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="{{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ __('Active Only') }}</span>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    {{ __('Filter') }}
                </button>
                <a href="{{ route('feature-flags.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('Total Flags') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $flags->total() }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('Enabled') }}</p>
                    <p class="text-2xl font-bold text-green-600">
                        {{ $flags->where('is_enabled', true)->count() }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('A/B Tests') }}</p>
                    <p class="text-2xl font-bold text-purple-600">
                        {{ $flags->where('type', 'multivariate')->count() }}
                    </p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">{{ __('Kill Switches') }}</p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ $flags->where('type', 'kill_switch')->count() }}
                    </p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Flags List -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('Flag') }}
                    </th>
                    <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('Type') }}
                    </th>
                    <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('Status') }}
                    </th>
                    <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('Rollout') }}
                    </th>
                    <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('Evaluations') }}
                    </th>
                    <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('Updated') }}
                    </th>
                    <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($flags as $flag)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $flag->name }}
                                    </div>
                                    <div class="text-sm text-gray-500 font-mono">
                                        {{ $flag->key }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $flag->type === 'boolean' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $flag->type === 'multivariate' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $flag->type === 'kill_switch' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $flag->type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $flag->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $flag->is_enabled ? __('Enabled') : __('Disabled') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($flag->rollout_percentage !== null)
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $flag->rollout_percentage }}%"></div>
                                    </div>
                                    <span>{{ $flag->rollout_percentage }}%</span>
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format($flag->evaluation_count) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $flag->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('feature-flags.show', $flag->flag_id) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    {{ __('View') }}
                                </a>
                                <a href="{{ route('feature-flags.edit', $flag->flag_id) }}"
                                   class="text-indigo-600 hover:text-indigo-900">
                                    {{ __('Edit') }}
                                </a>
                                @if($flag->is_enabled)
                                    <form method="POST" action="{{ route('feature-flags.disable', $flag->flag_id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-900">
                                            {{ __('Disable') }}
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('feature-flags.enable', $flag->flag_id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">
                                            {{ __('Enable') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                            <p class="mt-4">{{ __('No feature flags found.') }}</p>
                            <a href="{{ route('feature-flags.create') }}" class="mt-2 inline-block text-blue-600 hover:text-blue-800">
                                {{ __('Create your first feature flag') }}
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($flags->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $flags->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
