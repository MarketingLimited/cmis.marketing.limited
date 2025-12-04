@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', $businessAsset->account_name . ' - ' . __('settings.tiktok_business_assets'))

@section('content')
<div class="space-y-6" x-data="tiktokAssetsPage()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
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
            <a href="{{ route('orgs.settings.platform-connections.tiktok-assets.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('settings.tiktok_business_assets') }}</a>
            <span class="text-gray-400">{{ $isRtl ? '\\' : '/' }}</span>
            <span class="text-gray-900 font-medium">{{ $businessAsset->account_name }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.configure_tiktok_business_asset') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('settings.tiktok_accounts_desc') }} & {{ __('settings.tiktok_ads_accounts_desc') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Business Asset Header --}}
    <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 w-12 h-12 bg-gray-800 rounded-lg flex items-center justify-center">
                    <i class="fab fa-tiktok text-white text-2xl"></i>
                </div>
                <div class="{{ $isRtl ? 'text-right' : '' }}">
                    <p class="font-medium text-white text-lg" x-text="businessAssetName">{{ $businessAsset->account_name }}</p>
                    <p class="text-sm text-gray-300">
                        <i class="fas fa-check-circle text-green-400 {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                        {{ __('settings.connected') }} {{ $businessAsset->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <button type="button"
                        @click="showEditNameModal = true"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-600 rounded-md text-sm text-gray-300 hover:bg-gray-800 transition">
                    <i class="fas fa-edit {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                    {{ __('common.edit') }}
                </button>
                <button type="button"
                        @click="showDeleteModal = true"
                        class="inline-flex items-center px-3 py-1.5 border border-red-600 rounded-md text-sm text-red-400 hover:bg-red-900/20 transition">
                    <i class="fas fa-trash {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                    {{ __('common.delete') }}
                </button>
            </div>
        </div>
    </div>

    {{-- TikTok Accounts Section --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-cyan-500 rounded-lg flex items-center justify-center">
                        <i class="fab fa-tiktok text-white"></i>
                    </div>
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('settings.tiktok_accounts_section_title') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('settings.tiktok_accounts_desc') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    @if($availableTiktokAccounts->count() > 0)
                        <button type="button"
                                @click="showLinkTiktokModal = true"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-link {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                            {{ __('settings.link_existing_account') }}
                        </button>
                    @endif
                    <a href="{{ route('orgs.settings.platform-connections.tiktok.authorize', $currentOrg) }}?business_asset_id={{ $businessAsset->connection_id }}"
                       class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800">
                        <i class="fab fa-tiktok {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                        {{ __('settings.connect_tiktok_account') }}
                    </a>
                </div>
            </div>

            @if($tiktokAccounts->count() > 0)
                <div class="space-y-3">
                    @foreach($tiktokAccounts as $account)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                                    @if(!empty($account->account_metadata['avatar_url']))
                                        <img src="{{ $account->account_metadata['avatar_url'] }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <i class="fab fa-tiktok text-gray-600"></i>
                                    @endif
                                </div>
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm font-medium text-gray-900">{{ $account->account_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        @if($account->isTokenExpired())
                                            <span class="text-red-500"><i class="fas fa-exclamation-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('settings.token_expired') }}</span>
                                        @else
                                            <span class="text-green-600"><i class="fas fa-check-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('settings.connected') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                @if($account->isTokenExpired())
                                    <form action="{{ route('orgs.settings.platform-connections.tiktok.refresh', [$currentOrg, $account->connection_id]) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-sync-alt {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('settings.refresh_token') }}
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('orgs.settings.platform-connections.tiktok-assets.unlink', [$currentOrg, $businessAsset->connection_id]) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="connection_id" value="{{ $account->connection_id }}">
                                    <input type="hidden" name="platform" value="tiktok">
                                    <button type="submit"
                                            onclick="return confirm('{{ __('settings.confirm_unlink_account') }}')"
                                            class="text-xs text-red-600 hover:text-red-800">
                                        <i class="fas fa-unlink {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('settings.unlink_account') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <i class="fab fa-tiktok text-gray-300 text-4xl mb-3"></i>
                    <p class="text-sm text-gray-500">{{ __('settings.no_linked_tiktok_accounts') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('settings.connect_tiktok_account_to_asset') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- TikTok Ads Accounts Section --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-ad text-white"></i>
                    </div>
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('settings.tiktok_ads_section_title') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('settings.tiktok_ads_accounts_desc') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    @if($availableTiktokAdsAccounts->count() > 0)
                        <button type="button"
                                @click="showLinkTiktokAdsModal = true"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-link {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                            {{ __('settings.link_existing_account') }}
                        </button>
                    @endif
                    <a href="{{ route('orgs.settings.platform-connections.tiktok-ads.authorize', $currentOrg) }}?business_asset_id={{ $businessAsset->connection_id }}"
                       class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800">
                        <i class="fab fa-tiktok {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                        {{ __('settings.connect_tiktok_ads') }}
                    </a>
                </div>
            </div>

            @if($tiktokAdsAccounts->count() > 0)
                <div class="space-y-3">
                    @foreach($tiktokAdsAccounts as $account)
                        @php
                            $metadata = $account->account_metadata ?? [];
                            $advertiserIds = $metadata['advertiser_ids'] ?? [];
                            $selectedAssets = $metadata['selected_assets'] ?? [];
                            $selectedAdvertiserIds = $selectedAssets['advertiser_ids'] ?? [];
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                    <i class="fab fa-tiktok text-white"></i>
                                </div>
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm font-medium text-gray-900">{{ $account->account_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        <span class="text-green-600"><i class="fas fa-check-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('settings.connected') }}</span>
                                        @if(count($selectedAdvertiserIds) > 0)
                                            &bull; {{ count($selectedAdvertiserIds) }} {{ __('settings.advertiser_accounts') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <a href="{{ route('orgs.settings.platform-connections.tiktok-ads.assets', [$currentOrg, $account->connection_id]) }}"
                                   class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-cog {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('settings.manage_tiktok_assets') }}
                                </a>
                                <form action="{{ route('orgs.settings.platform-connections.tiktok-assets.unlink', [$currentOrg, $businessAsset->connection_id]) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="connection_id" value="{{ $account->connection_id }}">
                                    <input type="hidden" name="platform" value="tiktok_ads">
                                    <button type="submit"
                                            onclick="return confirm('{{ __('settings.confirm_unlink_account') }}')"
                                            class="text-xs text-red-600 hover:text-red-800">
                                        <i class="fas fa-unlink {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>{{ __('settings.unlink_account') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <i class="fas fa-ad text-gray-300 text-4xl mb-3"></i>
                    <p class="text-sm text-gray-500">{{ __('settings.no_linked_tiktok_ads_accounts') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('settings.connect_tiktok_ads_to_asset') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Help Section --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 {{ $isRtl ? 'text-right' : '' }}">
        <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <i class="fas fa-info-circle text-blue-500 flex-shrink-0 mt-0.5"></i>
            <div class="text-sm text-blue-700">
                <p class="font-medium mb-1">{{ __('settings.about_tiktok_advertiser_accounts') }}</p>
                <ul class="list-disc {{ $isRtl ? 'pr-4' : 'pl-4' }} space-y-1 text-blue-600">
                    <li>{{ __('settings.tiktok_advertiser_hint_1') }}</li>
                    <li>{{ __('settings.tiktok_advertiser_hint_2') }}</li>
                    <li>{{ __('settings.tiktok_advertiser_hint_3') }}</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('orgs.settings.platform-connections.tiktok-assets.index', $currentOrg) }}"
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }} {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
            {{ __('settings.back_to_tiktok_assets') }}
        </a>
    </div>

    {{-- Edit Name Modal --}}
    <div x-show="showEditNameModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="edit-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showEditNameModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showEditNameModal = false"></div>

            <div x-show="showEditNameModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-{{ $isRtl ? 'right' : 'left' }} overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('orgs.settings.platform-connections.tiktok-assets.update', [$currentOrg, $businessAsset->connection_id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="sm:flex sm:items-start {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-edit text-gray-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 {{ $isRtl ? 'sm:me-4 sm:text-right' : 'sm:ms-4 sm:text-left' }} flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="edit-modal-title">
                                {{ __('settings.edit_business_asset_name') }}
                            </h3>
                            <div class="mt-4">
                                <input type="text"
                                       name="name"
                                       x-model="businessAssetName"
                                       required
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex {{ $isRtl ? 'sm:flex-row-reverse' : '' }} gap-3">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 sm:w-auto sm:text-sm">
                            {{ __('common.save') }}
                        </button>
                        <button type="button"
                                @click="showEditNameModal = false; businessAssetName = '{{ $businessAsset->account_name }}'"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="delete-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showDeleteModal = false"></div>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-{{ $isRtl ? 'right' : 'left' }} overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 {{ $isRtl ? 'sm:me-4 sm:text-right' : 'sm:ms-4 sm:text-left' }}">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="delete-modal-title">
                            {{ __('settings.delete_business_asset') }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                {{ __('settings.confirm_delete_business_asset') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex {{ $isRtl ? 'sm:flex-row-reverse' : '' }} gap-3">
                    <form action="{{ route('orgs.settings.platform-connections.tiktok-assets.destroy', [$currentOrg, $businessAsset->connection_id]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                            {{ __('common.delete') }}
                        </button>
                    </form>
                    <button type="button"
                            @click="showDeleteModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm">
                        {{ __('common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Link TikTok Account Modal --}}
    @if($availableTiktokAccounts->count() > 0)
    <div x-show="showLinkTiktokModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="link-tiktok-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showLinkTiktokModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showLinkTiktokModal = false"></div>

            <div x-show="showLinkTiktokModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-{{ $isRtl ? 'right' : 'left' }} overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="link-tiktok-modal-title">
                        {{ __('settings.link_existing_account') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('settings.available_accounts') }}</p>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($availableTiktokAccounts as $account)
                        <form action="{{ route('orgs.settings.platform-connections.tiktok-assets.link', [$currentOrg, $businessAsset->connection_id]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="connection_id" value="{{ $account->connection_id }}">
                            <input type="hidden" name="platform" value="tiktok">
                            <button type="submit"
                                    class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                        <i class="fab fa-tiktok text-gray-600 text-sm"></i>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $account->account_name }}</span>
                                </div>
                                <i class="fas fa-plus text-gray-400"></i>
                            </button>
                        </form>
                    @endforeach
                </div>
                <div class="mt-4">
                    <button type="button"
                            @click="showLinkTiktokModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm">
                        {{ __('common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Link TikTok Ads Account Modal --}}
    @if($availableTiktokAdsAccounts->count() > 0)
    <div x-show="showLinkTiktokAdsModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="link-tiktok-ads-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showLinkTiktokAdsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showLinkTiktokAdsModal = false"></div>

            <div x-show="showLinkTiktokAdsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-{{ $isRtl ? 'right' : 'left' }} overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="link-tiktok-ads-modal-title">
                        {{ __('settings.link_existing_account') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('settings.available_accounts') }}</p>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($availableTiktokAdsAccounts as $account)
                        <form action="{{ route('orgs.settings.platform-connections.tiktok-assets.link', [$currentOrg, $businessAsset->connection_id]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="connection_id" value="{{ $account->connection_id }}">
                            <input type="hidden" name="platform" value="tiktok_ads">
                            <button type="submit"
                                    class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                                        <i class="fab fa-tiktok text-white text-sm"></i>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $account->account_name }}</span>
                                </div>
                                <i class="fas fa-plus text-gray-400"></i>
                            </button>
                        </form>
                    @endforeach
                </div>
                <div class="mt-4">
                    <button type="button"
                            @click="showLinkTiktokAdsModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm">
                        {{ __('common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function tiktokAssetsPage() {
    return {
        businessAssetName: @json($businessAsset->account_name),
        showEditNameModal: false,
        showDeleteModal: false,
        showLinkTiktokModal: false,
        showLinkTiktokAdsModal: false,
    }
}
</script>
@endpush
