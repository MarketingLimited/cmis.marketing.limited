{{-- Manage Groups Modal --}}
<div x-show="showGroupsModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="groups-modal-title"
     role="dialog"
     aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showGroupsModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             @click="showGroupsModal = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="showGroupsModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" id="groups-modal-title">
                        {{ __('profiles.manage_groups') }}
                    </h3>
                    <button @click="showGroupsModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Current Group Display --}}
                    @if($profile->profileGroup)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">{{ __('profiles.profile_group') }}</p>
                            <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold"
                                     style="background-color: {{ $profile->profileGroup->color ?? '#3B82F6' }}">
                                    {{ strtoupper(substr($profile->profileGroup->name, 0, 1)) }}
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $profile->profileGroup->name }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Group Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $profile->profileGroup ? __('profiles.select_group') : __('profiles.profile_group') }}
                        </label>
                        <select x-model="selectedGroupId"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('profiles.no_groups') }}</option>
                            @foreach($profileGroups as $group)
                                <option value="{{ $group->group_id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Available Groups List --}}
                    @if($profileGroups->count() > 0)
                        <div>
                            <p class="text-sm text-gray-600 mb-2">{{ __('profiles.all_groups') }}</p>
                            <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-200">
                                @foreach($profileGroups as $group)
                                    <label class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer transition">
                                        <input type="radio"
                                               x-model="selectedGroupId"
                                               value="{{ $group->group_id }}"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold"
                                             style="background-color: {{ $group->color ?? '#3B82F6' }}">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $group->name }}</p>
                                            @if($group->client_country)
                                                <p class="text-xs text-gray-500">{{ $group->client_country }}</p>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                <i class="fas fa-layer-group text-gray-400"></i>
                            </div>
                            <p class="text-sm text-gray-500">{{ __('profiles.no_groups') }}</p>
                            <a href="{{ route('orgs.settings.profile-groups.index', $currentOrg) }}"
                               class="inline-block mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                {{ __('profiles.manage_profile_groups') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                        @click="assignToGroup()"
                        :disabled="loading"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm disabled:opacity-50">
                    <span x-show="loading" class="me-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                    {{ __('profiles.save') }}
                </button>
                <button type="button"
                        @click="showGroupsModal = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    {{ __('profiles.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
