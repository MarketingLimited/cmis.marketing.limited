@extends('layouts.admin')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', ($connection ? __('Edit') : __('Add')) . ' ' . __('Google Connection') . ' - ' . __('Settings'))

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
            <span class="text-gray-900 font-medium">{{ $connection ? __('Edit') : __('Add') }} {{ __('Google Connection') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0 w-12 h-12 bg-white rounded-lg shadow flex items-center justify-center">
                <svg class="w-8 h-8" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            </div>
            <div class="{{ $isRtl ? 'mr-4 text-right' : 'ml-4' }}">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $connection ? __('Edit Google Connection') : __('Add Google Service Account') }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('Connect your Google account using a Service Account or OAuth credentials') }}
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

    @if(!$connection)
    {{-- Quick Connect with Google OAuth --}}
    @php
        $googleConfig = config('social-platforms.google');
        $hasGoogleCredentials = !empty($googleConfig['client_id']) && !empty($googleConfig['client_secret']);
    @endphp

    <div class="bg-white shadow sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="{{ $isRtl ? 'text-right' : '' }}">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fab fa-google text-blue-500 {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('Quick Connect with Google') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ __('Connect instantly using your Google account. This grants access to YouTube, Analytics, Ads, and more.') }}
                    </p>
                </div>
                @if($hasGoogleCredentials)
                    <a href="{{ route('orgs.settings.platform-connections.google.authorize', $currentOrg) }}"
                       class="inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <svg class="w-5 h-5 {{ $isRtl ? 'ml-2' : 'mr-2' }}" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        {{ __('Connect with Google') }}
                    </a>
                @else
                    <div class="{{ $isRtl ? 'text-left' : 'text-right' }}">
                        <span class="inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }} px-4 py-2 text-sm text-gray-500 bg-gray-100 rounded-lg">
                            <i class="fas fa-info-circle {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>{{ __('Google API not configured') }}
                        </span>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Add GOOGLE_CLIENT_ID to .env') }}</p>
                    </div>
                @endif
            </div>

            @if($hasGoogleCredentials)
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs text-blue-700 {{ $isRtl ? 'text-right' : '' }}">
                    <i class="fas fa-shield-alt {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                    <strong>{{ __('Recommended:') }}</strong> {{ __('OAuth connection is more secure and doesn\'t require manual credential management.') }}
                </p>
            </div>
            @endif
        </div>
    </div>

    <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-3 bg-gray-100 text-gray-500">{{ __('Or add credentials manually') }}</span>
        </div>
    </div>
    @endif

    {{-- Form --}}
    <form action="{{ $connection
            ? route('orgs.settings.platform-connections.google.update', [$currentOrg, $connection->connection_id])
            : route('orgs.settings.platform-connections.google.store', $currentOrg) }}"
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
                               placeholder="{{ __('e.g., My Business Google Account') }}"
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
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Credentials Type Selection --}}
        <div class="bg-white shadow sm:rounded-lg" x-data="{ credentialType: '{{ old('credential_type', 'service_account') }}' }">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('Authentication Method') }}</h3>

                <div class="space-y-4">
                    {{-- Credential Type Selection --}}
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer rounded-lg border p-4 {{ $isRtl ? 'text-right' : '' }}"
                               :class="{ 'border-blue-500 bg-blue-50 ring-2 ring-blue-500': credentialType === 'service_account', 'border-gray-300': credentialType !== 'service_account' }">
                            <input type="radio" name="credential_type" value="service_account" x-model="credentialType" class="sr-only">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">{{ __('Service Account') }}</span>
                                <span class="text-xs text-gray-500 mt-1">{{ __('For server-to-server apps (recommended)') }}</span>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer rounded-lg border p-4 {{ $isRtl ? 'text-right' : '' }}"
                               :class="{ 'border-blue-500 bg-blue-50 ring-2 ring-blue-500': credentialType === 'oauth', 'border-gray-300': credentialType !== 'oauth' }">
                            <input type="radio" name="credential_type" value="oauth" x-model="credentialType" class="sr-only">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">{{ __('OAuth 2.0') }}</span>
                                <span class="text-xs text-gray-500 mt-1">{{ __('For user-authorized access') }}</span>
                            </div>
                        </label>
                    </div>

                    {{-- Service Account Credentials --}}
                    <div x-show="credentialType === 'service_account'" x-cloak class="space-y-4 mt-4">
                        <div>
                            <label for="service_account_json" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                                {{ __('Service Account JSON') }} {{ $connection ? '' : '*' }}
                            </label>
                            <div class="mt-1">
                                <textarea name="service_account_json" id="service_account_json" rows="6"
                                          placeholder="{{ __('Paste your service account JSON key here...') }}"
                                          {{ $connection ? '' : 'required' }}
                                          class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono text-xs {{ $isRtl ? 'text-right' : '' }}">{{ old('service_account_json') }}</textarea>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">
                                <i class="fas fa-lock {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                                {{ __('Your credentials will be encrypted before storage') }}
                            </p>
                        </div>

                        @if($connection)
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-800 {{ $isRtl ? 'text-right' : '' }}">
                                    <i class="fas fa-info-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                                    {{ __('Leave empty to keep current credentials') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- OAuth Credentials --}}
                    <div x-show="credentialType === 'oauth'" x-cloak class="space-y-4 mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="client_id" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                                    {{ __('Client ID') }} {{ $connection ? '' : '*' }}
                                </label>
                                <input type="text" name="client_id" id="client_id"
                                       value="{{ old('client_id') }}"
                                       placeholder="{{ __('Your OAuth Client ID') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            </div>
                            <div>
                                <label for="client_secret" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                                    {{ __('Client Secret') }} {{ $connection ? '' : '*' }}
                                </label>
                                <input type="password" name="client_secret" id="client_secret"
                                       placeholder="{{ __('Your OAuth Client Secret') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            </div>
                        </div>

                        <div>
                            <label for="refresh_token" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                                {{ __('Refresh Token') }}
                            </label>
                            <input type="text" name="refresh_token" id="refresh_token"
                                   value="{{ old('refresh_token') }}"
                                   placeholder="{{ __('OAuth Refresh Token (if available)') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono text-xs {{ $isRtl ? 'text-right' : '' }}">
                            <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('Optional: Provide if you already have a refresh token') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Required Scopes Info --}}
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                <i class="fas fa-key {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i> {{ __('Required API Scopes') }}
            </h4>
            <p class="text-sm text-blue-800 mb-3 {{ $isRtl ? 'text-right' : '' }}">
                {{ __('Make sure your Google credentials have access to the following APIs:') }}
            </p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fab fa-youtube text-red-500"></i>
                    <span>{{ __('YouTube Data API') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-ad text-green-500"></i>
                    <span>{{ __('Google Ads API') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-chart-line text-orange-500"></i>
                    <span>{{ __('Google Analytics') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-code text-purple-500"></i>
                    <span>{{ __('Tag Manager API') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-store text-blue-500"></i>
                    <span>{{ __('Google Business Profile') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-shopping-cart text-teal-500"></i>
                    <span>{{ __('Merchant Center') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-search text-indigo-500"></i>
                    <span>{{ __('Search Console') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-calendar text-cyan-500"></i>
                    <span>{{ __('Calendar API') }}</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-blue-100 rounded text-xs text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-folder text-yellow-600"></i>
                    <span>{{ __('Drive API') }}</span>
                </div>
            </div>
            <p class="mt-3 text-xs text-blue-600 {{ $isRtl ? 'text-right' : '' }}">
                <i class="fas fa-info-circle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                {{ __('Not all APIs are required. Enable only those you plan to use.') }}
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
                {{ $connection ? __('Update Connection') : __('Save & Validate') }}
            </button>
        </div>
    </form>

    {{-- Help Section --}}
    <div class="mt-8 border-t border-gray-200 pt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('How to Create Google Credentials') }}</h3>

        <div class="prose prose-sm text-gray-600 {{ $isRtl ? 'text-right' : '' }}">
            <h4 class="text-md font-medium text-gray-800">{{ __('Service Account (Recommended)') }}</h4>
            <ol class="space-y-2 {{ $isRtl ? 'list-decimal-rtl pr-4' : '' }}">
                <li>
                    <strong>{{ __('Go to Google Cloud Console') }}</strong>
                    <p class="text-gray-500">{{ __('Navigate to') }} <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" class="text-blue-600 hover:underline">{{ __('Service Accounts') }}</a></p>
                </li>
                <li>
                    <strong>{{ __('Create a Service Account') }}</strong>
                    <p class="text-gray-500">{{ __('Click "Create Service Account" and follow the wizard') }}</p>
                </li>
                <li>
                    <strong>{{ __('Enable Required APIs') }}</strong>
                    <p class="text-gray-500">{{ __('Go to "APIs & Services" → "Enable APIs" and enable the APIs you need') }}</p>
                </li>
                <li>
                    <strong>{{ __('Create JSON Key') }}</strong>
                    <p class="text-gray-500">{{ __('Click on the service account, go to "Keys" tab, and create a JSON key') }}</p>
                </li>
                <li>
                    <strong>{{ __('Grant Permissions') }}</strong>
                    <p class="text-gray-500">{{ __('Share access to your assets (Analytics, Ads, etc.) with the service account email') }}</p>
                </li>
            </ol>
        </div>

        <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
            <p class="text-sm text-yellow-800 {{ $isRtl ? 'text-right' : '' }}">
                <i class="fas fa-exclamation-triangle {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                <strong>{{ __('Security Note:') }}</strong> {{ __('Keep your credentials private. Service account keys provide full access to your Google assets.') }}
            </p>
        </div>
    </div>
</div>
@endsection
