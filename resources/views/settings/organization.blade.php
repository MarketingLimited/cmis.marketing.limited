@extends('layouts.app')

@section('title', __('Organization Settings'))

@section('content')
<div class="min-h-screen bg-gray-50" x-data="orgSettingsPage()">
    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <nav class="text-sm text-gray-500 mb-2">
                <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-gray-700">{{ __('Dashboard') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ __('Organization Settings') }}</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Organization Settings') }}</h1>
            <p class="mt-2 text-gray-600">{{ __('Manage your organization, team, and integrations') }}</p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 rounded-md bg-green-50 p-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="show = false" class="text-green-500 hover:text-green-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-md bg-red-50 p-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="show = false" class="text-red-500 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Sidebar Navigation --}}
            <div class="lg:w-56 flex-shrink-0">
                <nav class="bg-white shadow rounded-lg overflow-hidden sticky top-6">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Organization') }}</h3>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        <li>
                            <button @click="activeTab = 'general'"
                                    :class="activeTab === 'general' ? 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-600' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent'"
                                    class="w-full flex items-center px-4 py-3 text-sm font-medium transition-colors">
                                <i class="fas fa-building w-5 h-5 mr-3"></i>
                                {{ __('General') }}
                            </button>
                        </li>
                        <li>
                            <button @click="activeTab = 'team'"
                                    :class="activeTab === 'team' ? 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-600' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent'"
                                    class="w-full flex items-center px-4 py-3 text-sm font-medium transition-colors">
                                <i class="fas fa-users w-5 h-5 mr-3"></i>
                                {{ __('Team Members') }}
                            </button>
                        </li>
                        <li>
                            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                               class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 border-l-4 border-transparent transition-colors">
                                <i class="fas fa-plug w-5 h-5 mr-3"></i>
                                {{ __('Platform Connections') }}
                            </a>
                        </li>
                        <li>
                            <button @click="activeTab = 'api'"
                                    :class="activeTab === 'api' ? 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-600' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent'"
                                    class="w-full flex items-center px-4 py-3 text-sm font-medium transition-colors">
                                <i class="fas fa-code w-5 h-5 mr-3"></i>
                                {{ __('API Keys') }}
                            </button>
                        </li>
                        <li>
                            <button @click="activeTab = 'billing'"
                                    :class="activeTab === 'billing' ? 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-600' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent'"
                                    class="w-full flex items-center px-4 py-3 text-sm font-medium transition-colors">
                                <i class="fas fa-credit-card w-5 h-5 mr-3"></i>
                                {{ __('Billing') }}
                            </button>
                        </li>
                    </ul>

                    {{-- Link to User Settings --}}
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                        <a href="{{ route('orgs.settings.user', $currentOrg) }}" class="flex items-center text-sm text-gray-600 hover:text-indigo-600">
                            <i class="fas fa-user w-5 h-5 mr-2"></i>
                            {{ __('User Settings') }}
                            <i class="fas fa-arrow-right ml-auto text-xs"></i>
                        </a>
                    </div>
                </nav>
            </div>

            {{-- Main Content --}}
            <div class="flex-1 min-w-0">
                {{-- General Section --}}
                <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Organization Details') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Manage your organization information and preferences') }}</p>
                        </div>
                        <form action="{{ route('orgs.settings.organization.update', $currentOrg) }}" method="POST" class="p-6 space-y-6">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="org_name" class="block text-sm font-medium text-gray-700">{{ __('Organization Name') }} *</label>
                                <input type="text" name="org_name" id="org_name" value="{{ old('org_name', $organization->name ?? '') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700">{{ __('Default Currency') }}</label>
                                    <select name="currency" id="currency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="BHD" {{ ($organization->currency ?? 'BHD') === 'BHD' ? 'selected' : '' }}>BHD - Bahraini Dinar</option>
                                        <option value="USD" {{ ($organization->currency ?? '') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option value="EUR" {{ ($organization->currency ?? '') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                        <option value="SAR" {{ ($organization->currency ?? '') === 'SAR' ? 'selected' : '' }}>SAR - Saudi Riyal</option>
                                        <option value="AED" {{ ($organization->currency ?? '') === 'AED' ? 'selected' : '' }}>AED - UAE Dirham</option>
                                        <option value="KWD" {{ ($organization->currency ?? '') === 'KWD' ? 'selected' : '' }}>KWD - Kuwaiti Dinar</option>
                                        <option value="QAR" {{ ($organization->currency ?? '') === 'QAR' ? 'selected' : '' }}>QAR - Qatari Riyal</option>
                                        <option value="OMR" {{ ($organization->currency ?? '') === 'OMR' ? 'selected' : '' }}>OMR - Omani Rial</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="default_locale" class="block text-sm font-medium text-gray-700">{{ __('Default Language') }}</label>
                                    <select name="default_locale" id="default_locale" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="ar-BH" {{ ($organization->default_locale ?? 'ar-BH') === 'ar-BH' ? 'selected' : '' }}>العربية (البحرين)</option>
                                        <option value="ar-SA" {{ ($organization->default_locale ?? '') === 'ar-SA' ? 'selected' : '' }}>العربية (السعودية)</option>
                                        <option value="ar-AE" {{ ($organization->default_locale ?? '') === 'ar-AE' ? 'selected' : '' }}>العربية (الإمارات)</option>
                                        <option value="en-US" {{ ($organization->default_locale ?? '') === 'en-US' ? 'selected' : '' }}>English (US)</option>
                                        <option value="en-GB" {{ ($organization->default_locale ?? '') === 'en-GB' ? 'selected' : '' }}>English (UK)</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Organization Info Card --}}
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">{{ __('Organization Information') }}</h4>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500">{{ __('Organization ID') }}</dt>
                                        <dd class="mt-1 font-mono text-xs text-gray-700 bg-gray-100 px-2 py-1 rounded">{{ $currentOrg }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">{{ __('Created') }}</dt>
                                        <dd class="mt-1 text-gray-700">{{ $organization->created_at?->format('M d, Y') ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">{{ __('Team Members') }}</dt>
                                        <dd class="mt-1 text-gray-700">{{ $teamMembers->count() ?? 0 }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">{{ __('Active Campaigns') }}</dt>
                                        <dd class="mt-1 text-gray-700">{{ $activeCampaigns ?? 0 }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200">
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('Save Changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Team Members Section --}}
                <div x-show="activeTab === 'team'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ __('Team Members') }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ __('Manage who has access to this organization') }}</p>
                            </div>
                            <button @click="showInviteMemberModal = true" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                <i class="fas fa-user-plus mr-2"></i>{{ __('Invite Member') }}
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse($teamMembers ?? [] as $member)
                                <div class="flex items-center justify-between px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-medium text-indigo-600">
                                            {{ substr($member->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $member->name }}
                                                @if($member->user_id === auth()->id())
                                                    <span class="ml-1 text-xs text-gray-400">({{ __('You') }})</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">
                                            {{ $member->pivot->role_name ?? __('Member') }}
                                        </span>
                                        @if($member->user_id !== auth()->id())
                                            <form action="{{ route('orgs.settings.team.remove', [$currentOrg, $member->user_id]) }}" method="POST"
                                                  onsubmit="return confirm('{{ __('Are you sure you want to remove this member?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                                    {{ __('Remove') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-8 text-center">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-sm text-gray-500">{{ __('No team members found') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- API Keys Section --}}
                <div x-show="activeTab === 'api'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ __('API Keys') }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ __('Manage API keys for programmatic access') }}</p>
                            </div>
                            <button @click="showCreateApiKeyModal = true" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                <i class="fas fa-plus mr-2"></i>{{ __('Create API Key') }}
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse($apiTokens ?? [] as $token)
                                <div class="flex items-center justify-between px-6 py-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $token->name }}</p>
                                        <p class="text-xs text-gray-500 font-mono">{{ $token->token_prefix }}...</p>
                                        <div class="flex items-center mt-1 space-x-3 text-xs text-gray-500">
                                            <span>{{ __('Created') }}: {{ $token->created_at->format('M d, Y') }}</span>
                                            @if($token->last_used_at)
                                                <span>{{ __('Last used') }}: {{ $token->last_used_at->diffForHumans() }}</span>
                                            @endif
                                            @if($token->expires_at)
                                                <span class="{{ $token->expires_at->isPast() ? 'text-red-600' : '' }}">
                                                    {{ __('Expires') }}: {{ $token->expires_at->format('M d, Y') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $token->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $token->is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                        <form action="{{ route('orgs.settings.api-tokens.destroy', [$currentOrg, $token->token_id]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('{{ __('Are you sure you want to revoke this API key?') }}')"
                                                    class="text-sm text-red-600 hover:text-red-800">
                                                {{ __('Revoke') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-8 text-center">
                                    <i class="fas fa-key text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-sm text-gray-500">{{ __('No API keys yet') }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('Create an API key to access the CMIS API programmatically') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- API Documentation Link --}}
                    <div class="mt-6 bg-blue-50 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-book text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">{{ __('API Documentation') }}</h3>
                                <p class="mt-1 text-sm text-blue-700">
                                    {{ __('Learn how to use the CMIS API to automate your campaigns and analytics.') }}
                                    <a href="#" class="font-medium underline">{{ __('View Documentation') }}</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing Section --}}
                <div x-show="activeTab === 'billing'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="space-y-6">
                        {{-- Current Plan --}}
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">{{ __('Current Plan') }}</h2>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-2xl font-bold text-gray-900">{{ $currentPlan ?? 'Professional' }}</p>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('Your subscription renews on') }} {{ $renewalDate ?? 'January 1, 2026' }}</p>
                                    </div>
                                    <button class="px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-600 rounded-md hover:bg-indigo-50">
                                        {{ __('Upgrade Plan') }}
                                    </button>
                                </div>

                                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <p class="text-sm text-gray-500">{{ __('Campaigns') }}</p>
                                        <p class="text-xl font-semibold text-gray-900">{{ $usage['campaigns'] ?? 0 }} / {{ $limits['campaigns'] ?? 'Unlimited' }}</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <p class="text-sm text-gray-500">{{ __('Team Members') }}</p>
                                        <p class="text-xl font-semibold text-gray-900">{{ $usage['team_members'] ?? 1 }} / {{ $limits['team_members'] ?? 10 }}</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <p class="text-sm text-gray-500">{{ __('API Calls') }}</p>
                                        <p class="text-xl font-semibold text-gray-900">{{ number_format($usage['api_calls'] ?? 0) }} / {{ number_format($limits['api_calls'] ?? 100000) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Method --}}
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">{{ __('Payment Method') }}</h2>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fab fa-cc-visa text-3xl text-blue-600 mr-4"></i>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ __('Visa ending in') }} {{ $paymentMethod['last4'] ?? '4242' }}</p>
                                            <p class="text-xs text-gray-500">{{ __('Expires') }} {{ $paymentMethod['exp_month'] ?? '12' }}/{{ $paymentMethod['exp_year'] ?? '2025' }}</p>
                                        </div>
                                    </div>
                                    <button class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Update') }}</button>
                                </div>
                            </div>
                        </div>

                        {{-- Billing History --}}
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">{{ __('Billing History') }}</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Description') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Amount') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Invoice') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($invoices ?? [] as $invoice)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $invoice->date }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->description }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $invoice->amount }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ $invoice->status }}</span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                    <a href="#" class="text-indigo-600 hover:text-indigo-800">{{ __('Download') }}</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                                    {{ __('No invoices yet') }}
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
    </div>

    {{-- Create API Key Modal --}}
    <div x-show="showCreateApiKeyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showCreateApiKeyModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 @click="showCreateApiKeyModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div x-show="showCreateApiKeyModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('orgs.settings.api-tokens.store', $currentOrg) }}" method="POST">
                    @csrf
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Create New API Key') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Give your API key a name and select the permissions it needs.') }}</p>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="token_name" class="block text-sm font-medium text-gray-700">{{ __('Key Name') }} *</label>
                            <input type="text" name="name" id="token_name" required
                                   placeholder="{{ __('e.g., Production Server, CI/CD Pipeline') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Permissions') }}</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
                                @foreach(\App\Models\Core\ApiToken::getAvailableScopes() as $scope => $description)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="scopes[]" value="{{ $scope }}"
                                               class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $description }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700">{{ __('Expiration (Optional)') }}</label>
                            <input type="date" name="expires_at" id="expires_at"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">{{ __('Leave empty for no expiration') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showCreateApiKeyModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                            {{ __('Create Key') }}
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
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('orgs.settings.team.invite', $currentOrg) }}" method="POST">
                    @csrf
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Invite Team Member') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Send an invitation to join your organization.') }}</p>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="invite_email" class="block text-sm font-medium text-gray-700">{{ __('Email Address') }} *</label>
                            <input type="email" name="email" id="invite_email" required
                                   placeholder="colleague@example.com"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="invite_role" class="block text-sm font-medium text-gray-700">{{ __('Role') }}</label>
                            <select name="role_id" id="invite_role"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($roles ?? [] as $role)
                                    <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showInviteMemberModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                            {{ __('Send Invitation') }}
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

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
