@extends('layouts.admin')

@section('title', __('Forecast Details'))

@section('content')
<div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('forecasts.index') }}" class="text-blue-600 hover:text-blue-700 mb-2 inline-block">
                <i class="fas fa-arrow-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                {{ __('Back to Forecasts') }}
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ __('Forecast Details') }}
            </h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('forecasts.edit', $forecast->forecast_id) }}" class="btn btn-secondary">
                <i class="fas fa-edit {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                {{ __('Edit') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Forecast Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('Forecast Overview') }}</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Metric') }}
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ ucfirst($forecast->metric_name) }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Forecast Date') }}
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $forecast->forecast_date->format('Y-m-d') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Predicted Value') }}
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($forecast->predicted_value, 2) }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Actual Value') }}
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            @if($forecast->actuals)
                                {{ number_format($forecast->actuals, 2) }}
                            @else
                                <span class="text-gray-400">{{ __('Not yet available') }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Confidence Interval -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                        {{ __('Confidence Interval') }} ({{ ($forecast->confidence_level * 100) }}%)
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <div class="relative pt-1">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold inline-block text-blue-600">
                                            {{ number_format($forecast->confidence_lower, 2) }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold inline-block text-blue-600">
                                            {{ number_format($forecast->confidence_upper, 2) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-2 text-xs flex rounded bg-blue-200">
                                    <div style="width:50%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($forecast->accuracy)
                    <!-- Accuracy -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                            {{ __('Forecast Accuracy') }}
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <div class="relative pt-1">
                                    <div class="overflow-hidden h-4 text-xs flex rounded bg-gray-200">
                                        <div style="width:{{ $forecast->accuracy * 100 }}%"
                                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $forecast->isAccurate() ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                    </div>
                                </div>
                            </div>
                            <span class="text-lg font-semibold {{ $forecast->isAccurate() ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($forecast->accuracy * 100, 1) }}%
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Model Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('Prediction Model') }}</h2>

                @if($forecast->predictionModel)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('Model Name') }}
                            </label>
                            <p class="text-gray-900 dark:text-white">
                                {{ $forecast->predictionModel->name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('Algorithm') }}
                            </label>
                            <p class="text-gray-900 dark:text-white">
                                {{ ucfirst($forecast->predictionModel->algorithm) }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('Model Version') }}
                            </label>
                            <p class="text-gray-900 dark:text-white">
                                v{{ $forecast->predictionModel->version }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('Last Trained') }}
                            </label>
                            <p class="text-gray-900 dark:text-white">
                                {{ $forecast->predictionModel->last_trained_at?->diffForHumans() ?? 'Never' }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">{{ __('No model information available') }}</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Meta Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Information') }}</h3>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Forecast Horizon') }}
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $forecast->forecast_horizon }} {{ __('days') }}
                        </p>
                    </div>

                    @if($forecast->campaign)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('Campaign') }}
                            </label>
                            <p class="text-sm text-gray-900 dark:text-white">
                                {{ $forecast->campaign->name }}
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Created') }}
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $forecast->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Last Updated') }}
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $forecast->updated_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            @if(!$forecast->actuals)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        {{ __('Record Actual Value') }}
                    </h3>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mb-3">
                        {{ __('Once the actual data is available, record it to calculate forecast accuracy.') }}
                    </p>
                    <form method="POST" action="{{ route('forecasts.recordActuals', $forecast->forecast_id) }}">
                        @csrf
                        <div class="mb-3">
                            <input type="number" step="0.01" name="actuals" class="form-input w-full"
                                   placeholder="{{ __('Actual value') }}" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-full">
                            {{ __('Record Actuals') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
