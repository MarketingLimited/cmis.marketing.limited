@props(['tabs' => [], 'active' => 0])

<div x-data="{ activeTab: {{ $active }} }" class="w-full">
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-reverse space-x-4" aria-label="Tabs">
            @foreach($tabs as $index => $tab)
                <button @click="activeTab = {{ $index }}"
                        :class="activeTab === {{ $index }} ?
                                'border-indigo-500 text-indigo-600' :
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    @if(isset($tab['icon']))
                        <i class="{{ $tab['icon'] }} ml-2"></i>
                    @endif
                    {{ $tab['label'] }}
                    @if(isset($tab['badge']))
                        <span class="mr-2 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">
                            {{ $tab['badge'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Content -->
    <div>
        {{ $slot }}
    </div>
</div>
