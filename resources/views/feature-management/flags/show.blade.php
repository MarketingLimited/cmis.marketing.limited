@extends('layouts.admin')

@section('title', $flag->name)

@section('content')
<div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ $flag->name }}
                </h1>
                <span class="px-3 py-1 text-sm font-semibold rounded-full
                    {{ $flag->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $flag->is_enabled ? __('Enabled') : __('Disabled') }}
                </span>
            </div>
            <p class="mt-2 text-sm text-gray-600 font-mono">
                {{ $flag->key }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($flag->is_enabled)
                <form method="POST" action="{{ route('feature-flags.disable', $flag->flag_id) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                        {{ __('Disable') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('feature-flags.enable', $flag->flag_id) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        {{ __('Enable') }}
                    </button>
                </form>
            @endif
            <a href="{{ route('feature-flags.edit', $flag->flag_id) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                {{ __('Edit') }}
            </a>
            <a href="{{ route('feature-flags.index') }}"
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                {{ __('Back') }}
            </a>
        </div>
    </div>

    <!-- Overview Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Flag Details -->
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Flag Details') }}</h2>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Type') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $flag->type === 'boolean' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $flag->type === 'multivariate' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $flag->type === 'kill_switch' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst(str_replace('_', ' ', $flag->type)) }}
                        </span>
                    </dd>
                </div>

                @if($flag->description)
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $flag->description }}</dd>
                    </div>
                @endif

                @if($flag->rollout_percentage !== null)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Rollout Percentage') }}</dt>
                        <dd class="mt-1">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-3">
                                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                                         style="width: {{ $flag->rollout_percentage }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ $flag->rollout_percentage }}%</span>
                            </div>
                        </dd>
                    </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Created') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $flag->created_at->format('Y-m-d H:i') }}
                        <span class="text-gray-500">({{ $flag->created_at->diffForHumans() }})</span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Last Updated') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $flag->updated_at->format('Y-m-d H:i') }}
                        <span class="text-gray-500">({{ $flag->updated_at->diffForHumans() }})</span>
                    </dd>
                </div>

                @if($flag->creator)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $flag->creator->name }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <!-- Statistics -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Statistics') }}</h2>

            <div class="space-y-4">
                <div class="border-b border-gray-200 pb-3">
                    <div class="text-2xl font-bold text-gray-900">
                        {{ number_format($flag->evaluation_count) }}
                    </div>
                    <div class="text-sm text-gray-500">{{ __('Total Evaluations') }}</div>
                </div>

                <div class="border-b border-gray-200 pb-3">
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $flag->variants->count() }}
                    </div>
                    <div class="text-sm text-gray-500">{{ __('Variants') }}</div>
                </div>

                <div class="border-b border-gray-200 pb-3">
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $flag->overrides->count() }}
                    </div>
                    <div class="text-sm text-gray-500">{{ __('Active Overrides') }}</div>
                </div>

                @if($flag->last_evaluated_at)
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $flag->last_evaluated_at->diffForHumans() }}
                        </div>
                        <div class="text-sm text-gray-500">{{ __('Last Evaluated') }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Targeting Rules -->
    @if($flag->targeting_rules && count($flag->targeting_rules) > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Targeting Rules') }}</h2>

            <div class="space-y-2">
                @foreach($flag->targeting_rules as $rule)
                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-md">
                        <code class="text-sm font-mono text-gray-900">
                            {{ $rule['attribute'] ?? 'unknown' }}
                        </code>
                        <span class="text-gray-500">{{ $rule['operator'] ?? '=' }}</span>
                        <code class="text-sm font-mono text-blue-600">
                            {{ is_array($rule['value'] ?? null) ? json_encode($rule['value']) : ($rule['value'] ?? 'null') }}
                        </code>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Variants Section (for multivariate flags) -->
    @if($flag->type === 'multivariate')
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('A/B Test Variants') }}</h2>
                <a href="{{ route('feature-flags.variants.create', $flag->flag_id) }}"
                   class="px-3 py-1 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700">
                    {{ __('Add Variant') }}
                </a>
            </div>

            @if($flag->variants->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($flag->variants as $variant)
                        <div class="border border-gray-200 rounded-lg p-4 {{ $variant->is_control ? 'border-blue-400 bg-blue-50' : '' }}">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $variant->name }}</h3>
                                    <p class="text-xs font-mono text-gray-500">{{ $variant->key }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($variant->is_control)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                            {{ __('Control') }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-1 {{ $variant->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} text-xs font-semibold rounded">
                                        {{ $variant->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </div>
                            </div>

                            @if($variant->description)
                                <p class="text-sm text-gray-600 mb-3">{{ $variant->description }}</p>
                            @endif

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500">{{ __('Weight') }}:</span>
                                    <span class="font-semibold text-gray-900">{{ $variant->weight }}%</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">{{ __('Exposures') }}:</span>
                                    <span class="font-semibold text-gray-900">{{ number_format($variant->exposures) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">{{ __('Conversions') }}:</span>
                                    <span class="font-semibold text-gray-900">{{ number_format($variant->conversions) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">{{ __('CVR') }}:</span>
                                    <span class="font-semibold text-{{ $variant->getConversionRate() > 0 ? 'green' : 'gray' }}-900">
                                        {{ number_format($variant->getConversionRate() * 100, 2) }}%
                                    </span>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-end gap-2">
                                <a href="{{ route('feature-flags.variants.show', [$flag->flag_id, $variant->variant_id]) }}"
                                   class="text-xs text-blue-600 hover:text-blue-800">
                                    {{ __('Details') }}
                                </a>
                                <a href="{{ route('feature-flags.variants.edit', [$flag->flag_id, $variant->variant_id]) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-800">
                                    {{ __('Edit') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 py-8">
                    {{ __('No variants configured. Add variants to start A/B testing.') }}
                </p>
            @endif
        </div>
    @endif

    <!-- Overrides Section -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Overrides') }}</h2>
            <a href="{{ route('feature-flags.overrides.create', $flag->flag_id) }}"
               class="px-3 py-1 bg-orange-600 text-white text-sm rounded-md hover:bg-orange-700">
                {{ __('Add Override') }}
            </a>
        </div>

        @if($flag->overrides->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase">
                                {{ __('Type') }}
                            </th>
                            <th class="px-4 py-2 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase">
                                {{ __('Target') }}
                            </th>
                            <th class="px-4 py-2 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase">
                                {{ __('Value') }}
                            </th>
                            <th class="px-4 py-2 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase">
                                {{ __('Status') }}
                            </th>
                            <th class="px-4 py-2 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase">
                                {{ __('Expires') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($flag->overrides->take(10) as $override)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                        {{ ucfirst($override->override_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 font-mono">
                                    {{ Str::limit($override->override_id_value, 20) }}
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        {{ $override->value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $override->value ? __('Enabled') : __('Disabled') }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 py-1 rounded text-xs
                                        {{ $override->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $override->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    {{ $override->expires_at ? $override->expires_at->format('Y-m-d') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($flag->overrides->count() > 10)
                <div class="mt-4 text-center">
                    <a href="{{ route('feature-flags.overrides.index', $flag->flag_id) }}"
                       class="text-sm text-blue-600 hover:text-blue-800">
                        {{ __('View all :count overrides', ['count' => $flag->overrides->count()]) }}
                    </a>
                </div>
            @endif
        @else
            <p class="text-center text-gray-500 py-8">
                {{ __('No overrides configured.') }}
            </p>
        @endif
    </div>

    <!-- Whitelist/Blacklist -->
    @if($flag->whitelist_user_ids || $flag->blacklist_user_ids)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if($flag->whitelist_user_ids && count($flag->whitelist_user_ids) > 0)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ __('Whitelist') }}
                        <span class="text-sm font-normal text-gray-500">({{ count($flag->whitelist_user_ids) }})</span>
                    </h2>
                    <div class="space-y-1">
                        @foreach(array_slice($flag->whitelist_user_ids, 0, 5) as $userId)
                            <div class="text-sm font-mono text-gray-600 p-2 bg-green-50 rounded">
                                {{ $userId }}
                            </div>
                        @endforeach
                        @if(count($flag->whitelist_user_ids) > 5)
                            <p class="text-sm text-gray-500 text-center pt-2">
                                {{ __('+ :count more', ['count' => count($flag->whitelist_user_ids) - 5]) }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            @if($flag->blacklist_user_ids && count($flag->blacklist_user_ids) > 0)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ __('Blacklist') }}
                        <span class="text-sm font-normal text-gray-500">({{ count($flag->blacklist_user_ids) }})</span>
                    </h2>
                    <div class="space-y-1">
                        @foreach(array_slice($flag->blacklist_user_ids, 0, 5) as $userId)
                            <div class="text-sm font-mono text-gray-600 p-2 bg-red-50 rounded">
                                {{ $userId }}
                            </div>
                        @endforeach
                        @if(count($flag->blacklist_user_ids) > 5)
                            <p class="text-sm text-gray-500 text-center pt-2">
                                {{ __('+ :count more', ['count' => count($flag->blacklist_user_ids) - 5]) }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
