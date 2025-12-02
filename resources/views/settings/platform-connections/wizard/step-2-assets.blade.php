@extends('layouts.admin')

@section('title', __('wizard.assets.title') . ' - ' . __($config['display_name']))

@section('content')
<div class="min-h-screen bg-gray-50 py-6 sm:py-12" x-data="wizardAssets(@js($assets), @js($smartDefaults))">
    {{-- Breadcrumb --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <nav class="text-sm text-gray-500 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.platform-connections.wizard.dashboard', $currentOrg) }}" class="hover:text-blue-600 transition">
                {{ __('wizard.dashboard.title') }}
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.platform-connections.wizard.start', [$currentOrg, $platform]) }}" class="hover:text-blue-600 transition">
                {{ __($config['display_name']) }}
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('wizard.steps.assets') }}</span>
        </nav>
    </div>

    {{-- Step Progress Indicator --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="flex items-center justify-center">
            <div class="flex items-center">
                {{-- Step 1: Connect (Completed) --}}
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                    <span class="hidden sm:block {{ $isRtl ? 'me-3' : 'ms-3' }} text-sm font-medium text-green-600">
                        {{ __('wizard.steps.connect') }}
                    </span>
                </div>

                {{-- Connector --}}
                <div class="w-12 sm:w-24 h-1 bg-green-600 mx-2 sm:mx-4"></div>

                {{-- Step 2: Assets (Active) --}}
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-sm">
                        2
                    </div>
                    <span class="hidden sm:block {{ $isRtl ? 'me-3' : 'ms-3' }} text-sm font-medium text-blue-600">
                        {{ __('wizard.steps.assets') }}
                    </span>
                </div>

                {{-- Connector --}}
                <div class="w-12 sm:w-24 h-1 bg-gray-200 mx-2 sm:mx-4"></div>

                {{-- Step 3: Complete (Upcoming) --}}
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-sm">
                        3
                    </div>
                    <span class="hidden sm:block {{ $isRtl ? 'me-3' : 'ms-3' }} text-sm font-medium text-gray-500">
                        {{ __('wizard.steps.complete') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-6 sm:px-8 border-b border-gray-100">
                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center {{ $isRtl ? 'ms-4' : 'me-4' }}"
                         style="background-color: {{ $config['color'] }}20;">
                        <i class="{{ $config['icon'] }} text-2xl" style="color: {{ $config['color'] }};"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ __('wizard.assets.title') }}</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('wizard.assets.subtitle', ['platform' => __($config['display_name'])]) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Smart Defaults Notice --}}
            <div class="px-6 py-4 sm:px-8 bg-blue-50 border-b border-blue-100">
                <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-magic text-blue-600 {{ $isRtl ? 'ms-3' : 'me-3' }}"></i>
                        <div>
                            <p class="text-sm font-medium text-blue-800">{{ __('wizard.assets.smart_defaults.applied') }}</p>
                            <p class="text-xs text-blue-600">{{ __('wizard.assets.smart_defaults.description') }}</p>
                        </div>
                    </div>
                    <button @click="resetDefaults()"
                            class="text-xs text-blue-600 hover:text-blue-800 underline">
                        {{ __('wizard.assets.smart_defaults.reset') }}
                    </button>
                </div>
            </div>

            {{-- Asset Selection Form --}}
            <form method="POST"
                  action="{{ route('orgs.settings.platform-connections.wizard.assets.store', [$currentOrg, $platform, $connection->id]) }}"
                  @submit="submitting = true">
                @csrf

                <div class="p-6 sm:p-8 space-y-8">
                    @foreach($config['asset_types'] as $assetType => $assetConfig)
                        @php
                            $assetKey = $assetType;
                            $assetList = $assets[$assetKey] ?? [];
                            $assetName = __($assetConfig['name']);
                            $assetHelp = isset($assetConfig['help']) ? __($assetConfig['help']) : '';
                        @endphp

                        <div class="space-y-4">
                            {{-- Section Header --}}
                            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">{{ $assetName }}</h3>
                                    @if($assetHelp)
                                        <p class="text-sm text-gray-500">{{ $assetHelp }}</p>
                                    @endif
                                </div>
                                @if(count($assetList) > 1)
                                    <div class="flex items-center gap-2 text-sm">
                                        <button type="button"
                                                @click="selectAll('{{ $assetKey }}')"
                                                class="text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ __('wizard.assets.select_all') }}
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button"
                                                @click="deselectAll('{{ $assetKey }}')"
                                                class="text-gray-600 hover:text-gray-800 hover:underline">
                                            {{ __('wizard.assets.deselect_all') }}
                                        </button>
                                    </div>
                                @endif
                            </div>

                            {{-- Asset Cards --}}
                            @if(count($assetList) > 0)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($assetList as $index => $asset)
                                        @php
                                            $assetId = $asset['id'] ?? $asset['platform_id'] ?? $index;
                                            $assetTitle = $asset['name'] ?? $asset['title'] ?? $asset['username'] ?? 'Unknown';
                                            $assetSubtitle = $asset['username'] ?? $asset['description'] ?? $asset['type'] ?? '';
                                            $assetImage = $asset['picture'] ?? $asset['thumbnail'] ?? null;
                                            $assetFollowers = $asset['followers_count'] ?? $asset['subscriber_count'] ?? null;
                                            $isRecommended = in_array($assetId, $smartDefaults[$assetKey] ?? []);
                                        @endphp

                                        <label :class="{ 'ring-2 ring-blue-500 border-blue-500': isSelected('{{ $assetKey }}', '{{ $assetId }}') }"
                                               class="relative flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition-all">
                                            <input type="checkbox"
                                                   name="assets[{{ $assetKey }}][]"
                                                   value="{{ $assetId }}"
                                                   x-model="selected.{{ $assetKey }}"
                                                   :value="'{{ $assetId }}'"
                                                   class="sr-only">

                                            {{-- Checkbox Visual --}}
                                            <div class="flex-shrink-0 {{ $isRtl ? 'ms-4' : 'me-4' }}">
                                                <div :class="isSelected('{{ $assetKey }}', '{{ $assetId }}') ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300'"
                                                     class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-colors">
                                                    <i x-show="isSelected('{{ $assetKey }}', '{{ $assetId }}')"
                                                       class="fas fa-check text-white text-xs"></i>
                                                </div>
                                            </div>

                                            {{-- Asset Image --}}
                                            @if($assetImage)
                                                <div class="flex-shrink-0 w-12 h-12 {{ $isRtl ? 'ms-3' : 'me-3' }}">
                                                    <img src="{{ $assetImage }}"
                                                         alt="{{ $assetTitle }}"
                                                         class="w-full h-full rounded-lg object-cover">
                                                </div>
                                            @else
                                                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center {{ $isRtl ? 'ms-3' : 'me-3' }}">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            @endif

                                            {{-- Asset Info --}}
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $assetTitle }}</p>
                                                @if($assetSubtitle)
                                                    <p class="text-xs text-gray-500 truncate">{{ $assetSubtitle }}</p>
                                                @endif
                                                @if($assetFollowers !== null)
                                                    <p class="text-xs text-gray-400 mt-1">
                                                        <i class="fas fa-users {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                                        {{ number_format($assetFollowers) }}
                                                    </p>
                                                @endif
                                            </div>

                                            {{-- Recommended Badge --}}
                                            @if($isRecommended)
                                                <div class="absolute -top-2 {{ $isRtl ? 'start-2' : 'end-2' }}">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-star {{ $isRtl ? 'ms-1' : 'me-1' }} text-xs"></i>
                                                        {{ __('wizard.assets.recommended') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                {{-- No Assets Found --}}
                                <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-xl">
                                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                    <p class="text-sm font-medium text-gray-600">
                                        {{ __('wizard.assets.no_assets', ['type' => strtolower($assetName)]) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ __('wizard.assets.no_assets_description', ['type' => strtolower($assetName), 'platform' => __($config['display_name'])]) }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Validation Error --}}
                    <div x-show="showValidationError"
                         x-cloak
                         class="rounded-lg bg-red-50 border border-red-200 p-4">
                        <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-600 {{ $isRtl ? 'ms-3' : 'me-3' }}"></i>
                            <p class="text-sm font-medium text-red-800">{{ __('wizard.assets.at_least_one') }}</p>
                        </div>
                    </div>

                    @if(session('error'))
                        <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-exclamation-circle text-red-600 {{ $isRtl ? 'ms-3' : 'me-3' }}"></i>
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer Actions --}}
                <div class="px-6 py-4 sm:px-8 bg-gray-50 border-t border-gray-100 flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <a href="{{ route('orgs.settings.platform-connections.wizard.start', [$currentOrg, $platform]) }}"
                       class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }} {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                        {{ __('wizard.assets.back') }}
                    </a>

                    <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <span class="text-sm text-gray-500" x-text="getSelectionCount() + ' {{ __('wizard.assets.selected') }}'"></span>
                        <button type="submit"
                                @click.prevent="validateAndSubmit($event)"
                                class="inline-flex items-center px-6 py-2.5 rounded-lg font-semibold text-white transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                style="background-color: {{ $config['color'] }};"
                                :class="{ 'opacity-75 cursor-wait': submitting }"
                                :disabled="submitting">
                            <template x-if="!submitting">
                                <span class="flex items-center">
                                    {{ __('wizard.assets.save_continue') }}
                                    <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} {{ $isRtl ? 'me-2' : 'ms-2' }}"></i>
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="flex items-center">
                                    <i class="fas fa-spinner fa-spin {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                                    {{ __('common.saving') }}
                                </span>
                            </template>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function wizardAssets(assets, smartDefaults) {
    return {
        assets: assets,
        smartDefaults: smartDefaults,
        selected: {},
        submitting: false,
        showValidationError: false,

        init() {
            // Initialize selected with smart defaults
            this.resetDefaults();
        },

        resetDefaults() {
            this.selected = {};
            // Apply smart defaults
            for (const [assetType, defaultIds] of Object.entries(this.smartDefaults)) {
                this.selected[assetType] = [...defaultIds];
            }
        },

        isSelected(assetType, assetId) {
            return this.selected[assetType]?.includes(assetId) || false;
        },

        selectAll(assetType) {
            const assetList = this.assets[assetType] || [];
            this.selected[assetType] = assetList.map(a => a.id || a.platform_id || '');
        },

        deselectAll(assetType) {
            this.selected[assetType] = [];
        },

        getSelectionCount() {
            let count = 0;
            for (const ids of Object.values(this.selected)) {
                count += ids.length;
            }
            return count;
        },

        validateAndSubmit(event) {
            if (this.getSelectionCount() === 0) {
                this.showValidationError = true;
                return;
            }
            this.showValidationError = false;
            this.submitting = true;
            event.target.closest('form').submit();
        }
    }
}
</script>
@endpush
