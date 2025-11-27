@extends('layouts.admin')

@section('title', __('Select :platform Assets', ['platform' => $platformName]) . ' - ' . __('Settings'))

@section('content')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    // Platform configuration
    $platformConfigs = [
        'twitter' => [
            'name' => 'X (Twitter)',
            'icon' => 'fab fa-twitter',
            'color' => 'sky',
            'bgClass' => 'bg-sky-100',
            'textClass' => 'text-sky-600',
            'borderClass' => 'border-sky-500',
            'hasAccount' => true,
            'hasAdAccount' => true,
            'hasPixel' => true,
            'hasCatalog' => true,
            'pixelName' => 'Twitter Pixel',
        ],
        'tiktok' => [
            'name' => 'TikTok',
            'icon' => 'fab fa-tiktok',
            'color' => 'gray',
            'bgClass' => 'bg-gray-900',
            'textClass' => 'text-white',
            'borderClass' => 'border-gray-700',
            'hasAccount' => true,
            'hasAdAccount' => true,
            'hasPixel' => true,
            'hasCatalog' => true,
            'pixelName' => 'TikTok Pixel',
        ],
        'snapchat' => [
            'name' => 'Snapchat',
            'icon' => 'fab fa-snapchat',
            'color' => 'yellow',
            'bgClass' => 'bg-yellow-400',
            'textClass' => 'text-gray-900',
            'borderClass' => 'border-yellow-500',
            'hasAccount' => true,
            'hasAdAccount' => true,
            'hasPixel' => true,
            'hasCatalog' => true,
            'pixelName' => 'Snap Pixel',
        ],
        'pinterest' => [
            'name' => 'Pinterest',
            'icon' => 'fab fa-pinterest',
            'color' => 'red',
            'bgClass' => 'bg-red-100',
            'textClass' => 'text-red-600',
            'borderClass' => 'border-red-500',
            'hasAccount' => true,
            'hasAdAccount' => true,
            'hasPixel' => true,
            'hasCatalog' => true,
            'pixelName' => 'Pinterest Tag',
        ],
        'youtube' => [
            'name' => 'YouTube',
            'icon' => 'fab fa-youtube',
            'color' => 'red',
            'bgClass' => 'bg-red-100',
            'textClass' => 'text-red-600',
            'borderClass' => 'border-red-500',
            'hasAccount' => false,
            'hasChannel' => true,
            'hasAdAccount' => false,
            'hasPixel' => false,
            'hasCatalog' => false,
        ],
        'google' => [
            'name' => 'Google',
            'icon' => 'fab fa-google',
            'color' => 'blue',
            'bgClass' => 'bg-blue-100',
            'textClass' => 'text-blue-600',
            'borderClass' => 'border-blue-500',
            'hasAccount' => false,
            'hasBusinessProfile' => true,
            'hasAdAccount' => true,
            'hasPixel' => false,
            'hasCatalog' => false,
        ],
        'reddit' => [
            'name' => 'Reddit',
            'icon' => 'fab fa-reddit',
            'color' => 'orange',
            'bgClass' => 'bg-orange-100',
            'textClass' => 'text-orange-600',
            'borderClass' => 'border-orange-500',
            'hasAccount' => true,
            'hasAdAccount' => false,
            'hasPixel' => false,
            'hasCatalog' => false,
        ],
    ];
    $config = $platformConfigs[$platform] ?? $platformConfigs['twitter'];
@endphp

