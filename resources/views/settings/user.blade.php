@extends('layouts.admin')

@section('title', __('settings.user_settings'))

@php
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div x-data="userSettingsPage()" dir="{{ $dir }}" class="{{ $isRtl ? 'rtl-layout' : '' }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('settings.user_settings') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('settings.user_settings') }}</h1>
        <p class="mt-1 text-gray-600">{{ __('settings.manage_personal_settings') }}</p>
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
                <button @click="activeTab = 'profile'"
                        :class="activeTab === 'profile' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                        class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px]"
                        role="tab"
                        :aria-selected="activeTab === 'profile'">
                    <i class="fas fa-user text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.profile') }}</span>
                </button>
                <button @click="activeTab = 'notifications'"
                        :class="activeTab === 'notifications' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                        class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px]"
                        role="tab"
                        :aria-selected="activeTab === 'notifications'">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.notifications') }}</span>
                </button>
                <button @click="activeTab = 'security'"
                        :class="activeTab === 'security' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                        class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-3 rounded-lg transition-all duration-200 min-w-[80px]"
                        role="tab"
                        :aria-selected="activeTab === 'security'">
                    <i class="fas fa-shield-alt text-lg"></i>
                    <span class="text-xs font-medium">{{ __('settings.security') }}</span>
                </button>
            </nav>
        </div>
        {{-- Link to Organization Settings (Mobile) --}}
        <div class="mt-3">
            <a href="{{ route('orgs.settings.organization', $currentOrg) }}"
               class="flex items-center justify-between px-4 py-3 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-gray-50 to-gray-100 rounded-xl hover:from-blue-50 hover:to-purple-50 transition {{ $isRtl ? 'flex-row-reverse' : '' }} group">
                <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center group-hover:bg-gradient-to-br group-hover:from-blue-500 group-hover:to-purple-500 group-hover:text-white transition">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <p class="text-sm font-medium text-gray-900">{{ __('settings.organization_settings') }}</p>
                        <p class="text-xs text-gray-500">{{ __('settings.manage_organization_settings') }}</p>
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
                        <i class="fas fa-user-cog text-lg"></i>
                        <span>{{ __('settings.user_settings') }}</span>
                    </h3>
                </div>
                <ul class="p-2 space-y-1">
                    <li>
                        <button @click="activeTab = 'profile'"
                                :class="activeTab === 'profile' ? 'bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 text-blue-700 border-{{ $isRtl ? 'l' : 'r' }}-4 border-blue-600 shadow-sm' : 'text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200'"
                                class="w-full flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}"
                                role="tab"
                                :aria-selected="activeTab === 'profile'">
                            <i class="fas fa-user text-base mt-0.5" :class="activeTab === 'profile' ? 'text-blue-600' : 'text-gray-400'"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.profile') }}</div>
                                <div class="text-xs mt-0.5" :class="activeTab === 'profile' ? 'text-blue-600' : 'text-gray-500'">{{ __('settings.update_personal_information') }}</div>
                            </div>
                        </button>
                    </li>
                    <li>
                        <button @click="activeTab = 'notifications'"
                                :class="activeTab === 'notifications' ? 'bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 text-blue-700 border-{{ $isRtl ? 'l' : 'r' }}-4 border-blue-600 shadow-sm' : 'text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200'"
                                class="w-full flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}"
                                role="tab"
                                :aria-selected="activeTab === 'notifications'">
                            <i class="fas fa-bell text-base mt-0.5" :class="activeTab === 'notifications' ? 'text-blue-600' : 'text-gray-400'"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.notifications') }}</div>
                                <div class="text-xs mt-0.5" :class="activeTab === 'notifications' ? 'text-blue-600' : 'text-gray-500'">{{ __('settings.choose_notification_method') }}</div>
                            </div>
                        </button>
                    </li>
                    <li>
                        <button @click="activeTab = 'security'"
                                :class="activeTab === 'security' ? 'bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-blue-50 to-purple-50 text-blue-700 border-{{ $isRtl ? 'l' : 'r' }}-4 border-blue-600 shadow-sm' : 'text-gray-700 hover:bg-gray-50 border-{{ $isRtl ? 'l' : 'r' }}-4 border-transparent hover:border-gray-200'"
                                class="w-full flex items-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 {{ $isRtl ? 'flex-row-reverse text-right' : '' }}"
                                role="tab"
                                :aria-selected="activeTab === 'security'">
                            <i class="fas fa-shield-alt text-base mt-0.5" :class="activeTab === 'security' ? 'text-blue-600' : 'text-gray-400'"></i>
                            <div class="flex-1">
                                <div class="font-semibold">{{ __('settings.security') }}</div>
                                <div class="text-xs mt-0.5" :class="activeTab === 'security' ? 'text-blue-600' : 'text-gray-500'">{{ __('settings.update_password_secure') }}</div>
                            </div>
                        </button>
                    </li>
                </ul>

                {{-- Link to Organization Settings (Desktop) --}}
                <div class="px-3 py-3 border-t border-gray-200 bg-gray-50">
                    <a href="{{ route('orgs.settings.organization', $currentOrg) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-white hover:text-blue-600 transition-all group {{ $isRtl ? 'flex-row-reverse text-right' : '' }}">
                        <i class="fas fa-building text-base text-gray-400 group-hover:text-blue-600 transition"></i>
                        <span class="flex-1">{{ __('settings.organization_settings') }}</span>
                        <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} text-xs text-gray-400 group-hover:text-blue-600 group-hover:translate-{{ $isRtl ? '-' : '' }}x-1 transition-all"></i>
                    </a>
                </div>
            </nav>
        </div>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">
            {{-- Profile Section --}}
            <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white shadow-sm rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.profile_information') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('settings.update_personal_information') }}</p>
                    </div>
                    <form action="{{ route('orgs.settings.profile.update', $currentOrg) }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Avatar --}}
                        <div class="flex items-center gap-6 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="flex-shrink-0">
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-2xl font-bold text-white">
                                    {{ substr($user->name ?? 'U', 0, 1) }}
                                </div>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <h3 class="text-sm font-medium text-gray-900">{{ __('settings.profile_photo') }}</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ __('settings.jpg_png_gif_max_2mb') }}</p>
                                <button type="button" class="mt-2 px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition">
                                    {{ __('settings.change_photo') }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.full_name') }} *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 {{ $isRtl ? 'text-right' : '' }}">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="display_name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.display_name') }}</label>
                                <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $user->display_name ?? '') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.how_name_appears') }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.email_address') }} *</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            @error('email')
                                <p class="mt-1 text-xs text-red-600 {{ $isRtl ? 'text-right' : '' }}">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="locale" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.language') }}</label>
                                <select name="locale" id="locale" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                    <option value="ar" {{ ($userSettings['locale'] ?? 'ar') === 'ar' ? 'selected' : '' }}>{{ __('settings.arabic') }}</option>
                                    <option value="en" {{ ($userSettings['locale'] ?? 'ar') === 'en' ? 'selected' : '' }}>{{ __('settings.english') }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.timezone') }}</label>
                                <select name="timezone" id="timezone" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                    <option value="Asia/Bahrain" {{ ($userSettings['timezone'] ?? 'Asia/Bahrain') === 'Asia/Bahrain' ? 'selected' : '' }}>{{ __('settings.timezone_bahrain') }}</option>
                                    <option value="Asia/Dubai" {{ ($userSettings['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : '' }}>{{ __('settings.timezone_dubai') }}</option>
                                    <option value="Asia/Riyadh" {{ ($userSettings['timezone'] ?? '') === 'Asia/Riyadh' ? 'selected' : '' }}>{{ __('settings.timezone_riyadh') }}</option>
                                    <option value="Europe/London" {{ ($userSettings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' }}>{{ __('settings.timezone_london') }}</option>
                                    <option value="America/New_York" {{ ($userSettings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' }}>{{ __('settings.timezone_new_york') }}</option>
                                    <option value="UTC" {{ ($userSettings['timezone'] ?? '') === 'UTC' ? 'selected' : '' }}>{{ __('settings.timezone_utc') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex {{ $isRtl ? 'justify-start' : 'justify-end' }} pt-4 border-t border-gray-200">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                {{ __('settings.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Notifications Section --}}
            <div x-show="activeTab === 'notifications'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white shadow-sm rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.notification_preferences') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('settings.choose_notification_method') }}</p>
                    </div>
                    <form action="{{ route('orgs.settings.notifications.update', $currentOrg) }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Email Notifications --}}
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-4 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-envelope text-gray-400"></i>
                                <span>{{ __('settings.email_notifications') }}</span>
                            </h3>

                            <div class="space-y-4 {{ $isRtl ? 'mr-0' : 'mr-6' }}">
                                <label class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <input type="checkbox" name="notifications[email_campaign_alerts]" value="1"
                                           {{ ($notificationSettings['email_campaign_alerts'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="{{ $isRtl ? 'mr-0 ml-3 text-right' : 'mr-3' }}">
                                        <span class="text-sm font-medium text-gray-700">{{ __('settings.campaign_alerts') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('settings.campaign_alerts_desc') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <input type="checkbox" name="notifications[email_performance_reports]" value="1"
                                           {{ ($notificationSettings['email_performance_reports'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="{{ $isRtl ? 'mr-0 ml-3 text-right' : 'mr-3' }}">
                                        <span class="text-sm font-medium text-gray-700">{{ __('settings.performance_reports') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('settings.performance_reports_desc') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <input type="checkbox" name="notifications[email_budget_alerts]" value="1"
                                           {{ ($notificationSettings['email_budget_alerts'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="{{ $isRtl ? 'mr-0 ml-3 text-right' : 'mr-3' }}">
                                        <span class="text-sm font-medium text-gray-700">{{ __('settings.budget_alerts') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('settings.budget_alerts_desc') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <input type="checkbox" name="notifications[email_team_activity]" value="1"
                                           {{ ($notificationSettings['email_team_activity'] ?? false) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="{{ $isRtl ? 'mr-0 ml-3 text-right' : 'mr-3' }}">
                                        <span class="text-sm font-medium text-gray-700">{{ __('settings.team_activity') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('settings.team_activity_desc') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- In-App Notifications --}}
                        <div class="pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900 mb-4 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-bell text-gray-400"></i>
                                <span>{{ __('settings.in_app_notifications') }}</span>
                            </h3>

                            <div class="space-y-4 {{ $isRtl ? 'mr-0' : 'mr-6' }}">
                                <label class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <input type="checkbox" name="notifications[app_realtime_alerts]" value="1"
                                           {{ ($notificationSettings['app_realtime_alerts'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="{{ $isRtl ? 'mr-0 ml-3 text-right' : 'mr-3' }}">
                                        <span class="text-sm font-medium text-gray-700">{{ __('settings.realtime_alerts') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('settings.realtime_alerts_desc') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <input type="checkbox" name="notifications[app_sound]" value="1"
                                           {{ ($notificationSettings['app_sound'] ?? false) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="{{ $isRtl ? 'mr-0 ml-3 text-right' : 'mr-3' }}">
                                        <span class="text-sm font-medium text-gray-700">{{ __('settings.sound_notifications') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('settings.sound_notifications_desc') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex {{ $isRtl ? 'justify-start' : 'justify-end' }} pt-4 border-t border-gray-200">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                {{ __('settings.save_preferences') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Security Section --}}
            <div x-show="activeTab === 'security'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-6">
                    {{-- Change Password --}}
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.change_password') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('settings.update_password_secure') }}</p>
                        </div>
                        <form action="{{ route('orgs.settings.password.update', $currentOrg) }}" method="POST" class="p-6 space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.current_password') }}</label>
                                <input type="password" name="current_password" id="current_password" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                @error('current_password')
                                    <p class="mt-1 text-xs text-red-600 {{ $isRtl ? 'text-right' : '' }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.new_password') }}</label>
                                <input type="password" name="password" id="password" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                                <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.minimum_8_characters') }}</p>
                                @error('password')
                                    <p class="mt-1 text-xs text-red-600 {{ $isRtl ? 'text-right' : '' }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('settings.confirm_new_password') }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            </div>

                            <div class="flex {{ $isRtl ? 'justify-start' : 'justify-end' }} pt-4 border-t border-gray-200">
                                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                    {{ __('settings.update_password') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Active Sessions --}}
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.active_sessions') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('settings.manage_active_sessions') }}</p>
                        </div>
                        <div class="p-6">
                            @forelse($sessions ?? [] as $session)
                                <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }} py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                    <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fas {{ str_contains($session->user_agent ?? '', 'Mobile') ? 'fa-mobile-alt' : 'fa-desktop' }} text-gray-500"></i>
                                        </div>
                                        <div class="{{ $isRtl ? 'mr-0 ml-4 text-right' : 'mr-4' }}">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $session->ip_address ?? __('settings.unknown_ip') }}
                                                @if($session->session_id === session()->getId())
                                                    <span class="{{ $isRtl ? 'ml-2' : 'mr-2' }} px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded-full">{{ __('settings.current') }}</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500">{{ __('settings.last_active') }}: {{ $session->last_activity ? \Carbon\Carbon::parse($session->last_activity)->diffForHumans() : __('settings.not_available') }}</p>
                                        </div>
                                    </div>
                                    @if($session->session_id !== session()->getId())
                                        <form action="{{ route('orgs.settings.sessions.destroy', [$currentOrg, $session->session_id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                                {{ __('settings.revoke') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 text-center py-4">{{ __('settings.no_active_sessions') }}</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Two-Factor Authentication --}}
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('settings.two_factor_authentication') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('settings.add_extra_security') }}</p>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <p class="text-sm text-gray-700">{{ __('settings.status') }}:
                                        <span class="font-medium {{ ($user->two_factor_enabled ?? false) ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ ($user->two_factor_enabled ?? false) ? __('settings.enabled') : __('settings.disabled') }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('settings.protect_account_2fa') }}</p>
                                </div>
                                <button type="button" class="px-4 py-2 text-sm font-medium {{ ($user->two_factor_enabled ?? false) ? 'text-red-600 border border-red-600 hover:bg-red-50' : 'text-blue-600 border border-blue-600 hover:bg-blue-50' }} rounded-lg transition">
                                    {{ ($user->two_factor_enabled ?? false) ? __('settings.disable_2fa') : __('settings.enable_2fa') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function userSettingsPage() {
    return {
        activeTab: '{{ request()->get('tab', 'profile') }}',

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
