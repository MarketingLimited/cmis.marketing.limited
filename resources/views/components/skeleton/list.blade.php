@props(['items' => 5, 'showAvatar' => true, 'class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white shadow rounded-lg divide-y divide-gray-200 animate-pulse ' . $class]) }}>
    @for($i = 0; $i < $items; $i++)
        <div class="px-6 py-4">
            <div class="flex items-center">
                @if($showAvatar)
                    <div class="h-10 w-10 bg-gray-200 rounded-full me-4"></div>
                @endif
                <div class="flex-1">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
                <div class="h-8 w-20 bg-gray-200 rounded ms-4"></div>
            </div>
        </div>
    @endfor
</div>
