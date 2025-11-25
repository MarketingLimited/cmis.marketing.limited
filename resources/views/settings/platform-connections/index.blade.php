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
                            <h3 class="text-lg font-medium text-gray-900">Meta (Facebook/Instagram)</h3>
                            <p class="text-sm text-gray-500">Connect using a System User access token from Meta Business Manager</p>
                        </div>
                    </div>
                    <a href="{{ route('orgs.settings.platform-connections.meta.create', $currentOrg) }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Add Token
                    </a>
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

                                {{-- Show selected assets summary --}}
                                @php $selectedAssets = $metadata['selected_assets'] ?? []; @endphp
                                @if(!empty($selectedAssets) && (count($selectedAssets['pages'] ?? []) > 0 || count($selectedAssets['instagram_accounts'] ?? []) > 0 || count($selectedAssets['ad_accounts'] ?? []) > 0 || count($selectedAssets['pixels'] ?? []) > 0 || count($selectedAssets['catalogs'] ?? []) > 0))
                                    <div class="ml-11 mt-3 flex flex-wrap gap-2">
                                        @if(count($selectedAssets['pages'] ?? []) > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                                                <i class="fab fa-facebook mr-1"></i>{{ count($selectedAssets['pages']) }} Page(s)
                                            </span>
                                        @endif
                                        @if(count($selectedAssets['instagram_accounts'] ?? []) > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gradient-to-r from-purple-100 to-pink-100 text-pink-700">
                                                <i class="fab fa-instagram mr-1"></i>{{ count($selectedAssets['instagram_accounts']) }} Instagram
                                            </span>
                                        @endif
                                        @if(count($selectedAssets['ad_accounts'] ?? []) > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                                <i class="fas fa-ad mr-1"></i>{{ count($selectedAssets['ad_accounts']) }} Ad Account(s)
                                            </span>
                                        @endif
                                        @if(count($selectedAssets['pixels'] ?? []) > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                                <i class="fas fa-code mr-1"></i>{{ count($selectedAssets['pixels']) }} Pixel(s)
                                            </span>
                                        @endif
                                        @if(count($selectedAssets['catalogs'] ?? []) > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-700">
                                                <i class="fas fa-shopping-bag mr-1"></i>{{ count($selectedAssets['catalogs']) }} Catalog(s)
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
                                            <i class="fas fa-layer-group mr-2"></i>Select Pages, Instagram, Pixels & Catalogs
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

        {{-- Other Platforms (Coming Soon) --}}
        @foreach(['google' => ['Google Ads', 'fab fa-google', 'red'], 'tiktok' => ['TikTok Ads', 'fab fa-tiktok', 'gray'], 'linkedin' => ['LinkedIn Ads', 'fab fa-linkedin', 'blue'], 'twitter' => ['Twitter (X) Ads', 'fab fa-twitter', 'sky'], 'snapchat' => ['Snapchat Ads', 'fab fa-snapchat', 'yellow']] as $platform => $info)
            @php $platformConnections = $connectionsByPlatform->get($platform, collect()); @endphp
            <div class="bg-white shadow sm:rounded-lg overflow-hidden opacity-75">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-12 h-12 bg-{{ $info[2] }}-100 rounded-lg flex items-center justify-center">
                                <i class="{{ $info[1] }} text-{{ $info[2] }}-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ $info[0] }}</h3>
                                <p class="text-sm text-gray-500">OAuth integration coming soon</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            Coming Soon
                        </span>
                    </div>

                    @if($platformConnections->count() > 0)
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <div class="space-y-2">
                                @foreach($platformConnections as $connection)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                        <span>{{ $connection->account_name }}</span>
                                        <span class="px-2 py-1 rounded text-xs {{ $connection->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ ucfirst($connection->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Help Section --}}
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-sm font-medium text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>How to get a Meta System User Access Token
        </h3>
        <ol class="text-sm text-blue-800 space-y-2 list-decimal list-inside">
            <li>Go to <a href="https://business.facebook.com/settings/system-users" target="_blank" class="underline">Meta Business Settings &rarr; System Users</a></li>
            <li>Create a new System User (or select existing one)</li>
            <li>Click "Generate New Token"</li>
            <li>Select your app and required permissions:
                <code class="bg-blue-100 px-1 rounded text-xs">ads_management, ads_read, business_management</code>
            </li>
            <li>Set token expiration to "Never" for long-lived tokens</li>
            <li>Copy the generated token and paste it here</li>
        </ol>
        <p class="mt-3 text-xs text-blue-700">
            <i class="fas fa-lock mr-1"></i>
            Your access tokens are encrypted and stored securely.
        </p>
    </div>
</div>
@endsection
