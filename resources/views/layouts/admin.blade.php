@extends('layouts.master')

@section('navigation')
    <!-- Dashboard -->
    <a href="{{ route('dashboard.index') }}"
       class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('dashboard.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-home w-5"></i>
        <span>{{ __('navigation.dashboard') }}</span>
    </a>

    <!-- Management Section -->
    <div class="pt-4 mt-4 border-t border-gray-200">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">{{ __('navigation.management') }}</p>

        <a href="{{ route('orgs.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('orgs.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-building w-5"></i>
            <span>{{ __('navigation.organizations') }}</span>
        </a>

        <a href="{{ route('campaigns.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('campaigns.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-bullhorn w-5"></i>
            <span>{{ __('navigation.campaigns') }}</span>
        </a>
    </div>

    <!-- Content Section -->
    <div class="pt-4 mt-4 border-t border-gray-200">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">{{ __('navigation.content') }}</p>

        <a href="{{ route('creative.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('creative.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-palette w-5"></i>
            <span>{{ __('navigation.creative') }}</span>
        </a>

        <a href="{{ route('social.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('social.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-share-alt w-5"></i>
            <span>{{ __('navigation.social_channels') }}</span>
        </a>
    </div>

    <!-- Analytics Section -->
    <div class="pt-4 mt-4 border-t border-gray-200">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">{{ __('navigation.analytics') }}</p>

        <a href="{{ route('analytics.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('analytics.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-chart-line w-5"></i>
            <span>{{ __('navigation.analytics') }}</span>
        </a>
    </div>

    <!-- AI Section -->
    <div class="pt-4 mt-4 border-t border-gray-200">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">{{ __('navigation.ai') }}</p>

        <a href="{{ route('ai.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('ai.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-robot w-5"></i>
            <span>{{ __('navigation.ai_tools') }}</span>
        </a>
    </div>

    <!-- Settings Section -->
    <div class="pt-4 mt-4 border-t border-gray-200">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">{{ __('navigation.settings') }}</p>

        <a href="{{ route('settings.integrations') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('settings.integrations') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-plug w-5"></i>
            <span>{{ __('navigation.integrations') }}</span>
        </a>

        <a href="{{ route('offerings.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('offerings.*') || request()->routeIs('products.*') || request()->routeIs('services.*') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-box w-5"></i>
            <span>{{ __('navigation.offerings') }}</span>
        </a>

        <a href="{{ route('settings.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth {{ request()->routeIs('settings.index') ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
            <i class="fas fa-cog w-5"></i>
            <span>{{ __('navigation.settings') }}</span>
        </a>
    </div>
@endsection
