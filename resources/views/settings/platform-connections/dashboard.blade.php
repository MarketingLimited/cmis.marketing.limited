@extends('layouts.admin')

@section('title', __('wizard.dashboard.title') . ' - ' . __('settings.settings'))

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="space-y-6" x-data="platformDashboard()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('settings.settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('wizard.dashboard.title') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('wizard.dashboard.title') }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('wizard.dashboard.subtitle') }}
            </p>
        </div>
        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-cog {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
            {{ __('wizard.dashboard.view_all') }}
        </a>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-check-circle text-green-400 {{ $isRtl ? 'ms-3' : 'me-3' }}"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-exclamation-circle text-red-400 {{ $isRtl ? 'ms-3' : 'me-3' }}"></i>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        {{-- Connected Platforms --}}
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plug text-blue-600"></i>
                </div>
                <div class="{{ $isRtl ? 'me-4' : 'ms-4' }}">
                    <p class="text-sm font-medium text-gray-500">{{ __('wizard.dashboard.summary.platforms_connected') }}</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ $summary['platforms_connected'] }}/{{ $summary['total_platforms'] }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Total Assets --}}
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-layer-group text-green-600"></i>
                </div>
                <div class="{{ $isRtl ? 'me-4' : 'ms-4' }}">
                    <p class="text-sm font-medium text-gray-500">{{ __('wizard.dashboard.summary.total_assets') }}</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $summary['total_assets'] }}</p>
                </div>
            </div>
        </div>

        {{-- Health Status --}}
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="flex items-center">
                @php
                    $healthColor = match($summary['health_status']) {
                        'healthy' => 'green',
                        'warning' => 'yellow',
                        'error' => 'red',
                        default => 'gray',
                    };
                    $healthIcon = match($summary['health_status']) {
                        'healthy' => 'fa-check-circle',
                        'warning' => 'fa-exclamation-triangle',
                        'error' => 'fa-times-circle',
                        default => 'fa-question-circle',
                    };
                    $healthText = match($summary['health_status']) {
                        'healthy' => __('wizard.dashboard.summary.healthy'),
                        'warning' => __('wizard.dashboard.summary.warning'),
                        'error' => __('wizard.dashboard.summary.error'),
                        default => __('wizard.dashboard.summary.healthy'),
                    };
                @endphp
                <div class="flex-shrink-0 w-10 h-10 bg-{{ $healthColor }}-100 rounded-lg flex items-center justify-center">
                    <i class="fas {{ $healthIcon }} text-{{ $healthColor }}-600"></i>
                </div>
                <div class="{{ $isRtl ? 'me-4' : 'ms-4' }}">
                    <p class="text-sm font-medium text-gray-500">{{ __('wizard.dashboard.summary.health_status') }}</p>
                    <p class="text-lg font-semibold text-{{ $healthColor }}-600">{{ $healthText }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Platform Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($platforms as $key => $platform)
            <a href="{{ route('orgs.settings.platform-connections.wizard.start', [$currentOrg, $key]) }}"
               class="group relative bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 p-4 sm:p-6 text-center border-2 {{ $platform['connected'] ? 'border-green-200 hover:border-green-300' : 'border-gray-100 hover:border-blue-200' }}">

                {{-- Status Badge --}}
                @if($platform['connected'])
                    <div class="absolute top-2 {{ $isRtl ? 'start-2' : 'end-2' }}">
                        @if($platform['status'] === 'active')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle {{ $isRtl ? 'ms-1' : 'me-1' }} text-xs"></i>
                                {{ __('wizard.dashboard.status.active') }}
                            </span>
                        @elseif($platform['status'] === 'warning')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-exclamation-triangle {{ $isRtl ? 'ms-1' : 'me-1' }} text-xs"></i>
                                {{ __('wizard.dashboard.status.warning') }}
                            </span>
                        @elseif($platform['status'] === 'error')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle {{ $isRtl ? 'ms-1' : 'me-1' }} text-xs"></i>
                                {{ __('wizard.dashboard.status.error') }}
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Platform Icon --}}
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto rounded-xl flex items-center justify-center mb-3 transition-transform group-hover:scale-110"
                     style="background-color: {{ $platform['color'] }}20;">
                    <i class="{{ $platform['icon'] }} text-2xl sm:text-3xl" style="color: {{ $platform['color'] }};"></i>
                </div>

                {{-- Platform Name --}}
                <h3 class="text-sm sm:text-base font-semibold text-gray-900 mb-1">
                    {{ $platform['display_name'] }}
                </h3>

                {{-- Connection Info --}}
                @if($platform['connected'])
                    <p class="text-xs text-gray-500">
                        {{ trans_choice('wizard.dashboard.assets_count', $platform['assets_count'], ['count' => $platform['assets_count']]) }}
                    </p>
                    <span class="mt-2 inline-flex items-center text-xs font-medium text-blue-600 group-hover:text-blue-700">
                        {{ __('wizard.dashboard.manage') }}
                        <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }} {{ $isRtl ? 'me-1' : 'ms-1' }} text-xs"></i>
                    </span>
                @else
                    <p class="text-xs text-gray-400 mb-2">{{ __('wizard.dashboard.not_connected') }}</p>
                    <span class="inline-flex items-center text-xs font-medium text-blue-600 group-hover:text-blue-700">
                        <i class="fas fa-plus-circle {{ $isRtl ? 'ms-1' : 'me-1' }}"></i>
                        {{ __('wizard.dashboard.connect_now') }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
function platformDashboard() {
    return {
        // Dashboard state
    }
}
</script>
@endpush
