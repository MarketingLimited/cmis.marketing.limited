@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.plans.edit_plan'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.plans.index') }}" class="text-gray-500 hover:text-red-600 transition">{{ __('super_admin.plans.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ $plan->name }}</span>
@endsection

@section('content')
<div x-data="planForm()">
    <form action="{{ route('super-admin.plans.update', $plan->plan_id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.plans.edit_plan') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.plans.edit_description') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('super-admin.plans.index') }}"
                   class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    {{ __('super_admin.plans.update_plan') }}
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
                                   value="{{ old('name', $plan->name) }}"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
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
                                   value="{{ old('code', $plan->code) }}"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent font-mono">
                            @error('code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.description') }}
                            </label>
                            <textarea name="description"
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">{{ old('description', $plan->description) }}</textarea>
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
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="USD" {{ old('currency', $plan->currency) === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                <option value="SAR" {{ old('currency', $plan->currency) === 'SAR' ? 'selected' : '' }}>SAR - Saudi Riyal</option>
                                <option value="AED" {{ old('currency', $plan->currency) === 'AED' ? 'selected' : '' }}>AED - UAE Dirham</option>
                                <option value="EUR" {{ old('currency', $plan->currency) === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                <option value="GBP" {{ old('currency', $plan->currency) === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.price_monthly') }}
                            </label>
                            <input type="number"
                                   name="price_monthly"
                                   value="{{ old('price_monthly', $plan->price_monthly) }}"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.price_yearly') }}
                            </label>
                            <input type="number"
                                   name="price_yearly"
                                   value="{{ old('price_yearly', $plan->price_yearly) }}"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
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
                                   value="{{ old('max_users', $plan->max_users) }}"
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
                                   value="{{ old('max_orgs', $plan->max_orgs) }}"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="{{ __('super_admin.plans.unlimited') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('super_admin.plans.api_calls') }}
                            </label>
                            <input type="number"
                                   name="max_api_calls_per_month"
                                   value="{{ old('max_api_calls_per_month', $plan->max_api_calls_per_month) }}"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="{{ __('super_admin.plans.unlimited') }}">
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.features') }}</h2>

                    @php
                        $features = (array)($plan->features ?? []);
                    @endphp

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
                                   value="1"
                                   {{ isset($features[$key]) && $features[$key] ? 'checked' : '' }}
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
                               value="1"
                               {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
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
                               value="{{ old('sort_order', $plan->sort_order) }}"
                               min="0"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Stats -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.plans.statistics') }}</h2>

                    <dl class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.plans.subscribers') }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ $plan->subscriptions_count ?? 0 }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.plans.created_at') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $plan->created_at->format('M j, Y') }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.plans.updated_at') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $plan->updated_at->format('M j, Y') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Danger Zone -->
                @if(($plan->subscriptions_count ?? 0) === 0)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-6">
                    <h2 class="text-lg font-semibold text-red-800 dark:text-red-400 mb-4">{{ __('super_admin.plans.danger_zone') }}</h2>
                    <p class="text-sm text-red-700 dark:text-red-300 mb-4">{{ __('super_admin.plans.delete_warning') }}</p>
                    <button type="button"
                            @click="deletePlan()"
                            class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        <i class="fas fa-trash {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        {{ __('super_admin.plans.delete_plan') }}
                    </button>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function planForm() {
    return {
        async deletePlan() {
            if (!confirm('{{ __('super_admin.plans.delete_confirm') }}')) return;

            try {
                const response = await fetch('{{ route('super-admin.plans.destroy', $plan->plan_id) }}', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.href = '{{ route('super-admin.plans.index') }}';
                }
            } catch (error) {
                console.error('Error deleting plan:', error);
            }
        }
    };
}
</script>
@endpush
