@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('Select Meta Assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="metaAssetsPage()" x-init="init()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Platform Connections') }}</a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <span class="text-gray-900 font-medium">{{ __('Meta Assets') }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('Configure Meta Assets') }}</h1>
                <p class="mt-1 text-sm text-gray-500 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('Select multiple assets: Facebook Pages, Instagram accounts, Threads accounts, Ad Accounts, Pixels, and Catalogs for this organization.') }}
                </p>
            </div>
            {{-- Refresh All Button --}}
            <button type="button" @click="refreshAll()" :disabled="isRefreshing"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': isRefreshing }"></i>
                <span x-text="isRefreshing ? '{{ __('Refreshing...') }}' : '{{ __('Refresh Assets') }}'"></span>
            </button>
        </div>
        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700 {{ $isRtl ? 'text-right' : '' }}">
            <i class="fas fa-info-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
            {{ __('You can select multiple accounts per asset type (e.g., multiple Pages, Ad Accounts).') }}
        </div>
    </div>

    {{-- Connection Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fab fa-facebook text-blue-600 text-xl"></i>
            </div>
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <p class="font-medium text-blue-900">{{ $connection->account_name }}</p>
                <p class="text-sm text-blue-700">
                    @if($connection->account_metadata['is_system_user'] ?? false)
                        <i class="fas fa-check-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('System User Token') }}
                    @endif
                    &bull; {{ __('Connected') }} {{ $connection->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
    </div>

    {{-- Loading Progress Bar --}}
    <div x-show="isInitialLoading" x-cloak class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <i class="fas fa-spinner fa-spin text-blue-600 text-2xl"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ __('Loading assets from Meta Business Manager...') }}</p>
                <p class="text-xs text-gray-500 mt-1" x-text="loadingStatus"></p>
                <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full transition-all duration-300"
                         :style="'width: ' + loadingProgress + '%'"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form x-show="!isInitialLoading" x-cloak action="{{ route('orgs.settings.platform-connections.meta.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- ========== Facebook Pages Section ========== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fab fa-facebook text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Facebook Pages') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.pages" class="inline-flex items-center">
                                        <i class="fas fa-spinner fa-spin me-1"></i>{{ __('Loading...') }}
                                    </span>
                                    <span x-show="!loading.pages && !errors.pages" x-text="pages.length + ' {{ __('page(s) available') }}'"></span>
                                    <span x-show="errors.pages" class="text-red-600">{{ __('Failed to load') }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="errors.pages" @click="loadPages()" class="text-sm text-blue-600 hover:text-blue-800">
                                <i class="fas fa-redo me-1"></i>{{ __('Retry') }}
                            </button>
                            <button type="button" @click="showManualPage = !showManualPage" class="text-sm text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <i class="fas fa-plus"></i>{{ __('Add manually') }}
                            </button>
                        </div>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.pages" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 4" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded me-3"></div>
                                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                                <div class="ms-3 flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.pages && errors.pages" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                        <p class="text-sm text-red-700" x-text="errors.pages"></p>
                        <button type="button" @click="loadPages()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                            {{ __('Retry') }}
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.pages && !errors.pages && pages.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fab fa-facebook text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Facebook Pages found') }}</p>
                    </div>

                    {{-- Pages List --}}
                    <div x-show="!loading.pages && !errors.pages && pages.length > 0">
                        {{-- Search & Bulk Actions --}}
                        <div class="mb-4 space-y-2">
                            <input type="text" x-model="pagesSearch" @input.debounce.300ms placeholder="{{ __('Search pages by name...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <div class="flex gap-2">
                                <button type="button" @click="selectAllPages" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                    <i class="fas fa-check-square"></i>{{ __('Select All Visible') }}
                                </button>
                                <button type="button" @click="deselectAllPages" class="text-xs text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                    <i class="fas fa-square"></i>{{ __('Deselect All') }}
                                </button>
                                <span class="text-xs text-gray-500" x-show="selectedPages.length > 0">
                                    (<span x-text="selectedPages.length"></span> {{ __('selected') }})
                                </span>
                            </div>
                        </div>

                        {{-- Virtual Scrolling Container --}}
                        <div class="max-h-96 overflow-y-auto" @scroll="handleScroll('pages', $event)">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="page in filteredPages" :key="page.id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition gap-3"
                                           :class="{ 'border-blue-500 bg-blue-50': selectedPages.includes(page.id) }">
                                        <input type="checkbox" name="page[]" :value="page.id"
                                               x-model="selectedPages"
                                               class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500 flex-shrink-0">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <img x-show="page.picture" :src="page.picture" class="w-8 h-8 rounded-full flex-shrink-0">
                                            <div x-show="!page.picture" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-flag text-gray-400"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-sm font-medium text-gray-900 block truncate" x-text="page.name"></span>
                                                <span x-show="page.category" class="block text-xs text-gray-500" x-text="page.category"></span>
                                                <span x-show="page.has_instagram" class="text-xs text-pink-600 inline-flex items-center gap-1">
                                                    <i class="fab fa-instagram"></i>{{ __('Instagram connected') }}
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Manual Page ID Input --}}
                    <div x-show="showManualPage" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Page ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_page_ids[]" placeholder="e.g., 123456789012345"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <button type="button" @click="showManualPage = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========== Instagram Accounts Section ========== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                                <i class="fab fa-instagram text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Instagram Accounts') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.instagram" class="inline-flex items-center">
                                        <i class="fas fa-spinner fa-spin me-1"></i>{{ __('Loading...') }}
                                    </span>
                                    <span x-show="!loading.instagram && !errors.instagram" x-text="instagramAccounts.length + ' {{ __('account(s) available') }}'"></span>
                                    <span x-show="errors.instagram" class="text-red-600">{{ __('Failed to load') }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="errors.instagram" @click="loadInstagramAccounts()" class="text-sm text-pink-600 hover:text-pink-800">
                                <i class="fas fa-redo me-1"></i>{{ __('Retry') }}
                            </button>
                            <button type="button" @click="showManualInstagram = !showManualInstagram" class="text-sm text-pink-600 hover:text-pink-800">
                                <i class="fas fa-plus me-1"></i>{{ __('Add manually') }}
                            </button>
                        </div>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.instagram" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 4" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded me-3"></div>
                                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                                <div class="ms-3 flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.instagram && errors.instagram" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                        <p class="text-sm text-red-700" x-text="errors.instagram"></p>
                        <button type="button" @click="loadInstagramAccounts()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                            {{ __('Retry') }}
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.instagram && !errors.instagram && instagramAccounts.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fab fa-instagram text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Instagram accounts found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Connect Instagram to a Facebook Page first') }}</p>
                    </div>

                    {{-- Instagram List --}}
                    <div x-show="!loading.instagram && !errors.instagram && instagramAccounts.length > 0">
                        <div class="mb-4 space-y-2">
                            <input type="text" x-model="instagramSearch" @input.debounce.300ms placeholder="{{ __('Search Instagram accounts by username...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 text-sm">
                            <div class="flex gap-2">
                                <button type="button" @click="selectAllInstagram" class="text-xs text-pink-600 hover:text-pink-800">
                                    <i class="fas fa-check-square me-1"></i>{{ __('Select All Visible') }}
                                </button>
                                <button type="button" @click="deselectAllInstagram" class="text-xs text-pink-600 hover:text-pink-800">
                                    <i class="fas fa-square me-1"></i>{{ __('Deselect All') }}
                                </button>
                                <span class="text-xs text-gray-500" x-show="selectedInstagrams.length > 0">
                                    (<span x-text="selectedInstagrams.length"></span> {{ __('selected') }})
                                </span>
                            </div>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="ig in filteredInstagram" :key="ig.id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                           :class="{ 'border-pink-500 bg-pink-50': selectedInstagrams.includes(ig.id) }">
                                        <input type="checkbox" name="instagram_account[]" :value="ig.id"
                                               x-model="selectedInstagrams"
                                               class="h-4 w-4 text-pink-600 border-gray-300 focus:ring-pink-500">
                                        <div class="ms-3 flex items-center gap-3">
                                            <img x-show="ig.profile_picture" :src="ig.profile_picture" class="w-8 h-8 rounded-full">
                                            <div x-show="!ig.profile_picture" class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center">
                                                <i class="fab fa-instagram text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900" x-text="ig.username || ig.name"></span>
                                                <span x-show="ig.followers_count" class="block text-xs text-gray-500" x-text="ig.followers_count.toLocaleString() + ' {{ __('settings.followers_label') }}'"></span>
                                                <span x-show="ig.connected_page_name" class="text-xs text-blue-600">
                                                    <i class="fab fa-facebook me-1"></i><span x-text="ig.connected_page_name"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Manual Input --}}
                    <div x-show="showManualInstagram" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Instagram Business Account ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_instagram_account_ids[]" placeholder="e.g., 17841400000000000"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 text-sm">
                            <button type="button" @click="showManualInstagram = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========== Threads Section ========== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Threads Accounts') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.threads" class="inline-flex items-center">
                                        <i class="fas fa-spinner fa-spin me-1"></i>{{ __('Loading...') }}
                                    </span>
                                    <span x-show="!loading.threads" x-text="threadsAccounts.length + ' {{ __('account(s) available') }}'"></span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualThreads = !showManualThreads" class="text-sm text-gray-600 hover:text-gray-800">
                            <i class="fas fa-plus me-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <div class="text-xs text-blue-700">
                                <p class="font-medium">{{ __('Threads uses the same ID as Instagram Business accounts') }}</p>
                                <p class="mt-1">{{ __('Select an Instagram account below to use for Threads, or enter a Threads ID manually.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.threads" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded me-3"></div>
                                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                                <div class="ms-3 flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Use Instagram accounts for Threads --}}
                    <div x-show="!loading.threads && instagramAccounts.length > 0">
                        {{-- Search --}}
                        <div class="mb-4">
                            <input type="text" x-model="threadsSearch" @input.debounce.300ms placeholder="{{ __('Search Threads accounts by username...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 text-sm">
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="ig in filteredThreads" :key="'threads-' + ig.id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                           :class="{ 'border-gray-700 bg-gray-100': selectedThreadsAccounts.includes(ig.id) }">
                                        <input type="checkbox" name="threads_account[]" :value="ig.id"
                                               x-model="selectedThreadsAccounts"
                                               class="h-4 w-4 text-gray-700 border-gray-300 focus:ring-gray-500">
                                        <div class="ms-3 flex items-center gap-3">
                                            <div class="relative">
                                                <img x-show="ig.profile_picture" :src="ig.profile_picture" class="w-8 h-8 rounded-full">
                                                <div x-show="!ig.profile_picture" class="w-8 h-8 bg-gray-900 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900" x-text="ig.username || ig.name"></span>
                                                <span class="ms-1 text-xs text-gray-400">({{ __('Threads') }})</span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.threads && instagramAccounts.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <div class="w-12 h-12 mx-auto bg-gray-900 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.332-3.023.85-.706 2.017-1.122 3.381-1.206.934-.057 1.9-.004 2.86.156.04-.61.04-1.185-.002-1.725-.075-.98-.378-1.735-.902-2.243-.524-.509-1.303-.776-2.317-.796-1.037.018-1.9.283-2.49.766-.49.4-.773.9-.87 1.53l-2.095-.36c.173-1.14.69-2.074 1.54-2.772 1.008-.826 2.397-1.263 4.018-1.264h.02c1.656 0 2.97.478 3.905 1.422.92.927 1.412 2.256 1.463 3.948.016.532.005 1.09-.033 1.668 1.244.834 2.174 1.96 2.683 3.27.707 1.82.645 4.315-1.612 6.528-1.877 1.838-4.2 2.646-7.543 2.668z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">{{ __('No Instagram accounts to use for Threads') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Connect an Instagram Business account first') }}</p>
                    </div>

                    {{-- Manual Input --}}
                    <div x-show="showManualThreads" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Threads User ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_threads_account_ids[]" placeholder="e.g., 17841400000000000"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 text-sm">
                            <button type="button" @click="showManualThreads = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========== Ad Accounts Section ========== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-green-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Meta Ad Accounts') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.adAccounts" class="inline-flex items-center">
                                        <i class="fas fa-spinner fa-spin me-1"></i>{{ __('Loading...') }}
                                    </span>
                                    <span x-show="!loading.adAccounts && !errors.adAccounts" x-text="adAccounts.length + ' {{ __('account(s) available') }}'"></span>
                                    <span x-show="errors.adAccounts" class="text-red-600">{{ __('Failed to load') }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="errors.adAccounts" @click="loadAdAccounts()" class="text-sm text-green-600 hover:text-green-800">
                                <i class="fas fa-redo me-1"></i>{{ __('Retry') }}
                            </button>
                            <button type="button" @click="showManualAdAccount = !showManualAdAccount" class="text-sm text-green-600 hover:text-green-800">
                                <i class="fas fa-plus me-1"></i>{{ __('Add manually') }}
                            </button>
                        </div>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.adAccounts" class="space-y-2">
                        <template x-for="i in 3" :key="i">
                            <div class="animate-pulse flex items-center justify-between p-3 border rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="h-4 w-4 bg-gray-200 rounded"></div>
                                    <div>
                                        <div class="h-4 bg-gray-200 rounded w-40 mb-2"></div>
                                        <div class="h-3 bg-gray-200 rounded w-24"></div>
                                    </div>
                                </div>
                                <div class="h-6 bg-gray-200 rounded w-16"></div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.adAccounts && errors.adAccounts" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                        <p class="text-sm text-red-700" x-text="errors.adAccounts"></p>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.adAccounts && !errors.adAccounts && adAccounts.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-ad text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Ad Accounts found') }}</p>
                    </div>

                    {{-- Ad Accounts List --}}
                    <div x-show="!loading.adAccounts && !errors.adAccounts && adAccounts.length > 0">
                        {{-- Search --}}
                        <div class="mb-4">
                            <input type="text" x-model="adAccountsSearch" @input.debounce.300ms placeholder="{{ __('Search ad accounts by name or ID...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                        </div>
                        <div class="max-h-96 overflow-y-auto space-y-2">
                            <template x-for="account in filteredAdAccounts" :key="account.id">
                                <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-green-500 bg-green-50': selectedAdAccounts.includes(account.id) }">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="ad_account[]" :value="account.id"
                                               x-model="selectedAdAccounts"
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <div class="ms-3">
                                            <span class="text-sm font-medium text-gray-900" x-text="account.name"></span>
                                            <span class="text-xs text-gray-400 ms-2" x-text="'(' + account.account_id + ')'"></span>
                                            <span x-show="account.business_name" class="block text-xs text-gray-500" x-text="account.business_name"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500" x-text="account.currency"></span>
                                        <span class="px-2 py-0.5 rounded-full text-xs"
                                              :class="account.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                              x-text="account.status"></span>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Manual Input --}}
                    <div x-show="showManualAdAccount" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Ad Account ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_ad_account_ids[]" placeholder="e.g., act_123456789 or 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <button type="button" @click="showManualAdAccount = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ __('The act_ prefix will be added automatically if missing') }}</p>
                    </div>
                </div>
            </div>

            {{-- ========== Pixels Section ========== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-code text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Meta Pixels') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.pixels" class="inline-flex items-center">
                                        <i class="fas fa-spinner fa-spin me-1"></i>{{ __('Loading...') }}
                                    </span>
                                    <span x-show="!loading.pixels && !errors.pixels" x-text="pixels.length + ' {{ __('pixel(s) available') }}'"></span>
                                    <span x-show="errors.pixels" class="text-red-600">{{ __('Failed to load') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualPixel = !showManualPixel" class="text-sm text-purple-600 hover:text-purple-800">
                            <i class="fas fa-plus me-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.pixels" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded me-3"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.pixels && !errors.pixels && pixels.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-code text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Pixels found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Create a Pixel in Meta Events Manager') }}</p>
                    </div>

                    {{-- Pixels List --}}
                    <div x-show="!loading.pixels && !errors.pixels && pixels.length > 0">
                        {{-- Search --}}
                        <div class="mb-4">
                            <input type="text" x-model="pixelsSearch" @input.debounce.300ms placeholder="{{ __('Search pixels by name or ID...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="pixel in filteredPixels" :key="pixel.id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                           :class="{ 'border-purple-500 bg-purple-50': selectedPixels.includes(pixel.id) }">
                                        <input type="checkbox" name="pixel[]" :value="pixel.id"
                                               x-model="selectedPixels"
                                               class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                        <div class="ms-3">
                                            <span class="text-sm font-medium text-gray-900" x-text="pixel.name"></span>
                                            <span class="text-xs text-gray-400 ms-1" x-text="'(' + pixel.id + ')'"></span>
                                            <span class="block text-xs text-gray-500" x-text="pixel.ad_account_name"></span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Manual Input --}}
                    <div x-show="showManualPixel" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Pixel ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_pixel_ids[]" placeholder="e.g., 123456789012345"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                            <button type="button" @click="showManualPixel = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========== Catalogs Section ========== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-orange-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Product Catalogs') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.catalogs" class="inline-flex items-center">
                                        <i class="fas fa-spinner fa-spin me-1"></i>{{ __('Loading...') }}
                                    </span>
                                    <span x-show="!loading.catalogs && !errors.catalogs" x-text="catalogs.length + ' {{ __('catalog(s) available') }}'"></span>
                                    <span x-show="errors.catalogs" class="text-red-600">{{ __('Failed to load') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualCatalog = !showManualCatalog" class="text-sm text-orange-600 hover:text-orange-800">
                            <i class="fas fa-plus me-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.catalogs" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded me-3"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.catalogs && !errors.catalogs && catalogs.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-shopping-bag text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Product Catalogs found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Create a catalog in Meta Commerce Manager') }}</p>
                    </div>

                    {{-- Catalogs List --}}
                    <div x-show="!loading.catalogs && !errors.catalogs && catalogs.length > 0">
                        {{-- Search --}}
                        <div class="mb-4">
                            <input type="text" x-model="catalogsSearch" @input.debounce.300ms placeholder="{{ __('Search catalogs by name...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="catalog in filteredCatalogs" :key="catalog.id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                           :class="{ 'border-orange-500 bg-orange-50': selectedCatalogs.includes(catalog.id) }">
                                        <input type="checkbox" name="catalog[]" :value="catalog.id"
                                               x-model="selectedCatalogs"
                                               class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                        <div class="ms-3">
                                            <span class="text-sm font-medium text-gray-900" x-text="catalog.name"></span>
                                            <span class="block text-xs text-gray-500">
                                                <span x-text="catalog.product_count?.toLocaleString() || 0"></span> {{ __('settings.products_label') }}
                                                <span x-show="catalog.vertical"> &bull; <span x-text="catalog.vertical"></span></span>
                                            </span>
                                            <span x-show="catalog.business_name" class="text-xs text-blue-600" x-text="catalog.business_name"></span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Manual Input --}}
                    <div x-show="showManualCatalog" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Catalog ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_catalog_ids[]" placeholder="e.g., 123456789012345"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                            <button type="button" @click="showManualCatalog = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========== WhatsApp Section ========== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fab fa-whatsapp text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('WhatsApp Business Accounts') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.whatsapp" class="inline-flex items-center">
                                        <i class="fas fa-spinner fa-spin me-1"></i>{{ __('Loading...') }}
                                    </span>
                                    <span x-show="!loading.whatsapp && !errors.whatsapp" x-text="whatsappAccounts.length + ' {{ __('number(s) available') }}'"></span>
                                    <span x-show="errors.whatsapp" class="text-red-600">{{ __('Failed to load') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualWhatsapp = !showManualWhatsapp" class="text-sm text-green-600 hover:text-green-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="fab fa-whatsapp text-green-500 mt-0.5"></i>
                            <div class="text-xs text-green-700">
                                <p class="font-medium">{{ __('WhatsApp Business numbers for Click-to-WhatsApp ads') }}</p>
                                <p class="mt-1">{{ __('Select WhatsApp numbers to use in advertising campaigns.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.whatsapp" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="h-4 w-4 bg-gray-200 rounded me-3"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.whatsapp && !errors.whatsapp && whatsappAccounts.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fab fa-whatsapp text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No WhatsApp Business numbers found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Connect WhatsApp Business to your Meta Business Manager') }}</p>
                    </div>

                    {{-- WhatsApp List --}}
                    <div x-show="!loading.whatsapp && !errors.whatsapp && whatsappAccounts.length > 0">
                        {{-- Search --}}
                        <div class="mb-4">
                            <input type="text" x-model="whatsappSearch" @input.debounce.300ms placeholder="{{ __('Search WhatsApp by name or number...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="wa in filteredWhatsapp" :key="wa.id">
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                           :class="{ 'border-green-500 bg-green-50': selectedWhatsappAccounts.includes(wa.id) }">
                                        <input type="checkbox" name="whatsapp_account[]" :value="wa.id"
                                               x-model="selectedWhatsappAccounts"
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <div class="{{ $isRtl ? 'mr-3' : 'ml-3' }}">
                                            <span class="text-sm font-medium text-gray-900" x-text="wa.verified_name || wa.display_phone_number"></span>
                                            <span x-show="wa.display_phone_number" class="block text-xs text-gray-500" x-text="wa.display_phone_number"></span>
                                            <span x-show="wa.waba_name" class="block text-xs text-green-600">
                                                <i class="fas fa-building {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i><span x-text="wa.waba_name"></span>
                                            </span>
                                            <span x-show="wa.quality_rating" class="text-xs"
                                                  :class="wa.quality_rating === 'GREEN' ? 'text-green-600' : (wa.quality_rating === 'YELLOW' ? 'text-yellow-600' : 'text-red-600')">
                                                <i class="fas fa-circle text-xs {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('Quality') }}: <span x-text="wa.quality_rating"></span>
                                            </span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Manual Input --}}
                    <div x-show="showManualWhatsapp" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter WhatsApp Phone Number ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_whatsapp_account_ids[]" placeholder="e.g., 123456789012345"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <button type="button" @click="showManualWhatsapp = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary & Submit --}}
        <div class="mt-8 bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 {{ $isRtl ? 'lg:flex-row-reverse' : '' }}">
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Selection Summary') }}</h3>
                        <p class="text-sm text-gray-500 mt-1 flex flex-wrap gap-2 {{ $isRtl ? 'flex-row-reverse justify-end' : '' }}">
                            <span :class="{ 'text-green-600 font-medium': selectedPages.length > 0 }">
                                <i class="fas" :class="selectedPages.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                <span x-text="selectedPages.length > 0 ? `${selectedPages.length} {{ __('Page(s)') }}` : '{{ __('Page') }}'"></span>
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedInstagrams.length > 0 }">
                                <i class="fas" :class="selectedInstagrams.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                <span x-text="selectedInstagrams.length > 0 ? `${selectedInstagrams.length} {{ __('Instagram') }}` : '{{ __('Instagram') }}'"></span>
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedThreadsAccounts.length > 0 }">
                                <i class="fas" :class="selectedThreadsAccounts.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                <span x-text="selectedThreadsAccounts.length > 0 ? `${selectedThreadsAccounts.length} {{ __('Threads') }}` : '{{ __('Threads') }}'"></span>
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedAdAccounts.length > 0 }">
                                <i class="fas" :class="selectedAdAccounts.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                <span x-text="selectedAdAccounts.length > 0 ? `${selectedAdAccounts.length} {{ __('Ad Account(s)') }}` : '{{ __('Ad Account') }}'"></span>
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedPixels.length > 0 }">
                                <i class="fas" :class="selectedPixels.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                <span x-text="selectedPixels.length > 0 ? `${selectedPixels.length} {{ __('Pixel(s)') }}` : '{{ __('Pixel') }}'"></span>
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedCatalogs.length > 0 }">
                                <i class="fas" :class="selectedCatalogs.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                <span x-text="selectedCatalogs.length > 0 ? `${selectedCatalogs.length} {{ __('Catalog(s)') }}` : '{{ __('Catalog') }}'"></span>
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedWhatsappAccounts.length > 0 }">
                                <i class="fab fa-whatsapp" :class="selectedWhatsappAccounts.length > 0 ? '' : 'text-gray-400'"></i>
                                <span x-text="selectedWhatsappAccounts.length > 0 ? `${selectedWhatsappAccounts.length} {{ __('WhatsApp') }}` : '{{ __('WhatsApp') }}'"></span>
                            </span>
                        </p>
                    </div>
                    <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit"
                                class="inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-save {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('Save Selection') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function metaAssetsPage() {
    return {
        // API Configuration
        connectionId: '{{ $connection->connection_id }}',
        orgId: '{{ $currentOrg }}',
        apiBaseUrl: '/orgs/{{ $currentOrg }}/settings/platform-connections/meta/{{ $connection->connection_id }}/assets/ajax',

        // Loading states
        isInitialLoading: true,
        isRefreshing: false,
        loadingStatus: '{{ __('Initializing...') }}',
        loadingProgress: 0,

        loading: {
            pages: true,
            instagram: true,
            threads: true,
            adAccounts: true,
            pixels: true,
            catalogs: true,
            whatsapp: true,
        },

        errors: {
            pages: null,
            instagram: null,
            threads: null,
            adAccounts: null,
            pixels: null,
            catalogs: null,
            whatsapp: null,
        },

        // Asset data (loaded via AJAX)
        pages: [],
        instagramAccounts: [],
        threadsAccounts: [],
        adAccounts: [],
        pixels: [],
        catalogs: [],
        whatsappAccounts: [],

        // Selected items (pre-populated from server)
        selectedPages: @json((array) ($selectedAssets['page'] ?? [])),
        selectedInstagrams: @json((array) ($selectedAssets['instagram_account'] ?? [])),
        selectedThreadsAccounts: @json((array) ($selectedAssets['threads_account'] ?? [])),
        selectedAdAccounts: @json((array) ($selectedAssets['ad_account'] ?? [])),
        selectedPixels: @json((array) ($selectedAssets['pixel'] ?? [])),
        selectedCatalogs: @json((array) ($selectedAssets['catalog'] ?? [])),
        selectedWhatsappAccounts: @json((array) ($selectedAssets['whatsapp_account'] ?? [])),

        // Manual input visibility
        showManualPage: false,
        showManualInstagram: false,
        showManualThreads: false,
        showManualAdAccount: false,
        showManualPixel: false,
        showManualCatalog: false,
        showManualWhatsapp: false,

        // Search filters
        pagesSearch: '',
        instagramSearch: '',
        threadsSearch: '',
        adAccountsSearch: '',
        pixelsSearch: '',
        catalogsSearch: '',
        whatsappSearch: '',

        // Computed properties
        get filteredPages() {
            if (!this.pagesSearch) return this.pages;
            const search = this.pagesSearch.toLowerCase();
            return this.pages.filter(p => p.name.toLowerCase().includes(search));
        },

        get filteredInstagram() {
            if (!this.instagramSearch) return this.instagramAccounts;
            const search = this.instagramSearch.toLowerCase();
            return this.instagramAccounts.filter(ig =>
                (ig.username || ig.name || '').toLowerCase().includes(search)
            );
        },

        get filteredThreads() {
            if (!this.threadsSearch) return this.instagramAccounts;
            const search = this.threadsSearch.toLowerCase();
            return this.instagramAccounts.filter(ig =>
                (ig.username || ig.name || '').toLowerCase().includes(search)
            );
        },

        get filteredAdAccounts() {
            if (!this.adAccountsSearch) return this.adAccounts;
            const search = this.adAccountsSearch.toLowerCase();
            return this.adAccounts.filter(a =>
                a.name.toLowerCase().includes(search) ||
                (a.account_id || '').toLowerCase().includes(search) ||
                (a.business_name || '').toLowerCase().includes(search)
            );
        },

        get filteredPixels() {
            if (!this.pixelsSearch) return this.pixels;
            const search = this.pixelsSearch.toLowerCase();
            return this.pixels.filter(p =>
                p.name.toLowerCase().includes(search) ||
                p.id.toLowerCase().includes(search)
            );
        },

        get filteredCatalogs() {
            if (!this.catalogsSearch) return this.catalogs;
            const search = this.catalogsSearch.toLowerCase();
            return this.catalogs.filter(c =>
                c.name.toLowerCase().includes(search) ||
                (c.business_name || '').toLowerCase().includes(search)
            );
        },

        get filteredWhatsapp() {
            if (!this.whatsappSearch) return this.whatsappAccounts;
            const search = this.whatsappSearch.toLowerCase();
            return this.whatsappAccounts.filter(w =>
                (w.verified_name || '').toLowerCase().includes(search) ||
                (w.display_phone_number || '').toLowerCase().includes(search) ||
                (w.waba_name || '').toLowerCase().includes(search)
            );
        },

        // Initialize - load assets in parallel
        async init() {
            this.loadingStatus = '{{ __('Loading Facebook Pages and Ad Accounts...') }}';
            this.loadingProgress = 10;

            // Load independent assets in parallel
            const results = await Promise.allSettled([
                this.loadPages(),
                this.loadAdAccounts(),
            ]);

            this.loadingProgress = 50;
            this.loadingStatus = '{{ __('Loading dependent assets...') }}';

            // Load dependent assets
            await Promise.allSettled([
                this.loadInstagramAccounts(),
                this.loadPixels(),
                this.loadCatalogs(),
                this.loadWhatsappAccounts(),
            ]);

            this.loadingProgress = 90;
            this.loadingStatus = '{{ __('Loading Threads...') }}';

            // Threads depends on Instagram
            await this.loadThreadsAccounts();

            this.loadingProgress = 100;
            this.isInitialLoading = false;
        },

        // Refresh all assets
        async refreshAll() {
            this.isRefreshing = true;

            try {
                // Clear cache on server
                await fetch(`${this.apiBaseUrl}/refresh`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                });

                // Reset and reload
                this.pages = [];
                this.instagramAccounts = [];
                this.threadsAccounts = [];
                this.adAccounts = [];
                this.pixels = [];
                this.catalogs = [];
                this.whatsappAccounts = [];

                Object.keys(this.loading).forEach(k => this.loading[k] = true);
                Object.keys(this.errors).forEach(k => this.errors[k] = null);

                await this.init();
            } catch (error) {
                console.error('Refresh failed:', error);
            } finally {
                this.isRefreshing = false;
            }
        },

        // Load Pages
        async loadPages() {
            this.loading.pages = true;
            this.errors.pages = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/pages`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.pages = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load pages') }}');
                }
            } catch (error) {
                this.errors.pages = error.message;
                console.error('Failed to load pages:', error);
            } finally {
                this.loading.pages = false;
            }
        },

        // Load Instagram
        async loadInstagramAccounts() {
            this.loading.instagram = true;
            this.errors.instagram = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/instagram`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.instagramAccounts = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Instagram accounts') }}');
                }
            } catch (error) {
                this.errors.instagram = error.message;
                console.error('Failed to load Instagram:', error);
            } finally {
                this.loading.instagram = false;
            }
        },

        // Load Threads
        async loadThreadsAccounts() {
            this.loading.threads = true;
            this.errors.threads = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/threads`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.threadsAccounts = data.data || [];
                }
            } catch (error) {
                console.error('Failed to load Threads:', error);
            } finally {
                this.loading.threads = false;
            }
        },

        // Load Ad Accounts
        async loadAdAccounts() {
            this.loading.adAccounts = true;
            this.errors.adAccounts = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/ad-accounts`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.adAccounts = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load ad accounts') }}');
                }
            } catch (error) {
                this.errors.adAccounts = error.message;
                console.error('Failed to load Ad Accounts:', error);
            } finally {
                this.loading.adAccounts = false;
            }
        },

        // Load Pixels
        async loadPixels() {
            this.loading.pixels = true;
            this.errors.pixels = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/pixels`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.pixels = data.data || [];
                }
            } catch (error) {
                this.errors.pixels = error.message;
                console.error('Failed to load Pixels:', error);
            } finally {
                this.loading.pixels = false;
            }
        },

        // Load Catalogs
        async loadCatalogs() {
            this.loading.catalogs = true;
            this.errors.catalogs = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/catalogs`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.catalogs = data.data || [];
                }
            } catch (error) {
                this.errors.catalogs = error.message;
                console.error('Failed to load Catalogs:', error);
            } finally {
                this.loading.catalogs = false;
            }
        },

        // Load WhatsApp
        async loadWhatsappAccounts() {
            this.loading.whatsapp = true;
            this.errors.whatsapp = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/whatsapp`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.whatsappAccounts = data.data || [];
                }
            } catch (error) {
                this.errors.whatsapp = error.message;
                console.error('Failed to load WhatsApp:', error);
            } finally {
                this.loading.whatsapp = false;
            }
        },

        // Bulk selection methods
        selectAllPages() {
            this.filteredPages.forEach(page => {
                if (!this.selectedPages.includes(page.id)) {
                    this.selectedPages.push(page.id);
                }
            });
        },

        deselectAllPages() {
            if (this.pagesSearch) {
                const filteredIds = this.filteredPages.map(p => p.id);
                this.selectedPages = this.selectedPages.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedPages = [];
            }
        },

        selectAllInstagram() {
            this.filteredInstagram.forEach(ig => {
                if (!this.selectedInstagrams.includes(ig.id)) {
                    this.selectedInstagrams.push(ig.id);
                }
            });
        },

        deselectAllInstagram() {
            if (this.instagramSearch) {
                const filteredIds = this.filteredInstagram.map(ig => ig.id);
                this.selectedInstagrams = this.selectedInstagrams.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedInstagrams = [];
            }
        },

        // Scroll handler for potential virtual scrolling
        handleScroll(type, event) {
            // Can be extended for virtual scrolling if needed
        },
    }
}
</script>
@endpush
@endsection
