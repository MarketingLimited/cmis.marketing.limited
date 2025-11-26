{{--
    Reusable Asset Selector Component

    Usage:
    @include('settings.platform-connections.partials.asset-selector', [
        'type' => 'account',           // Type identifier
        'name' => 'account',           // Form field name
        'title' => 'Account',          // Display title
        'icon' => 'fas fa-user',       // Icon class
        'color' => 'blue',             // Color theme (blue, pink, green, purple, orange, gray, red, sky, yellow)
        'items' => $accounts,          // Array of items
        'selectedValue' => $selected,  // Currently selected value
        'alpineModel' => 'selectedAccount', // Alpine.js model name
        'emptyMessage' => 'No accounts found',
        'emptyHint' => 'Connect an account first',
        'showManual' => true,          // Show manual ID input
        'manualPlaceholder' => 'Enter ID',
        'manualHint' => 'Enter account ID manually',
    ])
--}}

@php
    $colorClasses = [
        'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-500', 'selected' => 'bg-blue-50', 'ring' => 'focus:ring-blue-500', 'hover' => 'hover:text-blue-800'],
        'pink' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600', 'border' => 'border-pink-500', 'selected' => 'bg-pink-50', 'ring' => 'focus:ring-pink-500', 'hover' => 'hover:text-pink-800'],
        'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'border' => 'border-green-500', 'selected' => 'bg-green-50', 'ring' => 'focus:ring-green-500', 'hover' => 'hover:text-green-800'],
        'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'border' => 'border-purple-500', 'selected' => 'bg-purple-50', 'ring' => 'focus:ring-purple-500', 'hover' => 'hover:text-purple-800'],
        'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-500', 'selected' => 'bg-orange-50', 'ring' => 'focus:ring-orange-500', 'hover' => 'hover:text-orange-800'],
        'gray' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'border' => 'border-gray-500', 'selected' => 'bg-gray-50', 'ring' => 'focus:ring-gray-500', 'hover' => 'hover:text-gray-800'],
        'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'border' => 'border-red-500', 'selected' => 'bg-red-50', 'ring' => 'focus:ring-red-500', 'hover' => 'hover:text-red-800'],
        'sky' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-600', 'border' => 'border-sky-500', 'selected' => 'bg-sky-50', 'ring' => 'focus:ring-sky-500', 'hover' => 'hover:text-sky-800'],
        'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'border' => 'border-yellow-500', 'selected' => 'bg-yellow-50', 'ring' => 'focus:ring-yellow-500', 'hover' => 'hover:text-yellow-800'],
        'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-500', 'selected' => 'bg-indigo-50', 'ring' => 'focus:ring-indigo-500', 'hover' => 'hover:text-indigo-800'],
    ];
    $colors = $colorClasses[$color] ?? $colorClasses['blue'];
    $showManual = $showManual ?? true;
    $manualFieldName = 'manual_' . $name . '_id';
    $showManualVar = 'showManual' . ucfirst($type);
@endphp

<div class="bg-white shadow sm:rounded-lg overflow-hidden">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 {{ $colors['bg'] }} rounded-lg flex items-center justify-center">
                    <i class="{{ $icon }} {{ $colors['text'] }}"></i>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
                    <p class="text-sm text-gray-500">{{ count($items) }} {{ __('available') }}</p>
                </div>
            </div>
            @if($showManual)
                <button type="button" @click="{{ $showManualVar }} = !{{ $showManualVar }}" class="text-sm {{ $colors['text'] }} {{ $colors['hover'] }}">
                    <i class="fas fa-plus mr-1"></i>{{ __('Add manually') }}
                </button>
            @endif
        </div>

        @if(count($items) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($items as $item)
                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition"
                           :class="{ '{{ $colors['border'] }} {{ $colors['selected'] }}': {{ $alpineModel }} === '{{ $item['id'] }}' }">
                        <input type="radio" name="{{ $name }}" value="{{ $item['id'] }}"
                               {{ $selectedValue === $item['id'] ? 'checked' : '' }}
                               x-model="{{ $alpineModel }}"
                               class="h-4 w-4 {{ $colors['text'] }} border-gray-300 {{ $colors['ring'] }}">
                        <div class="ml-3 flex items-center gap-3">
                            @if($item['picture'] ?? $item['profile_picture'] ?? null)
                                <img src="{{ $item['picture'] ?? $item['profile_picture'] }}" alt="" class="w-8 h-8 rounded-full">
                            @else
                                <div class="w-8 h-8 {{ $colors['bg'] }} rounded-full flex items-center justify-center">
                                    <i class="{{ $icon }} {{ $colors['text'] }} text-sm"></i>
                                </div>
                            @endif
                            <div>
                                <span class="text-sm font-medium text-gray-900">{{ $item['name'] ?? $item['username'] ?? 'Unknown' }}</span>
                                @if($item['id'] ?? null)
                                    <span class="text-xs text-gray-400 ml-1">({{ Str::limit($item['id'], 15) }})</span>
                                @endif
                                @if($item['description'] ?? $item['category'] ?? null)
                                    <span class="block text-xs text-gray-500">{{ $item['description'] ?? $item['category'] }}</span>
                                @endif
                                @if($item['status'] ?? null)
                                    <span class="px-1.5 py-0.5 rounded text-xs {{ ($item['status'] === 'Active' || $item['status'] === 'active') ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $item['status'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        @else
            <div class="text-center py-6 bg-gray-50 rounded-lg">
                <i class="{{ $icon }} text-gray-300 text-3xl mb-2"></i>
                <p class="text-sm text-gray-500">{{ $emptyMessage ?? __('No items found') }}</p>
                @if($emptyHint ?? null)
                    <p class="text-xs text-gray-400 mt-1">{{ $emptyHint }}</p>
                @endif
            </div>
        @endif

        @if($showManual)
            <div x-show="{{ $showManualVar }}" x-cloak class="mt-4 p-4 bg-gray-50 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Enter ID manually') }}</label>
                <div class="flex gap-2">
                    <input type="text" name="{{ $manualFieldName }}" placeholder="{{ $manualPlaceholder ?? 'e.g., 123456789' }}"
                           class="flex-1 rounded-md border-gray-300 shadow-sm {{ $colors['ring'] }} text-sm">
                    <button type="button" @click="{{ $showManualVar }} = false" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                @if($manualHint ?? null)
                    <p class="text-xs text-gray-500 mt-1">{{ $manualHint }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
