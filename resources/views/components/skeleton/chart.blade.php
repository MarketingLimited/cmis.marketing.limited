@props(['height' => 'h-64', 'class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white shadow rounded-lg p-6 animate-pulse ' . $class]) }}>
    <!-- Title skeleton -->
    <div class="h-5 bg-gray-200 rounded w-48 mb-4"></div>

    <!-- Chart area skeleton -->
    <div class="{{ $height }} flex items-end justify-around bg-gray-50 rounded p-4 space-x-2">
        <!-- Bar chart skeleton -->
        <div class="bg-gray-200 rounded-t w-8" style="height: 45%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 65%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 55%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 80%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 70%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 45%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 60%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 75%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 50%"></div>
        <div class="bg-gray-200 rounded-t w-8" style="height: 85%"></div>
    </div>

    <!-- Legend skeleton -->
    <div class="flex justify-center space-x-4 mt-4">
        <div class="flex items-center">
            <div class="h-3 w-3 bg-gray-200 rounded-full me-2"></div>
            <div class="h-3 bg-gray-200 rounded w-16"></div>
        </div>
        <div class="flex items-center">
            <div class="h-3 w-3 bg-gray-200 rounded-full me-2"></div>
            <div class="h-3 bg-gray-200 rounded w-16"></div>
        </div>
    </div>
</div>
