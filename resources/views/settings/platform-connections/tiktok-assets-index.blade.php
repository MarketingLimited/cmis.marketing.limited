@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('settings.tiktok_business_assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="tiktokAssetsIndexPage()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
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
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.tiktok_business_assets') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('settings.create_first_tiktok_asset') }}
                </p>
            </div>
            <button type="button"
                    @click="showCreateModal = true"
                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                <i class="fas fa-plus {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                {{ __('settings.create_tiktok_business_asset') }}
            </button>
        </div>
    </div>

    {{-- Business Assets List --}}
    @if($businessAssets->count() > 0)
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <ul class="divide-y divide-gray-200">
                @foreach($businessAssets as $asset)
                    <li>
                        <a href="{{ route('orgs.settings.platform-connections.tiktok-assets.show', [$currentOrg, $asset->connection_id]) }}"
                           class="block hover:bg-gray-50 transition">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <div class="flex-shrink-0 w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                            <i class="fab fa-tiktok text-white text-lg"></i>
                                        </div>
                                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                                            <p class="text-sm font-medium text-gray-900">{{ $asset->account_name }}</p>
                                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                                <span>
                                                    <i class="fas fa-user {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                                    {{ trans_choice('settings.linked_tiktok_accounts_count', $asset->tiktok_count, ['count' => $asset->tiktok_count]) }}
                                                </span>
                                                <span>
                                                    <i class="fas fa-ad {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                                                    {{ trans_choice('settings.linked_tiktok_ads_count', $asset->tiktok_ads_count, ['count' => $asset->tiktok_ads_count]) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ __('settings.connected') }}
                                        </span>
                                        <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }} text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-12 sm:px-6 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fab fa-tiktok text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('settings.no_tiktok_business_assets') }}</h3>
                <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                    {{ __('settings.create_first_tiktok_asset') }}
                </p>
                <button type="button"
                        @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                    <i class="fas fa-plus {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                    {{ __('settings.create_tiktok_business_asset') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }} {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
            {{ __('settings.back_to_tiktok_assets') }}
        </a>
    </div>

    {{-- Create Modal --}}
    <div x-show="showCreateModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div x-show="showCreateModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showCreateModal = false"></div>

            {{-- Modal panel --}}
            <div x-show="showCreateModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-{{ $isRtl ? 'right' : 'left' }} overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('orgs.settings.platform-connections.tiktok-assets.create', $currentOrg) }}" method="POST">
                    @csrf
                    <div class="sm:flex sm:items-start {{ $isRtl ? 'sm:flex-row-reverse' : '' }}">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-gray-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fab fa-tiktok text-white"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 {{ $isRtl ? 'sm:me-4 sm:text-right' : 'sm:ms-4 sm:text-left' }} flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                {{ __('settings.create_tiktok_business_asset') }}
                            </h3>
                            <div class="mt-4">
                                <label for="asset-name" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('settings.tiktok_business_asset_name') }}
                                </label>
                                <input type="text"
                                       name="name"
                                       id="asset-name"
                                       x-model="assetName"
                                       required
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm"
                                       placeholder="{{ __('settings.tiktok_business_asset_name_placeholder') }}">
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex {{ $isRtl ? 'sm:flex-row-reverse' : '' }} gap-3">
                        <button type="submit"
                                :disabled="!assetName.trim()"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('common.create') }}
                        </button>
                        <button type="button"
                                @click="showCreateModal = false; assetName = ''"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tiktokAssetsIndexPage() {
    return {
        showCreateModal: false,
        assetName: '',
    }
}
</script>
@endpush
