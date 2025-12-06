@extends('super-admin.layouts.app')

@section('title', __('super_admin.security.ip_blacklist'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.security.index') }}" class="text-gray-500 hover:text-red-600">{{ __('super_admin.security.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.security.ip_blacklist') }}</span>
@endsection

@section('content')
<div x-data="ipBlacklist()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
                </div>
                {{ __('super_admin.security.ip_blacklist') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.security.ip_blacklist_subtitle') }}</p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('super-admin.security.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                {{ __('super_admin.actions.back') }}
            </a>
            <button @click="showBlockModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-plus"></i>
                {{ __('super_admin.security.block_ip') }}
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.security.total_blocked') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                    <i class="fas fa-infinity text-gray-600 dark:text-gray-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.security.permanent_blocks') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['permanent']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.security.temporary_blocks') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['temporary']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Blacklist Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.ip_address') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.reason') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.blocked_by') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.blocked_at') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.expires') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.actions.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($blacklist as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-ban text-red-600 dark:text-red-400 text-xs"></i>
                                    </div>
                                    <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $entry->ip_address }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ Str::limit($entry->reason, 50) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ $entry->blocked_by_name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($entry->created_at)->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($entry->blocked_until)
                                    @if(\Carbon\Carbon::parse($entry->blocked_until)->isPast())
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('super_admin.security.expired') }}</span>
                                    @else
                                        <span class="text-sm text-yellow-600 dark:text-yellow-400">
                                            {{ \Carbon\Carbon::parse($entry->blocked_until)->diffForHumans() }}
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <i class="fas fa-infinity"></i>
                                        {{ __('super_admin.security.permanent') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="unblockIp('{{ $entry->blacklist_id }}', '{{ $entry->ip_address }}')" class="p-2 text-green-400 hover:text-green-600 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors" title="{{ __('super_admin.security.unblock') }}">
                                    <i class="fas fa-unlock"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-check-circle text-4xl mb-2 text-green-500 opacity-50"></i>
                                <p>{{ __('super_admin.security.no_blocked_ips') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($blacklist->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $blacklist->links() }}
            </div>
        @endif
    </div>

    <!-- Block IP Modal -->
    <div x-show="showBlockModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-black/50" @click="showBlockModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl max-w-md w-full mx-auto shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.security.block_ip') }}</h3>
                    <button @click="showBlockModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('super-admin.security.block-ip') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.ip_address') }} *</label>
                            <input type="text" name="ip_address" required pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$" placeholder="192.168.1.1" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.reason') }} *</label>
                            <textarea name="reason" required rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm" placeholder="{{ __('super_admin.security.reason_placeholder') }}"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.duration') }}</label>
                            <select name="duration" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                                <option value="">{{ __('super_admin.security.permanent') }}</option>
                                <option value="1">1 {{ __('super_admin.security.hour') }}</option>
                                <option value="6">6 {{ __('super_admin.security.hours') }}</option>
                                <option value="24">24 {{ __('super_admin.security.hours') }}</option>
                                <option value="168">7 {{ __('super_admin.security.days') }}</option>
                                <option value="720">30 {{ __('super_admin.security.days') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" @click="showBlockModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                            {{ __('super_admin.common.cancel') }}
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            {{ __('super_admin.security.block_ip') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Unblock Confirmation Modal -->
    <div x-show="showUnblockModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-black/50" @click="showUnblockModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl max-w-md w-full mx-auto shadow-xl p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-unlock text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('super_admin.security.confirm_unblock') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        {{ __('super_admin.security.unblock_confirm_message') }}
                        <span class="font-mono font-bold" x-text="unblockIpAddress"></span>?
                    </p>
                    <div class="flex gap-3">
                        <button @click="showUnblockModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                            {{ __('super_admin.common.cancel') }}
                        </button>
                        <form :action="`{{ url('super-admin/security/ip-blacklist') }}/${unblockId}/unblock`" method="POST" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                {{ __('super_admin.security.unblock') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ipBlacklist() {
    return {
        showBlockModal: false,
        showUnblockModal: false,
        unblockId: null,
        unblockIpAddress: '',

        unblockIp(id, ipAddress) {
            this.unblockId = id;
            this.unblockIpAddress = ipAddress;
            this.showUnblockModal = true;
        }
    };
}
</script>
@endpush
