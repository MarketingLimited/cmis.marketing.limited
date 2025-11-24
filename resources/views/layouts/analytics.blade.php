@extends('layouts.master')

@push('chart-scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.js"></script>
@endpush

@section('navigation')
    <!-- Analytics Dashboard -->
    <a href="{{ route('analytics.enterprise') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.enterprise') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-th-large w-5"></i>
        <span>{{ __('navigation.enterprise') }}</span>
    </a>

    <a href="{{ route('analytics.realtime') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.realtime') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-bolt w-5"></i>
        <span>{{ __('navigation.realtime') }}</span>
    </a>

    <a href="{{ route('analytics.campaigns') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.campaigns') || request()->routeIs('analytics.campaign') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-bullhorn w-5"></i>
        <span>{{ __('navigation.campaigns') }}</span>
    </a>

    <a href="{{ route('analytics.kpis') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.kpis') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-tachometer-alt w-5"></i>
        <span>{{ __('navigation.kpis') }}</span>
    </a>

    <a href="{{ route('analytics.reports.view') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.reports.view') || request()->routeIs('analytics.reports-detail') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-file-alt w-5"></i>
        <span>{{ __('navigation.reports') }}</span>
    </a>

    <a href="{{ route('analytics.insights.view') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.insights.view') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-lightbulb w-5"></i>
        <span>{{ __('navigation.insights') }}</span>
    </a>

    <a href="{{ route('analytics.metrics.view') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.metrics.view') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-chart-bar w-5"></i>
        <span>{{ __('navigation.metrics') }}</span>
    </a>

    <a href="{{ route('analytics.export.view') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.export.view') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-download w-5"></i>
        <span>{{ __('navigation.export') }}</span>
    </a>

    <!-- Main App -->
    <div class="pt-4 mt-4 border-t border-gray-200">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">{{ __('navigation.main_app') }}</p>

        <a href="{{ route('dashboard.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth text-gray-700 hover:bg-gray-100">
            <i class="fas fa-home w-5"></i>
            <span>{{ __('navigation.dashboard') }}</span>
        </a>

        <a href="{{ route('campaigns.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth text-gray-700 hover:bg-gray-100">
            <i class="fas fa-folder w-5"></i>
            <span>{{ __('navigation.campaigns') }}</span>
        </a>

        <a href="{{ route('settings.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth text-gray-700 hover:bg-gray-100">
            <i class="fas fa-cog w-5"></i>
            <span>{{ __('navigation.settings') }}</span>
        </a>
    </div>
@endsection

@push('header-actions')
    <!-- Refresh Button -->
    <button onclick="location.reload()"
            class="hidden lg:flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
        <i class="fas fa-sync-alt"></i>
        <span>{{ __('common.refresh') }}</span>
    </button>
@endpush

@push('styles')
    <style>
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        @media (max-width: 640px) {
            .chart-container {
                height: 250px;
            }
        }

        /* Loading Spinner */
        .spinner {
            border: 3px solid rgba(79, 70, 229, 0.3);
            border-radius: 50%;
            border-top: 3px solid rgb(79, 70, 229);
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        // Import Phase 8 components if available
        try {
            const { realtimeDashboard, campaignAnalytics, kpiDashboard, notificationCenter } = await import('/resources/js/components/index.js');

            // Register with Alpine
            if (window.Alpine) {
                window.Alpine.data('realtimeDashboard', realtimeDashboard);
                window.Alpine.data('campaignAnalytics', campaignAnalytics);
                window.Alpine.data('kpiDashboard', kpiDashboard);
                window.Alpine.data('notificationCenter', notificationCenter);
            }
        } catch (error) {
            console.log('Analytics components not yet available:', error.message);
        }

        // Store auth token for API calls
        @auth
        localStorage.setItem('auth_token', '{{ Auth::user()->currentAccessToken()?->plainTextToken ?? session('api_token') ?? '' }}');
        localStorage.setItem('user_id', '{{ Auth::user()->user_id }}');
        @endauth
    </script>
@endpush
