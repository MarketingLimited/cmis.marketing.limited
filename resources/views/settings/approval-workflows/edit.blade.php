@extends('layouts.admin')

@section('title', __('Edit') . ' ' . $workflow->name . ' - ' . __('Approval Workflows'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.approval-workflows.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Approval Workflows') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.approval-workflows.show', [$currentOrg, $workflow->workflow_id]) }}" class="hover:text-blue-600 transition">{{ $workflow->name }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Edit') }}</span>
        </nav>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Edit Approval Workflow</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Update approval steps and workflow settings.
            </p>
        </div>
    </div>

    <form action="{{ route('orgs.settings.approval-workflows.update', [$currentOrg, $workflow->workflow_id]) }}" method="POST" class="space-y-6"
          x-data="{
              steps: {{ json_encode(old('steps', $workflow->approval_steps ?? [['role' => '', 'users' => [], 'required_approvals' => 1]])) }},
              addStep() {
                  this.steps.push({ role: '', users: [], required_approvals: 1 });
              },
              removeStep(index) {
                  if (this.steps.length > 1) {
                      this.steps.splice(index, 1);
                  }
              }
          }">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-indigo-500 mr-2"></i>Basic Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Workflow Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $workflow->name) }}" required
                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="2"
                              class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $workflow->description) }}</textarea>
                </div>

                <div>
                    <label for="profile_group_id" class="block text-sm font-medium text-gray-700 mb-1">Profile Group</label>
                    <select name="profile_group_id" id="profile_group_id"
                            class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Profile Groups</option>
                        @foreach($profileGroups ?? [] as $group)
                            <option value="{{ $group->group_id }}" {{ old('profile_group_id', $workflow->profile_group_id) == $group->group_id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="trigger_condition" class="block text-sm font-medium text-gray-700 mb-1">Trigger Condition</label>
                    <select name="trigger_condition" id="trigger_condition"
                            class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all_posts" {{ old('trigger_condition', $workflow->trigger_condition) == 'all_posts' ? 'selected' : '' }}>All Posts</option>
                        <option value="scheduled_posts" {{ old('trigger_condition', $workflow->trigger_condition) == 'scheduled_posts' ? 'selected' : '' }}>Scheduled Posts Only</option>
                        <option value="external_links" {{ old('trigger_condition', $workflow->trigger_condition) == 'external_links' ? 'selected' : '' }}>Posts with External Links</option>
                        <option value="mentions" {{ old('trigger_condition', $workflow->trigger_condition) == 'mentions' ? 'selected' : '' }}>Posts with Mentions</option>
                        <option value="media_posts" {{ old('trigger_condition', $workflow->trigger_condition) == 'media_posts' ? 'selected' : '' }}>Posts with Media</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Approval Steps --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">
                    <i class="fas fa-tasks text-indigo-500 mr-2"></i>Approval Steps
                </h3>
                <button type="button" @click="addStep()"
                        class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-1"></i>Add Step
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(step, index) in steps" :key="index">
                    <div class="relative p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="absolute -left-3 top-4 w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-xs font-bold"
                             x-text="index + 1"></div>

                        <div class="ml-4">
                            <div class="flex items-start justify-between mb-4">
                                <h4 class="text-sm font-medium text-gray-900">Step <span x-text="index + 1"></span></h4>
                                <button type="button" @click="removeStep(index)" x-show="steps.length > 1"
                                        class="text-gray-400 hover:text-red-500 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Approver Role</label>
                                    <select x-model="step.role" :name="'steps[' + index + '][role]'"
                                            class="w-full text-sm rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Any Team Member</option>
                                        <option value="manager">Manager</option>
                                        <option value="admin">Admin</option>
                                        <option value="content_lead">Content Lead</option>
                                        <option value="legal">Legal/Compliance</option>
                                        <option value="executive">Executive</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Specific Users</label>
                                    <select :name="'steps[' + index + '][users][]'" multiple
                                            class="w-full text-sm rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach($teamMembers ?? [] as $member)
                                            <option value="{{ $member->user_id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Required Approvals</label>
                                    <input type="number" x-model="step.required_approvals" :name="'steps[' + index + '][required_approvals]'"
                                           min="1" max="10"
                                           class="w-full text-sm rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Settings --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-cog text-gray-500 mr-2"></i>Workflow Settings
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="auto_approve_after_hours" class="block text-sm font-medium text-gray-700 mb-1">Auto-Approve After (hours)</label>
                        <input type="number" name="auto_approve_after_hours" id="auto_approve_after_hours"
                               value="{{ old('auto_approve_after_hours', $workflow->auto_approve_after_hours) }}" min="0" max="168"
                               class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Leave empty to disable">
                    </div>

                    <div>
                        <label for="reminder_hours" class="block text-sm font-medium text-gray-700 mb-1">Send Reminder After (hours)</label>
                        <input type="number" name="reminder_hours" id="reminder_hours"
                               value="{{ old('reminder_hours', $workflow->settings['reminder_hours'] ?? 24) }}" min="1" max="72"
                               class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Allow Skip Step</p>
                        <p class="text-xs text-gray-500">Higher-level approvers can skip lower steps</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="allow_skip" value="1" {{ old('allow_skip', $workflow->settings['allow_skip'] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Require Rejection Reason</p>
                        <p class="text-xs text-gray-500">Approvers must provide a reason when rejecting</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="require_rejection_reason" value="1" {{ old('require_rejection_reason', $workflow->settings['require_rejection_reason'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Notify Creator on Approval</p>
                        <p class="text-xs text-gray-500">Send notification at each approval step</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="notify_on_approval" value="1" {{ old('notify_on_approval', $workflow->settings['notify_on_approval'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Workflow Status</h3>
                    <p class="text-sm text-gray-500">Enable or disable this workflow</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $workflow->is_active) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <form action="{{ route('orgs.settings.approval-workflows.destroy', [$currentOrg, $workflow->workflow_id]) }}" method="POST"
                  onsubmit="return confirm('Delete this workflow? This action cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-red-600 hover:text-red-700 text-sm font-medium">
                    <i class="fas fa-trash mr-1"></i>Delete Workflow
                </button>
            </form>

            <div class="flex items-center gap-3">
                <a href="{{ route('orgs.settings.approval-workflows.show', [$currentOrg, $workflow->workflow_id]) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
