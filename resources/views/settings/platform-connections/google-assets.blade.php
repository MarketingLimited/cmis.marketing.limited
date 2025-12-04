@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('Select Google Assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="googleAssetsPage()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Platform Connections') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Google Assets') }}</span>
        </nav>
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Configure Google Assets') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('Select multiple Google services and assets for this organization.') }}
                </p>
            </div>
            <button type="button" @click="refreshAll()" :disabled="isRefreshing"
                    class="inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                <i class="fas fa-sync-alt {{ $isRtl ? 'ms-2' : 'me-2' }}" :class="{ 'animate-spin': isRefreshing }"></i>
                {{ __('Refresh') }}
            </button>
        </div>
        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
            <i class="fas fa-info-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
            {{ __('You can select multiple accounts per service type (e.g., multiple YouTube channels, Google Ads accounts).') }}
        </div>
    </div>

    {{-- Loading Progress Bar --}}
    <div x-show="isInitialLoading" x-cloak class="bg-white shadow sm:rounded-lg p-6">
        <div class="text-center">
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="{ width: loadingProgress + '%' }"></div>
            </div>
            <p class="text-sm text-gray-600" x-text="loadingStatus"></p>
            <p class="text-xs text-gray-400 mt-1">{{ __('Loading your Google assets...') }}</p>
        </div>
    </div>

    {{-- Connection Info --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0 w-10 h-10 bg-white rounded-lg shadow flex items-center justify-center">
                <svg class="w-6 h-6" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            </div>
            <div class="{{ $isRtl ? 'text-end' : 'text-start' }}">
                <p class="font-medium text-gray-900">{{ $connection->account_name }}</p>
                <p class="text-sm text-gray-600">
                    @if($connection->account_metadata['credential_type'] ?? false)
                        <span class="inline-flex items-center"><i class="fas fa-key {{ $isRtl ? 'ms-1' : 'me-1' }} text-xs"></i>{{ ucfirst(str_replace('_', ' ', $connection->account_metadata['credential_type'])) }}</span>
                        &bull;
                    @endif
                    {{ __('Connected') }} {{ $connection->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form action="{{ route('orgs.settings.platform-connections.google.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- YouTube Channels --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fab fa-youtube text-red-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('YouTube Channel') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.youtube">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.youtube && !errors.youtube && !youtubeNeedsAuth" x-text="youtubeChannels.length + ' {{ __('channel(s) available') }}'"></span>
                                    <span x-show="!loading.youtube && youtubeNeedsAuth" class="text-amber-600">{{ __('settings.youtube_access_not_authorized') }}</span>
                                    <span x-show="errors.youtube && !youtubeNeedsAuth" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            {{-- Connect YouTube Button (always visible for re-authorization) --}}
                            <a href="{{ route('orgs.settings.platform-connections.google.youtube.authorize', [$currentOrg, $connection->connection_id]) }}"
                               class="inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium transition {{ $isRtl ? 'flex-row-reverse' : '' }}"
                               :class="youtubeNeedsAuth ? 'border-red-500 text-red-600 bg-red-50 hover:bg-red-100' : 'border-gray-300 text-gray-600 bg-white hover:bg-gray-50'">
                                <i class="fab fa-youtube {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                                <span x-text="youtubeNeedsAuth ? '{{ __('settings.connect_youtube') }}' : '{{ __('settings.reconnect_youtube') }}'"></span>
                            </a>
                            <button type="button" @click="showManualYoutube = !showManualYoutube" class="text-sm text-red-600 hover:text-red-800">
                                <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                            </button>
                        </div>
                    </div>

                    {{-- YouTube Authorization Required Info Box --}}
                    <div x-show="!loading.youtube && youtubeNeedsAuth" x-cloak class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-key text-amber-500 mt-0.5"></i>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <p class="text-sm font-medium text-amber-800">{{ __('settings.youtube_authorization_required') }}</p>
                                <p class="mt-1 text-xs text-amber-700">
                                    {{ __('settings.youtube_authorization_description') }}
                                </p>
                                <p class="mt-2 text-xs text-amber-600">
                                    <i class="fas fa-info-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                    {{ __('settings.youtube_permissions_preserved') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.youtube" class="space-y-3">
                        <template x-for="i in 3" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="w-10 h-10 bg-gray-200 rounded-full {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.youtube && errors.youtube" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.youtube"></p>
                                <button type="button" @click="loadYouTube()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State (only show when authorized but no channels found) --}}
                    <div x-show="!loading.youtube && !errors.youtube && !youtubeNeedsAuth && youtubeChannels.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fab fa-youtube text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No YouTube channels found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Enable YouTube Data API in Google Console') }}</p>
                    </div>

                    {{-- YouTube Channels List --}}
                    <div x-show="!loading.youtube && !errors.youtube && youtubeChannels.length > 0">
                        {{-- Search & Bulk Actions --}}
                        <div class="mb-4 space-y-2">
                            <input type="text" x-model="youtubeSearch" placeholder="{{ __('Search channels...') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                            <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllYoutube" class="text-xs text-red-600 hover:text-red-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <button type="button" @click="deselectAllYoutube" class="text-xs text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                                <span class="text-xs text-gray-500" x-show="selectedYoutubeChannels.length > 0">
                                    (<span x-text="selectedYoutubeChannels.length"></span> {{ __('selected') }})
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="channel in filteredYoutubeChannels" :key="channel.id">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-red-500 bg-red-50': selectedYoutubeChannels.includes(channel.id) }">
                                    <input type="checkbox" name="youtube_channel[]" :value="channel.id"
                                           x-model="selectedYoutubeChannels"
                                           class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                    <div class="{{ $isRtl ? 'me-3' : 'ms-3' }} flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <img x-show="channel.thumbnail" :src="channel.thumbnail" alt="" class="w-10 h-10 rounded-full">
                                        <div x-show="!channel.thumbnail" class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                            <i class="fab fa-youtube text-red-600"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                                <span class="text-sm font-medium text-gray-900" x-text="channel.title"></span>
                                                <span x-show="channel.type === 'brand'" class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">{{ __('settings.brand_label') }}</span>
                                                <span x-show="channel.type === 'managed'" class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">{{ __('settings.managed_label') }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                                <span x-show="channel.subscriber_count" x-text="Number(channel.subscriber_count).toLocaleString() + ' {{ __('settings.subscribers_label') }}'"></span>
                                                <span x-show="channel.custom_url" class="text-gray-400" x-text="channel.custom_url"></span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Brand Account Search & Add --}}
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between mb-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-search text-blue-600"></i>
                                <span class="text-sm font-medium text-blue-800">{{ __('settings.find_brand_channel') }}</span>
                            </div>
                            <button type="button" @click="showYoutubeChannelSearch = !showYoutubeChannelSearch"
                                    class="text-xs text-blue-600 hover:text-blue-800">
                                <span x-text="showYoutubeChannelSearch ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                            </button>
                        </div>
                        <p class="text-xs text-blue-700 mb-3">
                            {{ __('settings.brand_channel_search_description') }}
                        </p>

                        {{-- Search Input --}}
                        <div x-show="showYoutubeChannelSearch" x-cloak class="space-y-3">
                            <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <input type="text"
                                       x-model="youtubeChannelSearchQuery"
                                       @keydown.enter.prevent="searchYoutubeChannels()"
                                       placeholder="{{ __('settings.search_channel_placeholder') }}"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <button type="button"
                                        @click="searchYoutubeChannels()"
                                        :disabled="youtubeChannelSearching || youtubeChannelSearchQuery.length < 2"
                                        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                    <i x-show="!youtubeChannelSearching" class="fas fa-search"></i>
                                    <i x-show="youtubeChannelSearching" class="fas fa-spinner fa-spin"></i>
                                    <span>{{ __('Search') }}</span>
                                </button>
                            </div>

                            {{-- Search Error --}}
                            <div x-show="youtubeChannelSearchError" x-cloak class="p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                                <i class="fas fa-exclamation-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                <span x-text="youtubeChannelSearchError"></span>
                            </div>

                            {{-- Search Results --}}
                            <div x-show="youtubeChannelSearchResults.length > 0" x-cloak class="border border-gray-200 rounded-lg overflow-hidden bg-white">
                                <div class="px-3 py-2 bg-gray-50 border-b text-xs text-gray-600">
                                    <span x-text="youtubeChannelSearchResults.length"></span> {{ __('settings.channels_found') }}
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <template x-for="channel in youtubeChannelSearchResults" :key="channel.id">
                                        <div class="flex items-center justify-between p-3 border-b last:border-b-0 hover:bg-gray-50 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                                <img x-show="channel.thumbnail" :src="channel.thumbnail" alt="" class="w-10 h-10 rounded-full">
                                                <div x-show="!channel.thumbnail" class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                                    <i class="fab fa-youtube text-red-600"></i>
                                                </div>
                                                <div class="{{ $isRtl ? 'text-end' : '' }}">
                                                    <p class="text-sm font-medium text-gray-900" x-text="channel.title"></p>
                                                    <p class="text-xs text-gray-500" x-text="channel.id"></p>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    @click="addChannelFromSearch(channel)"
                                                    class="px-3 py-1.5 bg-green-600 text-white text-xs rounded-md hover:bg-green-700 flex items-center gap-1">
                                                <i class="fas fa-plus"></i>
                                                <span>{{ __('Add') }}</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- No Results --}}
                            <div x-show="!youtubeChannelSearching && youtubeChannelSearchResults.length === 0 && youtubeChannelSearchQuery.length >= 2 && !youtubeChannelSearchError" x-cloak
                                 class="text-center py-4 text-sm text-gray-500">
                                <i class="fas fa-search text-gray-300 text-2xl mb-2"></i>
                                <p>{{ __('settings.no_channels_found') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Manual Channel ID Entry (Alternative) --}}
                    <div class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-keyboard {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                {{ __('settings.prefer_manual_entry') }}
                            </p>
                            <button type="button" @click="showManualYoutube = !showManualYoutube" class="text-xs text-gray-600 hover:text-gray-800">
                                <span x-text="showManualYoutube ? '{{ __('Hide') }}' : '{{ __('settings.enter_channel_id') }}'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="showManualYoutube" x-cloak class="mt-3 p-4 bg-gray-50 border border-gray-200 rounded-lg" x-data="{ manualIds: '' }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter YouTube Channel IDs (comma or newline separated)') }}</label>
                        <textarea x-model="manualIds" rows="3" placeholder="UC..., UC..., UC..."
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"></textarea>
                        <template x-for="(id, index) in manualIds.split(/[,\n]+/).filter(i => i.trim())" :key="index">
                            <input type="hidden" name="manual_youtube_channel_ids[]" :value="id.trim()">
                        </template>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ __('To find your Channel ID: YouTube Studio → Settings → Channel → Advanced settings → Copy "Channel ID"') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Google Ads Accounts --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-green-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Ads Account') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.ads">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.ads && !errors.ads" x-text="adsAccounts.length + ' {{ __('account(s) available') }}'"></span>
                                    <span x-show="errors.ads" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualGoogleAds = !showManualGoogleAds" class="text-sm text-green-600 hover:text-green-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.ads" class="space-y-2">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center justify-between p-3 border rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                    <div class="flex-1">
                                        <div class="h-4 bg-gray-200 rounded w-48 mb-2"></div>
                                        <div class="h-3 bg-gray-200 rounded w-32"></div>
                                    </div>
                                </div>
                                <div class="h-5 bg-gray-200 rounded w-16"></div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.ads && errors.ads" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.ads"></p>
                                <button type="button" @click="loadAds()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- API Error State --}}
                    <div x-show="!loading.ads && !errors.ads && adsApiError" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-times-circle text-red-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-red-800" x-text="adsApiError?.title || '{{ __('Google Ads API Error') }}'"></p>
                                <p class="mt-1 text-xs text-red-700" x-text="adsApiError?.message || ''"></p>
                                <p class="mt-2 text-xs text-red-600">
                                    {{ __('You can still use "Add manually" above to enter your Customer ID.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.ads && !errors.ads && !adsApiError && adsAccounts.length === 0" class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-amber-800">{{ __('No Google Ads accounts found') }}</p>
                                <p class="mt-1 text-xs text-amber-700">
                                    {{ __('No accessible Google Ads accounts were found for this Google account.') }}
                                </p>
                                <p class="mt-2 text-xs text-amber-600">
                                    <i class="fas fa-info-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                    {{ __('Find your Customer ID in Google Ads: Click your profile → "Customer ID" (format: XXX-XXX-XXXX)') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Ads Accounts List --}}
                    <div x-show="!loading.ads && !errors.ads && !adsApiError && adsAccounts.length > 0" class="space-y-3">
                        {{-- Search and Bulk Actions --}}
                        <div class="flex flex-col sm:flex-row gap-3 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                            <div class="flex-1">
                                <input type="text" x-model="adsSearch" placeholder="{{ __('Search accounts...') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm {{ $isRtl ? 'text-end' : '' }}">
                            </div>
                            <div class="flex items-center gap-2 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllAds()" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAllAds()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Account Items (Virtual Scroll) --}}
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <template x-for="(account, index) in filteredAdsAccounts" :key="account.id">
                                <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                       :class="{ 'border-green-500 bg-green-50': selectedGoogleAds.includes(account.id) }">
                                    <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <input type="checkbox" name="google_ads[]" :value="account.id"
                                               x-model="selectedGoogleAds"
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <div class="ms-3">
                                            <span class="text-sm font-medium text-gray-900" x-text="account.name || account.descriptive_name"></span>
                                            <span class="text-xs text-gray-400 ms-2" x-text="'(' + account.id + ')'"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <span x-show="account.currency" class="text-xs text-gray-500" x-text="account.currency"></span>
                                        <span class="px-2 py-0.5 rounded-full text-xs"
                                              :class="account.status === 'ENABLED' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                              x-text="account.status || 'Unknown'"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Results count --}}
                        <div x-show="adsSearch && filteredAdsAccounts.length !== adsAccounts.length" class="text-xs text-gray-500 {{ $isRtl ? 'text-end' : '' }}">
                            <span x-text="filteredAdsAccounts.length"></span> {{ __('of') }} <span x-text="adsAccounts.length"></span> {{ __('accounts match your search') }}
                        </div>
                    </div>

                    <div x-show="showManualGoogleAds" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Google Ads Customer ID') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text" name="manual_google_ads_id" placeholder="e.g., 123-456-7890"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <button type="button" @click="showManualGoogleAds = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Format: XXX-XXX-XXXX (with or without dashes)') }}</p>
                    </div>

                    {{-- Keyword Planner Info --}}
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs text-blue-700">
                            <i class="fas fa-lightbulb text-blue-500 {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                            <strong>{{ __('Keyword Planner') }}</strong>: {{ __('Accessible through the selected Google Ads account') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Google Analytics Properties --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-orange-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Analytics') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.analytics">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.analytics && !errors.analytics" x-text="analyticsProperties.length + ' {{ __('property(ies) available') }}'"></span>
                                    <span x-show="errors.analytics" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualAnalytics = !showManualAnalytics" class="text-sm text-orange-600 hover:text-orange-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.analytics" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.analytics && errors.analytics" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.analytics"></p>
                                <button type="button" @click="loadAnalytics()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.analytics && !errors.analytics && analyticsProperties.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-chart-line text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Analytics properties found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Enable Google Analytics API in Cloud Console') }}</p>
                    </div>

                    {{-- Analytics Properties List --}}
                    <div x-show="!loading.analytics && !errors.analytics && analyticsProperties.length > 0" class="space-y-3">
                        {{-- Search and Bulk Actions --}}
                        <div class="flex flex-col sm:flex-row gap-3 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                            <div class="flex-1">
                                <input type="text" x-model="analyticsSearch" placeholder="{{ __('Search properties...') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm {{ $isRtl ? 'text-end' : '' }}">
                            </div>
                            <div class="flex items-center gap-2 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllAnalytics()" class="text-orange-600 hover:text-orange-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAllAnalytics()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Properties Grid (Virtual Scroll) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                            <template x-for="(property, idx) in filteredAnalyticsProperties" :key="'analytics-' + idx">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-orange-500 bg-orange-50': selectedAnalytics.includes(property.name) }">
                                    <input type="checkbox" name="analytics[]" :value="property.name"
                                           x-model="selectedAnalytics"
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                    <div class="{{ $isRtl ? 'me-3' : 'ms-3' }}">
                                        <span class="text-sm font-medium text-gray-900" x-text="property.displayName || property.name"></span>
                                        <span class="text-xs text-gray-400 ms-1" x-text="'(' + (property.name ? property.name.split('/')[1] : '') + ')'"></span>
                                        <span x-show="property.propertyType" class="block text-xs text-gray-500" x-text="property.propertyType"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Results count --}}
                        <div x-show="analyticsSearch && filteredAnalyticsProperties.length !== analyticsProperties.length" class="text-xs text-gray-500 {{ $isRtl ? 'text-end' : '' }}">
                            <span x-text="filteredAnalyticsProperties.length"></span> {{ __('of') }} <span x-text="analyticsProperties.length"></span> {{ __('properties match your search') }}
                        </div>
                    </div>

                    <div x-show="showManualAnalytics" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter GA4 Property ID') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text" name="manual_analytics_id" placeholder="e.g., properties/123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                            <button type="button" @click="showManualAnalytics = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Google Business Profile --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-store text-blue-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Business Profile') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.businessProfiles">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.businessProfiles && !errors.businessProfiles" x-text="businessProfiles.length + ' {{ __('location(s) available') }}'"></span>
                                    <span x-show="errors.businessProfiles" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualBusiness = !showManualBusiness" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.businessProfiles" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.businessProfiles && errors.businessProfiles" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.businessProfiles"></p>
                                <button type="button" @click="loadBusinessProfiles()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- API Error State --}}
                    <div x-show="!loading.businessProfiles && !errors.businessProfiles && businessProfilesApiError" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-red-800" x-text="businessProfilesApiError?.title || '{{ __('settings.business_profile_quota_zero') }}'"></p>
                                <p class="mt-1 text-xs text-red-700" x-text="businessProfilesApiError?.message || '{{ __('settings.business_profile_quota_zero_desc') }}'"></p>
                                <div class="mt-3 space-y-2">
                                    <a href="https://console.cloud.google.com/apis/api/mybusinessaccountmanagement.googleapis.com/quotas"
                                       target="_blank"
                                       class="inline-flex items-center text-sm text-red-700 hover:text-red-900 underline">
                                        <i class="fas fa-external-link-alt {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                        {{ __('settings.request_quota_account_api') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.businessProfiles && !errors.businessProfiles && !businessProfilesApiError && businessProfiles.length === 0" class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-amber-800">{{ __('settings.business_profile_requires_setup') }}</p>
                                <ul class="mt-2 text-xs text-amber-700 space-y-1 list-disc {{ $isRtl ? 'list-inside-rtl pe-4' : 'list-inside' }}">
                                    <li>{{ __('settings.enable_my_business_account_api') }}</li>
                                    <li>{{ __('settings.enable_my_business_info_api') }}</li>
                                    <li>{{ __('settings.request_quota_increase') }}</li>
                                </ul>
                                <p class="mt-2 text-xs text-amber-600">
                                    <i class="fas fa-info-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                    {{ __('settings.use_add_manually_location_id') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Business Profiles List --}}
                    <div x-show="!loading.businessProfiles && !errors.businessProfiles && !businessProfilesApiError && businessProfiles.length > 0" class="space-y-3">
                        {{-- Search and Bulk Actions --}}
                        <div class="flex flex-col sm:flex-row gap-3 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                            <div class="flex-1">
                                <input type="text" x-model="businessSearch" placeholder="{{ __('Search locations...') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm {{ $isRtl ? 'text-end' : '' }}">
                            </div>
                            <div class="flex items-center gap-2 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllBusiness()" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAllBusiness()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Profiles Grid (Virtual Scroll) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                            <template x-for="(profile, idx) in filteredBusinessProfiles" :key="'bp-' + idx">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-blue-500 bg-blue-50': selectedBusiness.includes(profile.name) }">
                                    <input type="checkbox" name="business_profile[]" :value="profile.name"
                                           x-model="selectedBusiness"
                                           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <div class="ms-3">
                                        <span class="text-sm font-medium text-gray-900" x-text="profile.title || profile.locationName"></span>
                                        <span x-show="profile.address" class="block text-xs text-gray-500" x-text="profile.address"></span>
                                        <span x-show="profile.primaryCategory" class="text-xs text-blue-600" x-text="profile.primaryCategory"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Results count --}}
                        <div x-show="businessSearch && filteredBusinessProfiles.length !== businessProfiles.length" class="text-xs text-gray-500 {{ $isRtl ? 'text-end' : '' }}">
                            <span x-text="filteredBusinessProfiles.length"></span> {{ __('of') }} <span x-text="businessProfiles.length"></span> {{ __('locations match your search') }}
                        </div>
                    </div>

                    <div x-show="showManualBusiness" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Business Profile Location ID') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text" name="manual_business_id" placeholder="e.g., accounts/123/locations/456"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <button type="button" @click="showManualBusiness = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Google Tag Manager --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-code text-purple-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Tag Manager') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.tagManager">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.tagManager && !errors.tagManager" x-text="tagManagerContainers.length + ' {{ __('container(s) available') }}'"></span>
                                    <span x-show="errors.tagManager" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualTagManager = !showManualTagManager" class="text-sm text-purple-600 hover:text-purple-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.tagManager" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.tagManager && errors.tagManager" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.tagManager"></p>
                                <button type="button" @click="loadTagManager()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.tagManager && !errors.tagManager && tagManagerContainers.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-code text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Tag Manager containers found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Enable Tag Manager API in Cloud Console') }}</p>
                    </div>

                    {{-- Tag Manager Containers List --}}
                    <div x-show="!loading.tagManager && !errors.tagManager && tagManagerContainers.length > 0" class="space-y-3">
                        {{-- Search and Bulk Actions --}}
                        <div class="flex flex-col sm:flex-row gap-3 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                            <div class="flex-1">
                                <input type="text" x-model="tagManagerSearch" placeholder="{{ __('Search containers...') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm {{ $isRtl ? 'text-end' : '' }}">
                            </div>
                            <div class="flex items-center gap-2 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllTagManager()" class="text-purple-600 hover:text-purple-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAllTagManager()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Containers Grid (Virtual Scroll) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                            <template x-for="(container, idx) in filteredTagManagerContainers" :key="'tagmgr-' + idx">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-purple-500 bg-purple-50': selectedTagManager.includes(container.path) }">
                                    <input type="checkbox" name="tag_manager[]" :value="container.path"
                                           x-model="selectedTagManager"
                                           class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                    <div class="{{ $isRtl ? 'me-3' : 'ms-3' }}">
                                        <span class="text-sm font-medium text-gray-900" x-text="container.name"></span>
                                        <span class="text-xs text-gray-400 ms-1" x-text="'(' + (container.publicId || container.containerId) + ')'"></span>
                                        <span x-show="container.domainName" class="block text-xs text-gray-500" x-text="Array.isArray(container.domainName) ? container.domainName.join(', ') : container.domainName"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Results count --}}
                        <div x-show="tagManagerSearch && filteredTagManagerContainers.length !== tagManagerContainers.length" class="text-xs text-gray-500 {{ $isRtl ? 'text-end' : '' }}">
                            <span x-text="filteredTagManagerContainers.length"></span> {{ __('of') }} <span x-text="tagManagerContainers.length"></span> {{ __('containers match your search') }}
                        </div>
                    </div>

                    <div x-show="showManualTagManager" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter GTM Container ID') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text" name="manual_tag_manager_id" placeholder="e.g., GTM-XXXXXXX"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                            <button type="button" @click="showManualTagManager = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Google Merchant Center --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-teal-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Merchant Center') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.merchantCenter">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.merchantCenter && !errors.merchantCenter" x-text="merchantCenterAccounts.length + ' {{ __('account(s) available') }}'"></span>
                                    <span x-show="errors.merchantCenter" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualMerchant = !showManualMerchant" class="text-sm text-teal-600 hover:text-teal-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.merchantCenter" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.merchantCenter && errors.merchantCenter" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.merchantCenter"></p>
                                <button type="button" @click="loadMerchantCenter()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- API Error State --}}
                    <div x-show="!loading.merchantCenter && !errors.merchantCenter && merchantCenterApiError" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-red-800">{{ __('Google Merchant Center requires re-authentication') }}</p>
                                <p class="mt-1 text-xs text-red-700" x-text="merchantCenterApiError?.message || ''"></p>
                                <p class="mt-2 text-xs text-red-600">
                                    <i class="fas fa-sync-alt {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                    {{ __('Please disconnect and reconnect your Google account to grant the new permission, or use "Add manually" above.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.merchantCenter && !errors.merchantCenter && !merchantCenterApiError && merchantCenterAccounts.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-shopping-cart text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Merchant Center accounts found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Enable Content API for Shopping in Google Cloud Console') }}</p>
                    </div>

                    {{-- Merchant Center Accounts List --}}
                    <div x-show="!loading.merchantCenter && !errors.merchantCenter && !merchantCenterApiError && merchantCenterAccounts.length > 0" class="space-y-3">
                        {{-- Search and Bulk Actions --}}
                        <div class="flex flex-col sm:flex-row gap-3 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                            <div class="flex-1">
                                <input type="text" x-model="merchantSearch" placeholder="{{ __('Search accounts...') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm {{ $isRtl ? 'text-end' : '' }}">
                            </div>
                            <div class="flex items-center gap-2 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllMerchant()" class="text-teal-600 hover:text-teal-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAllMerchant()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Accounts Grid (Virtual Scroll) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                            <template x-for="(merchant, idx) in filteredMerchantCenterAccounts" :key="'merchant-' + idx">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-teal-500 bg-teal-50': selectedMerchant.includes(merchant.id) }">
                                    <input type="checkbox" name="merchant_center[]" :value="merchant.id"
                                           x-model="selectedMerchant"
                                           class="h-4 w-4 text-teal-600 border-gray-300 focus:ring-teal-500">
                                    <div class="ms-3">
                                        <span class="text-sm font-medium text-gray-900" x-text="merchant.name"></span>
                                        <span class="text-xs text-gray-400 ms-1" x-text="'(' + merchant.id + ')'"></span>
                                        <span x-show="merchant.websiteUrl" class="block text-xs text-gray-500" x-text="merchant.websiteUrl"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Results count --}}
                        <div x-show="merchantSearch && filteredMerchantCenterAccounts.length !== merchantCenterAccounts.length" class="text-xs text-gray-500 {{ $isRtl ? 'text-end' : '' }}">
                            <span x-text="filteredMerchantCenterAccounts.length"></span> {{ __('of') }} <span x-text="merchantCenterAccounts.length"></span> {{ __('accounts match your search') }}
                        </div>
                    </div>

                    <div x-show="showManualMerchant" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Merchant Center ID') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text" name="manual_merchant_id" placeholder="e.g., 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                            <button type="button" @click="showManualMerchant = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Google Search Console --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-search text-indigo-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Search Console') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.searchConsole">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.searchConsole && !errors.searchConsole" x-text="searchConsoleSites.length + ' {{ __('site(s) available') }}'"></span>
                                    <span x-show="errors.searchConsole" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualSearchConsole = !showManualSearchConsole" class="text-sm text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.searchConsole" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.searchConsole && errors.searchConsole" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.searchConsole"></p>
                                <button type="button" @click="loadSearchConsole()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.searchConsole && !errors.searchConsole && searchConsoleSites.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-search text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No Search Console sites found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Enable Search Console API and verify site ownership') }}</p>
                    </div>

                    {{-- Search Console Sites List --}}
                    <div x-show="!loading.searchConsole && !errors.searchConsole && searchConsoleSites.length > 0" class="space-y-3">
                        {{-- Search and Bulk Actions --}}
                        <div class="flex flex-col sm:flex-row gap-3 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                            <div class="flex-1">
                                <input type="text" x-model="searchConsoleSearch" placeholder="{{ __('Search sites...') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm {{ $isRtl ? 'text-end' : '' }}">
                            </div>
                            <div class="flex items-center gap-2 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllSearchConsole()" class="text-indigo-600 hover:text-indigo-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAllSearchConsole()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Sites Grid (Virtual Scroll) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                            <template x-for="(site, idx) in filteredSearchConsoleSites" :key="'sc-' + idx">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-indigo-500 bg-indigo-50': selectedSearchConsole.includes(site.siteUrl) }">
                                    <input type="checkbox" name="search_console[]" :value="site.siteUrl"
                                           x-model="selectedSearchConsole"
                                           class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    <div class="{{ $isRtl ? 'me-3' : 'ms-3' }}">
                                        <span class="text-sm font-medium text-gray-900" x-text="site.siteUrl"></span>
                                        <span class="block text-xs text-gray-500" x-text="(site.permissionLevel || 'Full') + ' access'"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Results count --}}
                        <div x-show="searchConsoleSearch && filteredSearchConsoleSites.length !== searchConsoleSites.length" class="text-xs text-gray-500 {{ $isRtl ? 'text-end' : '' }}">
                            <span x-text="filteredSearchConsoleSites.length"></span> {{ __('of') }} <span x-text="searchConsoleSites.length"></span> {{ __('sites match your search') }}
                        </div>
                    </div>

                    <div x-show="showManualSearchConsole" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Search Console Site URL') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text" name="manual_search_console_id" placeholder="e.g., https://example.com or sc-domain:example.com"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <button type="button" @click="showManualSearchConsole = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Google Calendar --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-cyan-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Calendar') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.calendars">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.calendars && !errors.calendars" x-text="calendars.length + ' {{ __('calendar(s) available') }}'"></span>
                                    <span x-show="errors.calendars" class="text-red-500">{{ __('Error loading') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualCalendar = !showManualCalendar" class="text-sm text-cyan-600 hover:text-cyan-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.calendars" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="w-3 h-3 bg-gray-200 rounded-full {{ $isRtl ? 'ms-2' : 'me-2' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.calendars && errors.calendars" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.calendars"></p>
                                <button type="button" @click="loadCalendars()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.calendars && !errors.calendars && calendars.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-calendar text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No calendars found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Enable Google Calendar API') }}</p>
                    </div>

                    {{-- Calendars List --}}
                    <div x-show="!loading.calendars && !errors.calendars && calendars.length > 0" class="space-y-3">
                        {{-- Search and Bulk Actions --}}
                        <div class="flex flex-col sm:flex-row gap-3 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                            <div class="flex-1">
                                <input type="text" x-model="calendarSearch" placeholder="{{ __('Search calendars...') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm {{ $isRtl ? 'text-end' : '' }}">
                            </div>
                            <div class="flex items-center gap-2 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllCalendars()" class="text-cyan-600 hover:text-cyan-800">
                                    <i class="fas fa-check-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All Visible') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAllCalendars()" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-square {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Calendars Grid (Virtual Scroll) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                            <template x-for="(calendar, idx) in filteredCalendars" :key="'cal-' + idx">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-cyan-500 bg-cyan-50': selectedCalendar.includes(calendar.id) }">
                                    <input type="checkbox" name="calendar[]" :value="calendar.id"
                                           x-model="selectedCalendar"
                                           class="h-4 w-4 text-cyan-600 border-gray-300 focus:ring-cyan-500">
                                    <div class="{{ $isRtl ? 'me-3' : 'ms-3' }} flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <div x-show="calendar.backgroundColor" class="w-3 h-3 rounded-full" :style="{ backgroundColor: calendar.backgroundColor }"></div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900" x-text="calendar.summary || calendar.name"></span>
                                            <span x-show="calendar.description" class="block text-xs text-gray-500" x-text="calendar.description.substring(0, 50) + (calendar.description.length > 50 ? '...' : '')"></span>
                                        </div>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Results count --}}
                        <div x-show="calendarSearch && filteredCalendars.length !== calendars.length" class="text-xs text-gray-500 {{ $isRtl ? 'text-end' : '' }}">
                            <span x-text="filteredCalendars.length"></span> {{ __('of') }} <span x-text="calendars.length"></span> {{ __('calendars match your search') }}
                        </div>
                    </div>

                    <div x-show="showManualCalendar" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Calendar ID') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text" name="manual_calendar_id" placeholder="e.g., primary or calendar@example.com"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                            <button type="button" @click="showManualCalendar = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Google Drive --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-folder text-yellow-600 text-lg"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-end' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Drive') }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span x-show="loading.drive">{{ __('Loading...') }}</span>
                                    <span x-show="!loading.drive && !errors.drive" x-text="driveFolders.length + ' {{ __('shared drive(s) available') }}'"></span>
                                    <span x-show="errors.drive" class="text-red-500">{{ __('Error loading') }}</span>
                                    <span x-show="selectedSharedDrives.length > 0" class="text-yellow-600">
                                        &bull; <span x-text="selectedSharedDrives.length"></span> {{ __('selected') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualDrive = !showManualDrive" class="text-sm text-yellow-600 hover:text-yellow-800">
                            <i class="fas fa-plus {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loading.drive" class="space-y-3">
                        <div class="animate-pulse p-4 border-2 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-5 h-5 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="w-10 h-10 bg-gray-200 rounded-lg {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-24 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-48"></div>
                                </div>
                            </div>
                        </div>
                        <template x-for="i in 2" :key="i">
                            <div class="animate-pulse flex items-center p-3 border rounded-lg">
                                <div class="w-4 h-4 bg-gray-200 rounded {{ $isRtl ? 'ms-3' : 'me-3' }}"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!loading.drive && errors.drive" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm text-red-700" x-text="errors.drive"></p>
                                <button type="button" @click="loadDrive()" class="mt-2 text-sm text-red-600 hover:text-red-800 underline">
                                    {{ __('Try again') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- My Drive Selection --}}
                    <div x-show="!loading.drive && !errors.drive" class="mb-4 p-4 border-2 rounded-lg transition"
                         :class="includeMyDrive ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 bg-gray-50'">
                        <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="checkbox" name="include_my_drive" value="1"
                                   x-model="includeMyDrive"
                                   class="h-5 w-5 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                            <div class="{{ $isRtl ? 'me-3' : 'ms-3' }} flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-cloud text-yellow-600 text-lg"></i>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ __('My Drive') }}</span>
                                    <span class="block text-xs text-gray-500">{{ __('Include your personal Google Drive storage') }}</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- Shared Drives Section --}}
                    <div x-show="!loading.drive && !errors.drive && driveFolders.length > 0">
                        {{-- Search Box --}}
                        <div class="mb-4">
                            <div class="relative">
                                <i class="fas fa-search absolute {{ $isRtl ? 'right-3' : 'left-3' }} top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text"
                                       x-model="driveSearchQuery"
                                       placeholder="{{ __('Search shared drives...') }}"
                                       class="w-full {{ $isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-2 border border-gray-300 rounded-lg text-sm focus:border-yellow-500 focus:ring-yellow-500">
                                <button type="button"
                                        x-show="driveSearchQuery.length > 0"
                                        @click="driveSearchQuery = ''"
                                        class="absolute {{ $isRtl ? 'left-3' : 'right-3' }} top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Select All / Deselect All --}}
                        <div class="flex items-center justify-between mb-3 text-sm {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <span class="text-gray-600">{{ __('Shared Drives') }}</span>
                            <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <button type="button" @click="selectAllDrives()" class="text-yellow-600 hover:text-yellow-800">
                                    <i class="fas fa-check-double {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Select All') }}
                                </button>
                                <button type="button" @click="deselectAllDrives()" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Drives List --}}
                        <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                            <div class="divide-y divide-gray-100">
                                <template x-for="drive in filteredDrives" :key="drive.id">
                                    <label class="flex items-center p-3 hover:bg-gray-50 cursor-pointer transition {{ $isRtl ? 'flex-row-reverse' : '' }}"
                                           :class="{ 'bg-yellow-50': selectedSharedDrives.includes(drive.id) }">
                                        <input type="checkbox"
                                               name="shared_drives[]"
                                               :value="drive.id"
                                               x-model="selectedSharedDrives"
                                               class="h-4 w-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                                        <div class="{{ $isRtl ? 'me-3' : 'ms-3' }} flex items-center gap-2 flex-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                            <i class="fas" :class="drive.kind === 'drive#drive' ? 'fa-hdd' : 'fa-folder'" class="text-yellow-500"></i>
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-medium text-gray-900 block truncate" x-text="drive.name"></span>
                                                <span class="text-xs text-gray-500" x-text="drive.kind === 'drive#drive' ? '{{ __('Shared Drive') }}' : '{{ __('Folder') }}'"></span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- No Results Message --}}
                        <div x-show="driveSearchQuery.length > 0 && filteredDrives.length === 0"
                             class="text-center py-4 text-sm text-gray-500">
                            <i class="fas fa-search text-gray-300 text-2xl mb-2"></i>
                            <p>{{ __('No shared drives match your search') }}</p>
                        </div>

                        {{-- Hidden input to store selected drives as JSON --}}
                        <input type="hidden" name="selected_shared_drives" :value="JSON.stringify(selectedSharedDrives)">
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading.drive && !errors.drive && driveFolders.length === 0" class="text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fas fa-hdd text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('No shared drives found') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Shared Drives will appear here if you have access to any') }}</p>
                    </div>

                    {{-- Manual Add --}}
                    <div x-show="showManualDrive" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Shared Drive ID') }}</label>
                        <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="text"
                                   x-model="manualDriveId"
                                   placeholder="e.g., 0ABC123..."
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm">
                            <button type="button"
                                    @click="addManualDrive()"
                                    :disabled="!manualDriveId"
                                    class="px-3 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" @click="showManualDrive = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ __('To find Shared Drive ID: Open the drive in browser and copy the ID from the URL') }}
                        </p>
                    </div>

                    {{-- Manually Added Drives --}}
                    <template x-if="manuallyAddedDrives.length > 0">
                        <div class="mt-4">
                            <p class="text-xs text-gray-600 mb-2">{{ __('Manually added drives:') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(driveId, index) in manuallyAddedDrives" :key="driveId">
                                    <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">
                                        <i class="fas fa-hdd {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                        <span x-text="driveId.substring(0, 12) + '...'"></span>
                                        <button type="button" @click="removeManualDrive(index)" class="{{ $isRtl ? 'me-1' : 'ms-1' }} hover:text-yellow-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <input type="hidden" name="manual_drives[]" :value="driveId">
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Google Trends - Informational --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-bar text-pink-600 text-lg"></i>
                        </div>
                        <div class="flex-1 {{ $isRtl ? 'text-end' : '' }}">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Google Trends') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('Trend analysis and keyword research') }}</p>
                        </div>
                        <span class="px-2 py-1 bg-pink-100 text-pink-700 text-xs rounded-full">{{ __('Auto-enabled') }}</span>
                    </div>
                    <div class="p-3 bg-pink-50 border border-pink-200 rounded-lg">
                        <p class="text-xs text-pink-700">
                            <i class="fas fa-info-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                            {{ __('Google Trends is available for all connected Google accounts. Access trend data and keyword insights through the Trends module.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary & Submit --}}
        <div class="mt-8 bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 {{ $isRtl ? 'lg:flex-row-reverse' : '' }}">
                    <div class="{{ $isRtl ? 'text-end' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Selection Summary') }}</h3>
                        <div class="text-sm text-gray-500 mt-2 flex flex-wrap gap-x-3 gap-y-1 {{ $isRtl ? 'flex-row-reverse justify-end' : '' }}">
                            <span :class="{ 'text-green-600 font-medium': selectedYoutubeChannels.length > 0 }">
                                <i class="fas" :class="selectedYoutubeChannels.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('YouTube') }}
                                <span x-show="selectedYoutubeChannels.length > 0" class="text-xs" x-text="'(' + selectedYoutubeChannels.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedGoogleAds.length > 0 }">
                                <i class="fas" :class="selectedGoogleAds.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Ads') }}
                                <span x-show="selectedGoogleAds.length > 0" class="text-xs" x-text="'(' + selectedGoogleAds.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedAnalytics.length > 0 }">
                                <i class="fas" :class="selectedAnalytics.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Analytics') }}
                                <span x-show="selectedAnalytics.length > 0" class="text-xs" x-text="'(' + selectedAnalytics.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedBusiness.length > 0 }">
                                <i class="fas" :class="selectedBusiness.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Business') }}
                                <span x-show="selectedBusiness.length > 0" class="text-xs" x-text="'(' + selectedBusiness.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedTagManager.length > 0 }">
                                <i class="fas" :class="selectedTagManager.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('GTM') }}
                                <span x-show="selectedTagManager.length > 0" class="text-xs" x-text="'(' + selectedTagManager.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedMerchant.length > 0 }">
                                <i class="fas" :class="selectedMerchant.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Merchant') }}
                                <span x-show="selectedMerchant.length > 0" class="text-xs" x-text="'(' + selectedMerchant.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedSearchConsole.length > 0 }">
                                <i class="fas" :class="selectedSearchConsole.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Search Console') }}
                                <span x-show="selectedSearchConsole.length > 0" class="text-xs" x-text="'(' + selectedSearchConsole.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedCalendar.length > 0 }">
                                <i class="fas" :class="selectedCalendar.length > 0 ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Calendar') }}
                                <span x-show="selectedCalendar.length > 0" class="text-xs" x-text="'(' + selectedCalendar.length + ')'"></span>
                            </span>
                            <span :class="{ 'text-green-600 font-medium': includeMyDrive || selectedSharedDrives.length > 0 }">
                                <i class="fas" :class="(includeMyDrive || selectedSharedDrives.length > 0) ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Drive') }}
                                <span x-show="selectedSharedDrives.length > 0" class="text-xs" x-text="'(' + selectedSharedDrives.length + ')'"></span>
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit"
                                class="inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-save {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>{{ __('Save Selection') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function googleAssetsPage() {
    return {
        // API Configuration
        connectionId: '{{ $connection->connection_id }}',
        orgId: '{{ $currentOrg }}',
        apiBaseUrl: '/orgs/{{ $currentOrg }}/settings/platform-connections/google/{{ $connection->connection_id }}/assets/ajax',

        // Loading states
        isInitialLoading: true,
        isRefreshing: false,
        loadingStatus: '{{ __('Initializing...') }}',
        loadingProgress: 0,

        loading: {
            youtube: true,
            ads: true,
            analytics: true,
            businessProfiles: true,
            tagManager: true,
            merchantCenter: true,
            searchConsole: true,
            calendars: true,
            drive: true,
        },

        errors: {
            youtube: null,
            ads: null,
            analytics: null,
            businessProfiles: null,
            tagManager: null,
            merchantCenter: null,
            searchConsole: null,
            calendars: null,
            drive: null,
        },

        // API-specific errors (for special handling)
        adsApiError: null,
        businessProfilesApiError: null,
        merchantCenterApiError: null,

        // Asset data (loaded via AJAX)
        youtubeChannels: [],
        adsAccounts: [],
        analyticsProperties: [],
        businessProfiles: [],
        tagManagerContainers: [],
        merchantCenterAccounts: [],
        searchConsoleSites: [],
        calendars: [],
        driveFolders: [],

        // YouTube incremental authorization state
        youtubeNeedsAuth: false,

        // Selected items (pre-populated from server)
        selectedYoutubeChannels: @json((array) ($selectedAssets['youtube_channel'] ?? [])),
        selectedGoogleAds: @json((array) ($selectedAssets['google_ads'] ?? [])),
        selectedAnalytics: @json((array) ($selectedAssets['analytics'] ?? [])),
        selectedBusiness: @json((array) ($selectedAssets['business_profile'] ?? [])),
        selectedTagManager: @json((array) ($selectedAssets['tag_manager'] ?? [])),
        selectedMerchant: @json((array) ($selectedAssets['merchant_center'] ?? [])),
        selectedSearchConsole: @json((array) ($selectedAssets['search_console'] ?? [])),
        selectedCalendar: @json((array) ($selectedAssets['calendar'] ?? [])),

        // Google Drive
        includeMyDrive: @json($selectedAssets['include_my_drive'] ?? false),
        selectedSharedDrives: @json((array) ($selectedAssets['shared_drives'] ?? [])),
        manuallyAddedDrives: @json((array) ($selectedAssets['manual_drives'] ?? [])),
        driveSearchQuery: '',
        manualDriveId: '',

        // Manual input visibility
        showManualYoutube: false,
        showYoutubeChannelSearch: false,

        // YouTube Channel Search state
        youtubeChannelSearchQuery: '',
        youtubeChannelSearchResults: [],
        youtubeChannelSearching: false,
        youtubeChannelSearchError: null,
        showManualGoogleAds: false,
        showManualAnalytics: false,
        showManualBusiness: false,
        showManualTagManager: false,
        showManualMerchant: false,
        showManualSearchConsole: false,
        showManualCalendar: false,
        showManualDrive: false,

        // Search filters
        youtubeSearch: '',
        adsSearch: '',
        analyticsSearch: '',
        businessSearch: '',
        tagManagerSearch: '',
        merchantSearch: '',
        searchConsoleSearch: '',
        calendarSearch: '',

        // Computed properties - Filtered lists
        get filteredYoutubeChannels() {
            if (!Array.isArray(this.youtubeChannels)) return [];
            if (!this.youtubeSearch) return this.youtubeChannels;
            const search = this.youtubeSearch.toLowerCase();
            return this.youtubeChannels.filter(ch => ch && ch.title && ch.title.toLowerCase().includes(search));
        },

        get filteredAdsAccounts() {
            if (!Array.isArray(this.adsAccounts)) return [];
            if (!this.adsSearch) return this.adsAccounts;
            const search = this.adsSearch.toLowerCase();
            return this.adsAccounts.filter(acc =>
                acc && ((acc.name && acc.name.toLowerCase().includes(search)) ||
                (acc.id && acc.id.toLowerCase().includes(search)))
            );
        },

        get filteredAnalyticsProperties() {
            if (!Array.isArray(this.analyticsProperties)) return [];
            if (!this.analyticsSearch) return this.analyticsProperties;
            const search = this.analyticsSearch.toLowerCase();
            return this.analyticsProperties.filter(prop =>
                prop && ((prop.displayName && prop.displayName.toLowerCase().includes(search)) ||
                (prop.propertyType && prop.propertyType.toLowerCase().includes(search)))
            );
        },

        get filteredBusinessProfiles() {
            if (!Array.isArray(this.businessProfiles)) return [];
            if (!this.businessSearch) return this.businessProfiles;
            const search = this.businessSearch.toLowerCase();
            return this.businessProfiles.filter(bp =>
                bp && ((bp.title && bp.title.toLowerCase().includes(search)) ||
                (bp.address && bp.address.toLowerCase().includes(search)))
            );
        },

        get filteredTagManagerContainers() {
            if (!Array.isArray(this.tagManagerContainers)) return [];
            if (!this.tagManagerSearch) return this.tagManagerContainers;
            const search = this.tagManagerSearch.toLowerCase();
            return this.tagManagerContainers.filter(tm =>
                tm && ((tm.name && tm.name.toLowerCase().includes(search)) ||
                (tm.publicId && tm.publicId.toLowerCase().includes(search)))
            );
        },

        get filteredMerchantCenterAccounts() {
            if (!Array.isArray(this.merchantCenterAccounts)) return [];
            if (!this.merchantSearch) return this.merchantCenterAccounts;
            const search = this.merchantSearch.toLowerCase();
            return this.merchantCenterAccounts.filter(acc =>
                acc && ((acc.name && acc.name.toLowerCase().includes(search)) ||
                (acc.id && acc.id.toLowerCase().includes(search)))
            );
        },

        get filteredSearchConsoleSites() {
            if (!Array.isArray(this.searchConsoleSites)) return [];
            if (!this.searchConsoleSearch) return this.searchConsoleSites;
            const search = this.searchConsoleSearch.toLowerCase();
            return this.searchConsoleSites.filter(site =>
                site && site.siteUrl && site.siteUrl.toLowerCase().includes(search)
            );
        },

        get filteredCalendars() {
            if (!Array.isArray(this.calendars)) return [];
            if (!this.calendarSearch) return this.calendars;
            const search = this.calendarSearch.toLowerCase();
            return this.calendars.filter(cal =>
                cal && ((cal.summary && cal.summary.toLowerCase().includes(search)) ||
                (cal.description && cal.description.toLowerCase().includes(search)))
            );
        },

        get filteredDrives() {
            if (!Array.isArray(this.driveFolders)) return [];
            if (!this.driveSearchQuery) return this.driveFolders;
            const search = this.driveSearchQuery.toLowerCase();
            return this.driveFolders.filter(d => d && d.name && d.name.toLowerCase().includes(search));
        },

        // YouTube bulk selection
        selectAllYoutube() {
            this.filteredYoutubeChannels.forEach(channel => {
                if (!this.selectedYoutubeChannels.includes(channel.id)) {
                    this.selectedYoutubeChannels.push(channel.id);
                }
            });
        },

        deselectAllYoutube() {
            if (this.youtubeSearch) {
                const filteredIds = this.filteredYoutubeChannels.map(ch => ch.id);
                this.selectedYoutubeChannels = this.selectedYoutubeChannels.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedYoutubeChannels = [];
            }
        },

        // YouTube Channel Search (for Brand Accounts)
        async searchYoutubeChannels() {
            const query = this.youtubeChannelSearchQuery.trim();
            if (query.length < 2) {
                this.youtubeChannelSearchError = '{{ __('google_assets.errors.search_query_too_short') }}';
                return;
            }

            this.youtubeChannelSearching = true;
            this.youtubeChannelSearchError = null;
            this.youtubeChannelSearchResults = [];

            try {
                const response = await fetch(`${this.apiBaseUrl}/youtube/search?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    this.youtubeChannelSearchResults = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('google_assets.errors.youtube_search') }}');
                }
            } catch (error) {
                this.youtubeChannelSearchError = error.message;
                console.error('YouTube channel search failed:', error);
            } finally {
                this.youtubeChannelSearching = false;
            }
        },

        // Add channel from search results to main channel list
        addChannelFromSearch(channel) {
            // Check if channel already exists in the list
            const exists = this.youtubeChannels.some(ch => ch.id === channel.id);
            if (!exists) {
                // Add to channels list
                this.youtubeChannels.push({
                    id: channel.id,
                    title: channel.title,
                    thumbnail: channel.thumbnail,
                    custom_url: channel.custom_url || null,
                    subscriber_count: channel.subscriber_count || 0,
                    type: 'brand'
                });
            }
            // Select the channel
            if (!this.selectedYoutubeChannels.includes(channel.id)) {
                this.selectedYoutubeChannels.push(channel.id);
            }
            // Remove from search results to indicate it was added
            this.youtubeChannelSearchResults = this.youtubeChannelSearchResults.filter(ch => ch.id !== channel.id);
        },

        // Google Ads bulk selection
        selectAllAds() {
            this.filteredAdsAccounts.forEach(acc => {
                if (!this.selectedGoogleAds.includes(acc.id)) {
                    this.selectedGoogleAds.push(acc.id);
                }
            });
        },

        deselectAllAds() {
            if (this.adsSearch) {
                const filteredIds = this.filteredAdsAccounts.map(acc => acc.id);
                this.selectedGoogleAds = this.selectedGoogleAds.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedGoogleAds = [];
            }
        },

        // Analytics bulk selection
        selectAllAnalytics() {
            this.filteredAnalyticsProperties.forEach(prop => {
                if (!this.selectedAnalytics.includes(prop.name)) {
                    this.selectedAnalytics.push(prop.name);
                }
            });
        },

        deselectAllAnalytics() {
            if (this.analyticsSearch) {
                const filteredIds = this.filteredAnalyticsProperties.map(prop => prop.name);
                this.selectedAnalytics = this.selectedAnalytics.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedAnalytics = [];
            }
        },

        // Business Profiles bulk selection
        selectAllBusiness() {
            this.filteredBusinessProfiles.forEach(bp => {
                if (!this.selectedBusiness.includes(bp.name)) {
                    this.selectedBusiness.push(bp.name);
                }
            });
        },

        deselectAllBusiness() {
            if (this.businessSearch) {
                const filteredIds = this.filteredBusinessProfiles.map(bp => bp.name);
                this.selectedBusiness = this.selectedBusiness.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedBusiness = [];
            }
        },

        // Tag Manager bulk selection
        selectAllTagManager() {
            this.filteredTagManagerContainers.forEach(tm => {
                if (!this.selectedTagManager.includes(tm.path)) {
                    this.selectedTagManager.push(tm.path);
                }
            });
        },

        deselectAllTagManager() {
            if (this.tagManagerSearch) {
                const filteredIds = this.filteredTagManagerContainers.map(tm => tm.path);
                this.selectedTagManager = this.selectedTagManager.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedTagManager = [];
            }
        },

        // Merchant Center bulk selection
        selectAllMerchant() {
            this.filteredMerchantCenterAccounts.forEach(acc => {
                if (!this.selectedMerchant.includes(acc.id)) {
                    this.selectedMerchant.push(acc.id);
                }
            });
        },

        deselectAllMerchant() {
            if (this.merchantSearch) {
                const filteredIds = this.filteredMerchantCenterAccounts.map(acc => acc.id);
                this.selectedMerchant = this.selectedMerchant.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedMerchant = [];
            }
        },

        // Search Console bulk selection
        selectAllSearchConsole() {
            this.filteredSearchConsoleSites.forEach(site => {
                if (!this.selectedSearchConsole.includes(site.siteUrl)) {
                    this.selectedSearchConsole.push(site.siteUrl);
                }
            });
        },

        deselectAllSearchConsole() {
            if (this.searchConsoleSearch) {
                const filteredIds = this.filteredSearchConsoleSites.map(site => site.siteUrl);
                this.selectedSearchConsole = this.selectedSearchConsole.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedSearchConsole = [];
            }
        },

        // Calendar bulk selection
        selectAllCalendars() {
            this.filteredCalendars.forEach(cal => {
                if (!this.selectedCalendar.includes(cal.id)) {
                    this.selectedCalendar.push(cal.id);
                }
            });
        },

        deselectAllCalendars() {
            if (this.calendarSearch) {
                const filteredIds = this.filteredCalendars.map(cal => cal.id);
                this.selectedCalendar = this.selectedCalendar.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedCalendar = [];
            }
        },

        // Drive bulk selection
        selectAllDrives() {
            this.filteredDrives.forEach(drive => {
                if (!this.selectedSharedDrives.includes(drive.id)) {
                    this.selectedSharedDrives.push(drive.id);
                }
            });
        },

        deselectAllDrives() {
            if (this.driveSearchQuery) {
                const filteredIds = this.filteredDrives.map(d => d.id);
                this.selectedSharedDrives = this.selectedSharedDrives.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedSharedDrives = [];
            }
        },

        addManualDrive() {
            if (this.manualDriveId && !this.manuallyAddedDrives.includes(this.manualDriveId)) {
                this.manuallyAddedDrives.push(this.manualDriveId);
                this.manualDriveId = '';
            }
        },

        removeManualDrive(index) {
            this.manuallyAddedDrives.splice(index, 1);
        },

        // Initialize - load assets progressively in 4 phases
        async init() {
            // Phase 1: Fast APIs (YouTube, Analytics)
            this.loadingStatus = '{{ __('Loading YouTube and Analytics...') }}';
            this.loadingProgress = 10;

            await Promise.allSettled([
                this.loadYouTube(),
                this.loadAnalytics(),
            ]);

            this.loadingProgress = 30;

            // Phase 2: Fast APIs (Calendar, Search Console)
            this.loadingStatus = '{{ __('Loading Calendar and Search Console...') }}';

            await Promise.allSettled([
                this.loadCalendars(),
                this.loadSearchConsole(),
            ]);

            this.loadingProgress = 50;

            // Phase 3: N+1 APIs (Business Profiles, Tag Manager)
            this.loadingStatus = '{{ __('Loading Business Profiles and Tag Manager...') }}';

            await Promise.allSettled([
                this.loadBusinessProfiles(),
                this.loadTagManager(),
            ]);

            this.loadingProgress = 75;

            // Phase 4: Slowest APIs (Ads, Merchant Center, Drive)
            this.loadingStatus = '{{ __('Loading Ads, Merchant Center, and Drive...') }}';

            await Promise.allSettled([
                this.loadAds(),
                this.loadMerchantCenter(),
                this.loadDrive(),
            ]);

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
                this.youtubeChannels = [];
                this.adsAccounts = [];
                this.analyticsProperties = [];
                this.businessProfiles = [];
                this.tagManagerContainers = [];
                this.merchantCenterAccounts = [];
                this.searchConsoleSites = [];
                this.calendars = [];
                this.driveFolders = [];

                this.adsApiError = null;
                this.businessProfilesApiError = null;
                this.merchantCenterApiError = null;

                Object.keys(this.loading).forEach(k => this.loading[k] = true);
                Object.keys(this.errors).forEach(k => this.errors[k] = null);

                this.isInitialLoading = true;
                await this.init();
            } catch (error) {
                console.error('Refresh failed:', error);
            } finally {
                this.isRefreshing = false;
            }
        },

        // Load YouTube Channels
        async loadYouTube() {
            this.loading.youtube = true;
            this.errors.youtube = null;
            this.youtubeNeedsAuth = false;

            try {
                const response = await fetch(`${this.apiBaseUrl}/youtube`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    // Check if YouTube authorization is needed (scope-insufficient)
                    if (data.data && (data.data.needs_auth || data.data.scope_insufficient)) {
                        this.youtubeNeedsAuth = true;
                        this.youtubeChannels = [];
                    } else {
                        // Handle both array format and object with channels key
                        this.youtubeChannels = Array.isArray(data.data) ? data.data : (data.data?.channels || []);
                    }
                } else {
                    throw new Error(data.message || '{{ __('Failed to load YouTube channels') }}');
                }
            } catch (error) {
                this.errors.youtube = error.message;
                console.error('Failed to load YouTube:', error);
            } finally {
                this.loading.youtube = false;
            }
        },

        // Load Google Ads Accounts
        async loadAds() {
            this.loading.ads = true;
            this.errors.ads = null;
            this.adsApiError = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/ads`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.adsAccounts = data.data?.accounts || [];
                    if (data.data?.error) {
                        this.adsApiError = data.data.error;
                    }
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Google Ads accounts') }}');
                }
            } catch (error) {
                this.errors.ads = error.message;
                console.error('Failed to load Ads:', error);
            } finally {
                this.loading.ads = false;
            }
        },

        // Load Analytics Properties
        async loadAnalytics() {
            this.loading.analytics = true;
            this.errors.analytics = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/analytics`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.analyticsProperties = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Analytics properties') }}');
                }
            } catch (error) {
                this.errors.analytics = error.message;
                console.error('Failed to load Analytics:', error);
            } finally {
                this.loading.analytics = false;
            }
        },

        // Load Business Profiles
        async loadBusinessProfiles() {
            this.loading.businessProfiles = true;
            this.errors.businessProfiles = null;
            this.businessProfilesApiError = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/business-profiles`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.businessProfiles = data.data?.profiles || [];
                    if (data.data?.error) {
                        this.businessProfilesApiError = data.data.error;
                    }
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Business Profiles') }}');
                }
            } catch (error) {
                this.errors.businessProfiles = error.message;
                console.error('Failed to load Business Profiles:', error);
            } finally {
                this.loading.businessProfiles = false;
            }
        },

        // Load Tag Manager Containers
        async loadTagManager() {
            this.loading.tagManager = true;
            this.errors.tagManager = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/tag-manager`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.tagManagerContainers = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Tag Manager containers') }}');
                }
            } catch (error) {
                this.errors.tagManager = error.message;
                console.error('Failed to load Tag Manager:', error);
            } finally {
                this.loading.tagManager = false;
            }
        },

        // Load Merchant Center Accounts
        async loadMerchantCenter() {
            this.loading.merchantCenter = true;
            this.errors.merchantCenter = null;
            this.merchantCenterApiError = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/merchant-center`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.merchantCenterAccounts = data.data?.accounts || [];
                    if (data.data?.error) {
                        this.merchantCenterApiError = data.data.error;
                    }
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Merchant Center accounts') }}');
                }
            } catch (error) {
                this.errors.merchantCenter = error.message;
                console.error('Failed to load Merchant Center:', error);
            } finally {
                this.loading.merchantCenter = false;
            }
        },

        // Load Search Console Sites
        async loadSearchConsole() {
            this.loading.searchConsole = true;
            this.errors.searchConsole = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/search-console`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.searchConsoleSites = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Search Console sites') }}');
                }
            } catch (error) {
                this.errors.searchConsole = error.message;
                console.error('Failed to load Search Console:', error);
            } finally {
                this.loading.searchConsole = false;
            }
        },

        // Load Calendars
        async loadCalendars() {
            this.loading.calendars = true;
            this.errors.calendars = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/calendars`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.calendars = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Calendars') }}');
                }
            } catch (error) {
                this.errors.calendars = error.message;
                console.error('Failed to load Calendars:', error);
            } finally {
                this.loading.calendars = false;
            }
        },

        // Load Drive Folders
        async loadDrive() {
            this.loading.drive = true;
            this.errors.drive = null;

            try {
                const response = await fetch(`${this.apiBaseUrl}/drive`, {
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (data.success) {
                    this.driveFolders = data.data || [];
                } else {
                    throw new Error(data.message || '{{ __('Failed to load Drive folders') }}');
                }
            } catch (error) {
                this.errors.drive = error.message;
                console.error('Failed to load Drive:', error);
            } finally {
                this.loading.drive = false;
            }
        },
    }
}
</script>
@endpush
@endsection
