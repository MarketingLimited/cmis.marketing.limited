{{-- Profile Groups & Profiles Selection (Responsive width) --}}
<div class="w-full lg:w-80 flex-shrink-0 bg-white flex flex-col">

    {{-- STEP 1: Profile Groups Selection --}}
    <div class="p-3 border-b border-gray-200 bg-white">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-sm font-bold text-gray-700">
                <i class="fas fa-folder-open text-indigo-500 ms-1"></i>
                {{ __('publish.account_groups') }}
            </h4>
            <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full" x-text="selectedGroupIds.length + ' / ' + profileGroups.length"></span>
        </div>

        {{-- Groups Multi-Select --}}
        <div class="space-y-1 max-h-32 overflow-y-auto">
            <template x-for="group in profileGroups" :key="group.group_id">
                <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition"
                       :class="selectedGroupIds.includes(group.group_id) ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50'">
                    <input type="checkbox" :value="group.group_id"
                           :checked="selectedGroupIds.includes(group.group_id)"
                           @change="toggleGroupId(group.group_id)"
                           class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-700" x-text="group.name"></span>
                    </div>
                    <span class="text-xs text-gray-400" x-text="'(' + (group.profiles?.length || 0) + ')'"></span>
                </label>
            </template>
        </div>

        {{-- Quick Actions --}}
        <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
            <button @click="selectAllGroups()" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                <i class="fas fa-check-double ms-1"></i>{{ __('publish.select_all') }}
            </button>
            <button @click="clearSelectedGroups()" class="text-xs text-gray-500 hover:text-gray-700">
                <i class="fas fa-times ms-1"></i>{{ __('publish.clear') }}
            </button>
        </div>
    </div>

    {{-- STEP 2: Profiles from Selected Groups --}}
    <div class="flex-shrink-0 p-3 border-b border-gray-200 bg-gradient-to-l from-purple-50 to-indigo-50">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-bold text-gray-700">
                <i class="fas fa-users text-purple-500 ms-1"></i>
                {{ __('publish.accounts') }}
            </h4>
            <span class="text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full" x-text="selectedProfiles.length + ' {{ __('publish.selected') }}'"></span>
        </div>
    </div>

    {{-- Search Profiles --}}
    <div class="p-3 border-b border-gray-200">
        <div class="relative">
            <input type="text" x-model="profileSearch" placeholder="{{ __('publish.search_accounts') }}"
                   class="w-full pe-9 ps-3 py-2 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            <i class="fas fa-search absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        </div>
        {{-- Platform Filters --}}
        <div class="flex flex-wrap gap-1 mt-3">
            <button @click="platformFilter = null"
                    :class="platformFilter === null ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                    class="px-2 py-1 text-xs rounded-full border border-gray-200 transition">{{ __('publish.all') }}</button>
            <template x-for="platform in availablePlatforms" :key="platform">
                <button @click="platformFilter = platform"
                        :class="platformFilter === platform ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                        class="px-2 py-1 text-xs rounded-full border border-gray-200 transition">
                    <i :class="getPlatformIcon(platform)"></i>
                </button>
            </template>
        </div>
    </div>

    {{-- Profile List --}}
    <div class="flex-1 overflow-y-auto p-2">
        {{-- Empty State: No Groups Selected --}}
        <div x-show="selectedGroupIds.length === 0" class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-folder-open text-2xl text-gray-400"></i>
            </div>
            <p class="text-sm font-medium text-gray-600">{{ __('publish.select_groups_first') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ __('publish.select_one_or_more') }}</p>
        </div>

        {{-- Profiles from Selected Groups --}}
        <div x-show="selectedGroupIds.length > 0">
            {{-- Select All/Clear --}}
            <div class="flex items-center justify-between px-2 py-2 mb-2">
                <button @click="selectAllProfiles()" class="text-xs text-blue-600 hover:text-blue-700">
                    <i class="fas fa-check-double ms-1"></i>{{ __('publish.select_all_accounts') }}
                </button>
                <button @click="clearSelectedProfiles()" class="text-xs text-gray-500 hover:text-gray-700">{{ __('publish.clear') }}</button>
            </div>

            {{-- Grouped Profiles --}}
            <template x-for="group in filteredProfileGroups" :key="group.group_id">
            <div class="mb-4">
                {{-- Group Header with Select All --}}
                <div class="flex items-center justify-between px-2 py-2 bg-gradient-to-l from-indigo-50 to-purple-50 rounded-lg mb-2 cursor-pointer hover:from-indigo-100 hover:to-purple-100 transition"
                     @click="toggleGroupSelection(group)">
                    <div class="flex items-center gap-2">
                        {{-- Group Checkbox --}}
                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition"
                             :class="isGroupFullySelected(group) ? 'bg-indigo-600 border-indigo-600' : (isGroupPartiallySelected(group) ? 'bg-indigo-300 border-indigo-400' : 'border-gray-300 bg-white')">
                            <i class="fas fa-check text-white text-xs" x-show="isGroupFullySelected(group)"></i>
                            <i class="fas fa-minus text-white text-xs" x-show="isGroupPartiallySelected(group) && !isGroupFullySelected(group)"></i>
                        </div>
                        <i class="fas fa-layer-group text-indigo-500"></i>
                        <span class="text-sm font-bold text-gray-700" x-text="group.name"></span>
                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full" x-text="group.profiles?.length || 0"></span>
                    </div>
                    <span class="text-xs text-indigo-600 font-medium">
                        <span x-show="!isGroupFullySelected(group)">{{ __('publish.select_all_group') }}</span>
                        <span x-show="isGroupFullySelected(group)">{{ __('publish.deselect_all') }}</span>
                    </span>
                </div>
                {{-- Accounts in Group --}}
                <template x-for="profile in group.profiles" :key="profile.integration_id">
                    <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-white transition me-2"
                           :class="{ 'bg-blue-50 ring-1 ring-blue-200': isProfileSelected(profile.integration_id) }">
                        <input type="checkbox" :value="profile.integration_id"
                               :checked="isProfileSelected(profile.integration_id)"
                               @change="toggleProfile(profile)"
                               class="sr-only">
                        <div class="relative">
                            <img :src="profile.avatar_url || getDefaultAvatar(profile)"
                                 :alt="profile.account_name"
                                 class="w-10 h-10 rounded-full ring-2"
                                 :class="isProfileSelected(profile.integration_id) ? 'ring-blue-500' : 'ring-gray-200'">
                            <div class="absolute -bottom-1 -left-1 w-5 h-5 rounded-full flex items-center justify-center text-white text-xs"
                                 :class="getPlatformBgClass(profile.platform)">
                                <i :class="getPlatformIcon(profile.platform)"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="profile.account_name"></p>
                            <p class="text-xs text-gray-500 truncate" x-text="profile.platform_handle || profile.platform"></p>
                        </div>
                        <template x-if="profile.status === 'error'">
                            <i class="fas fa-exclamation-circle text-red-500" title="{{ __('publish.connection_error') }}"></i>
                        </template>
                        <div x-show="isProfileSelected(profile.integration_id)" class="text-green-500">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </label>
                </template>
            </div>
        </template>
        </div>
    </div>

    {{-- Selected Profiles Bar --}}
    <div class="flex-shrink-0 p-3 bg-white border-t border-gray-200">
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">
                <span x-text="selectedProfiles.length" class="font-semibold text-blue-600"></span> {{ __('publish.selected') }}
            </span>
            <div class="flex -space-x-reverse -space-x-2 flex-1 overflow-hidden">
                <template x-for="profile in selectedProfiles.slice(0, 5)" :key="profile.integration_id">
                    <img :src="profile.avatar_url || getDefaultAvatar(profile)"
                         class="w-7 h-7 rounded-full ring-2 ring-white" :alt="profile.account_name">
                </template>
                <template x-if="selectedProfiles.length > 5">
                    <div class="w-7 h-7 rounded-full bg-gray-300 ring-2 ring-white flex items-center justify-center text-xs font-medium text-gray-600"
                         x-text="'+' + (selectedProfiles.length - 5)"></div>
                </template>
            </div>
        </div>
    </div>
</div>
