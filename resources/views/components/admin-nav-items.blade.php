{{--
    Admin Navigation Items Component

    Renders dynamic navigation based on enabled marketplace apps.
    Designed to work within the existing admin layout's sidebar structure.

    Props:
    - $currentOrg: The current organization ID
    - $isRtl: Whether the layout is RTL (for Arabic)
--}}

@php
    $navigationService = app(\App\Services\Navigation\NavigationService::class);
    $enabledSlugs = collect($navigationService->getSidebarItems($currentOrg))->pluck('slug')->toArray();

    // Helper function to check if app is enabled
    $isEnabled = fn($slug) => in_array($slug, $enabledSlugs);
@endphp

<!-- Dashboard (Always visible - Core) -->
<a href="{{ route('orgs.dashboard.index', ['org' => $currentOrg]) }}"
   :title="compactMode ? '{{ __('navigation.dashboard') }}' : ''"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.dashboard.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.dashboard.*') ? 'bg-gradient-to-br from-blue-500 to-purple-600 text-white shadow-lg shadow-blue-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-home text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.dashboard') }}</span>
</a>

<!-- Favorites Section (preserved from original) -->
<template x-if="!compactMode && favorites.length > 0">
    <div class="mt-3 mb-2">
        <div class="flex items-center justify-between px-3">
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.favorites') }}</p>
            <i class="fas fa-star text-yellow-400 text-xs"></i>
        </div>
        <div class="mt-2 space-y-1">
            <template x-for="favRoute in favorites" :key="favRoute">
                <template x-if="menuItems.find(m => m.route === favRoute)">
                    <a :href="`/orgs/{{ $currentOrg }}/${favRoute}`"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 text-slate-400 hover:text-white hover:bg-slate-700/30">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white">
                            <i class="fas text-sm" :class="menuItems.find(m => m.route === favRoute)?.icon"></i>
                        </div>
                        <span class="font-medium text-sm flex-1" x-text="menuItems.find(m => m.route === favRoute)?.name"></span>
                        <button @click.prevent="toggleFavorite(favRoute)"
                                class="opacity-0 group-hover:opacity-100 p-1 hover:bg-slate-600/50 rounded transition-all">
                            <i class="fas fa-star text-yellow-400 text-xs"></i>
                        </button>
                    </a>
                </template>
            </template>
        </div>
    </div>
</template>

