@extends('layouts.admin')

@section('title', __('Platform Connections') . ' - ' . __('Settings'))

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('settings.settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('settings.platform_connections') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6 sm:mb-8">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('settings.platform_connections') }}</h1>
        <p class="mt-1 text-xs sm:text-sm text-gray-500">
            {{ __('settings.platform_connections_description') }}
        </p>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-check-circle text-green-400 {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-exclamation-circle text-red-400 {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Platform Cards --}}
    <div class="space-y-6">
        {{-- Meta (Facebook/Instagram) --}}
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fab fa-facebook text-blue-600 text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900">{{ __('settings.meta_facebook_instagram_threads') }}</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">{{ __('settings.meta_description') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-2 flex-shrink-0">
                        {{-- OAuth Connect Button --}}
                        <a href="{{ route('orgs.settings.platform-connections.meta.authorize', $currentOrg) }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-blue-600 rounded-md shadow-sm text-xs sm:text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 flex-1 sm:flex-none min-w-0">
                            <i class="fab fa-facebook {{ app()->getLocale() === 'ar' ? 'ml-1 sm:ml-2' : 'mr-1 sm:mr-2' }}"></i>
                            <span class="truncate">{{ __('settings.connect') }}</span>
                        </a>
                        {{-- Manual Token Button --}}
                        <a href="{{ route('orgs.settings.platform-connections.meta.create', $currentOrg) }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-transparent rounded-md shadow-sm text-xs sm:text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 flex-1 sm:flex-none min-w-0">
                            <i class="fas fa-key {{ app()->getLocale() === 'ar' ? 'ml-1 sm:ml-2' : 'mr-1 sm:mr-2' }}"></i>
                            <span class="truncate">{{ __('settings.add_token') }}</span>
                        </a>
                    </div>
                </div>

                @php $metaConnections = $connectionsByPlatform->get('meta', collect()); @endphp

                @if($metaConnections->count() > 0)
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __('settings.connected_accounts') }}</h4>
                        <div class="space-y-3">
                            @foreach($metaConnections as $connection)
                                @php
                                    $metadata = $connection->account_metadata ?? [];
                                    $isSystemUser = $metadata['is_system_user'] ?? false;
                                    $isNeverExpires = $metadata['is_never_expires'] ?? false;
                                    $warnings = $metadata['warnings'] ?? [];
                                    // Check multiple sources for ad accounts count
                                    $selectedAdAccounts = $metadata['selected_assets']['ad_account'] ?? [];
                                    $adAccountsCount = $metadata['ad_accounts_count'] ?? count($metadata['ad_accounts'] ?? $selectedAdAccounts);
                                    $activeAdAccountsCount = $metadata['active_ad_accounts_count'] ?? $adAccountsCount;
                                    $hasWarnings = !empty($warnings);
                                    $hasErrors = collect($warnings)->contains('type', 'error');
                                @endphp

                                <div class="p-3 sm:p-4 bg-gray-50 rounded-lg" x-data="{ mobileMenuOpen: false }">
                                    <div class="flex items-start sm:items-center justify-between gap-3">
                                        <div class="flex items-start sm:items-center flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                @if($connection->status === 'active' && !$hasErrors)
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-green-100">
                                                        <i class="fas fa-check text-green-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @elseif($connection->status === 'error' || $hasErrors)
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-red-100">
                                                        <i class="fas fa-exclamation text-red-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @elseif($connection->status === 'warning' || $hasWarnings)
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-yellow-100">
                                                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gray-100">
                                                        <i class="fas fa-clock text-gray-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="ml-2 sm:ml-3 min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                                    <p class="text-xs sm:text-sm font-medium text-gray-900">{{ $connection->account_name }}</p>
                                                    @if($isSystemUser)
                                                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded whitespace-nowrap">{{ __('settings.system_user') }}</span>
                                                    @endif
                                                    @if($isNeverExpires)
                                                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded whitespace-nowrap">{{ __('settings.never_expires') }}</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    {{ $adAccountsCount }} {{ __('settings.ad_accounts') }}
                                                    @if($activeAdAccountsCount < $adAccountsCount)
                                                        <span class="text-yellow-600">({{ $activeAdAccountsCount }} {{ __('settings.active_status') }})</span>
                                                    @endif
                                                    @if($connection->token_expires_at)
                                                        <span class="hidden sm:inline">&bull;</span>
                                                        <br class="sm:hidden">
                                                        <span>{{ __('settings.expires_in', ['time' => $connection->token_expires_at->diffForHumans()]) }}</span>
                                                    @elseif($isNeverExpires)
                                                        <span class="hidden sm:inline">&bull;</span>
                                                        <span class="text-green-600">{{ __('settings.long_lived_token') }}</span>
                                                    @endif
                                                    @if($metadata['validated_at'] ?? null)
                                                        <span class="hidden sm:inline">&bull; {{ __('settings.validated') }} {{ \Carbon\Carbon::parse($metadata['validated_at'])->diffForHumans() }}</span>
                                                    @endif
                                                </p>
                                                @if($connection->last_error_message)
                                                    <p class="text-xs text-red-600 mt-1">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                        {{ Str::limit($connection->last_error_message, 60) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Desktop Actions (hidden on mobile) --}}
                                        <div class="hidden md:flex items-center gap-1 flex-shrink-0">
                                            <a href="{{ route('orgs.settings.platform-connections.meta.assets', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-purple-600 transition" title="{{ __('settings.select_assets') }}">
                                                <i class="fas fa-layer-group"></i>
                                            </a>
                                            <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.test_connection') }}">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('orgs.settings.platform-connections.meta.refresh-accounts', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-green-600 transition" title="{{ __('settings.refresh_accounts') }}">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('orgs.settings.platform-connections.meta.edit', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.edit') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                  method="POST" class="inline" onsubmit="return confirm('{{ __('settings.confirm_delete_connection') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition" title="{{ __('settings.delete') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        {{-- Mobile Actions (dropdown menu) --}}
                                        <div class="md:hidden relative flex-shrink-0" @click.away="mobileMenuOpen = false">
                                            <button @click="mobileMenuOpen = !mobileMenuOpen"
                                                    class="p-2 text-gray-400 hover:text-gray-600 transition"
                                                    type="button">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="mobileMenuOpen"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="opacity-100 scale-100"
                                                 x-transition:leave-end="opacity-0 scale-95"
                                                 class="absolute right-0 top-10 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
                                                 style="display: none;">
                                                <div class="py-1">
                                                    <a href="{{ route('orgs.settings.platform-connections.meta.assets', [$currentOrg, $connection->connection_id]) }}"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-layer-group w-4 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.select_assets') }}
                                                    </a>
                                                    <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-sync-alt w-4 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.test_connection') }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('orgs.settings.platform-connections.meta.refresh-accounts', [$currentOrg, $connection->connection_id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-redo w-4 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.refresh_accounts') }}
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('orgs.settings.platform-connections.meta.edit', [$currentOrg, $connection->connection_id]) }}"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-edit w-4 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.edit') }}
                                                    </a>
                                                    <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                          method="POST" onsubmit="return confirm('{{ __('settings.confirm_delete_connection') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            <i class="fas fa-trash w-4 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.delete') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
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
                                                    <i class="fas fa-chevron-right group-open:rotate-90 transition-transform {{ app()->getLocale() === 'ar' ? 'ml-1' : 'mr-1' }}"></i>
                                                    {{ count($connection->scopes) }} {{ __('settings.permissions_granted') }}
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
                                                <i class="fab fa-facebook {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_page') }}
                                            </span>
                                        @endif
                                        @if($selectedAssets['instagram_account'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gradient-to-r from-purple-100 to-pink-100 text-pink-700">
                                                <i class="fab fa-instagram {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_instagram') }}
                                            </span>
                                        @endif
                                        @if($selectedAssets['threads_account'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                                <i class="fas fa-at {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_threads') }}
                                            </span>
                                        @endif
                                        @if($selectedAssets['ad_account'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                                <i class="fas fa-ad {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_ad_account') }}
                                            </span>
                                        @endif
                                        @if($selectedAssets['pixel'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                                <i class="fas fa-code {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_pixel') }}
                                            </span>
                                        @endif
                                        @if($selectedAssets['catalog'] ?? null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-700">
                                                <i class="fas fa-shopping-bag {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_catalog') }}
                                            </span>
                                        @endif
                                        <a href="{{ route('orgs.settings.platform-connections.meta.assets', [$currentOrg, $connection->connection_id]) }}"
                                           class="text-xs text-blue-600 hover:text-blue-800 {{ app()->getLocale() === 'ar' ? 'mr-1' : 'ml-1' }}">
                                            <i class="fas fa-edit {{ app()->getLocale() === 'ar' ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.edit_assets') }}
                                        </a>
                                    </div>
                                @else
                                    <div class="ml-11 mt-3">
                                        <a href="{{ route('orgs.settings.platform-connections.meta.assets', [$currentOrg, $connection->connection_id]) }}"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 rounded-md hover:bg-purple-100 transition">
                                            <i class="fas fa-layer-group {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.select_page_instagram_threads') }}
                                        </a>
                                    </div>
                                @endif

                                {{-- Show ad accounts if available --}}
                                @if($metadata['ad_accounts'] ?? null)
                                    <div class="ml-11 mt-2 mb-4">
                                        <details class="group">
                                            <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                                                <i class="fas fa-chevron-right group-open:rotate-90 transition-transform {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                                                {{ __('settings.view_ad_accounts', ['count' => count($metadata['ad_accounts'])]) }}
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
                                                                    {{ ($adAccount['status'] ?? '') === 'Active' ? __('settings.ad_account_status_active') : __('settings.ad_account_status_unknown') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @if($adAccount['disable_reason'] ?? null)
                                                            <p class="text-xs text-red-500 mt-1">
                                                                <i class="fas fa-ban {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ $adAccount['disable_reason'] }}
                                                            </p>
                                                        @endif
                                                        @if(($adAccount['amount_spent'] ?? '0') !== '0')
                                                            <p class="text-xs text-gray-400 mt-1">
                                                                {{ __('settings.total_spent_label') }}: {{ number_format(($adAccount['amount_spent'] ?? 0) / 100, 2) }} {{ $adAccount['currency'] ?? 'USD' }}
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
                        <p class="text-sm text-gray-500">{{ __('settings.no_accounts_connected') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('settings.add_system_user_token') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Google (All Google Services) --}}
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-lg shadow flex items-center justify-center">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                        </div>
                        <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900">{{ __('settings.google_services') }}</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">{{ __('settings.google_services_description') }}</p>
                        </div>
                    </div>
                    @php
                        $googleConfig = config('social-platforms.google');
                        $hasGoogleApiCredentials = !empty($googleConfig['client_id']) && !empty($googleConfig['client_secret']);
                    @endphp
                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- Direct Connect with Google OAuth --}}
                        @if($hasGoogleApiCredentials)
                            <a href="{{ route('orgs.settings.platform-connections.google.authorize', $currentOrg) }}"
                               class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-transparent rounded-md shadow-sm text-xs sm:text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 flex-1 sm:flex-none min-w-0">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 {{ app()->getLocale() === 'ar' ? 'ml-1 sm:ml-2' : 'mr-1 sm:mr-2' }} flex-shrink-0" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="truncate">{{ __('settings.connect') }}</span>
                            </a>
                        @endif
                        {{-- Add Service Account/OAuth Manually --}}
                        <a href="{{ route('orgs.settings.platform-connections.google.create', $currentOrg) }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-gray-300 rounded-md shadow-sm text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 flex-1 sm:flex-none min-w-0"
                           title="{{ __('settings.click_add_manually') }}">
                            <i class="fas fa-key {{ app()->getLocale() === 'ar' ? 'ml-1 sm:ml-2' : 'mr-1 sm:mr-2' }}"></i>
                            <span class="truncate">{{ __('settings.add_manually') }}</span>
                        </a>
                    </div>
                </div>

                @php $googleConnections = $connectionsByPlatform->get('google', collect()); @endphp

                @if($googleConnections->count() > 0)
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __('settings.connected_accounts') }}</h4>
                        <div class="space-y-3">
                            @foreach($googleConnections as $connection)
                                @php
                                    $metadata = $connection->account_metadata ?? [];
                                    $credentialType = $metadata['credential_type'] ?? 'unknown';
                                    $selectedAssets = $metadata['selected_assets'] ?? [];
                                @endphp

                                <div class="p-3 sm:p-4 bg-gray-50 rounded-lg" x-data="{ mobileMenuOpen: false }">
                                    <div class="flex items-start sm:items-center justify-between gap-3">
                                        <div class="flex items-start sm:items-center flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                @if($connection->status === 'active')
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-green-100">
                                                        <i class="fas fa-check text-green-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @elseif($connection->status === 'error')
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-red-100">
                                                        <i class="fas fa-exclamation text-red-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gray-100">
                                                        <i class="fas fa-clock text-gray-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="ml-2 sm:ml-3 min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                                    <p class="text-xs sm:text-sm font-medium text-gray-900">{{ $connection->account_name }}</p>
                                                    @if($credentialType === 'service_account')
                                                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded whitespace-nowrap">{{ __('settings.service_account') }}</span>
                                                    @elseif($credentialType === 'oauth')
                                                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded whitespace-nowrap">{{ __('settings.oauth') }}</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    @if($metadata['service_account_email'] ?? null)
                                                        {{ Str::limit($metadata['service_account_email'], 30) }}
                                                    @endif
                                                    @if($metadata['validated_at'] ?? null)
                                                        <span class="hidden sm:inline">&bull; {{ __('settings.validated') }} {{ \Carbon\Carbon::parse($metadata['validated_at'])->diffForHumans() }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Desktop Actions (hidden on mobile) --}}
                                        <div class="hidden md:flex items-center gap-1 flex-shrink-0">
                                            <a href="{{ route('orgs.settings.platform-connections.google.assets', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.platform_select_services') }}">
                                                <i class="fas fa-layer-group"></i>
                                            </a>
                                            <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.platform_test_connection') }}">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('orgs.settings.platform-connections.google.edit', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.edit_credentials_button') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('{{ __('settings.remove_connection_confirm') }}')"
                                                        class="p-2 text-gray-400 hover:text-red-600 transition" title="{{ __('settings.platform_remove_connection') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        {{-- Mobile Actions (dropdown menu) --}}
                                        <div class="md:hidden relative flex-shrink-0" @click.away="mobileMenuOpen = false">
                                            <button @click="mobileMenuOpen = !mobileMenuOpen"
                                                    class="p-2 text-gray-400 hover:text-gray-600 transition"
                                                    type="button">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="mobileMenuOpen"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="opacity-100 scale-100"
                                                 x-transition:leave-end="opacity-0 scale-95"
                                                 class="absolute right-0 top-10 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
                                                 style="display: none;">
                                                <div class="py-1">
                                                    <a href="{{ route('orgs.settings.platform-connections.google.assets', [$currentOrg, $connection->connection_id]) }}"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $isRtl ? 'text-right' : '' }}">
                                                        <i class="fas fa-layer-group w-4 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.select_services_menu') }}
                                                    </a>
                                                    <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="w-full {{ $isRtl ? 'text-right' : 'text-left' }} px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-sync-alt w-4 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.test_connection_button') }}
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('orgs.settings.platform-connections.google.edit', [$currentOrg, $connection->connection_id]) }}"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $isRtl ? 'text-right' : '' }}">
                                                        <i class="fas fa-edit w-4 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.edit_label') }}
                                                    </a>
                                                    <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                          method="POST" onsubmit="return confirm('{{ __('settings.remove_connection_confirm') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="w-full {{ $isRtl ? 'text-right' : 'text-left' }} px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            <i class="fas fa-trash w-4 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.delete_label') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Show selected assets summary --}}
                                    @if(!empty($selectedAssets))
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if($selectedAssets['youtube_channel'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                                                    <i class="fab fa-youtube {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_youtube') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['google_ads'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                                    <i class="fas fa-ad {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_google_ads') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['analytics'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-700">
                                                    <i class="fas fa-chart-line {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_analytics') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['business_profile'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                                                    <i class="fas fa-store {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_business') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['tag_manager'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                                    <i class="fas fa-code {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_gtm') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['merchant_center'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-teal-100 text-teal-700">
                                                    <i class="fas fa-shopping-cart {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_merchant') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['search_console'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-indigo-100 text-indigo-700">
                                                    <i class="fas fa-search {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_search_console') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['calendar'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-cyan-100 text-cyan-700">
                                                    <i class="fas fa-calendar {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_calendar') }}
                                                </span>
                                            @endif
                                            @if($selectedAssets['drive'] ?? null)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">
                                                    <i class="fas fa-folder {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.asset_type_drive') }}
                                                </span>
                                            @endif
                                            <a href="{{ route('orgs.settings.platform-connections.google.assets', [$currentOrg, $connection->connection_id]) }}"
                                               class="text-xs text-blue-600 hover:text-blue-800 {{ $isRtl ? 'mr-1' : 'ml-1' }}">
                                                <i class="fas fa-edit {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.edit_label') }}
                                            </a>
                                        </div>
                                    @else
                                        <div class="mt-3">
                                            <a href="{{ route('orgs.settings.platform-connections.google.assets', [$currentOrg, $connection->connection_id]) }}"
                                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 transition">
                                                <i class="fas fa-layer-group {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.select_google_services_youtube') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-4 text-center py-6 bg-gray-50 rounded-lg">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <p class="text-sm text-gray-500">{{ __('settings.no_google_accounts_connected_yet') }}</p>
                        @if($hasGoogleApiCredentials)
                            <p class="text-xs text-gray-400 mt-1">{{ __('settings.click_connect_sign_in_google') }}</p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">{{ __('settings.click_add_manually_credentials') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Social Media Platforms --}}
        {{-- Note: Threads is now part of Meta assets, not a standalone platform --}}
        {{-- Note: YouTube and Google Business Profile are now part of Google Services assets --}}
        {{-- TikTok (Account + Ads) - Separate section with 2 buttons like Meta/Google --}}
        @php
            $tiktokAccountConnections = $connectionsByPlatform->get('tiktok', collect());
            $tiktokAdsConnections = $connectionsByPlatform->get('tiktok_ads', collect());
        @endphp
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fab fa-tiktok text-gray-800 text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ms-3 sm:ms-4 min-w-0 flex-1">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900">TikTok</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">{{ __('settings.tiktok_description') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-2 flex-shrink-0">
                        {{-- Connect Account Button (Login Kit - for video publishing) --}}
                        <a href="{{ route('orgs.settings.platform-connections.tiktok.authorize', $currentOrg) }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-gray-600 rounded-md shadow-sm text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 flex-1 sm:flex-none min-w-0">
                            <i class="fab fa-tiktok {{ $isRtl ? 'ms-1 sm:ms-2' : 'me-1 sm:me-2' }}"></i>
                            <span class="truncate">{{ __('settings.connect_account') }}</span>
                        </a>
                        {{-- Connect Ads Button (Business API - for advertising) --}}
                        <a href="{{ route('orgs.settings.platform-connections.tiktok-ads.authorize', $currentOrg) }}"
                           class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-transparent rounded-md shadow-sm text-xs sm:text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 flex-1 sm:flex-none min-w-0">
                            <i class="fas fa-ad {{ $isRtl ? 'ms-1 sm:ms-2' : 'me-1 sm:me-2' }}"></i>
                            <span class="truncate">{{ __('settings.connect_ads') }}</span>
                        </a>
                    </div>
                </div>

                {{-- TikTok Account Connections --}}
                @if($tiktokAccountConnections->count() > 0)
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">
                            <i class="fab fa-tiktok {{ $isRtl ? 'ms-1' : 'me-1' }} text-gray-500"></i>
                            {{ __('settings.tiktok_accounts') }}
                        </h4>
                        <div class="space-y-3">
                            @foreach($tiktokAccountConnections as $connection)
                                @php
                                    $connMetadata = $connection->account_metadata ?? [];
                                    $connSelectedAssets = $connMetadata['selected_assets'] ?? [];
                                    $hasAssets = !empty(array_filter($connSelectedAssets));
                                @endphp
                                <div class="p-3 sm:p-4 bg-gray-50 rounded-lg" x-data="{ mobileMenuOpen: false }">
                                    <div class="flex items-start sm:items-center justify-between gap-3">
                                        <div class="flex items-start sm:items-center flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                @if($connection->status === 'active')
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-green-100">
                                                        <i class="fas fa-check text-green-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gray-100">
                                                        <i class="fas fa-clock text-gray-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="ms-2 sm:ms-3 min-w-0 flex-1">
                                                <p class="text-xs sm:text-sm font-medium text-gray-900">{{ $connection->account_name ?? __('settings.account_label') }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    @if($connection->token_expires_at)
                                                        {{ __('settings.expires_in', ['time' => $connection->token_expires_at->diffForHumans()]) }}
                                                    @else
                                                        {{ __('settings.active_connection') }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        {{-- Desktop Actions --}}
                                        <div class="hidden md:flex items-center gap-1 flex-shrink-0">
                                            <a href="{{ route('orgs.settings.platform-connections.tiktok.assets', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-purple-600 transition" title="{{ __('settings.select_assets_label') }}">
                                                <i class="fas fa-layer-group"></i>
                                            </a>
                                            <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.platform_test_connection') }}">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                  method="POST" class="inline" onsubmit="return confirm('{{ __('settings.confirm_disconnect_platform', ['platform' => 'TikTok']) }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition" title="{{ __('settings.disconnect_label') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        {{-- Mobile Actions --}}
                                        <div class="md:hidden relative flex-shrink-0" @click.away="mobileMenuOpen = false">
                                            <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-gray-400 hover:text-gray-600 transition" type="button">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="mobileMenuOpen" x-transition class="absolute {{ $isRtl ? 'left-0' : 'right-0' }} top-10 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10" style="display: none;">
                                                <div class="py-1">
                                                    <a href="{{ route('orgs.settings.platform-connections.tiktok.assets', [$currentOrg, $connection->connection_id]) }}"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $isRtl ? 'text-end' : '' }}">
                                                        <i class="fas fa-layer-group w-4 {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>{{ __('settings.select_assets_label') }}
                                                    </a>
                                                    <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="w-full {{ $isRtl ? 'text-end' : 'text-start' }} px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-sync-alt w-4 {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>{{ __('settings.platform_test_connection') }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                          method="POST" onsubmit="return confirm('{{ __('settings.confirm_disconnect_platform', ['platform' => 'TikTok']) }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="w-full {{ $isRtl ? 'text-end' : 'text-start' }} px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            <i class="fas fa-trash w-4 {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>{{ __('settings.delete_label') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- TikTok Ads Connections --}}
                @if($tiktokAdsConnections->count() > 0)
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-ad {{ $isRtl ? 'ms-1' : 'me-1' }} text-gray-500"></i>
                            {{ __('settings.tiktok_ads_accounts') }}
                        </h4>
                        <div class="space-y-3">
                            @foreach($tiktokAdsConnections as $connection)
                                @php
                                    $connMetadata = $connection->account_metadata ?? [];
                                    $advertiserCount = $connMetadata['advertiser_count'] ?? count($connMetadata['advertiser_ids'] ?? []);
                                @endphp
                                <div class="p-3 sm:p-4 bg-gray-50 rounded-lg" x-data="{ mobileMenuOpen: false }">
                                    <div class="flex items-start sm:items-center justify-between gap-3">
                                        <div class="flex items-start sm:items-center flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                @if($connection->status === 'active')
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-green-100">
                                                        <i class="fas fa-check text-green-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gray-100">
                                                        <i class="fas fa-clock text-gray-600 text-xs sm:text-sm"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="ms-2 sm:ms-3 min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                                    <p class="text-xs sm:text-sm font-medium text-gray-900">{{ $connection->account_name ?? __('settings.tiktok_ads_manager') }}</p>
                                                    <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded whitespace-nowrap">{{ __('settings.never_expires') }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    {{ $advertiserCount }} {{ __('settings.advertiser_accounts') }}
                                                    &bull; {{ __('settings.active_connection') }}
                                                </p>
                                            </div>
                                        </div>
                                        {{-- Desktop Actions --}}
                                        <div class="hidden md:flex items-center gap-1 flex-shrink-0">
                                            <a href="{{ route('orgs.settings.platform-connections.tiktok-ads.assets', [$currentOrg, $connection->connection_id]) }}"
                                               class="p-2 text-gray-400 hover:text-purple-600 transition" title="{{ __('settings.select_assets_label') }}">
                                                <i class="fas fa-layer-group"></i>
                                            </a>
                                            <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.platform_test_connection') }}">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                  method="POST" class="inline" onsubmit="return confirm('{{ __('settings.confirm_disconnect_platform', ['platform' => 'TikTok Ads']) }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition" title="{{ __('settings.disconnect_label') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        {{-- Mobile Actions --}}
                                        <div class="md:hidden relative flex-shrink-0" @click.away="mobileMenuOpen = false">
                                            <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-gray-400 hover:text-gray-600 transition" type="button">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="mobileMenuOpen" x-transition class="absolute {{ $isRtl ? 'left-0' : 'right-0' }} top-10 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10" style="display: none;">
                                                <div class="py-1">
                                                    <a href="{{ route('orgs.settings.platform-connections.tiktok-ads.assets', [$currentOrg, $connection->connection_id]) }}"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $isRtl ? 'text-end' : '' }}">
                                                        <i class="fas fa-layer-group w-4 {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>{{ __('settings.select_assets_label') }}
                                                    </a>
                                                    <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="w-full {{ $isRtl ? 'text-end' : 'text-start' }} px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-sync-alt w-4 {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>{{ __('settings.platform_test_connection') }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                          method="POST" onsubmit="return confirm('{{ __('settings.confirm_disconnect_platform', ['platform' => 'TikTok Ads']) }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="w-full {{ $isRtl ? 'text-end' : 'text-start' }} px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            <i class="fas fa-trash w-4 {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>{{ __('settings.delete_label') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Empty State --}}
                @if($tiktokAccountConnections->count() === 0 && $tiktokAdsConnections->count() === 0)
                    <div class="mt-4 text-center py-6 bg-gray-50 rounded-lg">
                        <i class="fab fa-tiktok text-gray-300 text-4xl mb-2"></i>
                        <p class="text-sm text-gray-500">{{ __('settings.no_tiktok_connected') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('settings.click_connect_to_authorize') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Other Social Media Platforms (excluding TikTok which now has its own section) --}}
        {{-- Note: Threads is now part of Meta assets, not a standalone platform --}}
        {{-- Note: YouTube and Google Business Profile are now part of Google Services assets --}}
        @php
            $socialPlatforms = [
                'linkedin' => ['LinkedIn', 'fab fa-linkedin', 'blue', 'Professional networking, text, images, carousel, video, articles, polls'],
                'twitter' => ['X (Twitter)', 'fab fa-twitter', 'sky', 'Tweets, threads, media, polls, and reply controls'],
                'pinterest' => ['Pinterest', 'fab fa-pinterest', 'red', 'Pins, video pins, Idea pins, and board management'],
                'tumblr' => ['Tumblr', 'fab fa-tumblr', 'indigo', 'Text, photos, videos, links, quotes, and queue management'],
                'reddit' => ['Reddit', 'fab fa-reddit', 'orange', 'Text, link, image, video posts, and crossposting'],
                'snapchat' => ['Snapchat', 'fab fa-snapchat', 'yellow', 'Snap Ads and Stories'],
            ];
        @endphp

        @foreach($socialPlatforms as $platform => $info)
            @php $platformConnections = $connectionsByPlatform->get($platform, collect()); @endphp
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-{{ $info[2] }}-100 rounded-lg flex items-center justify-center">
                                <i class="{{ $info[1] }} text-{{ $info[2] }}-600 text-xl sm:text-2xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                <h3 class="text-base sm:text-lg font-medium text-gray-900">{{ $info[0] }}</h3>
                                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">{{ $info[3] }}</p>
                            </div>
                        </div>
                        <button onclick="connectPlatform('{{ $platform }}')"
                               class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-transparent rounded-md shadow-sm text-xs sm:text-sm font-medium text-white bg-{{ $info[2] }}-600 hover:bg-{{ $info[2] }}-700 flex-shrink-0 w-full sm:w-auto">
                            <i class="fas fa-plug {{ $isRtl ? 'ml-1 sm:ml-2' : 'mr-1 sm:mr-2' }}"></i>
                            <span>Connect</span>
                        </button>
                    </div>

                    @if($platformConnections->count() > 0)
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __('settings.connected_accounts') }}</h4>
                            <div class="space-y-3">
                                @foreach($platformConnections as $connection)
                                    @php
                                        $connMetadata = $connection->account_metadata ?? [];
                                        $connSelectedAssets = $connMetadata['selected_assets'] ?? [];
                                        $hasAssets = !empty(array_filter($connSelectedAssets));

                                        // Define asset types per platform
                                        // Note: youtube and google_business are now part of Google Services
                                        $platformAssetTypes = [
                                            'linkedin' => ['profile', 'page', 'ad_account', 'pixel'],
                                            'twitter' => ['account', 'ad_account', 'pixel', 'catalog'],
                                            'tiktok' => ['account', 'ad_account', 'pixel', 'catalog'],
                                            'snapchat' => ['account', 'ad_account', 'pixel', 'catalog'],
                                            'pinterest' => ['account', 'ad_account', 'pixel', 'catalog'],
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
                                    <div class="p-3 sm:p-4 bg-gray-50 rounded-lg" x-data="{ mobileMenuOpen: false }">
                                        <div class="flex items-start sm:items-center justify-between gap-3">
                                            <div class="flex items-start sm:items-center flex-1 min-w-0">
                                                <div class="flex-shrink-0">
                                                    @if($connection->status === 'active')
                                                        <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-green-100">
                                                            <i class="fas fa-check text-green-600 text-xs sm:text-sm"></i>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gray-100">
                                                            <i class="fas fa-clock text-gray-600 text-xs sm:text-sm"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="ml-2 sm:ml-3 min-w-0 flex-1">
                                                    <p class="text-xs sm:text-sm font-medium text-gray-900">{{ $connection->account_name ?? __('settings.account_label') }}</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        @if($connection->token_expires_at)
                                                            {{ __('settings.expires_in', ['time' => $connection->token_expires_at->diffForHumans()]) }}
                                                        @else
                                                            {{ __('settings.active_connection') }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- Desktop Actions (hidden on mobile) --}}
                                            <div class="hidden md:flex items-center gap-1 flex-shrink-0">
                                                @if($assetRoute && Route::has($assetRoute))
                                                    <a href="{{ route($assetRoute, [$currentOrg, $connection->connection_id]) }}"
                                                       class="p-2 text-gray-400 hover:text-purple-600 transition" title="{{ __('settings.select_assets_label') }}">
                                                        <i class="fas fa-layer-group"></i>
                                                    </a>
                                                @endif
                                                <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-2 text-gray-400 hover:text-blue-600 transition" title="{{ __('settings.platform_test_connection') }}">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                      method="POST" class="inline" onsubmit="return confirm('{{ __('settings.confirm_disconnect_platform', ['platform' => $info[0]]) }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition" title="{{ __('settings.disconnect_label') }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Mobile Actions (dropdown menu) --}}
                                            <div class="md:hidden relative flex-shrink-0" @click.away="mobileMenuOpen = false">
                                                <button @click="mobileMenuOpen = !mobileMenuOpen"
                                                        class="p-2 text-gray-400 hover:text-gray-600 transition"
                                                        type="button">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div x-show="mobileMenuOpen"
                                                     x-transition:enter="transition ease-out duration-100"
                                                     x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100"
                                                     x-transition:leave="transition ease-in duration-75"
                                                     x-transition:leave-start="opacity-100 scale-100"
                                                     x-transition:leave-end="opacity-0 scale-95"
                                                     class="absolute right-0 top-10 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
                                                     style="display: none;">
                                                    <div class="py-1">
                                                        @if($assetRoute && Route::has($assetRoute))
                                                            <a href="{{ route($assetRoute, [$currentOrg, $connection->connection_id]) }}"
                                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $isRtl ? 'text-right' : '' }}">
                                                                <i class="fas fa-layer-group w-4 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.select_assets_label') }}
                                                            </a>
                                                        @endif
                                                        <form action="{{ route('orgs.settings.platform-connections.test', [$currentOrg, $connection->connection_id]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="w-full {{ $isRtl ? 'text-right' : 'text-left' }} px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                <i class="fas fa-sync-alt w-4 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.platform_test_connection') }}
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('orgs.settings.platform-connections.destroy', [$currentOrg, $connection->connection_id]) }}"
                                                              method="POST" onsubmit="return confirm('{{ __('settings.confirm_disconnect_platform', ['platform' => $info[0]]) }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="w-full {{ $isRtl ? 'text-right' : 'text-left' }} px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                                <i class="fas fa-trash w-4 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.delete_label') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
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
                                                                <i class="{{ $labelInfo[1] }} {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ $labelInfo[0] }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                    <a href="{{ route($assetRoute, [$currentOrg, $connection->connection_id]) }}"
                                                       class="text-xs text-blue-600 hover:text-blue-800 {{ $isRtl ? 'mr-1' : 'ml-1' }}">
                                                        <i class="fas fa-edit {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('settings.edit_label') }}
                                                    </a>
                                                </div>
                                            @else
                                                <div class="ml-11 mt-3">
                                                    <a href="{{ route($assetRoute, [$currentOrg, $connection->connection_id]) }}"
                                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-{{ $info[2] }}-700 bg-{{ $info[2] }}-50 rounded-md hover:bg-{{ $info[2] }}-100 transition">
                                                        <i class="fas fa-layer-group {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.select_assets_label') }}
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
            <i class="fas fa-info-circle {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>{{ __('settings.platform_connection_guides') }}
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
            <i class="fas fa-lock {{ app()->getLocale() === 'ar' ? 'ml-1' : 'mr-1' }}"></i>
            {{ __('settings.all_tokens_encrypted') }}
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
