@extends('layouts.admin')

@section('title', __('Add Ad Account') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.ad-accounts.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Ad Accounts') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Add Account') }}</span>
        </nav>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('settings.ad_accounts.add_ad_account') }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('settings.ad_accounts.add_account_description') }}
            </p>
        </div>
    </div>

    {{-- Platform Selection --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-plug text-green-500 me-2"></i>{{ __('settings.select_platform') }}
        </h3>
        <p class="text-sm text-gray-500 mb-6">
            {{ __('settings.select_platform_description') }}
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Meta (Facebook/Instagram) --}}
            <a href="{{ route('orgs.settings.platform-connections.meta.authorize', $currentOrg) }}?type=ad_account"
               class="group p-6 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition text-center">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-200 transition">
                    <i class="fab fa-facebook text-blue-600 text-3xl"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-900 mb-1">Meta Ads</h4>
                <p class="text-xs text-gray-500">Facebook & Instagram advertising</p>
            </a>

            {{-- Google Ads --}}
            <a href="{{ route('orgs.settings.platform-connections.google.authorize', $currentOrg) }}?type=ad_account"
               class="group p-6 border border-gray-200 rounded-lg hover:border-red-500 hover:bg-red-50 transition text-center">
                <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-red-200 transition">
                    <i class="fab fa-google text-red-500 text-3xl"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-900 mb-1">Google Ads</h4>
                <p class="text-xs text-gray-500">Search, Display & YouTube ads</p>
            </a>

            {{-- TikTok Ads --}}
            <a href="{{ route('orgs.settings.platform-connections.tiktok.authorize', $currentOrg) }}?type=ad_account"
               class="group p-6 border border-gray-200 rounded-lg hover:border-gray-800 hover:bg-gray-50 transition text-center">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-gray-200 transition">
                    <i class="fab fa-tiktok text-gray-900 text-3xl"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-900 mb-1">TikTok Ads</h4>
                <p class="text-xs text-gray-500">TikTok For Business advertising</p>
            </a>

            {{-- LinkedIn Ads --}}
            <a href="{{ route('orgs.settings.platform-connections.linkedin.authorize', $currentOrg) }}?type=ad_account"
               class="group p-6 border border-gray-200 rounded-lg hover:border-blue-700 hover:bg-blue-50 transition text-center">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-200 transition">
                    <i class="fab fa-linkedin text-blue-700 text-3xl"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-900 mb-1">LinkedIn Ads</h4>
                <p class="text-xs text-gray-500">B2B professional advertising</p>
            </a>

            {{-- Twitter/X Ads --}}
            <a href="{{ route('orgs.settings.platform-connections.twitter.authorize', $currentOrg) }}?type=ad_account"
               class="group p-6 border border-gray-200 rounded-lg hover:border-sky-500 hover:bg-sky-50 transition text-center">
                <div class="w-16 h-16 mx-auto bg-sky-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-sky-200 transition">
                    <i class="fab fa-twitter text-sky-500 text-3xl"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-900 mb-1">X Ads</h4>
                <p class="text-xs text-gray-500">Twitter/X promoted content</p>
            </a>

            {{-- Snapchat Ads --}}
            <a href="{{ route('orgs.settings.platform-connections.snapchat.authorize', $currentOrg) }}?type=ad_account"
               class="group p-6 border border-gray-200 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition text-center">
                <div class="w-16 h-16 mx-auto bg-yellow-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-yellow-200 transition">
                    <i class="fab fa-snapchat text-yellow-500 text-3xl"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-900 mb-1">Snapchat Ads</h4>
                <p class="text-xs text-gray-500">Snap Ads & AR Lenses</p>
            </a>
        </div>
    </div>

    {{-- Already Connected Accounts --}}
    @if(($existingConnections ?? collect())->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-link text-gray-500 mr-2"></i>Import from Connected Platforms
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                You can also import ad accounts from your existing platform connections.
            </p>

            <div class="space-y-3">
                @foreach($existingConnections as $connection)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            @php
                                $platformIcons = [
                                    'meta' => 'fab fa-facebook text-blue-600',
                                    'google' => 'fab fa-google text-red-500',
                                    'tiktok' => 'fab fa-tiktok text-gray-900',
                                    'linkedin' => 'fab fa-linkedin text-blue-700',
                                    'twitter' => 'fab fa-twitter text-sky-500',
                                    'snapchat' => 'fab fa-snapchat text-yellow-500',
                                ];
                            @endphp
                            <i class="{{ $platformIcons[$connection->platform] ?? 'fas fa-plug text-gray-500' }} text-xl"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $connection->name ?? ucfirst($connection->platform) }}</p>
                                <p class="text-xs text-gray-500">Connected {{ $connection->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <form action="{{ route('orgs.settings.ad-accounts.import', [$currentOrg, $connection->connection_id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-download mr-1"></i>Import Ad Accounts
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Help Section --}}
    <div class="bg-blue-50 rounded-lg border border-blue-200 p-6">
        <h3 class="text-base font-semibold text-blue-900 mb-2">
            <i class="fas fa-question-circle mr-2"></i>Need Help?
        </h3>
        <p class="text-sm text-blue-800 mb-4">
            Connecting an ad account allows you to:
        </p>
        <ul class="text-sm text-blue-800 space-y-2">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-600 mt-0.5"></i>
                <span>Boost organic posts directly from CMIS</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-600 mt-0.5"></i>
                <span>Set up automatic boost rules based on engagement</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-600 mt-0.5"></i>
                <span>Track ad spend and performance in one dashboard</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-600 mt-0.5"></i>
                <span>Set budget limits to control spending</span>
            </li>
        </ul>
    </div>
</div>
@endsection
