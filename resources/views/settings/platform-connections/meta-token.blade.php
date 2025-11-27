@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', ($connection ? __('Edit') : __('Add')) . ' ' . __('Meta Token') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
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
            <span class="text-gray-900 font-medium">{{ $connection ? __('Edit') : __('Add') }} {{ __('Meta Token') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fab fa-facebook text-blue-600 text-2xl"></i>
            </div>
            <div class="{{ $isRtl ? 'mr-4 text-right' : 'ml-4' }}">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $connection ? __('Edit Meta Connection') : __('Add Meta System User Token') }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('Connect your Meta Business account using a System User access token') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-3 text-right' : 'ml-3' }}">
                    <h3 class="text-sm font-medium text-red-800">{{ __('Please fix the following errors:') }}</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc {{ $isRtl ? 'list-inside mr-4' : 'list-inside' }}">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ $connection
            ? route('orgs.settings.platform-connections.meta.update', [$currentOrg, $connection->connection_id])
            : route('orgs.settings.platform-connections.meta.store', $currentOrg) }}"
          method="POST" class="space-y-6">
        @csrf
        @if($connection)
            @method('PUT')
        @endif

        {{-- Account Name --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('Connection Details') }}</h3>

                <div class="space-y-4">
                    <div>
                        <label for="account_name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('Connection Name') }} *
                        </label>
                        <input type="text" name="account_name" id="account_name"
                               value="{{ old('account_name', $connection->account_name ?? '') }}"
                               placeholder="{{ __('e.g., My Business Meta Account') }}"
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                        <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('A friendly name to identify this connection') }}</p>
                    </div>

                    @if($connection)
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm font-medium text-gray-700">{{ __('Current Status') }}</p>
                                    <p class="text-xs text-gray-500">
                                        @if($connection->status === 'active')
                                            <span class="text-green-600"><i class="fas fa-check-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('Active') }}</span>
                                        @elseif($connection->status === 'error')
                                            <span class="text-red-600"><i class="fas fa-exclamation-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ __('Error') }}</span>
                                        @else
                                            <span class="text-yellow-600"><i class="fas fa-clock {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>{{ ucfirst($connection->status) }}</span>
                                        @endif
                                        @if($connection->last_sync_at)
                                            &bull; {{ __('Last synced') }} {{ $connection->last_sync_at->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>
                                @if($connection->account_metadata['ad_accounts'] ?? null)
                                    <span class="text-sm text-gray-600">
                                        {{ count($connection->account_metadata['ad_accounts']) }} {{ __('ad account(s)') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Access Token --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('Access Token') }}
                    @if($connection)
                        <span class="text-sm font-normal text-gray-500">({{ __('leave empty to keep current token') }})</span>
                    @endif
                </h3>

                <div class="space-y-4">
                    <div>
                        <label for="access_token" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('System User Access Token') }} {{ $connection ? '' : '*' }}
                        </label>
                        <div class="mt-1 relative">
                            <textarea name="access_token" id="access_token" rows="4"
                                      placeholder="{{ __('Paste your Meta System User access token here...') }}"
                                      {{ $connection ? '' : 'required' }}
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono text-xs {{ $isRtl ? 'text-right' : '' }}">{{ old('access_token') }}</textarea>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">
                            <i class="fas fa-lock {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                            {{ __('Your token will be encrypted before storage') }}
                        </p>
                    </div>

                    @if($connection && $connection->token_expires_at)
                        <div class="p-3 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-yellow-800 {{ $isRtl ? 'text-right' : '' }}">
                                <i class="fas fa-clock {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                                {{ __('Current token expires') }} {{ $connection->token_expires_at->diffForHumans() }}
                                ({{ $connection->token_expires_at->format('M d, Y H:i') }})
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Required Permissions Info --}}
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                <i class="fas fa-key {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i> {{ __('Required Permissions') }}
            </h4>
            <p class="text-sm text-blue-800 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                {{ __('Make sure your System User token has the following permissions:') }}
            </p>
            <div class="flex flex-wrap gap-2 {{ $isRtl ? 'justify-end' : '' }}">
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">ads_management</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">ads_read</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">business_management</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">catalog_management</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">pages_read_engagement</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">pages_show_list</span>
            </div>
            <p class="mt-3 text-xs text-blue-700 {{ $isRtl ? 'text-right' : '' }}">
                {{ __('Optional for advanced features:') }}
                <span class="px-1 bg-blue-100 rounded">instagram_basic</span>,
                <span class="px-1 bg-blue-100 rounded">instagram_content_publish</span>,
                <span class="px-1 bg-blue-100 rounded">leads_retrieval</span>
            </p>
            <p class="mt-2 text-xs text-blue-600 {{ $isRtl ? 'text-right' : '' }}">
                <i class="fas fa-info-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                {{ __('Business ID is automatically detected from your token - no manual entry needed.') }}
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex {{ $isRtl ? 'justify-start flex-row-reverse' : 'justify-end' }} gap-3">
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                {{ __('Cancel') }}
            </a>
            <button type="submit"
                    class="inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                {{ $connection ? __('Update Connection') : __('Save & Validate Token') }}
            </button>
        </div>
    </form>

    {{-- Help Section --}}
    <div class="mt-8 border-t border-gray-200 pt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('How to Generate a System User Token') }}</h3>

        <div class="prose prose-sm text-gray-600 {{ $isRtl ? 'text-right' : '' }}">
            <ol class="space-y-3 {{ $isRtl ? 'list-decimal-rtl pr-4' : '' }}">
                <li>
                    <strong>{{ __('Go to Meta Business Settings') }}</strong>
                    <p class="text-gray-500">{{ __('Navigate to') }} <a href="https://business.facebook.com/settings" target="_blank" class="text-blue-600 hover:underline">business.facebook.com/settings</a></p>
                </li>
                <li>
                    <strong>{{ __('Create or Select a System User') }}</strong>
                    <p class="text-gray-500">{{ __('In the left menu, go to Users → System Users. Create a new one or select existing.') }}</p>
                </li>
                <li>
                    <strong>{{ __('Assign Assets') }}</strong>
                    <p class="text-gray-500">{{ __('Click "Add Assets" and assign your Ad Accounts with appropriate permissions (typically "Manage campaigns").') }}</p>
                </li>
                <li>
                    <strong>{{ __('Generate Token') }}</strong>
                    <p class="text-gray-500">{{ __('Click "Generate New Token", select your app, choose the required permissions, and set expiration to "Never" for long-lived tokens.') }}</p>
                </li>
                <li>
                    <strong>{{ __('Copy the Token') }}</strong>
                    <p class="text-gray-500">{{ __('Copy the generated token immediately (it won\'t be shown again) and paste it here.') }}</p>
                </li>
            </ol>
        </div>

        <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
            <p class="text-sm text-yellow-800 {{ $isRtl ? 'text-right' : '' }}">
                <i class="fas fa-exclamation-triangle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                <strong>{{ __('Security Note:') }}</strong> {{ __('Never share your access token with anyone. System User tokens provide full access to your ad accounts.') }}
            </p>
        </div>
    </div>
</div>
@endsection
