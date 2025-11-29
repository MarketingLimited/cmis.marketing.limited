@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'space-y-6 ' . $class]) }}>
    <!-- KPI Cards Row -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-skeleton.card />
        <x-skeleton.card />
        <x-skeleton.card />
        <x-skeleton.card />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-skeleton.chart />
        <x-skeleton.chart />
    </div>

    <!-- Table -->
    <x-skeleton.table :rows="5" :columns="5" />
</div>
