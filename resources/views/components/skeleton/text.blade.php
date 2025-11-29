@props(['lines' => 3, 'class' => ''])

<div {{ $attributes->merge(['class' => 'animate-pulse ' . $class]) }}>
    @for($i = 0; $i < $lines; $i++)
        <div class="h-4 bg-gray-200 rounded mb-2 {{ $i === $lines - 1 ? 'w-3/4' : 'w-full' }}"></div>
    @endfor
</div>
