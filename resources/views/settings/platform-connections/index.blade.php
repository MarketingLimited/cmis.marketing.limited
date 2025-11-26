@extends('layouts.app')

@section('title', __('Platform Connections') . ' - Settings')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Platform Connections</h1>
        <p class="mt-1 text-sm text-gray-500">
            Connect your ad platform accounts to manage campaigns directly from CMIS.
        </p>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Platform Cards --}}
    <div class="space-y-6">
        {{-- Meta (Facebook/Instagram) --}}
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fab fa-facebook text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Meta (Facebook/Instagram/Threads)</h3>
                            <p class="text-sm text-gray-500">Connect via Facebook Login or add a System User token from Business Manager</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        {{-- OAuth Connect Button --}}
                        <a href="{{ route('orgs.settings.platform-connections.meta.authorize', $currentOrg) }}"
                           class="inline-flex items-center px-4 py-2 border border-blue-600 rounded-md shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50">
                            <i class="fab fa-facebook mr-2"></i> Connect
                        </a>
                        {{-- Manual Token Button --}}
                        <a href="{{ route('orgs.settings.platform-connections.meta.create', $currentOrg) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-key mr-2"></i> Add Token
                        </a>
                    </div>
                </div>

                @php $metaConnections = $connectionsByPlatform->get('meta', collect()); @endphp

                @if($metaConnections->count() > 0)
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Connected Accounts</h4>
                        <div class="space-y-3">
                            @foreach($metaConnections as $connection)
                                @php
                                    $metadata = $connection->account_metadata ?? [];
                                    $isSystemUser = $metadata['is_system_user'] ?? false;
                                    $isNeverExpires = $metadata['is_never_expires'] ?? false;
                                    $warnings = $metadata['warnings'] ?? [];
                                    $adAccountsCount = $metadata['ad_accounts_count'] ?? count($metadata['ad_accounts'] ?? []);
                                    $activeAdAccountsCount = $metadata['active_ad_accounts_count'] ?? $adAccountsCount;
                                    $hasWarnings = !empty($warnings);
                                    $hasErrors = collect($warnings)->contains('type', 'error');
                                @endphp

                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                @if($connection->status === 'active' && !$hasErrors)
                                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                                                        <i class="fas fa-check text-green-600"></i>
                                                    </span>
                                                @elseif($connection->status === 'error' || $hasErrors)
                                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100">
                                                        <i class="fas fa-exclamation text-red-600"></i>
                                                    </span>
                                                @elseif($connection->status === 'warning' || $hasWarnings)
                                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100">
                                                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100">
                                                        <i class="fas fa-clock text-gray-600"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="ml-3">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-medium text-gray-900">{{ $connection->account_name }}</p>
                                                    @if($isSystemUser)
                                                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">System User</span>
                                                    @endif
                                                    @if($isNeverExpires)
                                                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded">Never Expires</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    {{ $adAccountsCount }} ad account(s)
                                                    @if($activeAdAccountsCount < $adAccountsCount)
                                                        <span class="text-yellow-600">({{ $activeAdAccountsCount }} active)</span>
                                                    @endif
                                                    @if($connection->token_expires_at)
                                                        &bull; Expires {{ $connection->token_expires_at->diffForHumans() }}
                                                    @elseif($isNeverExpires)
                                                        &bull; <span class="text-green-600">Long-lived token</span>
                                                    @endif
                                                    @if($metadata['validated_at'] ?? null)
                                                        &bull; Validated {{ \Carbon\Carbon::parse($metadata['validated_at'])->diffForHumans() }}
                                                    @endif
                                                </p>
                                                @if($connection->last_error_message)
                                                    <p class="text-xs text-red-600 mt-1">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                        {{ Str::limit($connection->last_error_message, 80) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            {{-- Select Assets (Pages, Instagram, etc.) --}}
                                            <a href="{{ route('orgs.settings.platform-connections.meta.assets', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-purple-600" title="Select Assets (Pages, Instagram, Pixels, etc.)">
                                                <i class="fas fa-layer-group"></i>
                                            </a>

                                            {{-- Test Connection --}}
                                            <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-blue-600" title="Test Connection">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>

                                            {{-- Refresh Ad Accounts --}}
                                            <form action="{{ route('orgs.settings.platform-connections.meta.refresh-accounts', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-green-600" title="Refresh Ad Accounts">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </form>

                                            {{-- Edit --}}
                                            <a href="{{ route('orgs.settings.platform-connections.meta.edit', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-blue-600" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            {{-- Delete --}}
                                            <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                  method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this connection?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Warnings Section --}}
                                    @if($hasWarnings)
                                        <div class="mt-3 space-y-1">
                                            @foreach($warnings as $warning)
                                                <div class="flex items-start text-xs p-2 rounded
                                                    {{ $warning['type'] === 'error' ? 'bg-red-50 text-red-700' : ($warning['type'] === 'warning' ? 'bg-yellow-50 text-yellow-700' : 'bg-blue-50 text-blue-700') }}">
                                                    <i class="fas {{ $warning['type'] === 'error' ? 'fa-times-circle' : ($warning['type'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle') }} mr-2 mt-0.5"></i>
                                                    <div>
                                                        <span class="font-medium">{{ $warning['message'] }}</span>
                                                        @if($warning['action'] ?? null)
                                                            <span class="block text-xs opacity-75 mt-0.5">{{ $warning['action'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Permission Scopes --}}
                                    @if($connection->scopes && count($connection->scopes) > 0)
                                        <div class="mt-3">
                                            <details class="group">
                                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                                                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                                                    {{ count($connection->scopes) }} permission(s) granted
                                                </summary>
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($connection->scopes as $scope)
                                                        <span class="px-2 py-0.5 text-xs rounded
                                                            {{ in_array($scope, ['ads_management', 'ads_read']) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ $scope }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </div>
                                    @endif
                                </div>

                                {{-- Show selected assets summary (1 account per asset type) --}}
                                @php $selectedAssets = $metadata['selected_assets'] ?? []; @endphp
                                @if(!empty($selectedAssets) && (($selectedAssets['page'] ?? null) || ($selectedAssets['instagram_account'] ?? null) || ($selectedAssets['threads_account'] ?? null) || ($selectedAssets['ad_account'] ?? null) || ($selectedAssets['pixel'] ?? null) || ($selectedAssets['catalog'] ?? null)))
                                    <div class="ml-11 mt-3 flex flex-wrap gap-2">
                                        @if($selectedAssets['page'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                                                <i class="fab fa-facebook mr-1"></i>Page
                                            </span>
                                        @endif
                                        @if($selectedAssets['instagram_account'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gradient-to-r from-purple-100 to-pink-100 text-pink-700">
                                                <i class="fab fa-instagram mr-1"></i>Instagram
                                            </span>
                                        @endif
                                        @if($selectedAssets['threads_account'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                                <i class="fas fa-at mr-1"></i>Threads
                                            </span>
                                        @endif
                                        @if($selectedAssets['ad_account'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                                <i class="fas fa-ad mr-1"></i>Ad Account
                                            </span>
                                        @endif
                                        @if($selectedAssets['pixel'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                                <i class="fas fa-code mr-1"></i>Pixel
                                            </span>
                                        @endif
                                        @if($selectedAssets['catalog'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-700">
                                                <i class="fas fa-shopping-bag mr-1"></i>Catalog
                                            </span>
                                        @endif
                                        <a href="{{ route('orgs.settings.platform-connections.meta.assets', [$currentOrg, $connection->connection_id]) }}"
                                           class="text-xs text-blue-600 hover:text-blue-800 ml-1">
                                            <i class="fas fa-edit mr-1"></i>Edit Assets
                                        </a>
                                    </div>
                                @else
                                    <div class="ml-11 mt-3">
                                        <a href="{{ route('orgs.settings.platform-connections.meta.assets', [$currentOrg, $connection->connection_id]) }}"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 rounded-md hover:bg-purple-100 transition">
                                            <i class="fas fa-layer-group mr-2"></i>Select Page, Instagram, Threads, Pixels & Catalog
                                        </a>
                                    </div>
                                @endif

                                {{-- Show ad accounts if available --}}
                                @if($metadata['ad_accounts'] ?? null)
                                    <div class="ml-11 mt-2 mb-4">
                                        <details class="group">
                                            <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                                                <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                                                View {{ count($metadata['ad_accounts']) }} ad account(s)
                                            </summary>
                                            <div class="mt-2 pl-4 space-y-2">
                                                @foreach($metadata['ad_accounts'] as $adAccount)
                                                    <div class="p-2 bg-white rounded border border-gray-100">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <span class="text-xs font-medium text-gray-700">
                                                                    {{ $adAccount['name'] }}
                                                                </span>
                                                                <span class="text-xs text-gray-400 ml-1">({{ $adAccount['account_id'] }})</span>
                                                                @if($adAccount['business_name'] ?? null)
                                                                    <span class="text-xs text-gray-500 block">{{ $adAccount['business_name'] }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                @if($adAccount['currency'] ?? null)
                                                                    <span class="text-xs text-gray-400">{{ $adAccount['currency'] }}</span>
                                                                @endif
                                                                <span class="px-2 py-0.5 rounded-full text-xs
                                                                    {{ ($adAccount['status'] ?? '') === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                                    {{ $adAccount['status'] ?? 'Unknown' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @if($adAccount['disable_reason'] ?? null)
                                                            <p class="text-xs text-red-500 mt-1">
                                                                <i class="fas fa-ban mr-1"></i>{{ $adAccount['disable_reason'] }}
                                                            </p>
                                                        @endif
                                                        @if(($adAccount['amount_spent'] ?? '0') !== '0')
                                                            <p class="text-xs text-gray-400 mt-1">
                                                                Total spent: {{ number_format(($adAccount['amount_spent'] ?? 0) / 100, 2) }} {{ $adAccount['currency'] ?? 'USD' }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-4 text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fab fa-facebook text-gray-300 text-4xl mb-2"></i>
                        <p class="text-sm text-gray-500">No Meta accounts connected yet</p>
                        <p class="text-xs text-gray-400 mt-1">Add a system user access token to get started</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Social Media Platforms --}}
        {{-- Note: Threads is now part of Meta assets, not a standalone platform --}}
        @php
            $socialPlatforms = [
                'youtube' => ['YouTube', 'fab fa-youtube', 'red', 'Video uploads, Shorts, thumbnails, and playlists'],
                'linkedin' => ['LinkedIn', 'fab fa-linkedin', 'blue', 'Professional networking, text, images, carousel, video, articles, polls'],
                'twitter' => ['X (Twitter)', 'fab fa-twitter', 'sky', 'Tweets, threads, media, polls, and reply controls'],
                'pinterest' => ['Pinterest', 'fab fa-pinterest', 'red', 'Pins, video pins, Idea pins, and board management'],
                'tiktok' => ['TikTok', 'fab fa-tiktok', 'gray', 'Video uploads, photo carousel, privacy controls'],
                'tumblr' => ['Tumblr', 'fab fa-tumblr', 'indigo', 'Text, photos, videos, links, quotes, and queue management'],
                'reddit' => ['Reddit', 'fab fa-reddit', 'orange', 'Text, link, image, video posts, and crossposting'],
                'google_business' => ['Google Business Profile', 'fab fa-google', 'blue', 'Local business posts, events, offers, and multi-location publishing'],
                'snapchat' => ['Snapchat', 'fab fa-snapchat', 'yellow', 'Snap Ads and Stories'],
            ];
        @endphp

        @foreach($socialPlatforms as $platform => $info)
            @php $platformConnections = $connectionsByPlatform->get($platform, collect()); @endphp
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-12 h-12 bg-{{ $info[2] }}-100 rounded-lg flex items-center justify-center">
                                <i class="{{ $info[1] }} text-{{ $info[2] }}-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ $info[0] }}</h3>
                                <p class="text-sm text-gray-500">{{ $info[3] }}</p>
                            </div>
                        </div>
                        <button onclick="connectPlatform('{{ $platform }}')"
                               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-{{ $info[2] }}-600 hover:bg-{{ $info[2] }}-700">
                            <i class="fas fa-plug mr-2"></i> Connect
                        </button>
                    </div>

                    @if($platformConnections->count() > 0)
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Connected Accounts</h4>
                            <div class="space-y-3">
                                @foreach($platformConnections as $connection)
                                    @php
                                        $connMetadata = $connection->account_metadata ?? [];
                                        $connSelectedAssets = $connMetadata['selected_assets'] ?? [];
                                        $hasAssets = !empty(array_filter($connSelectedAssets));

                                        // Define asset types per platform
                                        $platformAssetTypes = [
                                            'linkedin' => ['profile', 'page', 'ad_account', 'pixel'],
                                            'twitter' => ['account', 'ad_account', 'pixel', 'catalog'],
                                            'tiktok' => ['account', 'ad_account', 'pixel', 'catalog'],
                                            'snapchat' => ['account', 'ad_account', 'pixel', 'catalog'],
                                            'pinterest' => ['account', 'ad_account', 'pixel', 'catalog'],
                                            'youtube' => ['channel'],
                                            'google' => ['business_profile', 'ad_account'],
                                            'google_business' => ['business_profile'],
                                            'reddit' => ['account'],
                                            'tumblr' => ['account'],
                                        ];
                                        $assetTypes = $platformAssetTypes[$platform] ?? ['account'];

                                        // Asset display labels
                                        $assetLabels = [
                                            'account' => ['Account', 'fas fa-user', 'sky'],
                                            'profile' => ['Profile', 'fas fa-user', 'blue'],
                                            'page' => ['Page', 'fas fa-building', 'blue'],
                                            'channel' => ['Channel', 'fab fa-youtube', 'red'],
                                            'business_profile' => ['Business', 'fab fa-google', 'blue'],
                                            'ad_account' => ['Ad Account', 'fas fa-ad', 'green'],
                                            'pixel' => ['Pixel', 'fas fa-code', 'purple'],
                                            'catalog' => ['Catalog', 'fas fa-shopping-bag', 'orange'],
                                        ];

                                        // Route names for asset selection
                                        $assetRoutes = [
                                            'linkedin' => 'orgs.settings.platform-connections.linkedin.assets',
                                            'twitter' => 'orgs.settings.platform-connections.twitter.assets',
                                            'tiktok' => 'orgs.settings.platform-connections.tiktok.assets',
                                            'snapchat' => 'orgs.settings.platform-connections.snapchat.assets',
                                            'pinterest' => 'orgs.settings.platform-connections.pinterest.assets',
                                            'youtube' => 'orgs.settings.platform-connections.youtube.assets',
                                            'google' => 'orgs.settings.platform-connections.google.assets',
                                            'google_business' => 'orgs.settings.platform-connections.google.assets',
                                            'reddit' => 'orgs.settings.platform-connections.reddit.assets',
                                        ];
                                        $assetRoute = $assetRoutes[$platform] ?? null;
                                    @endphp
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    @if($connection->status === 'active')
                                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                                                            <i class="fas fa-check text-green-600"></i>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100">
                                                            <i class="fas fa-clock text-gray-600"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900">{{ $connection->account_name ?? 'Account' }}</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        @if($connection->token_expires_at)
                                                            Expires {{ $connection->token_expires_at->diffForHumans() }}
                                                        @else
                                                            Active connection
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                {{-- Select Assets (if route exists) --}}
                                                @if($assetRoute && Route::has($assetRoute))
                                                    <a href="{{ route($assetRoute, [$currentOrg, $connection->connection_id]) }}"
                                                       class="p-2 text-gray-400 hover:text-purple-600" title="Select Assets">
                                                        <i class="fas fa-layer-group"></i>
                                                    </a>
                                                @endif

                                                <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-2 text-gray-400 hover:text-blue-600" title="Test Connection">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                      method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect this {{ $info[0] }} account?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600" title="Disconnect">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Show selected assets summary --}}
                                        @if($assetRoute && Route::has($assetRoute))
                                            @if($hasAssets)
                                                <div class="ml-11 mt-3 flex flex-wrap gap-2">
                                                    @foreach($assetTypes as $assetType)
                                                        @if($connSelectedAssets[$assetType] ?? null)
                                                            @php $labelInfo = $assetLabels[$assetType] ?? ['Asset', 'fas fa-check', 'gray']; @endphp
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-{{ $labelInfo[2] }}-100 text-{{ $labelInfo[2] }}-700">
                                                                <i class="{{ $labelInfo[1] }} mr-1"></i>{{ $labelInfo[0] }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                    <a href="{{ route($assetRoute, [$currentOrg, $connection->connection_id]) }}"
                                                       class="text-xs text-blue-600 hover:text-blue-800 ml-1">
                                                        <i class="fas fa-edit mr-1"></i>Edit
                                                    </a>
                                                </div>
                                            @else
                                                <div class="ml-11 mt-3">
                                                    <a href="{{ route($assetRoute, [$currentOrg, $connection->connection_id]) }}"
                                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-{{ $info[2] }}-700 bg-{{ $info[2] }}-50 rounded-md hover:bg-{{ $info[2] }}-100 transition">
                                                        <i class="fas fa-layer-group mr-2"></i>Select Assets
                                                    </a>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mt-4 text-center py-6 bg-gray-50 rounded-lg">
                            <i class="{{ $info[1] }} text-gray-300 text-4xl mb-2"></i>
                            <p class="text-sm text-gray-500">No {{ $info[0] }} accounts connected yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click "Connect" to authorize access</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Help Section --}}
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-sm font-medium text-blue-900 mb-3">
            <i class="fas fa-info-circle mr-2"></i>Platform Connection Guides
        </h3>

        <div class="space-y-4">
            {{-- Meta (Facebook/Instagram/Threads) --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    Meta (Facebook, Instagram, Threads)
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://business.facebook.com/settings/system-users" target="_blank" class="underline">Meta Business Settings → System Users</a></li>
                    <li>Create a new System User (or select existing one)</li>
                    <li>Click "Generate New Token" and select your app</li>
                    <li>Required permissions: <code class="bg-blue-100 px-1 rounded text-xs">ads_management, pages_manage_posts, instagram_basic, instagram_content_publish</code></li>
                    <li>Set token expiration to "Never" for long-lived tokens</li>
                </ol>
            </details>

            {{-- YouTube --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    YouTube
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://console.cloud.google.com" target="_blank" class="underline">Google Cloud Console</a></li>
                    <li>Enable YouTube Data API v3 for your project</li>
                    <li>Create OAuth 2.0 credentials (Web application)</li>
                    <li>Required scopes: <code class="bg-blue-100 px-1 rounded text-xs">youtube.upload, youtube.force-ssl</code></li>
                </ol>
            </details>

            {{-- LinkedIn --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    LinkedIn
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://www.linkedin.com/developers/apps" target="_blank" class="underline">LinkedIn Developers</a></li>
                    <li>Create a new app or select existing one</li>
                    <li>Request access to LinkedIn Pages API and Marketing API</li>
                    <li>Required scopes: <code class="bg-blue-100 px-1 rounded text-xs">w_member_social, r_organization_social</code></li>
                </ol>
            </details>

            {{-- X (Twitter) --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    X (Twitter)
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://developer.twitter.com/en/portal/dashboard" target="_blank" class="underline">Twitter Developer Portal</a></li>
                    <li>Create a new app with OAuth 2.0 enabled</li>
                    <li>Required scopes: <code class="bg-blue-100 px-1 rounded text-xs">tweet.read, tweet.write, users.read</code></li>
                </ol>
            </details>

            {{-- Pinterest --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    Pinterest
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://developers.pinterest.com/apps/" target="_blank" class="underline">Pinterest Developers</a></li>
                    <li>Create a new app and complete verification</li>
                    <li>Required scopes: <code class="bg-blue-100 px-1 rounded text-xs">pins:read, pins:write, boards:read, boards:write</code></li>
                </ol>
            </details>

            {{-- TikTok --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    TikTok
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://developers.tiktok.com/" target="_blank" class="underline">TikTok for Developers</a></li>
                    <li>Create a new app and apply for Content Posting API access</li>
                    <li>Required scopes: <code class="bg-blue-100 px-1 rounded text-xs">video.upload, video.publish</code></li>
                    <li class="text-xs text-red-700"><strong>Note:</strong> Public posting requires audit approval from TikTok</li>
                </ol>
            </details>

            {{-- Reddit --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    Reddit
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://www.reddit.com/prefs/apps" target="_blank" class="underline">Reddit Apps</a></li>
                    <li>Create a "web app" or "script" type application</li>
                    <li>Required scopes: <code class="bg-blue-100 px-1 rounded text-xs">submit, read, identity</code></li>
                </ol>
            </details>

            {{-- Tumblr --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    Tumblr
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://www.tumblr.com/oauth/apps" target="_blank" class="underline">Tumblr Applications</a></li>
                    <li>Register a new OAuth application</li>
                    <li>OAuth 1.0a is used (Consumer Key and Secret)</li>
                </ol>
            </details>

            {{-- Google Business Profile --}}
            <details class="group">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 hover:text-blue-900">
                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform mr-1"></i>
                    Google Business Profile
                </summary>
                <ol class="mt-2 ml-4 text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://console.cloud.google.com" target="_blank" class="underline">Google Cloud Console</a></li>
                    <li>Enable Google Business Profile API</li>
                    <li>Use the same Google OAuth credentials as YouTube</li>
                    <li>Required scopes: <code class="bg-blue-100 px-1 rounded text-xs">business.manage</code></li>
                </ol>
            </details>
        </div>

        <p class="mt-4 text-xs text-blue-700">
            <i class="fas fa-lock mr-1"></i>
            All access tokens are encrypted and stored securely using Laravel's encryption.
        </p>
    </div>
</div>

<script>
function connectPlatform(platform) {
    const orgId = '{{ $currentOrg }}';

    // OAuth endpoints for each platform
    // Note: Threads is now part of Meta assets, accessed via Meta OAuth
    const oauthEndpoints = {
        'youtube': `/orgs/${orgId}/settings/platform-connections/youtube/authorize`,
        'linkedin': `/orgs/${orgId}/settings/platform-connections/linkedin/authorize`,
        'twitter': `/orgs/${orgId}/settings/platform-connections/twitter/authorize`,
        'pinterest': `/orgs/${orgId}/settings/platform-connections/pinterest/authorize`,
        'tiktok': `/orgs/${orgId}/settings/platform-connections/tiktok/authorize`,
        'tumblr': `/orgs/${orgId}/settings/platform-connections/tumblr/authorize`,
        'reddit': `/orgs/${orgId}/settings/platform-connections/reddit/authorize`,
        'google_business': `/orgs/${orgId}/settings/platform-connections/google-business/authorize`,
        'snapchat': `/orgs/${orgId}/settings/platform-connections/snapchat/authorize`,
    };

    const endpoint = oauthEndpoints[platform];

    if (endpoint) {
        // Redirect to platform OAuth authorization
        window.location.href = endpoint;
    } else {
        alert(`Connection flow for ${platform} is coming soon!`);
    }
}
</script>
@endsection
