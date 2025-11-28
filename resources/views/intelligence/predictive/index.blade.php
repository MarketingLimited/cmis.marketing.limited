@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', __('predictive.page_title'))
@section('page-subtitle', __('predictive.page_subtitle'))

@section('content')
<div x-data="predictiveAnalytics()" x-init="init()">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">{{ __('predictive.stats.active_models') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.activeModels"></p>
                </div>
                <i class="fas fa-brain text-5xl text-purple-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">{{ __('predictive.stats.predictions_today') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.todayPredictions"></p>
                </div>
                <i class="fas fa-chart-line text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">{{ __('predictive.stats.model_accuracy') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.accuracy + '%'"></p>
                </div>
                <i class="fas fa-bullseye text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm mb-1">{{ __('predictive.stats.predicted_savings') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.predictedSavings.toLocaleString()"></p>
                </div>
                <i class="fas fa-piggy-bank text-5xl text-orange-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Predictive Models -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
        <!-- Budget Forecasting -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i class="fas fa-coins text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-bold text-gray-900">{{ __('predictive.models.budget_forecasting.title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('predictive.models.budget_forecasting.description') }}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">{{ __('predictive.status.in_development') }}</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.expected_accuracy') }}</span>
                    <span class="font-bold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.last_training') }}</span>
                    <span class="font-bold text-gray-900">{{ __('predictive.labels.not_started') }}</span>
                </div>
            </div>
            <button class="w-full mt-4 bg-gray-100 text-gray-600 py-2 rounded-lg font-medium cursor-not-allowed" disabled>
                <i class="fas fa-lock ms-2"></i>
                {{ __('common.not_available') }}
            </button>
        </div>

        <!-- Performance Prediction -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-bold text-gray-900">{{ __('predictive.models.performance_prediction.title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('predictive.models.performance_prediction.description') }}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">{{ __('predictive.status.in_development') }}</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.expected_accuracy') }}</span>
                    <span class="font-bold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.last_training') }}</span>
                    <span class="font-bold text-gray-900">{{ __('predictive.labels.not_started') }}</span>
                </div>
            </div>
            <button class="w-full mt-4 bg-gray-100 text-gray-600 py-2 rounded-lg font-medium cursor-not-allowed" disabled>
                <i class="fas fa-lock ms-2"></i>
                {{ __('common.not_available') }}
            </button>
        </div>

        <!-- Churn Prediction -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i class="fas fa-user-slash text-red-600 text-2xl"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-bold text-gray-900">{{ __('predictive.models.churn_prediction.title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('predictive.models.churn_prediction.description') }}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">{{ __('predictive.status.in_development') }}</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.expected_accuracy') }}</span>
                    <span class="font-bold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.last_training') }}</span>
                    <span class="font-bold text-gray-900">{{ __('predictive.labels.not_started') }}</span>
                </div>
            </div>
            <button class="w-full mt-4 bg-gray-100 text-gray-600 py-2 rounded-lg font-medium cursor-not-allowed" disabled>
                <i class="fas fa-lock ms-2"></i>
                {{ __('common.not_available') }}
            </button>
        </div>

        <!-- Anomaly Detection -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-bold text-gray-900">{{ __('predictive.models.anomaly_detection.title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('predictive.models.anomaly_detection.description') }}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">{{ __('predictive.status.in_development') }}</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.expected_accuracy') }}</span>
                    <span class="font-bold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.last_training') }}</span>
                    <span class="font-bold text-gray-900">{{ __('predictive.labels.not_started') }}</span>
                </div>
            </div>
            <button class="w-full mt-4 bg-gray-100 text-gray-600 py-2 rounded-lg font-medium cursor-not-allowed" disabled>
                <i class="fas fa-lock ms-2"></i>
                {{ __('common.not_available') }}
            </button>
        </div>

        <!-- LTV Prediction -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fas fa-trophy text-green-600 text-2xl"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-bold text-gray-900">{{ __('predictive.models.ltv_prediction.title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('predictive.models.ltv_prediction.description') }}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">{{ __('predictive.status.in_development') }}</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.expected_accuracy') }}</span>
                    <span class="font-bold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.last_training') }}</span>
                    <span class="font-bold text-gray-900">{{ __('predictive.labels.not_started') }}</span>
                </div>
            </div>
            <button class="w-full mt-4 bg-gray-100 text-gray-600 py-2 rounded-lg font-medium cursor-not-allowed" disabled>
                <i class="fas fa-lock ms-2"></i>
                {{ __('common.not_available') }}
            </button>
        </div>

        <!-- Bid Optimization -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="bg-indigo-100 p-3 rounded-lg">
                        <i class="fas fa-gavel text-indigo-600 text-2xl"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-bold text-gray-900">{{ __('predictive.models.bid_optimization.title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('predictive.models.bid_optimization.description') }}</p>
                    </div>
                </div>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">{{ __('predictive.status.in_development') }}</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.expected_accuracy') }}</span>
                    <span class="font-bold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('predictive.labels.last_training') }}</span>
                    <span class="font-bold text-gray-900">{{ __('predictive.labels.not_started') }}</span>
                </div>
            </div>
            <button class="w-full mt-4 bg-gray-100 text-gray-600 py-2 rounded-lg font-medium cursor-not-allowed" disabled>
                <i class="fas fa-lock ms-2"></i>
                {{ __('common.not_available') }}
            </button>
        </div>
    </div>

    <!-- Recent Predictions -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-history text-indigo-600 ms-2"></i>
            {{ __('predictive.recent_predictions') }}
        </h3>

        <div class="text-center py-12">
            <i class="fas fa-robot text-gray-300 text-6xl mb-4"></i>
            <h4 class="text-xl font-bold text-gray-900 mb-2">{{ __('predictive.no_predictions') }}</h4>
            <p class="text-gray-600 mb-6">{{ __('predictive.models_in_development') }}</p>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-2xl mx-auto">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle ms-2"></i>
                    {{ __('predictive.coming_soon_message') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function predictiveAnalytics() {
    return {
        stats: {
            activeModels: 0,
            todayPredictions: 0,
            accuracy: 0,
            predictedSavings: 0
        },
        predictions: [],
        models: [
            {
                id: 'budget-forecasting',
                name: '{{ __('predictive.models.budget_forecasting.title') }}',
                status: 'development',
                accuracy: null,
                lastTrained: null
            },
            {
                id: 'performance-prediction',
                name: '{{ __('predictive.models.performance_prediction.title') }}',
                status: 'development',
                accuracy: null,
                lastTrained: null
            },
            {
                id: 'churn-prediction',
                name: '{{ __('predictive.models.churn_prediction.title') }}',
                status: 'development',
                accuracy: null,
                lastTrained: null
            },
            {
                id: 'anomaly-detection',
                name: '{{ __('predictive.models.anomaly_detection.title') }}',
                status: 'development',
                accuracy: null,
                lastTrained: null
            },
            {
                id: 'ltv-prediction',
                name: '{{ __('predictive.models.ltv_prediction.title') }}',
                status: 'development',
                accuracy: null,
                lastTrained: null
            }
        ],

        async init() {
            // Will load data when models are implemented
            this.loadStats();
        },

        async loadStats() {
            // Placeholder - will be implemented with actual API calls
            this.stats = {
                activeModels: 0,
                todayPredictions: 0,
                accuracy: 0,
                predictedSavings: 0
            };
        },

        async loadPredictions() {
            // Will fetch predictions when available
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/predictive/predictions`);
                if (response.ok) {
                    const data = await response.json();
                    this.predictions = data.predictions || [];
                }
            } catch (error) {
                console.error('Failed to load predictions:', error);
            }
        },

        formatDate(date) {
            if (!date) return '{{ __('predictive.labels.not_started') }}';
            return new Date(date).toLocaleDateString('{{ app()->getLocale() }}'{{ app()->getLocale() === 'ar' ? '-SA' : '' }}, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    };
}
</script>
@endpush
