@extends('layouts.admin')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', __('workflows.manage_workflows'))
@section('page-subtitle', __('workflows.track_workflows'))

@section('content')
<div x-data="workflowManager()" x-init="init()">
    <!-- Header with Action -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ __('workflows.active_workflows') }}</h2>
            <p class="text-gray-600 mt-1">{{ __('common.total') }}: <span class="font-medium" x-text="workflows.length"></span></p>
        </div>
        <button @click="showNewWorkflowModal = true"
                class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition">
            <i class="fas fa-plus ms-2"></i>
            {{ __('workflows.new_workflow') }}
        </button>
    </div>

    <!-- Workflows Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <template x-for="workflow in workflows" :key="workflow.flow_id">
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 text-white">
                            <h3 class="text-xl font-bold mb-2" x-text="workflow.flow_name"></h3>
                            <p class="text-indigo-100 text-sm" x-text="workflow.entity_type"></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-green-100 text-green-800': workflow.status === 'completed',
                                  'bg-yellow-100 text-yellow-800': workflow.status === 'in_progress',
                                  'bg-gray-100 text-gray-800': workflow.status === 'pending'
                              }">
                            <span x-text="getStatusLabel(workflow.status)"></span>
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-white text-sm mb-2">
                            <span>{{ __('workflows.progress') }}</span>
                            <span x-text="`${workflow.completed_steps}/${workflow.total_steps}`"></span>
                        </div>
                        <div class="w-full bg-white/20 rounded-full h-2">
                            <div class="bg-white rounded-full h-2 transition-all"
                                 :style="`width: ${(workflow.completed_steps / workflow.total_steps) * 100}%`"></div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Info -->
                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <p class="text-gray-600 mb-1">{{ __('workflows.start_date') }}</p>
                            <p class="font-medium text-gray-900" x-text="formatDate(workflow.created_at)"></p>
                        </div>
                        <div>
                            <p class="text-gray-600 mb-1">{{ __('workflows.last_update') }}</p>
                            <p class="font-medium text-gray-900" x-text="formatDate(workflow.updated_at)"></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t">
                        <a :href="'/orgs/{{ $currentOrg }}/workflows/' + workflow.flow_id"
                           class="flex-1 bg-indigo-50 text-indigo-600 text-center py-2 rounded-lg font-medium hover:bg-indigo-100 transition">
                            <i class="fas fa-eye ms-2"></i>
                            {{ __('common.view_details') }}
                        </a>
                        <button @click="deleteWorkflow(workflow.flow_id)"
                                class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="workflows.length === 0">
        <x-empty-state
            icon="fas fa-project-diagram"
            :title="__('workflows.no_workflows')"
            :description="__('workflows.start_creating')"
            :action-text="__('workflows.create_workflow')"
            action-click="showNewWorkflowModal = true"
        />
    </template>

    <!-- New Workflow Modal -->
    <div x-show="showNewWorkflowModal" @click.away="showNewWorkflowModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ __('workflows.new_workflow') }}</h3>
            <form @submit.prevent="createWorkflow">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('workflows.workflow_type') }}</label>
                        <select x-model="newWorkflow.type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">{{ __('workflows.select_type') }}</option>
                            <option value="campaign">{{ __('workflows.campaign_type') }}</option>
                            <option value="content">{{ __('workflows.content_type') }}</option>
                            <option value="creative">{{ __('workflows.creative_type') }}</option>
                        </select>
                    </div>

                    <template x-if="newWorkflow.type === 'campaign'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('workflows.campaign') }}</label>
                            <select x-model="newWorkflow.campaign_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">{{ __('workflows.select_campaign') }}</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('workflows.workflow_name') }}</label>
                        <input type="text" x-model="newWorkflow.name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                class="flex-1 bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700">
                            <i class="fas fa-check ms-2"></i>
                            {{ __('common.create') }}
                        </button>
                        <button type="button" @click="showNewWorkflowModal = false"
                                class="flex-1 bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-medium hover:bg-gray-300">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function workflowManager() {
    return {
        workflows: @json($workflows ?? []),
        showNewWorkflowModal: false,
        newWorkflow: {
            type: '',
            campaign_id: '',
            name: ''
        },

        init() {
            // Initialize
        },

        getStatusLabel(status) {
            const labels = {
                'pending': '{{ __('common.pending') }}',
                'in_progress': '{{ __('common.in_progress') }}',
                'completed': '{{ __('common.completed') }}',
                'cancelled': '{{ __('workflows.cancelled') }}'
            };
            return labels[status] || status;
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        async createWorkflow() {
            if (this.newWorkflow.type === 'campaign' && this.newWorkflow.campaign_id) {
                try {
                    const response = await fetch('/workflows/initialize-campaign', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            campaign_id: this.newWorkflow.campaign_id,
                            campaign_name: this.newWorkflow.name
                        })
                    });

                    if (response.ok) {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Failed to create workflow:', error);
                    alert('{{ __('workflows.create_failed') }}');
                }
            }
        },

        async deleteWorkflow(flowId) {
            if (!confirm('{{ __('workflows.delete_confirm') }}')) return;

            try {
                const response = await fetch(`/api/workflows/${flowId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    // Remove workflow from the list
                    this.workflows = this.workflows.filter(w => w.flow_id !== flowId);

                    // Show success notification
                    if (window.notify) {
                        window.notify('{{ __('workflows.delete_success') }}', 'success');
                    } else {
                        alert('{{ __('workflows.delete_success') }}');
                    }
                } else {
                    const error = await response.json();
                    const errorMsg = error.message || '{{ __('workflows.delete_failed') }}';

                    if (window.notify) {
                        window.notify(errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
                }
            } catch (error) {
                console.error('Error deleting workflow:', error);
                const errorMsg = '{{ __('workflows.delete_error') }}';

                if (window.notify) {
                    window.notify(errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            }
        }
    };
}
</script>
@endpush
