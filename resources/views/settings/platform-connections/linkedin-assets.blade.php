@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('Select LinkedIn Assets') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="linkedinAssetsPage()" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
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
            <span class="text-gray-900 font-medium">{{ __('LinkedIn Assets') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('Configure LinkedIn Assets') }}</h1>
        <p class="mt-1 text-sm text-gray-500 {{ $isRtl ? 'text-right' : '' }}">
            {{ __('Select one of each: LinkedIn Account, Company Page, Ad Account, and Insight Tag for this organization.') }}
        </p>
        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700 {{ $isRtl ? 'text-right' : '' }}">
            <i class="fas fa-info-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
            {{ __('Each organization can have only one account per asset type.') }}
        </div>
    </div>

    {{-- Connection Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fab fa-linkedin text-blue-700 text-xl"></i>
            </div>
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <p class="font-medium text-blue-900">{{ $connection->account_name }}</p>
                <p class="text-sm text-blue-700">
                    {{ __('Connected') }} {{ $connection->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
    </div>

    {{-- Asset Selection Form --}}
    <form action="{{ route('orgs.settings.platform-connections.linkedin.assets.store', [$currentOrg, $connection->connection_id]) }}" method="POST">
        @csrf

        <div class="space-y-6">
            {{-- LinkedIn Profile/Account --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-blue-700"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ __('LinkedIn Profile') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($profiles ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                    </div>

                    @if(count($profiles ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($profiles as $profile)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-blue-500 bg-blue-50': selectedProfile === '{{ $profile['id'] }}' }">
                                    <input type="radio" name="profile" value="{{ $profile['id'] }}"
                                           {{ ($selectedAssets['profile'] ?? null) === $profile['id'] ? 'checked' : '' }}
                                           x-model="selectedProfile"
                                           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($profile['picture'] ?? null)
                                            <img src="{{ $profile['picture'] }}" alt="" class="w-8 h-8 rounded-full">
                                        @else
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600 text-sm"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $profile['name'] }}</span>
                                            @if($profile['headline'] ?? null)
                                                <span class="block text-xs text-gray-500">{{ Str::limit($profile['headline'], 50) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-user text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('Profile connected via OAuth') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- LinkedIn Company Pages --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-building text-blue-700"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Company Page') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($pages ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualPage = !showManualPage" class="text-sm text-blue-600 hover:text-blue-800 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-plus {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($pages ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($pages as $page)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-blue-500 bg-blue-50': selectedPage === '{{ $page['id'] }}' }">
                                    <input type="radio" name="page" value="{{ $page['id'] }}"
                                           {{ ($selectedAssets['page'] ?? null) === $page['id'] ? 'checked' : '' }}
                                           x-model="selectedPage"
                                           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <div class="ml-3 flex items-center gap-3">
                                        @if($page['logo'] ?? null)
                                            <img src="{{ $page['logo'] }}" alt="" class="w-8 h-8 rounded">
                                        @else
                                            <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                                                <i class="fas fa-building text-blue-600 text-sm"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $page['name'] }}</span>
                                            @if($page['follower_count'] ?? null)
                                                <span class="block text-xs text-gray-500">{{ number_format($page['follower_count']) }} followers</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-building text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Company Pages found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('You need admin access to a LinkedIn Company Page') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualPage" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Company Page ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_page_id" placeholder="e.g., 12345678"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <button type="button" @click="showManualPage = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LinkedIn Ad Account --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ad text-green-600"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Ad Account') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($adAccounts ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualAdAccount = !showManualAdAccount" class="text-sm text-green-600 hover:text-green-800 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-plus {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('Add manually') }}
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
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900">{{ $account['name'] }}</span>
                                            <span class="text-xs text-gray-400 ml-2">({{ $account['id'] }})</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($account['currency'] ?? null)
                                            <span class="text-xs text-gray-500">{{ $account['currency'] }}</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ ($account['status'] ?? '') === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
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
                            <p class="text-xs text-gray-400 mt-1">{{ __('Create an ad account in LinkedIn Campaign Manager') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualAdAccount" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Ad Account ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_ad_account_id" placeholder="e.g., 123456789"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <button type="button" @click="showManualAdAccount = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LinkedIn Insight Tag (Pixel) --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-code text-purple-600"></i>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Insight Tag (Pixel)') }}</h3>
                                <p class="text-sm text-gray-500">{{ count($insightTags ?? []) }} {{ __('available') }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showManualPixel = !showManualPixel" class="text-sm text-purple-600 hover:text-purple-800 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-plus {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('Add manually') }}
                        </button>
                    </div>

                    @if(count($insightTags ?? []) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($insightTags as $tag)
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                       :class="{ 'border-purple-500 bg-purple-50': selectedPixel === '{{ $tag['id'] }}' }">
                                    <input type="radio" name="pixel" value="{{ $tag['id'] }}"
                                           {{ ($selectedAssets['pixel'] ?? null) === $tag['id'] ? 'checked' : '' }}
                                           x-model="selectedPixel"
                                           class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $tag['name'] ?? 'Insight Tag' }}</span>
                                        <span class="text-xs text-gray-400 ml-1">({{ $tag['id'] }})</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <i class="fas fa-code text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">{{ __('No Insight Tags found') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Create an Insight Tag in LinkedIn Campaign Manager') }}</p>
                        </div>
                    @endif

                    <div x-show="showManualPixel" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter Insight Tag ID manually') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="manual_pixel_id" placeholder="e.g., 123456"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                            <button type="button" @click="showManualPixel = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
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
                            <span :class="{ 'text-green-600 font-medium': selectedProfile }">
                                <i class="fas" :class="selectedProfile ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Profile') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedPage }">
                                <i class="fas" :class="selectedPage ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Page') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedAdAccount }">
                                <i class="fas" :class="selectedAdAccount ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Ad Account') }}
                            </span>
                            <span class="mx-1">•</span>
                            <span :class="{ 'text-green-600 font-medium': selectedPixel }">
                                <i class="fas" :class="selectedPixel ? 'fa-check-circle' : 'fa-circle'"></i> {{ __('Insight Tag') }}
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
function linkedinAssetsPage() {
    return {
        showManualPage: false,
        showManualAdAccount: false,
        showManualPixel: false,

        selectedProfile: @json($selectedAssets['profile'] ?? null),
        selectedPage: @json($selectedAssets['page'] ?? null),
        selectedAdAccount: @json($selectedAssets['ad_account'] ?? null),
        selectedPixel: @json($selectedAssets['pixel'] ?? null),
    }
}
</script>
@endpush
@endsection
