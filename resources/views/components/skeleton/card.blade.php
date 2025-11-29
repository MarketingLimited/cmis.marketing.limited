@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow rounded-lg animate-pulse ' . $class]) }}>
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="rounded-md bg-gray-200 p-3 h-12 w-12"></div>
            </div>
            <div class="ms-5 w-0 flex-1">
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div class="flex items-baseline">
                    <div class="h-6 bg-gray-200 rounded w-20 me-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-12"></div>
                </div>
            </div>
        </div>
    </div>
</div>
