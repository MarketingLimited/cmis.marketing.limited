@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.plans.create_plan'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.plans.index') }}" class="text-gray-500 hover:text-red-600 transition">{{ __('super_admin.plans.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.plans.create_plan') }}</span>
@endsection

@section('content')
<div x-data="planForm()">
    <form action="{{ route('super-admin.plans.store') }}" method="POST" @submit="validateForm($event)">
        @csrf

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.plans.create_plan') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.plans.create_description') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('super-admin.plans.index') }}"
                   class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    {{ __('super_admin.plans.save_plan') }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.basic_info') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   x-model="form.name"
                                   @input="generateCode()"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="{{ __('super_admin.plans.name_placeholder') }}">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.code') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="code"
                                   x-model="form.code"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent font-mono"
                                   placeholder="{{ __('super_admin.plans.code_placeholder') }}">
                            @error('code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.description') }}
                            </label>
                            <textarea name="description"
                                      x-model="form.description"
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                      placeholder="{{ __('super_admin.plans.description_placeholder') }}"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.pricing') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.currency') }}
                            </label>
                            <select name="currency"
                                    x-model="form.currency"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="USD">USD - US Dollar</option>
                                <option value="SAR">SAR - Saudi Riyal</option>
                                <option value="AED">AED - UAE Dirham</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.price_monthly') }}
                            </label>
                            <div class="relative">
                                <input type="number"
                                       name="price_monthly"
                                       x-model="form.price_monthly"
                                       step="0.01"
                                       min="0"
                                       class="w-full {{ $isRtl ? 'pr-12 pl-4' : 'pl-12 pr-4' }} py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                       placeholder="0.00">
                                <span class="absolute {{ $isRtl ? 'right-4' : 'left-4' }} top-1/2 -translate-y-1/2 text-gray-400" x-text="form.currency"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.price_yearly') }}
                            </label>
                            <div class="relative">
                                <input type="number"
                                       name="price_yearly"
                                       x-model="form.price_yearly"
                                       step="0.01"
                                       min="0"
                                       class="w-full {{ $isRtl ? 'pr-12 pl-4' : 'pl-12 pr-4' }} py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                       placeholder="0.00">
                                <span class="absolute {{ $isRtl ? 'right-4' : 'left-4' }} top-1/2 -translate-y-1/2 text-gray-400" x-text="form.currency"></span>
                            </div>
                            <p x-show="form.price_monthly > 0 && form.price_yearly > 0" class="mt-1 text-sm text-green-600">
                                <span x-text="calculateYearlyDiscount()"></span>% {{ __('super_admin.plans.discount') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Limits -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.limits') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.max_users') }}
                            </label>
                            <input type="number"
                                   name="max_users"
                                   x-model="form.max_users"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="{{ __('super_admin.plans.unlimited') }}">
                            <p class="mt-1 text-xs text-gray-500">{{ __('super_admin.plans.leave_empty_unlimited') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.max_orgs') }}
                            </label>
                            <input type="number"
                                   name="max_orgs"
                                   x-model="form.max_orgs"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="{{ __('super_admin.plans.unlimited') }}">
                            <p class="mt-1 text-xs text-gray-500">{{ __('super_admin.plans.leave_empty_unlimited') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.api_calls') }}
                            </label>
                            <input type="number"
                                   name="max_api_calls_per_month"
                                   x-model="form.max_api_calls_per_month"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="{{ __('super_admin.plans.unlimited') }}">
                            <p class="mt-1 text-xs text-gray-500">{{ __('super_admin.plans.per_month') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.features') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach([
                            'social_publishing' => __('super_admin.plans.feature_social_publishing'),
                            'ai_features' => __('super_admin.plans.feature_ai'),
                            'analytics' => __('super_admin.plans.feature_analytics'),
                            'team_collaboration' => __('super_admin.plans.feature_team'),
                            'api_access' => __('super_admin.plans.feature_api'),
                            'white_label' => __('super_admin.plans.feature_white_label'),
                            'priority_support' => __('super_admin.plans.feature_priority_support'),
                            'custom_integrations' => __('super_admin.plans.feature_custom_integrations'),
                        ] as $key => $label)
                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition">
                            <input type="checkbox"
                                   name="features[{{ $key }}]"
                                   x-model="form.features.{{ $key }}"
                                   class="w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.status') }}</h2>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               x-model="form.is_active"
                               value="1"
                               class="w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <div>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">{{ __('super_admin.plans.active') }}</span>
                            <p class="text-sm text-gray-500">{{ __('super_admin.plans.active_description') }}</p>
                        </div>
                    </label>
                </div>

                <!-- Sort Order -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.display') }}</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('super_admin.plans.sort_order') }}
                        </label>
                        <input type="number"
                               name="sort_order"
                               x-model="form.sort_order"
                               min="0"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500">{{ __('super_admin.plans.sort_order_description') }}</p>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.preview') }}</h2>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-red-500 to-red-700 text-white">
                            <h3 class="text-lg font-bold" x-text="form.name || '{{ __('super_admin.plans.plan_name') }}'"></h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white" x-text="form.currency"></span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white" x-text="form.price_monthly || '0'"></span>
                                <span class="text-gray-500">/{{ __('super_admin.plans.month') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function planForm() {
    return {
        form: {
            name: '',
            code: '',
            description: '',
            currency: 'USD',
            price_monthly: '',
            price_yearly: '',
            max_users: '',
            max_orgs: '',
            max_api_calls_per_month: '',
            is_active: true,
            sort_order: 0,
            features: {
                social_publishing: false,
                ai_features: false,
                analytics: false,
                team_collaboration: false,
                api_access: false,
                white_label: false,
                priority_support: false,
                custom_integrations: false,
            }
        },

        generateCode() {
            if (this.form.name) {
                this.form.code = this.form.name
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_|_$/g, '');
            }
        },

        calculateYearlyDiscount() {
            if (this.form.price_monthly > 0 && this.form.price_yearly > 0) {
                const monthlyTotal = this.form.price_monthly * 12;
                const discount = ((monthlyTotal - this.form.price_yearly) / monthlyTotal) * 100;
                return Math.round(discount);
            }
            return 0;
        },

        validateForm(event) {
            if (!this.form.name || !this.form.code) {
                event.preventDefault();
                alert('{{ __('super_admin.plans.validation_required') }}');
            }
        }
    };
}
</script>
@endpush
