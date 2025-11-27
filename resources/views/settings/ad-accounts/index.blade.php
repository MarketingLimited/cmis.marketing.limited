@extends('layouts.admin')

@section('title', __('Ad Accounts') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Ad Accounts') }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Ad Accounts</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Manage connected advertising accounts for boosting and paid campaigns.
            </p>
        </div>
        <a href="{{ route('orgs.settings.ad-accounts.create', $currentOrg) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
            <i class="fas fa-plus mr-2"></i>Add Account
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex"><i class="fas fa-check-circle text-green-400 mr-3"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($adAccounts->count() > 0)
        @foreach($accountsByPlatform as $platform => $accounts)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center">
                        @php
                            $platformIcons = [
                                'meta' => 'fab fa-facebook text-blue-600',
                                'google' => 'fab fa-google text-red-500',
                                'tiktok' => 'fab fa-tiktok text-gray-900',
                                'linkedin' => 'fab fa-linkedin text-blue-700',
                                'twitter' => 'fab fa-twitter text-sky-500',
                                'snapchat' => 'fab fa-snapchat text-yellow-500',
                                'pinterest' => 'fab fa-pinterest text-red-600',
                            ];
                        @endphp
                        <i class="{{ $platformIcons[$platform] ?? 'fas fa-ad text-gray-500' }} text-xl mr-3"></i>
                        <h3 class="text-base font-medium text-gray-900">{{ ucfirst($platform) }} Ad Accounts</h3>
                        <span class="ml-2 px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded-full">{{ $accounts->count() }}</span>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($accounts as $account)
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <i class="fas fa-ad text-gray-500"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $account->account_name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $account->platform_account_id }}
                                        @if($account->profileGroup)
                                            &bull; {{ $account->profileGroup->name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right text-xs">
                                    <div class="text-gray-500">{{ $account->currency }} &bull; {{ $account->timezone }}</div>
                                    @if($account->monthly_budget_limit)
                                        <div class="text-gray-400">Limit: {{ number_format($account->monthly_budget_limit, 2) }}/mo</div>
                                    @endif
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('orgs.settings.ad-accounts.show', [$currentOrg, $account->id]) }}" class="p-2 text-gray-400 hover:text-blue-600"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('orgs.settings.ad-accounts.edit', [$currentOrg, $account->id]) }}" class="p-2 text-gray-400 hover:text-blue-600"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('orgs.settings.ad-accounts.destroy', [$currentOrg, $account->id]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this ad account?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-ad text-green-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Ad Accounts Yet</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                Connect ad accounts to enable post boosting and paid campaign management.
            </p>
            <a href="{{ route('orgs.settings.ad-accounts.create', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Add First Account
            </a>
        </div>
    @endif
</div>
@endsection
