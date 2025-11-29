{{-- Location Tagging (All Platforms) --}}
<div class="mt-4" x-data="{ showLocationPicker: false }">
    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
        <i class="fas fa-map-marker-alt ms-1"></i>
        {{ __('publish.add_location') }}
    </label>
    <div class="relative">
        <input type="text"
               x-model="content.platforms[platform].location_query"
               @focus="showLocationPicker = true"
               @input="searchLocation($event.target.value, platform)"
               :placeholder="'{{ __('publish.search_location') }}'"
               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">

        {{-- Location Search Results --}}
        <div x-show="showLocationPicker && locationResults[platform]?.length > 0"
             @click.away="showLocationPicker = false"
             class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
            <template x-for="location in locationResults[platform]" :key="location.place_id">
                <button @click="selectLocation(location, platform); showLocationPicker = false"
                        class="w-full px-4 py-2 text-start hover:bg-gray-50 text-sm border-b border-gray-100 last:border-b-0">
                    <i class="fas fa-map-marker-alt text-gray-400 ms-1"></i>
                    <span x-text="location.name"></span>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="location.address"></p>
                </button>
            </template>
        </div>
    </div>
    {{-- Selected Location Display --}}
    <div x-show="content.platforms[platform]?.location" class="mt-2 flex items-center gap-2 text-sm text-gray-700">
        <i class="fas fa-check-circle text-green-500"></i>
        <span x-text="content.platforms[platform]?.location?.name"></span>
        <button @click="content.platforms[platform].location = null" class="text-red-500 hover:text-red-700 text-xs">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

{{-- PHASE 5B: APPLY TO ALL PROFILES (VISTASOCIAL PARITY) --}}
<div class="mt-4 pt-4 border-t border-gray-200">
    <button @click="applyToAllPlatformProfiles(platform)"
            x-show="getProfileCountForPlatform(platform) > 1"
            class="w-full px-4 py-2.5 bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 border border-blue-200 rounded-lg text-sm font-medium text-blue-700 transition-all duration-200 flex items-center justify-center gap-2 group">
        <i class="fas fa-users group-hover:scale-110 transition-transform"></i>
        <span x-text="'{{ __('publish.apply_to_all_platform') }}'.replace(':platform', getPlatformName(platform))"></span>
        <span class="text-xs text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full" x-text="getProfileCountForPlatform(platform) + ' {{ __('publish.profiles') }}'"></span>
    </button>
</div>
