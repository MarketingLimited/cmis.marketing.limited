@php
    $currentOrg = auth()->user()->active_org_id ?? auth()->user()->current_org_id ?? auth()->user()->org_id ?? request()->route('org');
    $navigationService = app(\App\Services\Navigation\NavigationService::class);
    $structuredNav = $currentOrg ? $navigationService->getStructuredNavigation($currentOrg) : [];
@endphp

<nav class="mt-6 px-4 space-y-2 flex-1 overflow-y-auto">
    @if($currentOrg)
        @foreach($structuredNav as $section)
            @if($section['is_core'])
                {{-- Core items without section header --}}
                @foreach($section['items'] as $item)
                    @if($item['slug'] !== 'marketplace')
                        @php
                            $routeName = $item['route_name'];
                            $routePrefix = $item['route_prefix'] ?? '';
                            $isActive = request()->routeIs("orgs.{$routePrefix}.*") ||
                                        request()->routeIs($routeName);

                            // Check sub-routes in metadata
                            $subRoutes = $item['metadata']['sub_routes'] ?? [];
                            foreach ($subRoutes as $subRoute) {
                                if (request()->routeIs($subRoute)) {
                                    $isActive = true;
                                    break;
                                }
                            }
                        @endphp

                        @if(Route::has($routeName))
                            <a href="{{ route($routeName, ['org' => $currentOrg]) }}"
                               class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ $isActive ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                                <i class="fas {{ $item['icon'] }} text-lg w-6"></i>
                                <span class="font-medium">{{ __($item['name_key']) }}</span>
                            </a>
                        @endif
                    @endif
                @endforeach
            @else
                {{-- Optional apps with section header --}}
                @if(!empty($section['items']))
                    <div class="pt-4 border-t border-white/20 mt-4">
                        <p class="text-white/50 text-xs font-medium px-4 mb-2">{{ __($section['category']['name_key']) }}</p>

                        @foreach($section['items'] as $item)
                            @php
                                $routeName = $item['route_name'];
                                $routePrefix = $item['route_prefix'] ?? '';
                                $isActive = request()->routeIs("orgs.{$routePrefix}.*") ||
                                            request()->routeIs($routeName);

                                // Check sub-routes in metadata
                                $subRoutes = $item['metadata']['sub_routes'] ?? [];
                                foreach ($subRoutes as $subRoute) {
                                    if (request()->routeIs($subRoute)) {
                                        $isActive = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if(Route::has($routeName))
                                <a href="{{ route($routeName, ['org' => $currentOrg]) }}"
                                   class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ $isActive ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                                    <i class="fas {{ $item['icon'] }} text-lg w-6"></i>
                                    <span class="font-medium">{{ __($item['name_key']) }}</span>
                                    @if($item['is_premium'] ?? false)
                                        <i class="fas fa-crown text-amber-400 text-xs ms-auto"></i>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            @endif
        @endforeach

        {{-- Tools Section (System-level routes) --}}
        <div class="pt-4 border-t border-white/20 mt-4">
            <p class="text-white/50 text-xs font-medium px-4 mb-2">{{ __('navigation.tools') }}</p>

            @can('viewAny', App\Models\User::class)
            <a href="{{ route('users.index') }}"
               class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('users.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                <i class="fas fa-users text-lg w-6"></i>
                <span class="font-medium">{{ __('navigation.users') }}</span>
            </a>
            @endcan

            <a href="{{ route('orgs.team.index', ['org' => $currentOrg]) }}"
               class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('orgs.team.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                <i class="fas fa-user-friends text-lg w-6"></i>
                <span class="font-medium">{{ __('navigation.team_management') }}</span>
            </a>

            <a href="{{ route('orgs.settings.user', ['org' => $currentOrg]) }}"
               class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('orgs.settings.user') || request()->routeIs('orgs.settings.profile') || request()->routeIs('orgs.settings.notifications') || request()->routeIs('orgs.settings.password') || request()->routeIs('orgs.settings.sessions') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                <i class="fas fa-user-cog text-lg w-6"></i>
                <span class="font-medium">{{ __('navigation.user_settings') }}</span>
            </a>

            <a href="{{ route('orgs.settings.organization', ['org' => $currentOrg]) }}"
               class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('orgs.settings.organization') || request()->routeIs('orgs.settings.team.*') || request()->routeIs('orgs.settings.api-tokens.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                <i class="fas fa-building text-lg w-6"></i>
                <span class="font-medium">{{ __('navigation.organization_settings') }}</span>
            </a>
        </div>

        {{-- Apps Marketplace Link (Always at bottom) --}}
        <div class="pt-4 border-t border-white/20 mt-4">
            <a href="{{ route('orgs.marketplace.index', ['org' => $currentOrg]) }}"
               class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('orgs.marketplace.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                <i class="fas fa-store text-lg w-6"></i>
                <span class="font-medium">{{ __('navigation.apps_marketplace') }}</span>
            </a>
        </div>
    @else
        <div class="px-4 py-3 text-white/60 text-sm">
            {{ __('navigation.no_organization_selected') }}
        </div>
    @endif
</nav>
