@extends('layouts.admin')

@section('title', __('wizard.success.title', ['platform' => __($config['display_name'])]))

@section('content')
<div class="min-h-screen bg-gray-50 py-6 sm:py-12">
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

                {{-- Step 2: Assets (Completed) --}}
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                    <span class="hidden sm:block {{ $isRtl ? 'me-3' : 'ms-3' }} text-sm font-medium text-green-600">
                        {{ __('wizard.steps.assets') }}
                    </span>
                </div>

                {{-- Connector --}}
                <div class="w-12 sm:w-24 h-1 bg-green-600 mx-2 sm:mx-4"></div>

                {{-- Step 3: Complete (Active) --}}
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                    <span class="hidden sm:block {{ $isRtl ? 'me-3' : 'ms-3' }} text-sm font-medium text-green-600">
                        {{ __('wizard.steps.complete') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Card --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            {{-- Success Header --}}
            <div class="px-6 py-10 sm:px-8 sm:py-12 text-center"
                 style="background: linear-gradient(135deg, {{ $config['color'] }}15 0%, {{ $config['color'] }}05 100%);">
                {{-- Success Animation --}}
                <div class="w-24 h-24 mx-auto mb-6 relative">
                    <div class="absolute inset-0 rounded-full animate-ping opacity-25"
                         style="background-color: {{ $config['color'] }};"></div>
                    <div class="relative w-24 h-24 rounded-full flex items-center justify-center"
                         style="background-color: {{ $config['color'] }}20;">
                        <div class="w-16 h-16 rounded-full bg-green-500 flex items-center justify-center">
                            <i class="fas fa-check text-white text-3xl"></i>
                        </div>
                    </div>
                </div>

                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                    {{ __('wizard.success.title', ['platform' => __($config['display_name'])]) }}
                </h1>
                <p class="mt-2 text-gray-600">
                    {{ __('wizard.success.subtitle') }}
                </p>
            </div>

            {{-- Connection Summary --}}
            <div class="px-6 py-6 sm:px-8 border-t border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                    {{ __('wizard.success.summary.title') }}
                </h3>

                <div class="space-y-4">
                    {{-- Platform Info --}}
                    <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} p-4 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center {{ $isRtl ? 'ms-4' : 'me-4' }}"
                             style="background-color: {{ $config['color'] }}20;">
                            <i class="{{ $config['icon'] }} text-xl" style="color: {{ $config['color'] }};"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ __($config['display_name']) }}</p>
                            <p class="text-sm text-gray-500">{{ $connection->name ?? $connection->account_name ?? 'Connected' }}</p>
                        </div>
                        <div class="{{ $isRtl ? 'me-4' : 'ms-4' }}">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                {{ __('wizard.dashboard.connected') }}
                            </span>
                        </div>
                    </div>

                    {{-- Synced Assets --}}
                    @if(count($syncedAssets) > 0)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <p class="text-sm font-medium text-gray-700">
                                    {{ trans_choice('wizard.success.summary.assets_synced', count($syncedAssets), ['count' => count($syncedAssets)]) }}
                                </p>
                            </div>
                            <ul class="divide-y divide-gray-100">
                                @foreach($syncedAssets as $asset)
                                    <li class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} px-4 py-3 hover:bg-gray-50">
                                        @if(isset($asset['picture']) || isset($asset['thumbnail']))
                                            <img src="{{ $asset['picture'] ?? $asset['thumbnail'] }}"
                                                 alt="{{ $asset['name'] ?? $asset['title'] ?? '' }}"
                                                 class="w-10 h-10 rounded-lg object-cover {{ $isRtl ? 'ms-3' : 'me-3' }}">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center {{ $isRtl ? 'ms-3' : 'me-3' }}">
                                                @php
                                                    $assetIcon = match($asset['type'] ?? 'default') {
                                                        'page' => 'fa-flag',
                                                        'instagram' => 'fa-instagram',
                                                        'threads' => 'fa-at',
                                                        'ad_account' => 'fa-ad',
                                                        'pixel' => 'fa-code',
                                                        'catalog' => 'fa-shopping-bag',
                                                        'youtube_channel' => 'fa-youtube',
                                                        'analytics' => 'fa-chart-line',
                                                        'profile' => 'fa-user',
                                                        'account' => 'fa-user-circle',
                                                        default => 'fa-check-circle',
                                                    };
                                                @endphp
                                                <i class="fas {{ $assetIcon }} text-gray-400"></i>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $asset['name'] ?? $asset['title'] ?? $asset['username'] ?? 'Asset' }}
                                            </p>
                                            <p class="text-xs text-gray-500 capitalize">
                                                {{ str_replace('_', ' ', $asset['type'] ?? '') }}
                                            </p>
                                        </div>
                                        <i class="fas fa-check-circle text-green-500 {{ $isRtl ? 'me-3' : 'ms-3' }}"></i>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-6 py-6 sm:px-8 bg-gray-50 border-t border-gray-100">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    {{-- Connect Another Platform --}}
                    <a href="{{ route('orgs.settings.platform-connections.wizard.dashboard', $currentOrg) }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <i class="fas fa-plus-circle {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                        {{ __('wizard.success.connect_another') }}
                    </a>

                    {{-- Done / Go to Connections --}}
                    <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-lg text-sm font-medium text-white transition-colors"
                       style="background-color: {{ $config['color'] }};">
                        <i class="fas fa-check {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                        {{ __('wizard.success.done') }}
                    </a>
                </div>

                {{-- View All Connections Link --}}
                <div class="mt-4 text-center">
                    <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                       class="text-sm text-gray-500 hover:text-gray-700 underline">
                        {{ __('wizard.success.view_connections') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Tips Card --}}
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-lightbulb text-yellow-500 {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                {{ __('common.tips') ?? 'Tips' }}
            </h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-start {{ $isRtl ? 'flex-row-reverse text-end' : '' }}">
                    <i class="fas fa-check text-green-500 {{ $isRtl ? 'ms-2' : 'me-2' }} mt-1 flex-shrink-0"></i>
                    <span>{{ __('wizard.tips.sync_data') ?? 'Your data will begin syncing automatically' }}</span>
                </li>
                <li class="flex items-start {{ $isRtl ? 'flex-row-reverse text-end' : '' }}">
                    <i class="fas fa-check text-green-500 {{ $isRtl ? 'ms-2' : 'me-2' }} mt-1 flex-shrink-0"></i>
                    <span>{{ __('wizard.tips.manage_assets') ?? 'You can add or remove assets anytime from the connections page' }}</span>
                </li>
                <li class="flex items-start {{ $isRtl ? 'flex-row-reverse text-end' : '' }}">
                    <i class="fas fa-check text-green-500 {{ $isRtl ? 'ms-2' : 'me-2' }} mt-1 flex-shrink-0"></i>
                    <span>{{ __('wizard.tips.token_refresh') ?? 'Access tokens are refreshed automatically when needed' }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@keyframes ping {
    75%, 100% {
        transform: scale(1.5);
        opacity: 0;
    }
}
.animate-ping {
    animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}
</style>
@endpush
