@extends('layouts.app')

@section('title', __('Select Google Assets') . ' - Settings')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="googleAssetsPage()">
    {{-- Header --}}
    <div class="mb-8">
        <nav class="text-sm text-gray-500 mb-2">
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-gray-700">{{ __('Platform Connections') }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ __('Select Google Assets') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Configure Google Assets') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Select your Google services and assets for this organization.') }}
        </p>
        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
            <i class="fas fa-info-circle mr-1"></i>
            {{ __('Each organization can have only one account per service type.') }}
        </div>
    </div>

    {{-- Connection Info --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-white rounded-lg shadow flex items-center justify-center">
                <svg class="w-6 h-6" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-gray-900">{{ $connection->account_name }}</p>
                <p class="text-sm text-gray-600">
                    @if($connection->account_metadata['credential_type'] ?? false)
                        <span class="inline-flex items-center"><i class="fas fa-key mr-1 text-xs"></i>{{ ucfirst(str_replace('_', ' ', $connection->account_metadata['credential_type'])) }}</span>
                        &bull;
                    @endif
                    Connected {{ $connection->created_at->diffForHumans() }}
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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fab fa-youtube text-red-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('YouTube Channel') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($youtubeChannels ?? []) }} {{ __('channel(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualYoutube = !showManualYoutube" class="text-sm text-red-600 hover:text-red-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($youtubeChannels ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($youtubeChannels as $channel)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-red-500 bg-red-50': selectedYoutubeChannel === '{{ $channel['id'] }}' }">
                                    <input type="radio" name="youtube_channel" value="{{ $channel['id'] }}"
                                           {{ ($selectedAssets['youtube_channel'] ?? null) === $channel['id'] ? 'checked' : '' }}
                                           x-model="selectedYoutubeChannel"
                                           class="h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($channel['thumbnail'] ?? null)
                                            <img src="{{ $channel['thumbnail'] }}" alt="" class="w-10 h-10 rounded-full">
                                        @else
                                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                                <i class="fab fa-youtube text-red-600"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">{{ $channel['title'] }}</span>
                                                @if(($channel['type'] ?? 'personal') === 'brand')
                                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">Brand</span>
                                                @elseif(($channel['type'] ?? 'personal') === 'managed')
                                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Managed</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                                @if($channel['subscriber_count'] ?? null)
                                                    <span>{{ number_format($channel['subscriber_count']) }} subscribers</span>
                                                @endif
                                                @if($channel['custom_url'] ?? null)
                                                    <span class="text-gray-400">{{ $channel['custom_url'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fab fa-youtube text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No YouTube channels found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Enable YouTube Data API in Google Console') }}</p>
                        </div>
                    @endif

                    {{-- Brand Account Note --}}
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <p class="text-xs text-amber-700">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>{{ __('Brand Accounts:') }}</strong>
                            {{ __('If you manage YouTube Brand Channels, click "Add manually" and enter the Channel ID. Find it at YouTube Studio → Settings → Channel → Advanced settings.') }}
                        </p>
                    </div>

                    <div x-show="showManualYoutube" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter YouTube Channel ID') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_youtube_channel_id" placeholder="e.g., UC..."
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                            <button type="button" @click="showManualYoutube = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ __('To find your Channel ID: YouTube Studio → Settings → Channel → Advanced settings → Copy "Channel ID"') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Google Ads Accounts --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-green-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Ads Account') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($googleAdsAccounts ?? []) }} {{ __('account(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualGoogleAds = !showManualGoogleAds" class="text-sm text-green-600 hover:text-green-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($googleAdsAccounts ?? []) > 0)
                        <div class="space-y-2">
                            @foreach($googleAdsAccounts as $account)
                                <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-green-500 bg-green-50': selectedGoogleAds === '{{ $account['id'] }}' }">
                                    <div class="flex items-center">
                                        <input type="radio" name="google_ads" value="{{ $account['id'] }}"
                                               {{ ($selectedAssets['google_ads'] ?? null) === $account['id'] ? 'checked' : '' }}
                                               x-model="selectedGoogleAds"
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900">{{ $account['name'] ?? $account['descriptive_name'] }}</span>
                                            <span class="text-xs text-gray-400 ml-2">({{ $account['id'] }})</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($account['currency'] ?? null)
                                            <span class="text-xs text-gray-500">{{ $account['currency'] }}</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ ($account['status'] ?? '') === 'ENABLED' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $account['status'] ?? 'Unknown' }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-medium text-amber-800">{{ __('Google Ads API requires additional setup') }}</p>
                                    <ul class="mt-2 text-xs text-amber-700 space-y-1 list-disc list-inside">
                                        <li>{{ __('A Developer Token is required (apply at Google Ads API Center)') }}</li>
                                        <li>{{ __('Use "Add manually" below to enter your Customer ID') }}</li>
                                    </ul>
                                    <p class="mt-2 text-xs text-amber-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ __('Find your Customer ID in Google Ads: Click your profile → "Customer ID" (format: XXX-XXX-XXXX)') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div x-show="showManualGoogleAds" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Google Ads Customer ID') }}</label>
                        <div class="flex gap-2">
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
                            <i class="fas fa-lightbulb text-blue-500 mr-1"></i>
                            <strong>{{ __('Keyword Planner') }}</strong>: {{ __('Accessible through the selected Google Ads account') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Google Analytics Properties --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-orange-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Analytics') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($analyticsProperties ?? []) }} {{ __('property(ies) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualAnalytics = !showManualAnalytics" class="text-sm text-orange-600 hover:text-orange-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($analyticsProperties ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($analyticsProperties as $property)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-orange-500 bg-orange-50': selectedAnalytics === '{{ $property['id'] }}' }">
                                    <input type="radio" name="analytics" value="{{ $property['id'] }}"
                                           {{ ($selectedAssets['analytics'] ?? null) === $property['id'] ? 'checked' : '' }}
                                           x-model="selectedAnalytics"
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $property['displayName'] ?? $property['name'] }}</span>
                                        <span class="text-xs text-gray-400 ml-1">({{ $property['id'] }})</span>
                                        @if($property['websiteUrl'] ?? null)
                                            <span class="block text-xs text-gray-500">{{ $property['websiteUrl'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-chart-line text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Analytics properties found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Enable Google Analytics API in Cloud Console') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualAnalytics" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter GA4 Property ID') }}</label>
                        <div class="flex gap-2">
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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-store text-blue-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Business Profile') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($businessProfiles ?? []) }} {{ __('location(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualBusiness = !showManualBusiness" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($businessProfiles ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($businessProfiles as $profile)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-blue-500 bg-blue-50': selectedBusiness === '{{ $profile['id'] }}' }">
                                    <input type="radio" name="business_profile" value="{{ $profile['id'] }}"
                                           {{ ($selectedAssets['business_profile'] ?? null) === $profile['id'] ? 'checked' : '' }}
                                           x-model="selectedBusiness"
                                           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $profile['name'] ?? $profile['locationName'] }}</span>
                                        @if($profile['address'] ?? null)
                                            <span class="block text-xs text-gray-500">{{ $profile['address'] }}</span>
                                        @endif
                                        @if($profile['primaryCategory'] ?? null)
                                            <span class="text-xs text-blue-600">{{ $profile['primaryCategory'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-medium text-amber-800">{{ __('Google Business Profile API requires setup') }}</p>
                                    <ul class="mt-2 text-xs text-amber-700 space-y-1 list-disc list-inside">
                                        <li>{{ __('Enable "My Business Account Management API" in Google Cloud Console') }}</li>
                                        <li>{{ __('Enable "My Business Business Information API" in Google Cloud Console') }}</li>
                                        <li>{{ __('Request quota increase if API returns rate limit errors') }}</li>
                                    </ul>
                                    <p class="mt-2 text-xs text-amber-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ __('Or use "Add manually" to enter your Location ID from Google Business Profile settings.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div x-show="showManualBusiness" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Business Profile Location ID') }}</label>
                        <div class="flex gap-2">
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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-code text-purple-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Tag Manager') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($tagManagerContainers ?? []) }} {{ __('container(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualTagManager = !showManualTagManager" class="text-sm text-purple-600 hover:text-purple-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($tagManagerContainers ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($tagManagerContainers as $container)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-purple-500 bg-purple-50': selectedTagManager === '{{ $container['containerId'] }}' }">
                                    <input type="radio" name="tag_manager" value="{{ $container['containerId'] }}"
                                           {{ ($selectedAssets['tag_manager'] ?? null) === $container['containerId'] ? 'checked' : '' }}
                                           x-model="selectedTagManager"
                                           class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $container['name'] }}</span>
                                        <span class="text-xs text-gray-400 ml-1">({{ $container['publicId'] ?? $container['containerId'] }})</span>
                                        @if($container['domainName'] ?? null)
                                            <span class="block text-xs text-gray-500">{{ implode(', ', (array)$container['domainName']) }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-code text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Tag Manager containers found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Enable Tag Manager API in Cloud Console') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualTagManager" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter GTM Container ID') }}</label>
                        <div class="flex gap-2">
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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-teal-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Merchant Center') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($merchantCenterAccounts ?? []) }} {{ __('account(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualMerchant = !showManualMerchant" class="text-sm text-teal-600 hover:text-teal-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($merchantCenterAccounts ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($merchantCenterAccounts as $merchant)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-teal-500 bg-teal-50': selectedMerchant === '{{ $merchant['id'] }}' }">
                                    <input type="radio" name="merchant_center" value="{{ $merchant['id'] }}"
                                           {{ ($selectedAssets['merchant_center'] ?? null) === $merchant['id'] ? 'checked' : '' }}
                                           x-model="selectedMerchant"
                                           class="h-4 w-4 text-teal-600 border-gray-300 focus:ring-teal-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $merchant['name'] }}</span>
                                        <span class="text-xs text-gray-400 ml-1">({{ $merchant['id'] }})</span>
                                        @if($merchant['websiteUrl'] ?? null)
                                            <span class="block text-xs text-gray-500">{{ $merchant['websiteUrl'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-shopping-cart text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Merchant Center accounts found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Enable Content API for Shopping') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualMerchant" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Merchant Center ID') }}</label>
                        <div class="flex gap-2">
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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-search text-indigo-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Search Console') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($searchConsoleSites ?? []) }} {{ __('site(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualSearchConsole = !showManualSearchConsole" class="text-sm text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($searchConsoleSites ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($searchConsoleSites as $site)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-indigo-500 bg-indigo-50': selectedSearchConsole === '{{ $site['siteUrl'] }}' }">
                                    <input type="radio" name="search_console" value="{{ $site['siteUrl'] }}"
                                           {{ ($selectedAssets['search_console'] ?? null) === $site['siteUrl'] ? 'checked' : '' }}
                                           x-model="selectedSearchConsole"
                                           class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $site['siteUrl'] }}</span>
                                        <span class="block text-xs text-gray-500">{{ ucfirst($site['permissionLevel'] ?? 'Full') }} access</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-search text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Search Console sites found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Enable Search Console API and verify site ownership') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualSearchConsole" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Search Console Site URL') }}</label>
                        <div class="flex gap-2">
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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-cyan-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Calendar') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($googleCalendars ?? []) }} {{ __('calendar(s) available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualCalendar = !showManualCalendar" class="text-sm text-cyan-600 hover:text-cyan-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($googleCalendars ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($googleCalendars as $calendar)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-cyan-500 bg-cyan-50': selectedCalendar === '{{ $calendar['id'] }}' }">
                                    <input type="radio" name="calendar" value="{{ $calendar['id'] }}"
                                           {{ ($selectedAssets['calendar'] ?? null) === $calendar['id'] ? 'checked' : '' }}
                                           x-model="selectedCalendar"
                                           class="h-4 w-4 text-cyan-600 border-gray-300 focus:ring-cyan-500">
                                    <div class="ml-3 flex items-center gap-2">
                                        @if($calendar['backgroundColor'] ?? null)
                                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $calendar['backgroundColor'] }}"></div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $calendar['summary'] ?? $calendar['name'] }}</span>
                                            @if($calendar['description'] ?? null)
                                                <span class="block text-xs text-gray-500">{{ Str::limit($calendar['description'], 50) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-calendar text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No calendars found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Enable Google Calendar API') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualCalendar" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Calendar ID') }}</label>
                        <div class="flex gap-2">
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
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-folder text-yellow-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Google Drive') }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ count($driveFolders ?? []) }} {{ __('shared drive(s) available') }}
                                    <span x-show="selectedSharedDrives.length > 0" class="text-yellow-600">
                                        &bull; <span x-text="selectedSharedDrives.length"></span> selected
                                    </span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showManualDrive = !showManualDrive" class="text-sm text-yellow-600 hover:text-yellow-800">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    {{-- My Drive Selection --}}
                    <div class="mb-4 p-4 border-2 rounded-lg transition"
                         :class="includeMyDrive ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 bg-gray-50'">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="include_my_drive" value="1"
                                   x-model="includeMyDrive"
                                   class="h-5 w-5 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                            <div class="ml-3 flex items-center gap-3">
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
                    @if(count($driveFolders ?? []) > 0)
                        {{-- Search Box --}}
                        <div class="mb-4">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text"
                                       x-model="driveSearchQuery"
                                       placeholder="{{ __('Search shared drives...') }}"
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:border-yellow-500 focus:ring-yellow-500">
                                <button type="button"
                                        x-show="driveSearchQuery.length > 0"
                                        @click="driveSearchQuery = ''"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Select All / Deselect All --}}
                        <div class="flex items-center justify-between mb-3 text-sm">
                            <span class="text-gray-600">{{ __('Shared Drives') }}</span>
                            <div class="flex gap-3">
                                <button type="button" @click="selectAllDrives()" class="text-yellow-600 hover:text-yellow-800">
                                    <i class="fas fa-check-double mr-1"></i>{{ __('Select All') }}
                                </button>
                                <button type="button" @click="deselectAllDrives()" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times mr-1"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        {{-- Drives List --}}
                        <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                            <div class="divide-y divide-gray-100">
                                @foreach($driveFolders as $index => $drive)
                                    <label class="flex items-center p-3 hover:bg-gray-50 cursor-pointer transition"
                                           x-show="driveMatchesSearch('{{ addslashes($drive['name']) }}')"
                                           :class="{ 'bg-yellow-50': selectedSharedDrives.includes('{{ $drive['id'] }}') }">
                                        <input type="checkbox"
                                               name="shared_drives[]"
                                               value="{{ $drive['id'] }}"
                                               x-model="selectedSharedDrives"
                                               class="h-4 w-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                                        <div class="ml-3 flex items-center gap-2 flex-1">
                                            <i class="fas {{ ($drive['kind'] ?? '') === 'drive#drive' ? 'fa-hdd' : 'fa-folder' }} text-yellow-500"></i>
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-medium text-gray-900 block truncate">{{ $drive['name'] }}</span>
                                                <span class="text-xs text-gray-500">{{ ($drive['kind'] ?? '') === 'drive#drive' ? __('Shared Drive') : __('Folder') }}</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- No Results Message --}}
                        <div x-show="driveSearchQuery.length > 0 && filteredDrivesCount === 0"
                             class="text-center py-4 text-sm text-gray-500">
                            <i class="fas fa-search text-gray-300 text-2xl mb-2"></i>
                            <p>{{ __('No shared drives match your search') }}</p>
                        </div>

                        {{-- Hidden input to store selected drives as JSON --}}
                        <input type="hidden" name="selected_shared_drives" :value="JSON.stringify(selectedSharedDrives)">
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-hdd text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No shared drives found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Shared Drives will appear here if you have access to any') }}</p>
                        </div>
                    @endif

                    {{-- Manual Add --}}
                    <div x-show="showManualDrive" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Shared Drive ID') }}</label>
                        <div class="flex gap-2">
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
                                        <i class="fas fa-hdd mr-1"></i>
                                        <span x-text="driveId.substring(0, 12) + '...'"></span>
                                        <button type="button" @click="removeManualDrive(index)" class="ml-1 hover:text-yellow-600">
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
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-bar text-pink-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Google Trends') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('Trend analysis and keyword research') }}</p>
                        </div>
                        <span class="px-2 py-1 bg-pink-100 text-pink-700 text-xs rounded-full">{{ __('Auto-enabled') }}</span>
                    </div>
                    <div class="p-3 bg-pink-50 border border-pink-200 rounded-lg">
                        <p class="text-xs text-pink-700">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ __('Google Trends is available for all connected Google accounts. Access trend data and keyword insights through the Trends module.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary & Submit --}}
        <div class="mt-8 bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Selection Summary') }}</h3>
                        <div class="text-sm text-gray-500 mt-2 flex flex-wrap gap-x-3 gap-y-1">
                            <span :class="{ 'text-green-600 font-medium': selectedYoutubeChannel }">
                                <i class="fas" :class="selectedYoutubeChannel ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('YouTube') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedGoogleAds }">
                                <i class="fas" :class="selectedGoogleAds ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Ads') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedAnalytics }">
                                <i class="fas" :class="selectedAnalytics ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Analytics') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedBusiness }">
                                <i class="fas" :class="selectedBusiness ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Business') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedTagManager }">
                                <i class="fas" :class="selectedTagManager ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('GTM') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedMerchant }">
                                <i class="fas" :class="selectedMerchant ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Merchant') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedSearchConsole }">
                                <i class="fas" :class="selectedSearchConsole ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Search Console') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': selectedCalendar }">
                                <i class="fas" :class="selectedCalendar ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Calendar') }}
                            </span>
                            <span :class="{ 'text-green-600 font-medium': includeMyDrive || selectedSharedDrives.length > 0 }">
                                <i class="fas" :class="(includeMyDrive || selectedSharedDrives.length > 0) ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ __('Drive') }}
                                <span x-show="selectedSharedDrives.length > 0" class="text-xs" x-text="'(' + selectedSharedDrives.length + ')'"></span>
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>{{ __('Save Selection') }}
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
        // Manual input visibility
        showManualYoutube: false,
        showManualGoogleAds: false,
        showManualAnalytics: false,
        showManualBusiness: false,
        showManualTagManager: false,
        showManualMerchant: false,
        showManualSearchConsole: false,
        showManualCalendar: false,
        showManualDrive: false,

        // Selected items (single value per type)
        selectedYoutubeChannel: @json($selectedAssets['youtube_channel'] ?? null),
        selectedGoogleAds: @json($selectedAssets['google_ads'] ?? null),
        selectedAnalytics: @json($selectedAssets['analytics'] ?? null),
        selectedBusiness: @json($selectedAssets['business_profile'] ?? null),
        selectedTagManager: @json($selectedAssets['tag_manager'] ?? null),
        selectedMerchant: @json($selectedAssets['merchant_center'] ?? null),
        selectedSearchConsole: @json($selectedAssets['search_console'] ?? null),
        selectedCalendar: @json($selectedAssets['calendar'] ?? null),

        // Google Drive - multi-select
        includeMyDrive: @json($selectedAssets['include_my_drive'] ?? false),
        selectedSharedDrives: @json($selectedAssets['shared_drives'] ?? []),
        manuallyAddedDrives: @json($selectedAssets['manual_drives'] ?? []),
        driveSearchQuery: '',
        manualDriveId: '',

        // All available drives for filtering
        allDrives: @json($driveFolders ?? []),

        // Computed property for filtered drives count
        get filteredDrivesCount() {
            if (!this.driveSearchQuery) return this.allDrives.length;
            const query = this.driveSearchQuery.toLowerCase();
            return this.allDrives.filter(drive =>
                drive.name.toLowerCase().includes(query)
            ).length;
        },

        // Check if drive matches search query
        driveMatchesSearch(driveName) {
            if (!this.driveSearchQuery) return true;
            return driveName.toLowerCase().includes(this.driveSearchQuery.toLowerCase());
        },

        // Select all visible drives
        selectAllDrives() {
            const query = this.driveSearchQuery.toLowerCase();
            this.allDrives.forEach(drive => {
                if (!query || drive.name.toLowerCase().includes(query)) {
                    if (!this.selectedSharedDrives.includes(drive.id)) {
                        this.selectedSharedDrives.push(drive.id);
                    }
                }
            });
        },

        // Deselect all drives
        deselectAllDrives() {
            if (this.driveSearchQuery) {
                // Only deselect filtered drives
                const query = this.driveSearchQuery.toLowerCase();
                this.selectedSharedDrives = this.selectedSharedDrives.filter(id => {
                    const drive = this.allDrives.find(d => d.id === id);
                    return drive && !drive.name.toLowerCase().includes(query);
                });
            } else {
                this.selectedSharedDrives = [];
            }
        },

        // Add manual drive ID
        addManualDrive() {
            if (this.manualDriveId && !this.manuallyAddedDrives.includes(this.manualDriveId)) {
                this.manuallyAddedDrives.push(this.manualDriveId);
                this.manualDriveId = '';
            }
        },

        // Remove manual drive
        removeManualDrive(index) {
            this.manuallyAddedDrives.splice(index, 1);
        },
    }
}
</script>
@endpush
@endsection
