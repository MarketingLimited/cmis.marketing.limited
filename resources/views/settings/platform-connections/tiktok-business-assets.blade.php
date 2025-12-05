@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('settings.select_tiktok_business_assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="tiktokBusinessAssetsPage()" x-init="init()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('settings.platform_connections') }}</a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <span class="text-gray-900 font-medium">{{ __('settings.tiktok_business_assets') }}</span>
        </nav>
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.configure_tiktok_business_assets') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('settings.select_business_assets_desc') }}
                </p>
            </div>
            {{-- Refresh Button --}}
            <button type="button"
                    @click="refreshAll()"
                    :disabled="isRefreshing"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-sync-alt {{ $isRtl ? 'ms-2' : 'me-2' }}" :class="{ 'animate-spin': isRefreshing }"></i>
                <span x-text="isRefreshing ? '{{ __('settings.refreshing') }}' : '{{ __('settings.refresh_assets') }}'"></span>
            </button>
        </div>
    </div>

    {{-- Connection Info --}}
    <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0 w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center">
                <i class="fab fa-tiktok text-white text-xl"></i>
            </div>
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <p class="font-medium text-white">{{ $connection->account_name ?? __('settings.tiktok_business_center') }}</p>
                <p class="text-sm text-gray-300">
                    <i class="fas fa-check-circle text-green-400 {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                    {{ __('settings.connected') }} {{ $connection->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
    </div>

    {{-- Loading Progress Bar (shown during initial load) --}}
    <div x-show="isInitialLoading" x-cloak class="bg-white shadow sm:rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-900"></div>
                <span class="text-sm text-gray-600" x-text="loadingStatus || '{{ __('settings.loading_assets') }}'"></span>
            </div>
            <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gray-900 h-2 rounded-full transition-all duration-300" :style="'width: ' + loadingProgress + '%'"></div>
            </div>
        </div>
    </div>

    {{-- TikTok Accounts for Video Publishing Section --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-cyan-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-video text-white"></i>
                    </div>
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('settings.tiktok_accounts_for_publishing') }}</h3>
                        <p class="text-sm text-gray-500" x-text="tiktokAccounts.length + ' {{ __('settings.accounts_connected') }}'"></p>
                    </div>
                </div>
                <a href="{{ route('orgs.settings.platform-connections.tiktok.authorize', ['org' => $currentOrg, 'return_url' => route('orgs.settings.platform-connections.tiktok-business.assets', [$currentOrg, $connection->connection_id])]) }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-cyan-500 hover:from-pink-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                    <i class="fab fa-tiktok {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                    {{ __('settings.connect_tiktok_account') }}
                </a>
            </div>

            {{-- Search Input --}}
            <div class="mb-4" x-show="tiktokAccounts.length > 3">
                <input type="text"
                       x-model="tiktokAccountSearch"
                       placeholder="{{ __('settings.search_accounts') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 text-sm">
            </div>

            {{-- Loading Skeleton --}}
            <div x-show="loading.tiktokAccounts" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <template x-for="i in 2" :key="i">
                    <div class="animate-pulse flex items-center p-3 border rounded-lg">
                        <div class="w-10 h-10 bg-gray-200 rounded-lg {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Error State --}}
            <div x-show="!loading.tiktokAccounts && errors.tiktokAccounts" x-cloak
                 class="p-4 bg-red-50 border border-red-200 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
                <p class="text-red-600" x-text="errors.tiktokAccounts"></p>
                <button type="button" @click="loadTikTokAccounts()"
                        class="mt-2 text-sm text-red-700 hover:text-red-900 inline-flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-redo"></i>
                    {{ __('settings.try_again') }}
                </button>
            </div>

            {{-- Empty State --}}
            <div x-show="!loading.tiktokAccounts && !errors.tiktokAccounts && tiktokAccounts.length === 0" x-cloak
                 class="text-center py-6 bg-gray-50 rounded-lg">
                <i class="fab fa-tiktok text-gray-300 text-3xl mb-2"></i>
                <p class="text-sm text-gray-500">{{ __('settings.no_tiktok_accounts_connected') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('settings.connect_tiktok_account_hint') }}</p>
            </div>

            {{-- Accounts List --}}
            <div x-show="!loading.tiktokAccounts && !errors.tiktokAccounts && tiktokAccounts.length > 0" x-cloak>
                <div class="max-h-64 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="account in filteredTikTokAccounts" :key="account.id">
                            <div class="flex items-center p-3 border rounded-lg bg-gray-50 gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-cyan-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fab fa-tiktok text-white"></i>
                                </div>
                                <div class="min-w-0 flex-1 {{ $isRtl ? 'text-right' : '' }}">
                                    <span class="text-sm font-medium text-gray-900 block truncate" x-text="account.account_name"></span>
                                    <span class="text-xs text-gray-500 block">
                                        <i class="fas fa-check-circle text-green-500 {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                        {{ __('settings.connected') }}
                                        <template x-if="account.is_expired">
                                            <span class="text-red-500 {{ $isRtl ? 'me-1' : 'ms-1' }}">
                                                <i class="fas fa-exclamation-circle"></i>
                                                {{ __('settings.token_expired') }}
                                            </span>
                                        </template>
                                        <template x-if="account.expires_soon && !account.is_expired">
                                            <span class="text-yellow-500 {{ $isRtl ? 'me-1' : 'ms-1' }}">
                                                <i class="fas fa-clock"></i>
                                                {{ __('settings.expires_soon') }}
                                            </span>
                                        </template>
                                    </span>
                                </div>
                                {{-- Action Buttons --}}
                                <div class="flex items-center gap-1 flex-shrink-0 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    {{-- Reconnect Button --}}
                                    <a :href="'{{ route('orgs.settings.platform-connections.tiktok.authorize', ['org' => $currentOrg, 'return_url' => route('orgs.settings.platform-connections.tiktok-business.assets', [$currentOrg, $connection->connection_id])]) }}'"
                                       class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition"
                                       title="{{ __('settings.reconnect') }}">
                                        <i class="fas fa-sync-alt text-sm"></i>
                                    </a>
                                    {{-- Delete Button --}}
                                    <button type="button"
                                            @click="confirmDeleteAccount(account)"
                                            class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition"
                                            title="{{ __('settings.delete') }}">
                                        <i class="fas fa-trash-alt text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="showDeleteModal = false"
         @keydown.escape.window="showDeleteModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 {{ $isRtl ? 'text-right' : '' }}" @click.stop>
            <div class="flex items-center gap-3 mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.confirm_delete') }}</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4" x-text="'{{ __('settings.delete_tiktok_account_warning', ['name' => '']) }}' + (accountToDelete?.account_name || '')"></p>
            <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <button type="button"
                        @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    {{ __('common.cancel') }}
                </button>
                <button type="button"
                        @click="deleteAccount()"
                        :disabled="isDeleting"
                        class="flex-1 px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                    <span x-show="!isDeleting">{{ __('settings.delete') }}</span>
                    <span x-show="isDeleting" class="inline-flex items-center gap-2">
                        <i class="fas fa-spinner animate-spin"></i>
                        {{ __('settings.deleting') }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form action="{{ route('orgs.settings.platform-connections.tiktok-business.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- Ad Accounts Section --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-white"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.ad_accounts') }}</h3>
                                <p class="text-sm text-gray-500" x-text="advertisers.length + ' {{ __('settings.accounts_available') }}'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2" x-show="advertisers.length > 0">
                            <button type="button" @click="selectAllAdvertisers()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-square"></i>{{ __('settings.select_all') }}
                            </button>
                            <button type="button" @click="deselectAllAdvertisers()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-square"></i>{{ __('settings.deselect_all') }}
                            </button>
                        </div>
                    </div>

                    {{-- Search Input --}}
                    <div class="mb-4" x-show="advertisers.length > 3">
                        <input type="text"
                               x-model="advertiserSearch"
                               placeholder="{{ __('settings.search_ad_accounts') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 text-sm">
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.advertisers" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 4" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="w-10 h-10 bg-gray-200 rounded-lg {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.advertisers && errors.advertisers" x-cloak
                         class="p-4 bg-red-50 border border-red-200 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
                        <p class="text-red-600" x-text="errors.advertisers"></p>
                        <button type="button" @click="loadAdvertisers()"
                                class="mt-2 text-sm text-red-700 hover:text-red-900 inline-flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-redo"></i>
                            {{ __('settings.try_again') }}
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.advertisers && !errors.advertisers && advertisers.length === 0" x-cloak
                         class="text-center py-8 bg-gray-50 rounded-lg">
                        <i class="fas fa-ad text-gray-300 text-4xl mb-3"></i>
                        <p class="text-sm text-gray-500">{{ __('settings.no_ad_accounts_found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('settings.tiktok_no_ad_accounts_hint') }}</p>
                    </div>

                    {{-- Advertisers List --}}
                    <div x-show="!loading.advertisers && !errors.advertisers && advertisers.length > 0" x-cloak>
                        {{-- Selected Count --}}
                        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                            <span class="text-sm text-gray-500" x-show="selectedAdvertisers.length > 0">
                                <span x-text="selectedAdvertisers.length"></span> {{ __('settings.selected') }}
                            </span>
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="advertiser in filteredAdvertisers" :key="advertiser.advertiser_id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                           :class="{ 'border-gray-900 bg-gray-50': selectedAdvertisers.includes(advertiser.advertiser_id) }">
                                        <input type="checkbox"
                                               name="advertiser_ids[]"
                                               :value="advertiser.advertiser_id"
                                               x-model="selectedAdvertisers"
                                               class="h-4 w-4 text-gray-900 border-gray-300 focus:ring-gray-900 flex-shrink-0">
                                        <div class="flex items-center gap-3 flex-1 min-w-0 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-ad text-gray-600"></i>
                                            </div>
                                            <div class="min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                                                <span class="text-sm font-medium text-gray-900 block truncate" x-text="advertiser.advertiser_name"></span>
                                                <span class="text-xs text-gray-500 block">ID: <span x-text="advertiser.advertiser_id"></span></span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pixels Section --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-white"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.pixels') }}</h3>
                                <p class="text-sm text-gray-500" x-text="pixels.length + ' {{ __('settings.pixels_available') }}'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2" x-show="pixels.length > 0">
                            <button type="button" @click="selectAllPixels()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-square"></i>{{ __('settings.select_all') }}
                            </button>
                            <button type="button" @click="deselectAllPixels()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-square"></i>{{ __('settings.deselect_all') }}
                            </button>
                        </div>
                    </div>

                    {{-- Search Input --}}
                    <div class="mb-4" x-show="pixels.length > 3">
                        <input type="text"
                               x-model="pixelSearch"
                               placeholder="{{ __('settings.search_pixels') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.pixels" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 4" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="w-10 h-10 bg-gray-200 rounded-lg {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.pixels && errors.pixels" x-cloak
                         class="p-4 bg-red-50 border border-red-200 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
                        <p class="text-red-600" x-text="errors.pixels"></p>
                        <button type="button" @click="loadPixels()"
                                class="mt-2 text-sm text-red-700 hover:text-red-900 inline-flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-redo"></i>
                            {{ __('settings.try_again') }}
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.pixels && !errors.pixels && pixels.length === 0" x-cloak
                         class="text-center py-8 bg-gray-50 rounded-lg">
                        <i class="fas fa-chart-line text-gray-300 text-4xl mb-3"></i>
                        <p class="text-sm text-gray-500">{{ __('settings.no_pixels_found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('settings.tiktok_no_pixels_hint') }}</p>
                    </div>

                    {{-- Pixels List --}}
                    <div x-show="!loading.pixels && !errors.pixels && pixels.length > 0" x-cloak>
                        {{-- Selected Count --}}
                        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                            <span class="text-sm text-gray-500" x-show="selectedPixels.length > 0">
                                <span x-text="selectedPixels.length"></span> {{ __('settings.selected') }}
                            </span>
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="pixel in filteredPixels" :key="pixel.pixel_id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                           :class="{ 'border-purple-600 bg-purple-50': selectedPixels.includes(pixel.pixel_id) }">
                                        <input type="checkbox"
                                               name="pixel_ids[]"
                                               :value="pixel.pixel_id"
                                               x-model="selectedPixels"
                                               class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500 flex-shrink-0">
                                        <div class="flex items-center gap-3 flex-1 min-w-0 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-chart-line text-purple-600"></i>
                                            </div>
                                            <div class="min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                                                <span class="text-sm font-medium text-gray-900 block truncate" x-text="pixel.pixel_name"></span>
                                                <span class="text-xs text-gray-500 block">ID: <span x-text="pixel.pixel_id"></span></span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Catalogs Section --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-th-large text-white"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('settings.catalogs') }}</h3>
                                <p class="text-sm text-gray-500" x-text="catalogs.length + ' {{ __('settings.catalogs_available') }}'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2" x-show="catalogs.length > 0">
                            <button type="button" @click="selectAllCatalogs()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-check-square"></i>{{ __('settings.select_all') }}
                            </button>
                            <button type="button" @click="deselectAllCatalogs()" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-square"></i>{{ __('settings.deselect_all') }}
                            </button>
                        </div>
                    </div>

                    {{-- Search Input --}}
                    <div class="mb-4" x-show="catalogs.length > 3">
                        <input type="text"
                               x-model="catalogSearch"
                               placeholder="{{ __('settings.search_catalogs') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.catalogs" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 4" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="w-10 h-10 bg-gray-200 rounded-lg {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.catalogs && errors.catalogs" x-cloak
                         class="p-4 bg-red-50 border border-red-200 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
                        <p class="text-red-600" x-text="errors.catalogs"></p>
                        <button type="button" @click="loadCatalogs()"
                                class="mt-2 text-sm text-red-700 hover:text-red-900 inline-flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-redo"></i>
                            {{ __('settings.try_again') }}
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.catalogs && !errors.catalogs && catalogs.length === 0" x-cloak
                         class="text-center py-8 bg-gray-50 rounded-lg">
                        <i class="fas fa-th-large text-gray-300 text-4xl mb-3"></i>
                        <p class="text-sm text-gray-500">{{ __('settings.no_catalogs_found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('settings.tiktok_no_catalogs_hint') }}</p>
                    </div>

                    {{-- Catalogs List --}}
                    <div x-show="!loading.catalogs && !errors.catalogs && catalogs.length > 0" x-cloak>
                        {{-- Selected Count --}}
                        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                            <span class="text-sm text-gray-500" x-show="selectedCatalogs.length > 0">
                                <span x-text="selectedCatalogs.length"></span> {{ __('settings.selected') }}
                            </span>
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="catalog in filteredCatalogs" :key="catalog.catalog_id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                           :class="{ 'border-orange-500 bg-orange-50': selectedCatalogs.includes(catalog.catalog_id) }">
                                        <input type="checkbox"
                                               name="catalog_ids[]"
                                               :value="catalog.catalog_id"
                                               x-model="selectedCatalogs"
                                               class="h-4 w-4 text-orange-500 border-gray-300 focus:ring-orange-500 flex-shrink-0">
                                        <div class="flex items-center gap-3 flex-1 min-w-0 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-th-large text-orange-600"></i>
                                            </div>
                                            <div class="min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                                                <span class="text-sm font-medium text-gray-900 block truncate" x-text="catalog.catalog_name"></span>
                                                <span class="text-xs text-gray-500 block">ID: <span x-text="catalog.catalog_id"></span></span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Help Section --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 {{ $isRtl ? 'text-right' : '' }}">
                <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-info-circle text-blue-500 flex-shrink-0 mt-0.5"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium mb-1">{{ __('settings.about_tiktok_business_assets') }}</p>
                        <ul class="list-disc {{ $isRtl ? 'pe-4' : 'ps-4' }} space-y-1 text-blue-600">
                            <li>{{ __('settings.tiktok_business_hint_1') }}</li>
                            <li>{{ __('settings.tiktok_business_hint_2') }}</li>
                            <li>{{ __('settings.tiktok_business_hint_3') }}</li>
                            <li>{{ __('settings.tiktok_business_hint_4') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                    <i class="fas fa-save {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                    {{ __('settings.save_assets') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function tiktokBusinessAssetsPage() {
    return {
        // API Configuration
        connectionId: '{{ $connection->connection_id }}',
        orgId: '{{ $currentOrg }}',
        apiBaseUrl: '/orgs/{{ $currentOrg }}/settings/platform-connections/tiktok-business/{{ $connection->connection_id }}/assets/ajax',

        // Loading states
        isInitialLoading: true,
        isRefreshing: false,
        loadingStatus: '',
        loadingProgress: 0,

        // Per-asset loading/error tracking
        loading: {
            tiktokAccounts: true,
            advertisers: true,
            pixels: true,
            catalogs: true,
        },
        errors: {
            tiktokAccounts: null,
            advertisers: null,
            pixels: null,
            catalogs: null,
        },

        // Asset data (loaded via AJAX)
        tiktokAccounts: [],
        advertisers: [],
        pixels: [],
        catalogs: [],

        // Search filters
        tiktokAccountSearch: '',
        advertiserSearch: '',
        pixelSearch: '',
        catalogSearch: '',

        // Selected items (pre-populated from server)
        selectedAdvertisers: @json($selectedAssets['advertiser_ids'] ?? []),
        selectedPixels: @json($selectedAssets['pixel_ids'] ?? []),
        selectedCatalogs: @json($selectedAssets['catalog_ids'] ?? []),

        // Delete modal state
        showDeleteModal: false,
        accountToDelete: null,
        isDeleting: false,

        // Computed: filtered lists
        get filteredTikTokAccounts() {
            if (!Array.isArray(this.tiktokAccounts)) return [];
            if (!this.tiktokAccountSearch) return this.tiktokAccounts;
            const search = this.tiktokAccountSearch.toLowerCase();
            return this.tiktokAccounts.filter(a =>
                a && a.account_name && a.account_name.toLowerCase().includes(search)
            );
        },

        get filteredAdvertisers() {
            if (!Array.isArray(this.advertisers)) return [];
            if (!this.advertiserSearch) return this.advertisers;
            const search = this.advertiserSearch.toLowerCase();
            return this.advertisers.filter(a =>
                a && (
                    (a.advertiser_name && a.advertiser_name.toLowerCase().includes(search)) ||
                    (a.advertiser_id && a.advertiser_id.toLowerCase().includes(search))
                )
            );
        },

        get filteredPixels() {
            if (!Array.isArray(this.pixels)) return [];
            if (!this.pixelSearch) return this.pixels;
            const search = this.pixelSearch.toLowerCase();
            return this.pixels.filter(p =>
                p && (
                    (p.pixel_name && p.pixel_name.toLowerCase().includes(search)) ||
                    (p.pixel_id && p.pixel_id.toLowerCase().includes(search))
                )
            );
        },

        get filteredCatalogs() {
            if (!Array.isArray(this.catalogs)) return [];
            if (!this.catalogSearch) return this.catalogs;
            const search = this.catalogSearch.toLowerCase();
            return this.catalogs.filter(c =>
                c && (
                    (c.catalog_name && c.catalog_name.toLowerCase().includes(search)) ||
                    (c.catalog_id && c.catalog_id.toLowerCase().includes(search))
                )
            );
        },

        // Initialize - load all assets
        async init() {
            this.loadingStatus = '{{ __('settings.loading_tiktok_accounts') }}';
            this.loadingProgress = 0;

            // Load all assets in parallel
            await Promise.allSettled([
                this.loadTikTokAccounts(),
                this.loadAdvertisers(),
                this.loadPixels(),
                this.loadCatalogs(),
            ]);

            this.loadingProgress = 100;
            this.isInitialLoading = false;
        },

        // Load TikTok accounts for publishing
        async loadTikTokAccounts() {
            this.loading.tiktokAccounts = true;
            this.errors.tiktokAccounts = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/tiktok-accounts`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.tiktokAccounts = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('settings.failed_to_load_tiktok_accounts') }}');
                }
            } catch (error) {
                this.errors.tiktokAccounts = error.message;
                console.error('Failed to load TikTok accounts:', error);
            } finally {
                this.loading.tiktokAccounts = false;
                this.loadingProgress = Math.min(this.loadingProgress + 25, 95);
            }
        },

        // Load advertisers
        async loadAdvertisers() {
            this.loading.advertisers = true;
            this.errors.advertisers = null;
            this.loadingStatus = '{{ __('settings.loading_ad_accounts') }}';

            try {
                const response = await fetch(`${this.apiBaseUrl}/advertisers`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.advertisers = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('settings.failed_to_load_tiktok_advertisers') }}');
                }
            } catch (error) {
                this.errors.advertisers = error.message;
                console.error('Failed to load advertisers:', error);
            } finally {
                this.loading.advertisers = false;
                this.loadingProgress = Math.min(this.loadingProgress + 25, 95);
            }
        },

        // Load pixels
        async loadPixels() {
            this.loading.pixels = true;
            this.errors.pixels = null;
            this.loadingStatus = '{{ __('settings.loading_pixels') }}';

            try {
                const response = await fetch(`${this.apiBaseUrl}/pixels`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.pixels = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('settings.failed_to_load_tiktok_pixels') }}');
                }
            } catch (error) {
                this.errors.pixels = error.message;
                console.error('Failed to load pixels:', error);
            } finally {
                this.loading.pixels = false;
                this.loadingProgress = Math.min(this.loadingProgress + 25, 95);
            }
        },

        // Load catalogs
        async loadCatalogs() {
            this.loading.catalogs = true;
            this.errors.catalogs = null;
            this.loadingStatus = '{{ __('settings.loading_catalogs') }}';

            try {
                const response = await fetch(`${this.apiBaseUrl}/catalogs`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.catalogs = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('settings.failed_to_load_tiktok_catalogs') }}');
                }
            } catch (error) {
                this.errors.catalogs = error.message;
                console.error('Failed to load catalogs:', error);
            } finally {
                this.loading.catalogs = false;
                this.loadingProgress = Math.min(this.loadingProgress + 25, 95);
            }
        },

        // Refresh all assets
        async refreshAll() {
            this.isRefreshing = true;

            try {
                // Clear server-side cache
                await fetch(`${this.apiBaseUrl}/refresh`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });

                // Reset state and reload
                this.tiktokAccounts = [];
                this.advertisers = [];
                this.pixels = [];
                this.catalogs = [];
                this.loadingProgress = 0;
                this.isInitialLoading = true;

                await this.init();
            } catch (error) {
                console.error('Failed to refresh assets:', error);
            } finally {
                this.isRefreshing = false;
            }
        },

        // Confirm delete account
        confirmDeleteAccount(account) {
            this.accountToDelete = account;
            this.showDeleteModal = true;
        },

        // Delete TikTok account
        async deleteAccount() {
            if (!this.accountToDelete) return;

            this.isDeleting = true;

            try {
                const response = await fetch(`${this.apiBaseUrl}/tiktok-accounts/${this.accountToDelete.id}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    // Remove from local list
                    this.tiktokAccounts = this.tiktokAccounts.filter(a => a.id !== this.accountToDelete.id);
                    this.showDeleteModal = false;
                    this.accountToDelete = null;
                } else {
                    throw new Error(data.message || '{{ __('settings.failed_to_delete_tiktok_account') }}');
                }
            } catch (error) {
                console.error('Failed to delete account:', error);
                alert(error.message);
            } finally {
                this.isDeleting = false;
            }
        },

        // Bulk selection methods
        selectAllAdvertisers() {
            this.filteredAdvertisers.forEach(a => {
                if (!this.selectedAdvertisers.includes(a.advertiser_id)) {
                    this.selectedAdvertisers.push(a.advertiser_id);
                }
            });
        },

        deselectAllAdvertisers() {
            if (this.advertiserSearch) {
                const filteredIds = this.filteredAdvertisers.map(a => a.advertiser_id);
                this.selectedAdvertisers = this.selectedAdvertisers.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedAdvertisers = [];
            }
        },

        selectAllPixels() {
            this.filteredPixels.forEach(p => {
                if (!this.selectedPixels.includes(p.pixel_id)) {
                    this.selectedPixels.push(p.pixel_id);
                }
            });
        },

        deselectAllPixels() {
            if (this.pixelSearch) {
                const filteredIds = this.filteredPixels.map(p => p.pixel_id);
                this.selectedPixels = this.selectedPixels.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedPixels = [];
            }
        },

        selectAllCatalogs() {
            this.filteredCatalogs.forEach(c => {
                if (!this.selectedCatalogs.includes(c.catalog_id)) {
                    this.selectedCatalogs.push(c.catalog_id);
                }
            });
        },

        deselectAllCatalogs() {
            if (this.catalogSearch) {
                const filteredIds = this.filteredCatalogs.map(c => c.catalog_id);
                this.selectedCatalogs = this.selectedCatalogs.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedCatalogs = [];
            }
        },
    }
}
</script>
@endpush
