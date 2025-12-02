@extends('layouts.admin')

@section('title', __('wizard.mode.title', ['platform' => __($config['display_name'])]) . ' - ' . __('settings.settings'))

@section('content')
<div class="min-h-screen bg-gray-50 py-6 sm:py-12" x-data="wizardMode()">
    {{-- Breadcrumb --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <nav class="text-sm text-gray-500 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.platform-connections.wizard.dashboard', $currentOrg) }}" class="hover:text-blue-600 transition">
                {{ __('wizard.dashboard.title') }}
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __($config['display_name']) }}</span>
        </nav>
    </div>

    {{-- Step Progress Indicator --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="flex items-center justify-center">
            <div class="flex items-center">
                {{-- Step 1: Connect (Active) --}}
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-sm">
                        1
                    </div>
                    <span class="hidden sm:block {{ $isRtl ? 'me-3' : 'ms-3' }} text-sm font-medium text-blue-600">
                        {{ __('wizard.steps.connect') }}
                    </span>
                </div>

                {{-- Connector --}}
                <div class="w-12 sm:w-24 h-1 bg-gray-200 mx-2 sm:mx-4"></div>

                {{-- Step 2: Assets (Upcoming) --}}
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-sm">
                        2
                    </div>
                    <span class="hidden sm:block {{ $isRtl ? 'me-3' : 'ms-3' }} text-sm font-medium text-gray-500">
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

    {{-- Main Content Card --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            {{-- Platform Header --}}
            <div class="px-6 py-8 sm:px-8 sm:py-10 text-center border-b border-gray-100"
                 style="background: linear-gradient(135deg, {{ $config['color'] }}10 0%, {{ $config['color'] }}05 100%);">
                <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center mb-4"
                     style="background-color: {{ $config['color'] }}20;">
                    <i class="{{ $config['icon'] }} text-4xl" style="color: {{ $config['color'] }};"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ __('wizard.mode.title', ['platform' => __($config['display_name'])]) }}
                </h1>
                <p class="mt-2 text-gray-500">
                    {{ __('wizard.mode.subtitle', ['platform' => __($config['display_name'])]) }}
                </p>
            </div>

            {{-- Connection Options --}}
            <div class="p-6 sm:p-8 space-y-6">
                {{-- Existing Connection Warning --}}
                @if($existingConnection)
                    <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                        <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-triangle text-yellow-600 {{ $isRtl ? 'ms-3' : 'me-3' }} mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-yellow-800">
                                    {{ __('wizard.mode.existing_connection') }}
                                </p>
                                <p class="mt-1 text-sm text-yellow-700">
                                    {{ __('wizard.mode.existing_connection_help') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Direct Connect (OAuth) - Primary Option --}}
                @if($config['supports_oauth'])
                    <div class="relative">
                        <div class="border-2 border-blue-200 rounded-xl p-6 bg-blue-50/30 hover:border-blue-400 transition-colors">
                            {{-- Recommended Badge --}}
                            <div class="absolute -top-3 {{ $isRtl ? 'start-4' : 'end-4' }}">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-600 text-white">
                                    <i class="fas fa-star {{ $isRtl ? 'ms-1' : 'me-1' }} text-xs"></i>
                                    {{ __('wizard.assets.recommended') }}
                                </span>
                            </div>

                            <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center {{ $isRtl ? 'ms-4' : 'me-4' }}">
                                    <i class="fas fa-bolt text-blue-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ __('wizard.mode.direct.title') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('wizard.mode.direct.description') }}
                                    </p>

                                    <div class="mt-4">
                                        <a href="{{ route($config['oauth_route'], $currentOrg) }}?wizard_mode=1"
                                           class="inline-flex items-center px-6 py-3 rounded-lg font-semibold text-white transition-all duration-200 hover:opacity-90 focus:ring-2 focus:ring-offset-2"
                                           style="background-color: {{ $config['color'] }}; --tw-ring-color: {{ $config['color'] }};"
                                           @click="connecting = true"
                                           :class="{ 'opacity-75 cursor-wait': connecting }">
                                            <template x-if="!connecting">
                                                <span class="flex items-center">
                                                    <i class="{{ $config['icon'] }} {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                                                    {{ __('wizard.mode.direct.button', ['platform' => __($config['display_name'])]) }}
                                                </span>
                                            </template>
                                            <template x-if="connecting">
                                                <span class="flex items-center">
                                                    <i class="fas fa-spinner fa-spin {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                                                    {{ __('wizard.mode.direct.connecting') }}
                                                </span>
                                            </template>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Manual Connect (Advanced) - Collapsible --}}
                @if($config['supports_manual'])
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        {{-- Toggle Header --}}
                        <button @click="showAdvanced = !showAdvanced"
                                class="w-full flex items-center justify-between px-6 py-4 text-start hover:bg-gray-50 transition-colors">
                            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-cog text-gray-400 {{ $isRtl ? 'ms-3' : 'me-3' }}"></i>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ __('wizard.mode.manual.toggle') }}
                                </span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200"
                               :class="{ 'rotate-180': showAdvanced }"></i>
                        </button>

                        {{-- Manual Connect Content --}}
                        <div x-show="showAdvanced"
                             x-collapse
                             class="border-t border-gray-200 px-6 py-6 bg-gray-50">
                            <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gray-200 flex items-center justify-center {{ $isRtl ? 'ms-4' : 'me-4' }}">
                                    <i class="fas fa-key text-gray-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ __('wizard.mode.manual.title') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('wizard.mode.manual.description') }}
                                    </p>

                                    {{-- Manual Form --}}
                                    <form method="POST"
                                          action="{{ route('orgs.settings.platform-connections.wizard.manual', [$currentOrg, $platform]) }}"
                                          class="mt-4 space-y-4"
                                          @submit="submittingManual = true">
                                        @csrf

                                        @if($platform === 'meta')
                                            <div>
                                                <label for="access_token" class="block text-sm font-medium text-gray-700 mb-1">
                                                    {{ __('wizard.platforms.meta.manual_label') }}
                                                </label>
                                                <input type="password"
                                                       name="access_token"
                                                       id="access_token"
                                                       required
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="EAAxxxxxxx...">
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ __('wizard.platforms.meta.manual_help') }}
                                                </p>
                                            </div>
                                        @elseif($platform === 'google')
                                            <div>
                                                <label for="service_account" class="block text-sm font-medium text-gray-700 mb-1">
                                                    {{ __('wizard.platforms.google.manual_label') }}
                                                </label>
                                                <input type="file"
                                                       name="service_account"
                                                       id="service_account"
                                                       accept=".json"
                                                       required
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ __('wizard.platforms.google.manual_help') }}
                                                </p>
                                            </div>
                                        @endif

                                        <button type="submit"
                                                class="inline-flex items-center px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                                                :class="{ 'opacity-75 cursor-wait': submittingManual }"
                                                :disabled="submittingManual">
                                            <template x-if="!submittingManual">
                                                <span class="flex items-center">
                                                    <i class="fas fa-key {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                                                    {{ __('wizard.mode.manual.button') }}
                                                </span>
                                            </template>
                                            <template x-if="submittingManual">
                                                <span class="flex items-center">
                                                    <i class="fas fa-spinner fa-spin {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                                                    {{ __('wizard.mode.direct.connecting') }}
                                                </span>
                                            </template>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Error Messages --}}
                @if(session('error'))
                    <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                        <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-600 {{ $isRtl ? 'ms-3' : 'me-3' }}"></i>
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <a href="{{ route('orgs.settings.platform-connections.wizard.dashboard', $currentOrg) }}"
                   class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }} {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                    {{ __('wizard.mode.back_to_dashboard') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function wizardMode() {
    return {
        connecting: false,
        showAdvanced: false,
        submittingManual: false
    }
}
</script>
@endpush