<div class="space-y-6" x-data="platformAssetsPage()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Platform Connections') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $config['name'] }} {{ __('Assets') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Configure :platform Assets', ['platform' => $config['name']]) }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Select one of each asset type for this organization.') }}
        </p>
        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
            <i class="fas fa-info-circle mr-1"></i>
            {{ __('Each organization can have only one account per asset type.') }}
        </div>
    </div>

    {{-- Connection Info --}}
    <div class="{{ $config['bgClass'] }} {{ $config['bgClass'] === 'bg-gray-900' ? 'text-white' : '' }} border border-gray-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 {{ $config['bgClass'] === 'bg-gray-900' ? 'bg-gray-700' : 'bg-white/50' }} rounded-lg flex items-center justify-center">
                <i class="{{ $config['icon'] }} {{ $config['textClass'] }} text-xl"></i>
            </div>
            <div>
                <p class="font-medium {{ $config['bgClass'] === 'bg-gray-900' ? 'text-white' : 'text-gray-900' }}">{{ $connection->account_name }}</p>
                <p class="text-sm {{ $config['bgClass'] === 'bg-gray-900' ? 'text-gray-300' : 'text-gray-600' }}">
                    Connected {{ $connection->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form action="{{ route('orgs.settings.platform-connections.' . $platform . '.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- Account/Profile --}}
            @if($config['hasAccount'] ?? false)
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 {{ $config['bgClass'] }} rounded-lg flex items-center justify-center">
                                <i class="fas fa-user {{ $config['textClass'] }}"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ $config['name'] }} {{ __('Account') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($accounts ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualAccount = !showManualAccount" class="text-sm {{ $config['textClass'] }}">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($accounts ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($accounts as $account)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ '{{ $config['borderClass'] }} bg-{{ $config['color'] }}-50': selectedAccount === '{{ $account['id'] }}' }">
                                    <input type="radio" name="account" value="{{ $account['id'] }}"
                                           {{ ($selectedAssets['account'] ?? null) === $account['id'] ? 'checked' : '' }}
                                           x-model="selectedAccount"
                                           class="h-4 w-4 {{ $config['textClass'] }} border-gray-300">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($account['picture'] ?? $account['profile_picture'] ?? null)
                                            <img src="{{ $account['picture'] ?? $account['profile_picture'] }}" alt="" class="w-8 h-8 rounded-full">
                                        @else
                                            <div class="w-8 h-8 {{ $config['bgClass'] }} rounded-full flex items-center justify-center">
                                                <i class="{{ $config['icon'] }} {{ $config['textClass'] }} text-sm"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $account['name'] ?? $account['username'] }}</span>
                                            @if($account['followers_count'] ?? null)
                                                <span class="block text-xs text-gray-500">{{ number_format($account['followers_count']) }} followers</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="{{ $config['icon'] }} text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('Account connected via OAuth') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualAccount" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Account ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_account_id" placeholder="e.g., 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="button" @click="showManualAccount = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- YouTube Channel --}}
            @if($config['hasChannel'] ?? false)
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fab fa-youtube text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('YouTube Channel') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($channels ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualChannel = !showManualChannel" class="text-sm text-red-600">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($channels ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($channels as $channel)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-red-500 bg-red-50': selectedChannel === '{{ $channel['id'] }}' }">
                                    <input type="radio" name="channel" value="{{ $channel['id'] }}"
                                           {{ ($selectedAssets['channel'] ?? null) === $channel['id'] ? 'checked' : '' }}
                                           x-model="selectedChannel"
                                           class="h-4 w-4 text-red-600 border-gray-300">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($channel['thumbnail'] ?? null)
                                            <img src="{{ $channel['thumbnail'] }}" alt="" class="w-8 h-8 rounded-full">
                                        @else
                                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                <i class="fab fa-youtube text-red-600 text-sm"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $channel['title'] ?? $channel['name'] }}</span>
                                            @if($channel['subscriber_count'] ?? null)
                                                <span class="block text-xs text-gray-500">{{ number_format($channel['subscriber_count']) }} subscribers</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fab fa-youtube text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No channels found') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualChannel" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Channel ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_channel_id" placeholder="e.g., UC..."
                                   class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="button" @click="showManualChannel = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Google Business Profile --}}
            @if($config['hasBusinessProfile'] ?? false)
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fab fa-google text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Business Profile') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($businessProfiles ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualBusinessProfile = !showManualBusinessProfile" class="text-sm text-blue-600">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($businessProfiles ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($businessProfiles as $profile)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-blue-500 bg-blue-50': selectedBusinessProfile === '{{ $profile['id'] }}' }">
                                    <input type="radio" name="business_profile" value="{{ $profile['id'] }}"
                                           {{ ($selectedAssets['business_profile'] ?? null) === $profile['id'] ? 'checked' : '' }}
                                           x-model="selectedBusinessProfile"
                                           class="h-4 w-4 text-blue-600 border-gray-300">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $profile['name'] }}</span>
                                        @if($profile['address'] ?? null)
                                            <span class="block text-xs text-gray-500">{{ $profile['address'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fab fa-google text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Business Profiles found') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualBusinessProfile" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Business Profile ID') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_business_profile_id" placeholder="e.g., accounts/123/locations/456"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="button" @click="showManualBusinessProfile = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Ad Account --}}
            @if($config['hasAdAccount'] ?? false)
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-green-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Ad Account') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($adAccounts ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualAdAccount = !showManualAdAccount" class="text-sm text-green-600">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($adAccounts ?? []) > 0)
                        <div class="space-y-2">
                            @foreach($adAccounts as $account)
                                <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-green-500 bg-green-50': selectedAdAccount === '{{ $account['id'] }}' }">
                                    <div class="flex items-center">
                                        <input type="radio" name="ad_account" value="{{ $account['id'] }}"
                                               {{ ($selectedAssets['ad_account'] ?? null) === $account['id'] ? 'checked' : '' }}
                                               x-model="selectedAdAccount"
                                               class="h-4 w-4 text-green-600 border-gray-300">
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900">{{ $account['name'] }}</span>
                                            <span class="text-xs text-gray-400 ml-2">({{ $account['id'] }})</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($account['currency'] ?? null)
                                            <span class="text-xs text-gray-500">{{ $account['currency'] }}</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ in_array($account['status'] ?? '', ['ACTIVE', 'Active', 'active']) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $account['status'] ?? 'Unknown' }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-ad text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Ad Accounts found') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualAdAccount" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Ad Account ID') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_ad_account_id" placeholder="e.g., 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="button" @click="showManualAdAccount = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Pixel --}}
            @if($config['hasPixel'] ?? false)
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-code text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ $config['pixelName'] ?? __('Pixel') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($pixels ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualPixel = !showManualPixel" class="text-sm text-purple-600">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($pixels ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($pixels as $pixel)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-purple-500 bg-purple-50': selectedPixel === '{{ $pixel['id'] }}' }">
                                    <input type="radio" name="pixel" value="{{ $pixel['id'] }}"
                                           {{ ($selectedAssets['pixel'] ?? null) === $pixel['id'] ? 'checked' : '' }}
                                           x-model="selectedPixel"
                                           class="h-4 w-4 text-purple-600 border-gray-300">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $pixel['name'] ?? 'Pixel' }}</span>
                                        <span class="text-xs text-gray-400 ml-1">({{ $pixel['id'] }})</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-code text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No pixels found') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualPixel" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Pixel ID') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_pixel_id" placeholder="e.g., 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="button" @click="showManualPixel = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Catalog --}}
            @if($config['hasCatalog'] ?? false)
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-orange-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Product Catalog') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($catalogs ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualCatalog = !showManualCatalog" class="text-sm text-orange-600">
                            <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($catalogs ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($catalogs as $catalog)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-orange-500 bg-orange-50': selectedCatalog === '{{ $catalog['id'] }}' }">
                                    <input type="radio" name="catalog" value="{{ $catalog['id'] }}"
                                           {{ ($selectedAssets['catalog'] ?? null) === $catalog['id'] ? 'checked' : '' }}
                                           x-model="selectedCatalog"
                                           class="h-4 w-4 text-orange-600 border-gray-300">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $catalog['name'] }}</span>
                                        @if($catalog['product_count'] ?? null)
                                            <span class="block text-xs text-gray-500">{{ number_format($catalog['product_count']) }} products</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-shopping-bag text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No catalogs found') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualCatalog" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Catalog ID') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_catalog_id" placeholder="e.g., 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
                            <button type="button" @click="showManualCatalog = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Summary & Submit --}}
        <div class="mt-8 bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Selection Summary') }}</h3>
                        <p class="text-sm text-gray-500 mt-1 flex flex-wrap gap-2">
                            @if($config['hasAccount'] ?? false)
                            <span :class="{ 'text-green-600 font-medium': selectedAccount }">
                                <i class="fas" :class="selectedAccount ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Account') }}
                            </span>
                            @endif
                            @if($config['hasChannel'] ?? false)
                            <span :class="{ 'text-green-600 font-medium': selectedChannel }">
                                <i class="fas" :class="selectedChannel ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Channel') }}
                            </span>
                            @endif
                            @if($config['hasBusinessProfile'] ?? false)
                            <span :class="{ 'text-green-600 font-medium': selectedBusinessProfile }">
                                <i class="fas" :class="selectedBusinessProfile ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Business') }}
                            </span>
                            @endif
                            @if($config['hasAdAccount'] ?? false)
                            <span :class="{ 'text-green-600 font-medium': selectedAdAccount }">
                                <i class="fas" :class="selectedAdAccount ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Ad Account') }}
                            </span>
                            @endif
                            @if($config['hasPixel'] ?? false)
                            <span :class="{ 'text-green-600 font-medium': selectedPixel }">
                                <i class="fas" :class="selectedPixel ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Pixel') }}
                            </span>
                            @endif
                            @if($config['hasCatalog'] ?? false)
                            <span :class="{ 'text-green-600 font-medium': selectedCatalog }">
                                <i class="fas" :class="selectedCatalog ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Catalog') }}
                            </span>
                            @endif
                        </p>
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
function platformAssetsPage() {
    return {
        showManualAccount: false,
        showManualChannel: false,
        showManualBusinessProfile: false,
        showManualAdAccount: false,
        showManualPixel: false,
        showManualCatalog: false,

        selectedAccount: @json($selectedAssets['account'] ?? null),
        selectedChannel: @json($selectedAssets['channel'] ?? null),
        selectedBusinessProfile: @json($selectedAssets['business_profile'] ?? null),
        selectedAdAccount: @json($selectedAssets['ad_account'] ?? null),
        selectedPixel: @json($selectedAssets['pixel'] ?? null),
        selectedCatalog: @json($selectedAssets['catalog'] ?? null),
    }
}
</script>
@endpush
@endsection
