@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.users.view_user'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.users.index') }}" class="text-gray-500 hover:text-red-600 transition">{{ __('super_admin.users.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ $user->name }}</span>
@endsection

@section('content')
<div x-data="userDetail()">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <!-- User Avatar -->
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center text-white font-bold text-xl">
                    {{ substr($user->name ?? 'U', 0, 2) }}
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                        <!-- Status Badge -->
                        @if($user->is_blocked)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                {{ __('super_admin.users.status_blocked') }}
                            </span>
                        @elseif($user->is_suspended)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                {{ __('super_admin.users.status_suspended') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                {{ __('super_admin.users.status_active') }}
                            </span>
                        @endif
                        <!-- Role Badge -->
                        @if($user->is_super_admin)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                <i class="fas fa-shield-alt {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                                {{ __('super_admin.users.role_super_admin') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $user->email }}</p>
                </div>
            </div>

            <!-- Actions -->
            @if(!$user->is_super_admin)
            <div class="flex items-center gap-2">
                <button @click="impersonateUser()"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-user-secret"></i>
                    <span class="hidden sm:inline">{{ __('super_admin.actions.impersonate') }}</span>
                </button>

                @if(!$user->is_blocked && !$user->is_suspended)
                    <button @click="openActionModal('suspend')"
                            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-pause"></i>
                        <span class="hidden sm:inline">{{ __('super_admin.actions.suspend') }}</span>
                    </button>
                @endif

                @if($user->is_suspended && !$user->is_blocked)
                    <button @click="restoreUser()"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-play"></i>
                        <span class="hidden sm:inline">{{ __('super_admin.actions.restore') }}</span>
                    </button>
                @endif

                @if(!$user->is_blocked)
                    <button @click="openActionModal('block')"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-ban"></i>
                        <span class="hidden sm:inline">{{ __('super_admin.actions.block') }}</span>
                    </button>
                @else
                    <button @click="restoreUser()"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-unlock"></i>
                        <span class="hidden sm:inline">{{ __('super_admin.actions.unblock') }}</span>
                    </button>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-building text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.users.organizations') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $activityStats['orgs_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.users.api_calls_month') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($activityStats['api_calls_total'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-clock text-purple-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.users.last_login') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('super_admin.users.never') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <i class="fas fa-calendar text-orange-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.users.member_since') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $user->created_at->format('M j, Y') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px overflow-x-auto">
                <button @click="activeTab = 'details'"
                        :class="activeTab === 'details' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-info-circle {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('super_admin.users.tab_details') }}
                </button>
                <button @click="activeTab = 'organizations'"
                        :class="activeTab === 'organizations' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-building {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('super_admin.users.tab_organizations') }}
                    <span class="{{ $isRtl ? 'mr-1' : 'ml-1' }} px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700">{{ $activityStats['orgs_count'] ?? 0 }}</span>
                </button>
                <button @click="activeTab = 'sessions'"
                        :class="activeTab === 'sessions' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-desktop {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('super_admin.users.tab_sessions') }}
                </button>
                <button @click="activeTab = 'activity'"
                        :class="activeTab === 'activity' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-6 text-sm font-medium border-b-2 whitespace-nowrap transition">
                    <i class="fas fa-history {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('super_admin.users.tab_activity') }}
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Details Tab -->
            <div x-show="activeTab === 'details'" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.users.basic_info') }}</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.user_id') }}</dt>
                                <dd class="text-gray-900 dark:text-white font-mono text-sm">{{ $user->user_id }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.email') }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $user->email }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.email_verified') }}</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    @if($user->email_verified_at)
                                        <span class="text-green-600"><i class="fas fa-check-circle"></i> {{ $user->email_verified_at->format('M j, Y') }}</span>
                                    @else
                                        <span class="text-red-600"><i class="fas fa-times-circle"></i> {{ __('common.no') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.phone') }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $user->phone ?? __('super_admin.users.not_provided') }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.locale') }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $user->locale ?? 'ar' }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.timezone') }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $user->timezone ?? 'Asia/Riyadh' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.users.account_status') }}</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.is_super_admin') }}</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    @if($user->is_super_admin)
                                        <span class="text-purple-600"><i class="fas fa-check-circle"></i> {{ __('common.yes') }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('common.no') }}</span>
                                    @endif
                                </dd>
                            </div>
                            @if($user->is_suspended)
                            <div class="py-3 px-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                <h4 class="font-medium text-yellow-800 dark:text-yellow-400 mb-2">
                                    <i class="fas fa-pause-circle {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                                    {{ __('super_admin.users.suspended_info') }}
                                </h4>
                                <dl class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-yellow-700 dark:text-yellow-500">{{ __('super_admin.users.suspended_at') }}</dt>
                                        <dd class="text-yellow-800 dark:text-yellow-400">{{ $user->suspended_at?->format('M j, Y H:i') }}</dd>
                                    </div>
                                    @if($user->suspension_reason)
                                    <div>
                                        <dt class="text-yellow-700 dark:text-yellow-500">{{ __('super_admin.users.reason') }}</dt>
                                        <dd class="text-yellow-800 dark:text-yellow-400 mt-1">{{ $user->suspension_reason }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            @endif
                            @if($user->is_blocked)
                            <div class="py-3 px-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <h4 class="font-medium text-red-800 dark:text-red-400 mb-2">
                                    <i class="fas fa-ban {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                                    {{ __('super_admin.users.blocked_info') }}
                                </h4>
                                <dl class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-red-700 dark:text-red-500">{{ __('super_admin.users.blocked_at') }}</dt>
                                        <dd class="text-red-800 dark:text-red-400">{{ $user->blocked_at?->format('M j, Y H:i') }}</dd>
                                    </div>
                                    @if($user->block_reason)
                                    <div>
                                        <dt class="text-red-700 dark:text-red-500">{{ __('super_admin.users.reason') }}</dt>
                                        <dd class="text-red-800 dark:text-red-400 mt-1">{{ $user->block_reason }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Organizations Tab -->
            <div x-show="activeTab === 'organizations'" x-transition>
                @if($user->orgs && $user->orgs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    {{ __('super_admin.users.organization') }}
                                </th>
                                <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    {{ __('super_admin.users.role') }}
                                </th>
                                <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    {{ __('super_admin.users.org_status') }}
                                </th>
                                <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    {{ __('super_admin.users.joined') }}
                                </th>
                                <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    {{ __('super_admin.users.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($user->orgs as $org)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ substr($org->name ?? 'O', 0, 2) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('super-admin.orgs.show', $org->org_id) }}" class="font-medium text-gray-900 dark:text-white hover:text-red-600">
                                                {{ $org->name }}
                                            </a>
                                            <p class="text-sm text-gray-500">{{ $org->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $org->pivot->role === 'owner' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                                           ($org->pivot->role === 'admin' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                        {{ ucfirst($org->pivot->role ?? 'member') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $org->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                           ($org->status === 'suspended' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                        {{ ucfirst($org->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $org->pivot->created_at?->format('M j, Y') ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('super-admin.orgs.show', $org->org_id) }}"
                                       class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition inline-flex"
                                       title="{{ __('super_admin.actions.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-building text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.no_organizations') }}</p>
                </div>
                @endif
            </div>

            <!-- Sessions Tab -->
            <div x-show="activeTab === 'sessions'" x-transition>
                @if(isset($sessions) && count($sessions) > 0)
                <div class="space-y-4">
                    @foreach($sessions as $session)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                <i class="fas {{ Str::contains($session->user_agent ?? '', 'Mobile') ? 'fa-mobile-alt' : 'fa-desktop' }} text-gray-600 dark:text-gray-300"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $session->ip_address ?? __('super_admin.users.unknown') }}</p>
                                <p class="text-sm text-gray-500 truncate max-w-md">{{ Str::limit($session->user_agent ?? '', 60) }}</p>
                            </div>
                        </div>
                        <div class="{{ $isRtl ? 'text-left' : 'text-right' }}">
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.users.last_activity') }}</p>
                            <p class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-desktop text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.no_sessions') }}</p>
                </div>
                @endif
            </div>

            <!-- Activity Tab -->
            <div x-show="activeTab === 'activity'" x-transition>
                @if(isset($activities) && count($activities) > 0)
                <div class="space-y-4">
                    @foreach($activities as $activity)
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-{{ $activity->icon ?? 'circle' }} text-xs text-gray-600 dark:text-gray-400"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-gray-900 dark:text-white">{{ $activity->description }}</p>
                            <p class="text-sm text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-history text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.no_activity') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div x-show="actionModal.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
         x-cloak>
        <div @click.away="actionModal.show = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center"
                         :class="actionModal.type === 'block' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30'">
                        <i class="fas text-xl"
                           :class="actionModal.type === 'block' ? 'fa-ban text-red-600' : 'fa-pause text-yellow-600'"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                            x-text="actionModal.type === 'block' ? '{{ __('super_admin.users.block_user') }}' : '{{ __('super_admin.users.suspend_user') }}'"></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->name }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.users.reason') }}
                    </label>
                    <textarea x-model="actionModal.reason"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              :placeholder="actionModal.type === 'block' ? '{{ __('super_admin.users.block_reason_placeholder') }}' : '{{ __('super_admin.users.suspend_reason_placeholder') }}'"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button @click="actionModal.show = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="executeAction()"
                            :disabled="actionModal.processing"
                            class="px-4 py-2 text-white rounded-lg transition disabled:opacity-50"
                            :class="actionModal.type === 'block' ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700'">
                        <i x-show="actionModal.processing" class="fas fa-spinner fa-spin {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <span x-text="actionModal.type === 'block' ? '{{ __('super_admin.actions.block') }}' : '{{ __('super_admin.actions.suspend') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function userDetail() {
    return {
        activeTab: 'details',
        actionModal: {
            show: false,
            type: '',
            reason: '',
            processing: false
        },

        openActionModal(type) {
            this.actionModal = {
                show: true,
                type: type,
                reason: '',
                processing: false
            };
        },

        async executeAction() {
            this.actionModal.processing = true;
            try {
                const route = this.actionModal.type === 'block'
                    ? '{{ route('super-admin.users.block', $user->user_id) }}'
                    : '{{ route('super-admin.users.suspend', $user->user_id) }}';

                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        reason: this.actionModal.reason
                    })
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error executing action:', error);
            } finally {
                this.actionModal.processing = false;
            }
        },

        async restoreUser() {
            try {
                const response = await fetch('{{ route('super-admin.users.restore', $user->user_id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error restoring user:', error);
            }
        },

        async impersonateUser() {
            if (confirm('{{ __('super_admin.users.impersonate_confirm') }}')) {
                try {
                    const response = await fetch('{{ route('super-admin.users.impersonate', $user->user_id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        window.location.href = '/';
                    }
                } catch (error) {
                    console.error('Error impersonating user:', error);
                }
            }
        }
    };
}
</script>
@endpush
