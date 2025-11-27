@extends('layouts.admin')

@section('title', __('Dashboards'))

@section('content')
<div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                {{ __('Dashboards') }}
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('Create and manage custom dashboards') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboards.templates') }}"
               class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                {{ __('Templates') }}
            </a>
            <a href="{{ route('dashboards.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 inline-flex items-center">
                <svg class="w-5 h-5 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Create Dashboard') }}
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('dashboards.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('Search') }}
                </label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('Search dashboards') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Created By Filter -->
            <div>
                <label for="created_by" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('Created By') }}
                </label>
                <select id="created_by"
                        name="created_by"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('All Users') }}</option>
                    <option value="{{ auth()->id() }}" {{ request('created_by') == auth()->id() ? 'selected' : '' }}>
                        {{ __('My Dashboards') }}
                    </option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    {{ __('Filter') }}
                </button>
                <a href="{{ route('dashboards.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Dashboards Grid -->
    @if($dashboards->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            @foreach($dashboards as $dashboard)
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <!-- Dashboard Preview (placeholder) -->
                    <div class="h-48 bg-gradient-to-br from-blue-50 to-purple-50 flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>

                    <!-- Dashboard Info -->
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                    {{ $dashboard->name }}
                                </h3>
                                @if($dashboard->description)
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        {{ $dashboard->description }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 {{ app()->getLocale() === 'ar' ? 'ml-1' : 'mr-1' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                                    </svg>
                                    {{ $dashboard->widgets->count() }} {{ __('widgets') }}
                                </span>
                            </div>
                            <span>{{ $dashboard->updated_at->diffForHumans() }}</span>
                        </div>

                        @if($dashboard->creator)
                            <div class="text-xs text-gray-500 mb-4">
                                {{ __('Created by') }} {{ $dashboard->creator->name }}
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                            <a href="{{ route('dashboards.show', $dashboard->dashboard_id) }}"
                               class="flex-1 text-center px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                {{ __('View') }}
                            </a>
                            <a href="{{ route('dashboards.edit', $dashboard->dashboard_id) }}"
                               class="px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                                {{ __('Edit') }}
                            </a>
                            <form method="POST" action="{{ route('dashboards.duplicate', $dashboard->dashboard_id) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                                    {{ __('Duplicate') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($dashboards->hasPages())
            <div class="bg-white rounded-lg shadow-sm p-4">
                {{ $dashboards->links() }}
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('No dashboards found') }}</h3>
            <p class="text-sm text-gray-600 mb-6">
                {{ __('Get started by creating your first dashboard or using a template.') }}
            </p>
            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('dashboards.create') }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    {{ __('Create Dashboard') }}
                </a>
                <a href="{{ route('dashboards.templates') }}"
                   class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    {{ __('Browse Templates') }}
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
