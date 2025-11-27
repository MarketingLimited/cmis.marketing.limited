@extends('layouts.admin')

@section('title', __('Forecasts'))

@section('content')
<div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ __('Forecasts') }}
        </h1>
        <a href="{{ route('forecasts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
            {{ __('Create Forecast') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6" x-data="{ showFilters: false }">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Filters') }}</h2>
            <button @click="showFilters = !showFilters" class="text-blue-600 hover:text-blue-700">
                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>

        <div x-show="showFilters" x-transition>
            <form method="GET" action="{{ route('forecasts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Metric') }}
                    </label>
                    <select name="metric" class="form-select w-full">
                        <option value="">{{ __('All Metrics') }}</option>
                        <option value="impressions">{{ __('Impressions') }}</option>
                        <option value="clicks">{{ __('Clicks') }}</option>
                        <option value="conversions">{{ __('Conversions') }}</option>
                        <option value="revenue">{{ __('Revenue') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Campaign') }}
                    </label>
                    <input type="text" name="campaign_id" class="form-input w-full" placeholder="{{ __('Campaign ID') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Date From') }}
                    </label>
                    <input type="date" name="date_from" class="form-input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Date To') }}
                    </label>
                    <input type="date" name="date_to" class="form-input w-full">
                </div>

                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                        {{ __('Apply Filters') }}
                    </button>
                    <a href="{{ route('forecasts.index') }}" class="btn btn-secondary">
                        {{ __('Reset') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forecasts Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        @if($forecasts->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Metric') }}
                            </th>
                            <th class="px-6 py-3 text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Forecast Date') }}
                            </th>
                            <th class="px-6 py-3 text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Predicted Value') }}
                            </th>
                            <th class="px-6 py-3 text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Actual Value') }}
                            </th>
                            <th class="px-6 py-3 text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Accuracy') }}
                            </th>
                            <th class="px-6 py-3 text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Model') }}
                            </th>
                            <th class="px-6 py-3 text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($forecasts as $forecast)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ucfirst($forecast->metric_name) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $forecast->forecast_date->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($forecast->predicted_value, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    @if($forecast->actuals)
                                        {{ number_format($forecast->actuals, 2) }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($forecast->accuracy)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $forecast->isAccurate() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ number_format($forecast->accuracy * 100, 1) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $forecast->predictionModel->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('forecasts.show', $forecast->forecast_id) }}" class="text-blue-600 hover:text-blue-900 {{ app()->getLocale() == 'ar' ? 'ml-3' : 'mr-3' }}">
                                        {{ __('View') }}
                                    </a>
                                    <a href="{{ route('forecasts.edit', $forecast->forecast_id) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('Edit') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $forecasts->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-chart-line text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 text-lg">{{ __('No forecasts found') }}</p>
                <a href="{{ route('forecasts.create') }}" class="btn btn-primary mt-4">
                    {{ __('Create Your First Forecast') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