<!-- Marketing Section -->
@if($isEnabled('campaigns') || $isEnabled('audiences') || $isEnabled('analytics') || $isEnabled('influencer-marketing') || $isEnabled('campaign-orchestration') || $isEnabled('social-listening'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.marketing_section') }}</p>
</div>

<!-- Campaigns (with submenu) -->
@if($isEnabled('campaigns'))
<div x-data="{ open: {{ request()->routeIs('orgs.campaigns.*') || request()->routeIs('orgs.keywords.*') ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="group w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200
                   {{ request()->routeIs('orgs.campaigns.*') || request()->routeIs('orgs.keywords.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('orgs.campaigns.*') ? 'bg-gradient-to-br from-orange-500 to-pink-600 text-white shadow-lg shadow-orange-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
                <i class="fas fa-bullhorn text-sm"></i>
            </div>
            <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.campaigns') }}</span>
        </div>
        <i class="fas fa-chevron-down text-xs transition-transform duration-200" x-show="!compactMode" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open && !compactMode" x-collapse class="{{ $isRtl ? 'mr-6' : 'ml-6' }} space-y-1">
        <a href="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.campaigns.index') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-list text-xs w-4"></i>
            <span>{{ __('navigation.all_campaigns') }}</span>
        </a>
        <a href="{{ route('orgs.campaigns.create', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-plus text-xs w-4"></i>
            <span>{{ __('navigation.new_campaign') }}</span>
        </a>
        <a href="{{ route('orgs.keywords.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.keywords.*') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-key text-xs w-4"></i>
            <span>{{ __('navigation.keywords') }}</span>
        </a>
    </div>
</div>
@endif

<!-- Audiences (with submenu) -->
@if($isEnabled('audiences'))
<div x-data="{ open: {{ request()->routeIs('orgs.audiences.*') ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="group w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200
                   {{ request()->routeIs('orgs.audiences.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('orgs.audiences.*') ? 'bg-gradient-to-br from-cyan-500 to-blue-600 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
                <i class="fas fa-users text-sm"></i>
            </div>
            <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.audiences') }}</span>
        </div>
        <i class="fas fa-chevron-down text-xs transition-transform duration-200" x-show="!compactMode" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open && !compactMode" x-collapse class="{{ $isRtl ? 'mr-6' : 'ml-6' }} space-y-1">
        <a href="{{ route('orgs.audiences.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.audiences.index') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-list text-xs w-4"></i>
            <span>{{ __('navigation.all_audiences') }}</span>
        </a>
        <a href="{{ route('orgs.audiences.create', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-plus text-xs w-4"></i>
            <span>{{ __('navigation.create_audience') }}</span>
        </a>
        <a href="{{ route('orgs.audiences.builder', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-magic text-xs w-4"></i>
            <span>{{ __('navigation.audience_builder') }}</span>
        </a>
    </div>
</div>
@endif

<!-- Analytics -->
@if($isEnabled('analytics'))
<a href="{{ route('orgs.analytics.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.analytics.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.analytics.*') ? 'bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-lg shadow-green-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-chart-bar text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.analytics') }}</span>
</a>
@endif

<!-- Influencer Marketing -->
@if($isEnabled('influencer-marketing'))
<a href="{{ route('orgs.influencer.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.influencer.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.influencer.*') ? 'bg-gradient-to-br from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-star text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.influencer_marketing') }}</span>
</a>
@endif

<!-- Campaign Orchestration -->
@if($isEnabled('campaign-orchestration'))
<a href="{{ route('orgs.orchestration.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.orchestration.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.orchestration.*') ? 'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-sitemap text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.campaign_orchestration') }}</span>
</a>
@endif

<!-- Social Listening -->
@if($isEnabled('social-listening'))
<a href="{{ route('orgs.listening.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.listening.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.listening.*') ? 'bg-gradient-to-br from-teal-500 to-cyan-600 text-white shadow-lg shadow-teal-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-headphones text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.social_listening') }}</span>
</a>
@endif
@endif

<!-- Content Section -->
@if($isEnabled('creative-assets') || $isEnabled('historical-content') || $isEnabled('social-media') || $isEnabled('profile-groups') || $isEnabled('products') || $isEnabled('workflows'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.content_section') }}</p>
</div>

<!-- Creative Content (with submenu) -->
@if($isEnabled('creative-assets'))
<div x-data="{ open: {{ request()->routeIs('orgs.creative.assets.*') || request()->routeIs('orgs.creative.briefs.*') ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="group w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200
                   {{ request()->routeIs('orgs.creative.assets.*') || request()->routeIs('orgs.creative.briefs.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('orgs.creative.assets.*') ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
                <i class="fas fa-palette text-sm"></i>
            </div>
            <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.creative_content') }}</span>
        </div>
        <i class="fas fa-chevron-down text-xs transition-transform duration-200" x-show="!compactMode" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open && !compactMode" x-collapse class="{{ $isRtl ? 'mr-6' : 'ml-6' }} space-y-1">
        <a href="{{ route('orgs.creative.assets.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.creative.assets.*') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-images text-xs w-4"></i>
            <span>{{ __('navigation.creative_assets_menu') }}</span>
        </a>
        <a href="{{ route('orgs.creative.briefs.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-file-alt text-xs w-4"></i>
            <span>{{ __('navigation.creative_briefs') }}</span>
        </a>
    </div>
</div>
@endif

<!-- Historical Content -->
@if($isEnabled('historical-content'))
<a href="{{ route('orgs.social.history.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.social.history.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.social.history.*') ? 'bg-gradient-to-br from-slate-500 to-slate-700 text-white shadow-lg shadow-slate-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-history text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.historical_content') }}</span>
</a>
@endif

<!-- Social Media (Core - Always visible) -->
<a href="{{ route('orgs.social.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.social.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.social.*') ? 'bg-gradient-to-br from-indigo-500 to-blue-600 text-white shadow-lg shadow-indigo-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-share-alt text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.social_media') }}</span>
</a>

<!-- Profile Groups (Core - Always visible) -->
<a href="{{ route('orgs.settings.profile-groups.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.settings.profile-groups.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.settings.profile-groups.*') ? 'bg-gradient-to-br from-fuchsia-500 to-pink-600 text-white shadow-lg shadow-fuchsia-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-layer-group text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.profile_groups') }}</span>
</a>

<!-- Products (with submenu) -->
@if($isEnabled('products'))
<div x-data="{ open: {{ request()->routeIs('orgs.products.*') || request()->routeIs('orgs.catalogs.*') ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="group w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200
                   {{ request()->routeIs('orgs.products.*') || request()->routeIs('orgs.catalogs.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('orgs.products.*') ? 'bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-lg shadow-emerald-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
                <i class="fas fa-box text-sm"></i>
            </div>
            <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.products') }}</span>
        </div>
        <i class="fas fa-chevron-down text-xs transition-transform duration-200" x-show="!compactMode" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open && !compactMode" x-collapse class="{{ $isRtl ? 'mr-6' : 'ml-6' }} space-y-1">
        <a href="{{ route('orgs.products', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.products') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-list text-xs w-4"></i>
            <span>{{ __('navigation.all_products') }}</span>
        </a>
        <a href="{{ route('orgs.catalogs.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-book text-xs w-4"></i>
            <span>{{ __('navigation.catalogs') }}</span>
        </a>
        <a href="{{ route('orgs.catalogs.import', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-upload text-xs w-4"></i>
            <span>{{ __('navigation.import_catalog') }}</span>
        </a>
    </div>
</div>
@endif

<!-- Workflows -->
@if($isEnabled('workflows'))
<a href="{{ route('orgs.workflows.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.workflows.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.workflows.*') ? 'bg-gradient-to-br from-lime-500 to-green-600 text-white shadow-lg shadow-lime-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-project-diagram text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.workflows') }}</span>
</a>
@endif
@endif

<!-- AI Section -->
@if($isEnabled('ai-assistant') || $isEnabled('knowledge-base'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.ai_section') }}</p>
</div>

@if($isEnabled('ai-assistant'))
<a href="{{ route('orgs.ai.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.ai.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.ai.*') ? 'bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg shadow-purple-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-robot text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.ai_assistant') }}</span>
</a>
@endif

@if($isEnabled('knowledge-base'))
<a href="{{ route('orgs.knowledge.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.knowledge.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.knowledge.*') ? 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg shadow-blue-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-book-open text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.knowledge_base') }}</span>
</a>
@endif
@endif

<!-- Intelligence Section -->
@if($isEnabled('predictive-analytics') || $isEnabled('ab-testing') || $isEnabled('optimization-engine'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.intelligence_section') }}</p>
</div>

@if($isEnabled('predictive-analytics'))
<a href="{{ route('orgs.predictive.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.predictive.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.predictive.*') ? 'bg-gradient-to-br from-cyan-500 to-blue-600 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-crystal-ball text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.predictive_analytics') }}</span>
</a>
@endif

@if($isEnabled('ab-testing'))
<a href="{{ route('orgs.experiments.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.experiments.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.experiments.*') ? 'bg-gradient-to-br from-amber-500 to-yellow-600 text-white shadow-lg shadow-amber-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-flask text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.ab_testing') }}</span>
</a>
@endif

@if($isEnabled('optimization-engine'))
<a href="{{ route('orgs.optimization.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.optimization.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.optimization.*') ? 'bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-cogs text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.optimization_engine') }}</span>
</a>
@endif
@endif

<!-- Automation Section -->
@if($isEnabled('automation-dashboard'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.automation_section') }}</p>
</div>

<a href="{{ route('orgs.automation.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.automation.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.automation.*') ? 'bg-gradient-to-br from-orange-500 to-amber-600 text-white shadow-lg shadow-orange-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-magic text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.automation_dashboard') }}</span>
</a>
@endif

<!-- System Section -->
@if($isEnabled('alerts') || $isEnabled('data-exports') || $isEnabled('dashboard-builder') || $isEnabled('feature-flags'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.system_section') }}</p>
</div>

@if($isEnabled('alerts'))
<a href="{{ route('orgs.alerts.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.alerts.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.alerts.*') ? 'bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-lg shadow-red-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-bell text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.alerts') }}</span>
</a>
@endif

@if($isEnabled('data-exports'))
<a href="{{ route('orgs.exports.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.exports.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.exports.*') ? 'bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-lg shadow-teal-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-download text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.data_exports') }}</span>
</a>
@endif

@if($isEnabled('dashboard-builder'))
<a href="{{ route('orgs.dashboard-builder.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.dashboard-builder.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.dashboard-builder.*') ? 'bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-th-large text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.dashboard_builder') }}</span>
</a>
@endif

@if($isEnabled('feature-flags'))
<a href="{{ route('orgs.feature-flags.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.feature-flags.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.feature-flags.*') ? 'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-flag text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.feature_flags') }}</span>
</a>
@endif
@endif

<!-- Communication Section (Inbox - Dynamic based on enabled status) -->
@if($isEnabled('inbox'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.communication_section') }}</p>
</div>

<a href="{{ route('orgs.inbox.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.inbox.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.inbox.*') ? 'bg-gradient-to-br from-blue-500 to-cyan-600 text-white shadow-lg shadow-blue-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-inbox text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.inbox') }}</span>
</a>
@endif

<!-- Settings Section (Core - Always visible) -->
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.settings_section') }}</p>
</div>

<!-- Settings with submenu -->
<div x-data="{ open: {{ request()->routeIs('orgs.settings.*') || request()->routeIs('orgs.platform-connections.*') || request()->routeIs('orgs.profiles.*') || request()->routeIs('orgs.ad-accounts.*') || request()->routeIs('orgs.brand-voices.*') || request()->routeIs('orgs.brand-safety.*') || request()->routeIs('orgs.approval-workflows.*') || request()->routeIs('orgs.boost-rules.*') ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="group w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200
                   {{ request()->routeIs('orgs.settings.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('orgs.settings.*') ? 'bg-gradient-to-br from-slate-500 to-slate-700 text-white shadow-lg shadow-slate-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
                <i class="fas fa-cog text-sm"></i>
            </div>
            <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.settings') }}</span>
        </div>
        <i class="fas fa-chevron-down text-xs transition-transform duration-200" x-show="!compactMode" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open && !compactMode" x-collapse class="{{ $isRtl ? 'mr-6' : 'ml-6' }} space-y-1">
        <a href="{{ route('orgs.settings.index', ['org' => $currentOrg]) }}#user-settings"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-user-cog text-xs w-4"></i>
            <span>{{ __('navigation.user_settings') }}</span>
        </a>
        <a href="{{ route('orgs.settings.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-building text-xs w-4"></i>
            <span>{{ __('navigation.organization_settings') }}</span>
        </a>
        <a href="{{ route('orgs.settings.platform-connections.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-plug text-xs w-4"></i>
            <span>{{ __('navigation.platform_connections') }}</span>
        </a>
        <a href="{{ route('orgs.settings.profiles.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-id-card text-xs w-4"></i>
            <span>{{ __('navigation.profile_management') }}</span>
        </a>
        @if($isEnabled('ad-accounts'))
        <a href="{{ route('orgs.settings.ad-accounts.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-ad text-xs w-4"></i>
            <span>{{ __('navigation.ad_accounts') }}</span>
        </a>
        @endif
        @if($isEnabled('brand-voices'))
        <a href="{{ route('orgs.settings.brand-voices.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-comment text-xs w-4"></i>
            <span>{{ __('navigation.brand_voices') }}</span>
        </a>
        @endif
        @if($isEnabled('brand-safety'))
        <a href="{{ route('orgs.settings.brand-safety.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-shield-alt text-xs w-4"></i>
            <span>{{ __('navigation.brand_safety') }}</span>
        </a>
        @endif
        @if($isEnabled('approval-workflows'))
        <a href="{{ route('orgs.settings.approval-workflows.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-check-double text-xs w-4"></i>
            <span>{{ __('navigation.approval_workflows') }}</span>
        </a>
        @endif
        @if($isEnabled('boost-rules'))
        <a href="{{ route('orgs.settings.boost-rules.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-rocket text-xs w-4"></i>
            <span>{{ __('navigation.boost_rules') }}</span>
        </a>
        @endif
    </div>
</div>

<!-- Team -->
<a href="{{ route('orgs.team.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.team.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.team.*') ? 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg shadow-blue-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-users text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.team') }}</span>
</a>

<!-- Organizations -->
<a href="{{ route('orgs.index') }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 text-slate-400 hover:text-white hover:bg-slate-700/30"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white">
        <i class="fas fa-building text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.organizations') }}</span>
</a>

<!-- Backup & Restore (Dynamic based on enabled status) -->
@if($isEnabled('org-backup-restore'))
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.backup_section') }}</p>
</div>

<div x-data="{ open: {{ request()->routeIs('orgs.backup.*') ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="group w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200
                   {{ request()->routeIs('orgs.backup.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('orgs.backup.*') ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
                <i class="fas fa-database text-sm"></i>
            </div>
            <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.backup_restore') }}</span>
        </div>
        <i class="fas fa-chevron-down text-xs transition-transform duration-200" x-show="!compactMode" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open && !compactMode" x-collapse class="{{ $isRtl ? 'mr-6' : 'ml-6' }} space-y-1">
        <a href="{{ route('orgs.backup.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.backup.index') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-list text-xs w-4"></i>
            <span>{{ __('navigation.all_backups') }}</span>
        </a>
        <a href="{{ route('orgs.backup.create', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all text-slate-400 hover:text-white hover:bg-slate-800/30">
            <i class="fas fa-plus text-xs w-4"></i>
            <span>{{ __('navigation.create_backup') }}</span>
        </a>
        <a href="{{ route('orgs.backup.restore.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.backup.restore.*') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-undo text-xs w-4"></i>
            <span>{{ __('navigation.restore_data') }}</span>
        </a>
        <a href="{{ route('orgs.backup.schedule.index', ['org' => $currentOrg]) }}"
           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all
                  {{ request()->routeIs('orgs.backup.schedule.*') ? 'text-blue-400 bg-slate-800/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <i class="fas fa-clock text-xs w-4"></i>
            <span>{{ __('navigation.backup_schedule') }}</span>
        </a>
    </div>
</div>
@endif

<!-- Apps Marketplace (Core - Always visible) -->
<div class="mt-4 mb-2" x-show="!compactMode">
    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('navigation.apps_marketplace') }}</p>
</div>

<a href="{{ route('orgs.marketplace.index', ['org' => $currentOrg]) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 transition-all duration-200 {{ request()->routeIs('orgs.marketplace.*') ? 'bg-gradient-to-l from-blue-600/20 to-purple-600/20 text-white border-r-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-700/30' }}"
   :class="compactMode ? 'justify-center' : ''">
    <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-all
                {{ request()->routeIs('orgs.marketplace.*') ? 'bg-gradient-to-br from-purple-500 to-pink-600 text-white shadow-lg shadow-purple-500/25' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-white' }}">
        <i class="fas fa-store text-sm"></i>
    </div>
    <span class="font-medium text-sm" x-show="!compactMode">{{ __('navigation.apps_marketplace') }}</span>
</a>
