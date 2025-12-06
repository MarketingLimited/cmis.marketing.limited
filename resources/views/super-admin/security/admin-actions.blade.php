@extends('super-admin.layouts.app')

@section('title', __('super_admin.security.admin_actions'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.security.index') }}" class="text-gray-500 hover:text-red-600">{{ __('super_admin.security.title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.security.admin_actions') }}</span>
@endsection

@section('content')
<div x-data="adminActions()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-shield text-purple-600 dark:text-purple-400"></i>
                </div>
                {{ __('super_admin.security.admin_actions') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.security.admin_actions_subtitle') }}</p>
        </div>

        <a href="{{ route('super-admin.security.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
            {{ __('super_admin.actions.back') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.admin') }}</label>
                <select name="admin_id" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="">{{ __('super_admin.security.all_admins') }}</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->user_id }}" {{ request('admin_id') === $admin->user_id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.action_type') }}</label>
                <select name="action_type" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                    <option value="">{{ __('super_admin.security.all_action_types') }}</option>
                    @foreach($actionTypes as $type)
                        <option value="{{ $type }}" {{ request('action_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('super_admin.security.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('super_admin.security.search_actions_placeholder') }}" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-search me-2"></i>{{ __('super_admin.common.filter') }}
                </button>
                <a href="{{ route('super-admin.security.admin-actions') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Actions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.timestamp') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.admin') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.action_type') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.target') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.security.ip_address') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">{{ __('super_admin.actions.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($actions as $action)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($action->created_at)->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user-shield text-purple-600 dark:text-purple-400 text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $action->admin_name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $action->admin_email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if(str_contains($action->action_type, 'create')) bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif(str_contains($action->action_type, 'update') || str_contains($action->action_type, 'edit')) bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif(str_contains($action->action_type, 'delete') || str_contains($action->action_type, 'remove')) bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @elseif(str_contains($action->action_type, 'login') || str_contains($action->action_type, 'view')) bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                    @else bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $action->action_type)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $action->target_name ?? '-' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $action->target_type ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 font-mono">
                                {{ $action->ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="showActionDetails({{ json_encode($action) }})" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-user-shield text-4xl mb-2 opacity-50"></i>
                                <p>{{ __('super_admin.security.no_admin_actions') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($actions->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $actions->links() }}
            </div>
        @endif
    </div>

    <!-- Action Details Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full mx-auto shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.security.action_details') }}</h3>
                    <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div x-html="modalContent" class="text-start"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminActions() {
    return {
        showModal: false,
        modalContent: '',

        showActionDetails(action) {
            let metadata = '';
            if (action.metadata && Object.keys(action.metadata).length > 0) {
                metadata = `
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('super_admin.security.metadata') }}</h4>
                        <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-3 rounded overflow-x-auto">${JSON.stringify(action.metadata, null, 2)}</pre>
                    </div>
                `;
            }

            this.modalContent = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.action_type') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${action.action_type.replace(/_/g, ' ')}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.timestamp') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${action.created_at}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.admin') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${action.admin_name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${action.admin_email}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.ip_address') }}</p>
                            <p class="text-sm font-mono text-gray-900 dark:text-white">${action.ip_address || '-'}</p>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.target_type') }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">${action.target_type || '-'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.target_id') }}</p>
                                <p class="text-sm font-mono text-gray-900 dark:text-white">${action.target_id || '-'}</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.target_name') }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${action.target_name || '-'}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.user_agent') }}</p>
                        <p class="text-sm text-gray-900 dark:text-white break-all">${action.user_agent || '-'}</p>
                    </div>
                    ${metadata}
                </div>
            `;
            this.showModal = true;
        }
    };
}
</script>
@endpush
