@props(['title', 'value', 'icon', 'color' => 'blue', 'trend' => null])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-xl transition duration-300">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $value }}</p>
            @if($trend)
                <div class="flex items-center mt-2 text-sm {{ $trend > 0 ? 'text-green-600' : 'text-red-600' }}">
                    <i class="fas fa-arrow-{{ $trend > 0 ? 'up' : 'down' }} ml-1"></i>
                    <span>{{ abs($trend) }}%</span>
                    <span class="text-gray-500 mr-1">من الشهر الماضي</span>
                </div>
            @endif
        </div>
        <div class="w-16 h-16 bg-{{ $color }}-100 dark:bg-{{ $color }}-900 rounded-full flex items-center justify-center">
            <i class="{{ $icon }} text-2xl text-{{ $color }}-600 dark:text-{{ $color }}-400"></i>
        </div>
    </div>
</div>
