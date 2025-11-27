@extends('layouts.admin')

@section('title', __('settings.organization_settings'))

@php
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div x-data="orgSettingsPage()" dir="{{ $dir }}" class="{{ $isRtl ? 'rtl-layout' : '' }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('settings.organization_settings') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.organization_settings') }}</h1>
        <p class="mt-1 text-gray-600">{{ __('settings.manage_organization_settings') }}</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-green-500 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    <p class="text-green-800 font-medium">{{ session('success') }}</p>
                </div>
                <button @click="show = false" class="text-green-500 hover:text-green-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-red-500 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    <p class="text-red-800 font-medium">{{ session('error') }}</p>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Mobile Tabs (visible on mobile/tablet) --}}
    <div class="lg:hidden mb-6">
        <div class="bg-white rounded-xl shadow-sm p-2">
            <nav class="flex gap-2 overflow-x-auto scrollbar-hide" role="tablist">
                <button @click="activeTab = 'general'"
                        :class="activeTab === 'general' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                        class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px]"
                        role="tab"
                        :aria-selected="activeTab === 'general'">
                    <i class="fas fa-cog text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.general') }}</span>
                </button>
                <button @click="activeTab = 'team'"
                        :class="activeTab === 'team' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                        class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px]"
                        role="tab"
                        :aria-selected="activeTab === 'team'">
                    <i class="fas fa-users text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.team_members') }}</span>
                </button>
                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                   class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px] bg-gray-50 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-plug text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.platform_connections') }}</span>
                </a>
                <button @click="activeTab = 'api'"
                        :class="activeTab === 'api' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                        class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px]"
                        role="tab"
                        :aria-selected="activeTab === 'api'">
                    <i class="fas fa-code text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.api_keys') }}</span>
                </button>
                <button @click="activeTab = 'billing'"
                        :class="activeTab === 'billing' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                        class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px]"
                        role="tab"
                        :aria-selected="activeTab === 'billing'">
                    <i class="fas fa-credit-card text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.billing') }}</span>
                </button>
            </nav>
        </div>
        {{-- Link to User Settings (Mobile) --}}
        <div class="mt-3">
            <a href="{{ route('orgs.settings.user', $currentOrg) }}"
               class="flex items-center justify-between px-4 py-3 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-gray-50 to-gray-100 rounded-xl hover:from-blue-50 hover:to-purple-50 transition {{ $isRtl ? 'flex-row-reverse' : '' }} group">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center group-hover:bg-gradient-to-br group-hover:from-blue-500 group-hover:to-purple-500 group-hover:text-white transition">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <p class="text-sm font-medium text-gray-900">{{ __('settings.user_settings') }}</p>
                        <p class="text-xs text-gray-500">{{ __('settings.manage_personal_settings') }}</p>
                    </div>
                </div>
                <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }} text-gray-400 group-hover:text-blue-600 transition"></i>
            </a>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 {{ $isRtl ? 'lg:flex-row-reverse' : '' }}">
        {{-- Desktop Sidebar Navigation (hidden on mobile) --}}
        <div class="hidden lg:block lg:w-64 flex-shrink-0">
            <nav class="bg-white shadow-sm rounded-xl overflow-hidden sticky top-24">
                <div class="px-5 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-600 to-purple-600">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-building text-lg"></i>
                        <span>{{ __('settings.organization_settings') }}</span>
                    </h3>
                </div>
                <ul class="p-2 space-y-1">
                    <li>
                        <button @click="activeTab = 'general'"
                                :class="activeTab === 'general' ? 'bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 text-blue-700 border-{{ $isRtl ? 'l' : 'r' }}-4 border-blue-600 shadow-sm' : 'text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200'"
                                class="w-full flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}"
                                role="tab"
                                :aria-selected="activeTab === 'general'">
                            <i class="fas fa-cog text-base mt-0.5" :class="activeTab === 'general' ? 'text-blue-600' : 'text-gray-400'"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.general') }}</div>
                                <div class="text-xs mt-0.5" :class="activeTab === 'general' ? 'text-blue-600' : 'text-gray-500'">{{ __('settings.manage_organization_info') }}</div>
                            </div>
                        </button>
                    </li>
                    <li>
                        <button @click="activeTab = 'team'"
                                :class="activeTab === 'team' ? 'bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 text-blue-700 border-{{ $isRtl ? 'l' : 'r' }}-4 border-blue-600 shadow-sm' : 'text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200'"
                                class="w-full flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}"
                                role="tab"
                                :aria-selected="activeTab === 'team'">
                            <i class="fas fa-users text-base mt-0.5" :class="activeTab === 'team' ? 'text-blue-600' : 'text-gray-400'"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.team_members') }}</div>
                                <div class="text-xs mt-0.5" :class="activeTab === 'team' ? 'text-blue-600' : 'text-gray-500'">{{ __('settings.manage_team_access') }}</div>
                            </div>
                        </button>
                    </li>
                    <li>
                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                           class="flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200 transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }} group">
                            <i class="fas fa-plug text-base mt-0.5 text-gray-400 group-hover:text-blue-600 transition"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.platform_connections') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ __('settings.platform_connections_description') }}</div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <button @click="activeTab = 'api'"
                                :class="activeTab === 'api' ? 'bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 text-blue-700 border-{{ $isRtl ? 'l' : 'r' }}-4 border-blue-600 shadow-sm' : 'text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200'"
                                class="w-full flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}"
                                role="tab"
                                :aria-selected="activeTab === 'api'">
                            <i class="fas fa-code text-base mt-0.5" :class="activeTab === 'api' ? 'text-blue-600' : 'text-gray-400'"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.api_keys') }}</div>
                                <div class="text-xs mt-0.5" :class="activeTab === 'api' ? 'text-blue-600' : 'text-gray-500'">{{ __('settings.manage_api_keys') }}</div>
                            </div>
                        </button>
                    </li>
                    <li>
                        <button @click="activeTab = 'billing'"
                                :class="activeTab === 'billing' ? 'bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 text-blue-700 border-{{ $isRtl ? 'l' : 'r' }}-4 border-blue-600 shadow-sm' : 'text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200'"
                                class="w-full flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}"
                                role="tab"
                                :aria-selected="activeTab === 'billing'">
                            <i class="fas fa-credit-card text-base mt-0.5" :class="activeTab === 'billing' ? 'text-blue-600' : 'text-gray-400'"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.billing') }}</div>
                                <div class="text-xs mt-0.5" :class="activeTab === 'billing' ? 'text-blue-600' : 'text-gray-500'">{{ __('settings.current_plan') }}</div>
                            </div>
                        </button>
                    </li>
                </ul>

                {{-- Link to User Settings (Desktop) --}}
                <div class="px-3 py-3 border-t border-gray-200 bg-gray-50">
                    <a href="{{ route('orgs.settings.user', $currentOrg) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-white hover:text-blue-600 transition-all group {{ $isRtl ? 'flex-row-reverse text-right' : '' }}">
                        <i class="fas fa-user text-base text-gray-400 group-hover:text-blue-600 transition"></i>
                        <span class="flex-1">{{ __('settings.user_settings') }}</span>
                        <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} text-xs text-gray-400 group-hover:text-blue-600 group-hover:translate-{{ $isRtl ? '-' : '' }}x-1 transition-all"></i>
                    </a>
                </div>
            </nav>
        </div>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">
            {{-- General Section --}}
            <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white shadow-sm rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.organization_details') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('settings.manage_organization_info') }}</p>
                    </div>
                    <form action="{{ route('orgs.settings.organization.update', $currentOrg) }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="org_name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.organization_name') }} *</label>
                            <input type="text" name="org_name" id="org_name" value="{{ old('org_name', $organization->name ?? '') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.default_currency') }}</label>
                                <select name="currency" id="currency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                    <option value="BHD" {{ ($organization->currency ?? 'BHD') === 'BHD' ? 'selected' : '' }}>{{ __('settings.bhd') }}</option>
                                    <option value="USD" {{ ($organization->currency ?? '') === 'USD' ? 'selected' : '' }}>{{ __('settings.usd') }}</option>
                                    <option value="EUR" {{ ($organization->currency ?? '') === 'EUR' ? 'selected' : '' }}>{{ __('settings.eur') }}</option>
                                    <option value="SAR" {{ ($organization->currency ?? '') === 'SAR' ? 'selected' : '' }}>{{ __('settings.sar') }}</option>
                                    <option value="AED" {{ ($organization->currency ?? '') === 'AED' ? 'selected' : '' }}>{{ __('settings.aed') }}</option>
                                    <option value="KWD" {{ ($organization->currency ?? '') === 'KWD' ? 'selected' : '' }}>{{ __('settings.kwd') }}</option>
                                    <option value="QAR" {{ ($organization->currency ?? '') === 'QAR' ? 'selected' : '' }}>{{ __('settings.qar') }}</option>
                                    <option value="OMR" {{ ($organization->currency ?? '') === 'OMR' ? 'selected' : '' }}>{{ __('settings.omr') }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="default_locale" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.default_language') }}</label>
                                <select name="default_locale" id="default_locale" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                    <option value="ar-BH" {{ ($organization->default_locale ?? 'ar-BH') === 'ar-BH' ? 'selected' : '' }}>{{ __('settings.arabic_bahrain') }}</option>
                                    <option value="ar-SA" {{ ($organization->default_locale ?? '') === 'ar-SA' ? 'selected' : '' }}>{{ __('settings.arabic_saudi') }}</option>
                                    <option value="ar-AE" {{ ($organization->default_locale ?? '') === 'ar-AE' ? 'selected' : '' }}>{{ __('settings.arabic_uae') }}</option>
                                    <option value="en-US" {{ ($organization->default_locale ?? '') === 'en-US' ? 'selected' : '' }}>{{ __('settings.english_us') }}</option>
                                    <option value="en-GB" {{ ($organization->default_locale ?? '') === 'en-GB' ? 'selected' : '' }}>{{ __('settings.english_uk') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Organization Info Card --}}
                        <div class="p-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 rounded-xl border border-blue-100">
                            <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-info-circle text-blue-500"></i>
                                <span>{{ __('settings.organization_information') }}</span>
                            </h4>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <dt class="text-gray-500">{{ __('settings.organization_id') }}</dt>
                                    <dd class="mt-1 font-mono text-xs text-gray-700 bg-white px-2 py-1 rounded {{ $isRtl ? 'text-right' : '' }}">{{ $currentOrg }}</dd>
                                </div>
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <dt class="text-gray-500">{{ __('settings.created') }}</dt>
                                    <dd class="mt-1 text-gray-700">{{ $organization->created_at?->format('M d, Y') ?? __('settings.not_available') }}</dd>
                                </div>
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <dt class="text-gray-500">{{ __('settings.team_members') }}</dt>
                                    <dd class="mt-1 text-gray-700">{{ $teamMembers->count() ?? 0 }}</dd>
                                </div>
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <dt class="text-gray-500">{{ __('settings.active_campaigns') }}</dt>
                                    <dd class="mt-1 text-gray-700">{{ $activeCampaigns ?? 0 }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="flex {{ $isRtl ? 'justify-start' : 'justify-end' }} pt-4 border-t border-gray-200">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                {{ __('settings.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Team Members Section --}}
            <div x-show="activeTab === 'team'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white shadow-sm rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.team_members') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('settings.manage_team_access') }}</p>
                        </div>
                        <button @click="showInviteMemberModal = true" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 transition flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-user-plus"></i>
                            <span>{{ __('settings.invite_member') }}</span>
                        </button>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse($teamMembers ?? [] as $member)
                            <div class="flex items-center justify-between px-6 py-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-sm font-medium text-white">
                                        {{ substr($member->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div class="{{ $isRtl ? 'mr-0 ml-4 text-right' : 'mr-4' }}">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $member->name }}
                                            @if($member->user_id === auth()->id())
                                                <span class="{{ $isRtl ? 'ml-1' : 'mr-1' }} text-xs text-gray-400">({{ __('settings.you') }})</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500">{{ $member->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-lg">
                                        {{ $member->pivot->role_name ?? __('settings.member') }}
                                    </span>
                                    @if($member->user_id !== auth()->id())
                                        <form action="{{ route('orgs.settings.team.remove', [$currentOrg, $member->user_id]) }}" method="POST"
                                              onsubmit="return confirm('{{ __('settings.are_you_sure_remove_member') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                                {{ __('settings.remove') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center">
                                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                <p class="text-sm text-gray-500">{{ __('settings.no_team_members') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- API Keys Section --}}
            <div x-show="activeTab === 'api'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white shadow-sm rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.api_keys') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('settings.manage_api_keys') }}</p>
                        </div>
                        <button @click="showCreateApiKeyModal = true" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 transition flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-plus"></i>
                            <span>{{ __('settings.create_api_key') }}</span>
                        </button>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse($apiTokens ?? [] as $token)
                            <div class="flex items-center justify-between px-6 py-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm font-medium text-gray-900">{{ $token->name }}</p>
                                    <p class="text-xs text-gray-500 font-mono">{{ $token->token_prefix }}...</p>
                                    <div class="flex items-center mt-1 gap-3 text-xs text-gray-500 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <span>{{ __('settings.created') }}: {{ $token->created_at->format('M d, Y') }}</span>
                                        @if($token->last_used_at)
                                            <span>{{ __('settings.last_used') }}: {{ $token->last_used_at->diffForHumans() }}</span>
                                        @endif
                                        @if($token->expires_at)
                                            <span class="{{ $token->expires_at->isPast() ? 'text-red-600' : '' }}">
                                                {{ __('settings.expires') }}: {{ $token->expires_at->format('M d, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $token->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $token->is_active ? __('settings.active') : __('settings.inactive') }}
                                    </span>
                                    <form action="{{ route('orgs.settings.api-tokens.destroy', [$currentOrg, $token->token_id]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('settings.are_you_sure_revoke_key') }}')"
                                                class="text-sm text-red-600 hover:text-red-800">
                                            {{ __('settings.revoke') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center">
                                <i class="fas fa-key text-4xl text-gray-300 mb-3"></i>
                                <p class="text-sm text-gray-500">{{ __('settings.no_api_keys') }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ __('settings.create_key_access_api') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- API Documentation Link --}}
                <div class="mt-6 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-start gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-blue-500"></i>
                        </div>
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <h3 class="text-sm font-medium text-gray-900">{{ __('settings.api_documentation') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('settings.learn_how_use_api') }}
                                <a href="#" class="font-medium text-blue-600 hover:text-blue-700 underline">{{ __('settings.view_documentation') }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Billing Section --}}
            <div x-show="activeTab === 'billing'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-6">
                    {{-- Current Plan --}}
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.current_plan') }}</h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-2xl font-bold text-gray-900">{{ $currentPlan ?? __('settings.professional') }}</p>
                                    <p class="text-sm text-gray-500 mt-1">{{ __('settings.subscription_renews_on') }} {{ $renewalDate ?? __('settings.january') . ' 1, 2026' }}</p>
                                </div>
                                <button class="px-4 py-2 text-sm font-medium text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition">
                                    {{ __('settings.upgrade_plan') }}
                                </button>
                            </div>

                            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl {{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm text-blue-600">{{ __('settings.campaigns') }}</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ $usage['campaigns'] ?? 0 }} / {{ $limits['campaigns'] ?? __('settings.unlimited') }}</p>
                                </div>
                                <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl {{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm text-green-600">{{ __('settings.team_members') }}</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ $usage['team_members'] ?? 1 }} / {{ $limits['team_members'] ?? 10 }}</p>
                                </div>
                                <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl {{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm text-purple-600">{{ __('settings.api_calls') }}</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ number_format($usage['api_calls'] ?? 0) }} / {{ number_format($limits['api_calls'] ?? 100000) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.payment_method') }}</h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="flex items-center gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <i class="fab fa-cc-visa text-3xl text-blue-600"></i>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="text-sm font-medium text-gray-900">{{ __('settings.visa_ending_in') }} {{ $paymentMethod['last4'] ?? '4242' }}</p>
                                        <p class="text-xs text-gray-500">{{ __('settings.expires') }} {{ $paymentMethod['exp_month'] ?? '12' }}/{{ $paymentMethod['exp_year'] ?? '2025' }}</p>
                                    </div>
                                </div>
                                <button class="text-sm text-blue-600 hover:text-blue-800">{{ __('settings.update') }}</button>
                            </div>
                        </div>
                    </div>

                    {{-- Billing History --}}
                    <div class="bg-white shadow-sm rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.billing_history') }}</h2>
                            <p class="text-xs text-gray-500 mt-1 lg:hidden">{{ __('settings.swipe_to_view_more') }}</p>
                        </div>
                        <div class="overflow-x-auto relative">
                            {{-- Scroll indicator shadows --}}
                            <div class="absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-white to-transparent pointer-events-none z-10 lg:hidden"></div>
                            <div class="absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white to-transparent pointer-events-none z-10 lg:hidden"></div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('settings.date') }}</th>
                                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('settings.description') }}</th>
                                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('settings.amount') }}</th>
                                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('settings.status') }}</th>
                                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('settings.invoice') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($invoices ?? [] as $invoice)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ $invoice->date }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ $invoice->description }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ $invoice->amount }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap {{ $isRtl ? 'text-right' : '' }}">
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ $invoice->status }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap {{ $isRtl ? 'text-right' : 'text-left' }} text-sm">
                                                <a href="#" class="text-blue-600 hover:text-blue-800">{{ __('settings.download') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                                {{ __('settings.no_invoices') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create API Key Modal --}}
    <div x-show="showCreateApiKeyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showCreateApiKeyModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 @click="showCreateApiKeyModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div x-show="showCreateApiKeyModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 {{ $isRtl ? 'text-left' : 'text-right' }} overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('orgs.settings.api-tokens.store', $currentOrg) }}" method="POST">
                    @csrf
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('settings.create_new_api_key') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('settings.give_key_name_permissions') }}</p>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="token_name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.key_name') }} *</label>
                            <input type="text" name="name" id="token_name" required
                                   placeholder="{{ __('settings.key_name_placeholder') }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.permissions') }}</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                @foreach(\App\Models\Core\ApiToken::getAvailableScopes() as $scope => $description)
                                    <label class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <input type="checkbox" name="scopes[]" value="{{ $scope }}"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="{{ $isRtl ? 'mr-0 ml-2' : 'mr-2' }} text-sm text-gray-700">{{ $description }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.expiration_optional') }}</label>
                            <input type="date" name="expires_at" id="expires_at"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.leave_empty_no_expiration') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex {{ $isRtl ? 'justify-start' : 'justify-end' }} gap-3">
                        <button type="button" @click="showCreateApiKeyModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            {{ __('settings.cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700">
                            {{ __('settings.create_key') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Invite Member Modal --}}
    <div x-show="showInviteMemberModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showInviteMemberModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 @click="showInviteMemberModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div x-show="showInviteMemberModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 {{ $isRtl ? 'text-left' : 'text-right' }} overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('orgs.settings.team.invite', $currentOrg) }}" method="POST">
                    @csrf
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('settings.invite_team_member') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('settings.send_invitation_join') }}</p>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="invite_email" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.email_address') }} *</label>
                            <input type="email" name="email" id="invite_email" required
                                   placeholder="{{ __('settings.email_placeholder') }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                        </div>

                        <div>
                            <label for="invite_role" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.role') }}</label>
                            <select name="role_id" id="invite_role"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                @foreach($roles ?? [] as $role)
                                    <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex {{ $isRtl ? 'justify-start' : 'justify-end' }} gap-3">
                        <button type="button" @click="showInviteMemberModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            {{ __('settings.cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700">
                            {{ __('settings.send_invitation') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function orgSettingsPage() {
    return {
        activeTab: '{{ request()->get('tab', 'general') }}',
        showCreateApiKeyModal: false,
        showInviteMemberModal: false,

        init() {
            this.$watch('activeTab', (value) => {
                const url = new URL(window.location);
                url.searchParams.set('tab', value);
                window.history.replaceState({}, '', url);
            });
        }
    }
}
</script>
@endpush

@push('styles')
<style>
.rtl-layout {
    direction: rtl;
    text-align: right;
}

/* Hide scrollbar for mobile tabs */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Smooth tab transitions */
[x-cloak] {
    display: none !important;
}

/* Focus visible for accessibility */
button:focus-visible, a:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>
@endpush
@endsection
