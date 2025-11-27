@extends('layouts.admin')

@section('title', __('Anomalies'))

@section('content')
<div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ __('Performance Anomalies') }}
        </h1>
        <a href="{{ route('anomalies.analytics') }}" class="btn btn-secondary">
            <i class="fas fa-chart-bar {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
            {{ __('View Analytics') }}
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-red-500 p-3">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                </div>
                <div class="{{ app()->getLocale() == 'ar' ? 'mr-4' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Critical') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $anomalies->where('severity', 'critical')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-orange-500 p-3">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
                <div class="{{ app()->getLocale() == 'ar' ? 'mr-4' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('High') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $anomalies->where('severity', 'high')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-yellow-500 p-3">
                        <i class="fas fa-info-circle text-white text-xl"></i>
                    </div>
                </div>
                <div class="{{ app()->getLocale() == 'ar' ? 'mr-4' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Medium') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $anomalies->where('severity', 'medium')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-green-500 p-3">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <div class="{{ app()->getLocale() == 'ar' ? 'mr-4' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Resolved') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $anomalies->where('status', 'resolved')->count() }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('anomalies.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Status') }}
                </label>
                <select name="status" class="form-select w-full">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="detected">{{ __('Detected') }}</option>
                    <option value="investigating">{{ __('Investigating') }}</option>
                    <option value="resolved">{{ __('Resolved') }}</option>
                    <option value="false_positive">{{ __('False Positive') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Severity') }}
                </label>
                <select name="severity" class="form-select w-full">
                    <option value="">{{ __('All Severities') }}</option>
                    <option value="critical">{{ __('Critical') }}</option>
                    <option value="high">{{ __('High') }}</option>
                    <option value="medium">{{ __('Medium') }}</option>
                    <option value="low">{{ __('Low') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Entity Type') }}
                </label>
                <input type="text" name="entity_type" class="form-input w-full" placeholder="{{ __('e.g., campaign') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Unresolved Only') }}
                </label>
                <div class="flex items-center h-10">
                    <input type="checkbox" name="unresolved_only" value="1" class="form-checkbox">
                    <span class="{{ app()->getLocale() == 'ar' ? 'mr-2' : 'ml-2' }} text-sm text-gray-700 dark:text-gray-300">{{ __('Show only unresolved') }}</span>
                </div>
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-filter {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('Apply') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Anomalies List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        @if($anomalies->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($anomalies as $anomaly)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        @if($anomaly->severity == 'critical') bg-red-100 text-red-800
                                        @elseif($anomaly->severity == 'high') bg-orange-100 text-orange-800
                                        @elseif($anomaly->severity == 'medium') bg-yellow-100 text-yellow-800
                                        @else bg-blue-100 text-blue-800
                                        @endif">
                                        {{ ucfirst($anomaly->severity) }}
                                    </span>

                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        @if($anomaly->status == 'resolved') bg-green-100 text-green-800
                                        @elseif($anomaly->status == 'investigating') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $anomaly->status)) }}
                                    </span>

                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $anomaly->detected_at->diffForHumans() }}
                                    </span>
                                </div>

                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    {{ ucfirst($anomaly->metric_name) }} {{ __('Anomaly') }}
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                                    {{ $anomaly->getDeviationDescription() }}
                                </p>

                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">{{ __('Expected') }}:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($anomaly->expected_value, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">{{ __('Actual') }}:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($anomaly->actual_value, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">{{ __('Confidence') }}:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($anomaly->confidence_score * 100, 1) }}%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2 {{ app()->getLocale() == 'ar' ? 'mr-4' : 'ml-4' }}">
                                <a href="{{ route('anomalies.show', $anomaly->anomaly_id) }}" class="btn btn-sm btn-secondary">
                                    {{ __('View Details') }}
                                </a>

                                @if($anomaly->status == 'detected')
                                    <form method="POST" action="{{ route('anomalies.investigate', $anomaly->anomaly_id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            {{ __('Investigate') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $anomalies->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-check-circle text-6xl text-green-300 dark:text-green-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 text-lg">{{ __('No anomalies detected') }}</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">{{ __('Your campaigns are performing as expected') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
