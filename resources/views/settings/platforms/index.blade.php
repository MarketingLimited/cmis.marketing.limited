@extends('layouts.admin')

@section('title', __('settings.platform_settings'))

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
            <span class="text-gray-900 font-medium">{{ __('settings.platform_settings') }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.platform_settings') }}</h1>
                <p class="mt-1 text-gray-600">{{ __('settings.platform_settings_description') }}</p>
            </div>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-plug me-2"></i>
                {{ __('settings.manage_connections') }}
            </a>
        </div>
    </div>

    {{-- Platform Cards --}}
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($platforms as $key => $platform)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center
                        @if($platform['connected']) bg-blue-100 @else bg-gray-100 @endif">
                        <i class="{{ $platform['icon'] }} text-2xl
                            @if($platform['connected']) text-blue-600 @else text-gray-400 @endif"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">{{ $platform['name'] }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            @if($platform['connected'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle me-1"></i>
                                    {{ __('settings.connected') }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $platform['connections_count'] }} {{ __('settings.accounts') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ __('settings.not_connected') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($platform['connected'])
                    <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('orgs.settings.platforms.' . $key, $currentOrg) }}"
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-cog me-2"></i>
                            {{ __('settings.configure') }}
                        </a>
                        @if($platform['configured'])
                            <span class="text-green-500" title="{{ __('settings.configured') }}">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        @else
                            <span class="text-yellow-500" title="{{ __('settings.not_configured') }}">
                                <i class="fas fa-exclamation-circle"></i>
                            </span>
                        @endif
                    </div>
                @else
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                           class="w-full inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition">
                            <i class="fas fa-link me-2"></i>
                            {{ __('settings.connect_platform') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Help Section --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="font-medium text-blue-900 mb-2">{{ __('settings.platform_help_title') }}</h3>
        <p class="text-sm text-blue-700 mb-4">{{ __('settings.platform_help_description') }}</p>
        <div class="flex gap-3">
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
               class="text-sm font-medium text-blue-600 hover:text-blue-800">
                {{ __('settings.view_connections') }} <i class="fas fa-arrow-{{ app()->isLocale('ar') ? 'left' : 'right' }} ms-1"></i>
            </a>
        </div>
    </div>
</div>
@endsection
