@extends('layouts.admin')

@section('title', __('User Settings'))

@section('content')
<div x-data="userSettingsPage()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('User Settings') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('User Settings') }}</h1>
        <p class="mt-1 text-gray-600">{{ __('Manage your personal account settings and preferences') }}</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-r-4 border-green-500 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
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
        <div class="mb-6 bg-red-50 border-r-4 border-red-500 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    <p class="text-red-800 font-medium">{{ session('error') }}</p>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Sidebar Navigation --}}
        <div class="lg:w-56 flex-shrink-0">
            <nav class="bg-white shadow-sm rounded-xl overflow-hidden sticky top-24">
                <div class="px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-user-cog"></i>
                        {{ __('User Settings') }}
                    </h3>
                </div>
                <ul class="divide-y divide-gray-100">
                    <li>
                        <button @click="activeTab = 'profile'"
                                :class="activeTab === 'profile' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600' : 'text-gray-700 hover:bg-gray-50 border-r-4 border-transparent'"
                                class="w-full flex items-center px-4 py-3 text-sm font-medium transition-colors">
                            <i class="fas fa-user w-5 ml-3"></i>
                            {{ __('Profile') }}
                        </button>
                    </li>
                    <li>
                        <button @click="activeTab = 'notifications'"
                                :class="activeTab === 'notifications' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600' : 'text-gray-700 hover:bg-gray-50 border-r-4 border-transparent'"
                                class="w-full flex items-center px-4 py-3 text-sm font-medium transition-colors">
                            <i class="fas fa-bell w-5 ml-3"></i>
                            {{ __('Notifications') }}
                        </button>
                    </li>
                    <li>
                        <button @click="activeTab = 'security'"
                                :class="activeTab === 'security' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600' : 'text-gray-700 hover:bg-gray-50 border-r-4 border-transparent'"
                                class="w-full flex items-center px-4 py-3 text-sm font-medium transition-colors">
                            <i class="fas fa-shield-alt w-5 ml-3"></i>
                            {{ __('Security') }}
                        </button>
                    </li>
                </ul>

                {{-- Link to Organization Settings --}}
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <a href="{{ route('orgs.settings.organization', $currentOrg) }}" class="flex items-center text-sm text-gray-600 hover:text-blue-600 transition">
                        <i class="fas fa-building w-5 ml-2"></i>
                        {{ __('Organization Settings') }}
                        <i class="fas fa-arrow-left mr-auto text-xs"></i>
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
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Profile Information') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Update your personal information and preferences') }}</p>
                    </div>
                    <form action="{{ route('orgs.settings.profile.update', $currentOrg) }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Avatar --}}
                        <div class="flex items-center gap-6">
                            <div class="flex-shrink-0">
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-2xl font-bold text-white">
                                    {{ substr($user->name ?? 'U', 0, 1) }}
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">{{ __('Profile Photo') }}</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ __('JPG, PNG or GIF. Max 2MB') }}</p>
                                <button type="button" class="mt-2 px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition">
                                    {{ __('Change Photo') }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Full Name') }} *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="display_name" class="block text-sm font-medium text-gray-700">{{ __('Display Name') }}</label>
                                <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $user->display_name ?? '') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">{{ __('How your name appears to others') }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email Address') }} *</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="locale" class="block text-sm font-medium text-gray-700">{{ __('Language') }}</label>
                                <select name="locale" id="locale" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="ar" {{ ($userSettings['locale'] ?? 'ar') === 'ar' ? 'selected' : '' }}>العربية</option>
                                    <option value="en" {{ ($userSettings['locale'] ?? 'ar') === 'en' ? 'selected' : '' }}>English</option>
                                </select>
                            </div>
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700">{{ __('Timezone') }}</label>
                                <select name="timezone" id="timezone" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="Asia/Bahrain" {{ ($userSettings['timezone'] ?? 'Asia/Bahrain') === 'Asia/Bahrain' ? 'selected' : '' }}>Asia/Bahrain (GMT+3)</option>
                                    <option value="Asia/Dubai" {{ ($userSettings['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : '' }}>Asia/Dubai (GMT+4)</option>
                                    <option value="Asia/Riyadh" {{ ($userSettings['timezone'] ?? '') === 'Asia/Riyadh' ? 'selected' : '' }}>Asia/Riyadh (GMT+3)</option>
                                    <option value="Europe/London" {{ ($userSettings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT+0)</option>
                                    <option value="America/New_York" {{ ($userSettings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' }}>America/New_York (GMT-5)</option>
                                    <option value="UTC" {{ ($userSettings['timezone'] ?? '') === 'UTC' ? 'selected' : '' }}>UTC</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Notifications Section --}}
            <div x-show="activeTab === 'notifications'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white shadow-sm rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Notification Preferences') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Choose how and when you want to be notified') }}</p>
                    </div>
                    <form action="{{ route('orgs.settings.notifications.update', $currentOrg) }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Email Notifications --}}
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-4 flex items-center gap-2">
                                <i class="fas fa-envelope text-gray-400"></i>
                                {{ __('Email Notifications') }}
                            </h3>

                            <div class="space-y-4 mr-6">
                                <label class="flex items-start">
                                    <input type="checkbox" name="notifications[email_campaign_alerts]" value="1"
                                           {{ ($notificationSettings['email_campaign_alerts'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="mr-3">
                                        <span class="text-sm font-medium text-gray-700">{{ __('Campaign alerts') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('Get notified when campaigns start, end, or need attention') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start">
                                    <input type="checkbox" name="notifications[email_performance_reports]" value="1"
                                           {{ ($notificationSettings['email_performance_reports'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="mr-3">
                                        <span class="text-sm font-medium text-gray-700">{{ __('Performance reports') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('Weekly and monthly performance summaries') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start">
                                    <input type="checkbox" name="notifications[email_budget_alerts]" value="1"
                                           {{ ($notificationSettings['email_budget_alerts'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="mr-3">
                                        <span class="text-sm font-medium text-gray-700">{{ __('Budget alerts') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('Get notified when budgets are running low') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start">
                                    <input type="checkbox" name="notifications[email_team_activity]" value="1"
                                           {{ ($notificationSettings['email_team_activity'] ?? false) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="mr-3">
                                        <span class="text-sm font-medium text-gray-700">{{ __('Team activity') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('Updates when team members make changes') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- In-App Notifications --}}
                        <div class="pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900 mb-4 flex items-center gap-2">
                                <i class="fas fa-bell text-gray-400"></i>
                                {{ __('In-App Notifications') }}
                            </h3>

                            <div class="space-y-4 mr-6">
                                <label class="flex items-start">
                                    <input type="checkbox" name="notifications[app_realtime_alerts]" value="1"
                                           {{ ($notificationSettings['app_realtime_alerts'] ?? true) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="mr-3">
                                        <span class="text-sm font-medium text-gray-700">{{ __('Real-time alerts') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('Show notifications in the app as they happen') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start">
                                    <input type="checkbox" name="notifications[app_sound]" value="1"
                                           {{ ($notificationSettings['app_sound'] ?? false) ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="mr-3">
                                        <span class="text-sm font-medium text-gray-700">{{ __('Sound notifications') }}</span>
                                        <p class="text-xs text-gray-500">{{ __('Play a sound for important alerts') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                {{ __('Save Preferences') }}
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
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Change Password') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Update your password to keep your account secure') }}</p>
                        </div>
                        <form action="{{ route('orgs.settings.password.update', $currentOrg) }}" method="POST" class="p-6 space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">{{ __('Current Password') }}</label>
                                <input type="password" name="current_password" id="current_password" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('current_password')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('New Password') }}</label>
                                <input type="password" name="password" id="password" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">{{ __('Minimum 8 characters') }}</p>
                                @error('password')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm New Password') }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200">
                                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                    {{ __('Update Password') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Active Sessions --}}
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Active Sessions') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Manage your active sessions across devices') }}</p>
                        </div>
                        <div class="p-6">
                            @forelse($sessions ?? [] as $session)
                                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fas {{ str_contains($session->user_agent ?? '', 'Mobile') ? 'fa-mobile-alt' : 'fa-desktop' }} text-gray-500"></i>
                                        </div>
                                        <div class="mr-4">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $session->ip_address ?? 'Unknown IP' }}
                                                @if($session->session_id === session()->getId())
                                                    <span class="mr-2 px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded-full">{{ __('Current') }}</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500">{{ __('Last active') }}: {{ $session->last_activity ? \Carbon\Carbon::parse($session->last_activity)->diffForHumans() : 'N/A' }}</p>
                                        </div>
                                    </div>
                                    @if($session->session_id !== session()->getId())
                                        <form action="{{ route('orgs.settings.sessions.destroy', [$currentOrg, $session->session_id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                                {{ __('Revoke') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 text-center py-4">{{ __('No active sessions found') }}</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Two-Factor Authentication --}}
                    <div class="bg-white shadow-sm rounded-xl">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Two-Factor Authentication') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Add an extra layer of security to your account') }}</p>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">{{ __('Status') }}:
                                        <span class="font-medium {{ ($user->two_factor_enabled ?? false) ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ ($user->two_factor_enabled ?? false) ? __('Enabled') : __('Disabled') }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('Protect your account with TOTP-based 2FA') }}</p>
                                </div>
                                <button type="button" class="px-4 py-2 text-sm font-medium {{ ($user->two_factor_enabled ?? false) ? 'text-red-600 border border-red-600 hover:bg-red-50' : 'text-blue-600 border border-blue-600 hover:bg-blue-50' }} rounded-lg transition">
                                    {{ ($user->two_factor_enabled ?? false) ? __('Disable 2FA') : __('Enable 2FA') }}
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
@endsection
