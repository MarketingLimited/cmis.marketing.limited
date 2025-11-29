{{-- Shared layout for platform settings pages --}}
@extends('layouts.admin')

@section('title', __('settings.platform_settings_' . $platform))

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                {{ __('settings.title') }}
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.platforms.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                {{ __('settings.platform_settings') }}
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('settings.platform_' . $platform) }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    @yield('platform-icon')
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">@yield('platform-title')</h1>
                    <p class="text-sm text-gray-500">@yield('platform-subtitle')</p>
                </div>
            </div>
            <a href="{{ route('orgs.settings.platforms.index', $currentOrg) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 transition">
                <i class="fas fa-arrow-{{ app()->isLocale('ar') ? 'right' : 'left' }} me-2"></i>
                {{ __('common.back') }}
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    {{-- Connection Status --}}
    @if($connections->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
            <div>
                <h3 class="font-medium text-yellow-800">{{ __('settings.no_connections') }}</h3>
                <p class="text-sm text-yellow-700 mt-1">{{ __('settings.connect_first_to_configure') }}</p>
                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                   class="inline-flex items-center mt-3 text-sm font-medium text-yellow-700 hover:text-yellow-900">
                    {{ __('settings.connect_now') }} <i class="fas fa-arrow-{{ app()->isLocale('ar') ? 'left' : 'right' }} ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    @else
    {{-- Platform-specific settings form --}}
    <form action="{{ route('orgs.settings.platforms.' . $platform . '.update', $currentOrg) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- General Settings --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('settings.general_settings') }}</h2>

            <div class="grid md:grid-cols-2 gap-6">
                {{-- Attribution Window --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('settings.attribution_window') }}
                    </label>
                    <select name="attribution_window" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1_day_click" @selected(($settings["platform_{$platform}_attribution_window"] ?? '') === '1_day_click')>1 Day Click</option>
                        <option value="7_day_click" @selected(($settings["platform_{$platform}_attribution_window"] ?? '7_day_click') === '7_day_click')>7 Day Click (Default)</option>
                        <option value="28_day_click" @selected(($settings["platform_{$platform}_attribution_window"] ?? '') === '28_day_click')>28 Day Click</option>
                        <option value="7_day_view" @selected(($settings["platform_{$platform}_attribution_window"] ?? '') === '7_day_view')>7 Day View</option>
                    </select>
                </div>

                {{-- Sync Frequency --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('settings.sync_frequency') }}
                    </label>
                    <select name="sync_frequency" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="realtime" @selected(($settings["platform_{$platform}_sync_frequency"] ?? '') === 'realtime')>{{ __('settings.realtime') }}</option>
                        <option value="hourly" @selected(($settings["platform_{$platform}_sync_frequency"] ?? 'hourly') === 'hourly')>{{ __('settings.hourly') }}</option>
                        <option value="daily" @selected(($settings["platform_{$platform}_sync_frequency"] ?? '') === 'daily')>{{ __('settings.daily') }}</option>
                    </select>
                </div>

                {{-- Default Currency --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('settings.default_currency') }}
                    </label>
                    <select name="default_currency" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="USD" @selected(($settings["platform_{$platform}_default_currency"] ?? 'USD') === 'USD')>USD - US Dollar</option>
                        <option value="EUR" @selected(($settings["platform_{$platform}_default_currency"] ?? '') === 'EUR')>EUR - Euro</option>
                        <option value="GBP" @selected(($settings["platform_{$platform}_default_currency"] ?? '') === 'GBP')>GBP - British Pound</option>
                        <option value="SAR" @selected(($settings["platform_{$platform}_default_currency"] ?? '') === 'SAR')>SAR - Saudi Riyal</option>
                        <option value="AED" @selected(($settings["platform_{$platform}_default_currency"] ?? '') === 'AED')>AED - UAE Dirham</option>
                        <option value="BHD" @selected(($settings["platform_{$platform}_default_currency"] ?? '') === 'BHD')>BHD - Bahraini Dinar</option>
                    </select>
                </div>

                {{-- Auto Sync Toggle --}}
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="auto_sync" value="1"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               @checked($settings["platform_{$platform}_auto_sync"] ?? true)>
                        <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_auto_sync') }}</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ms-6">{{ __('settings.auto_sync_description') }}</p>
                </div>
            </div>
        </div>

        {{-- Platform-specific settings --}}
        @yield('platform-specific-settings')

        {{-- Connected Accounts --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('settings.connected_accounts') }}</h2>

            <div class="space-y-3">
                @foreach($connections as $connection)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm">
                            @yield('platform-icon-small')
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $connection->account_name ?? $connection->name ?? 'Account' }}</p>
                            <p class="text-xs text-gray-500">{{ $connection->account_id ?? $connection->external_id }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full
                        @if($connection->is_active) bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ $connection->is_active ? __('settings.active') : __('settings.inactive') }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('orgs.settings.platforms.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                {{ __('common.save_changes') }}
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
