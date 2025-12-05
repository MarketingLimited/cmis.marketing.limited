@php
$typeConfig = [
    'full' => ['bg' => 'bg-red-100 dark:bg-red-900', 'text' => 'text-red-800 dark:text-red-200', 'icon' => 'fas fa-database'],
    'selective' => ['bg' => 'bg-blue-100 dark:bg-blue-900', 'text' => 'text-blue-800 dark:text-blue-200', 'icon' => 'fas fa-list-check'],
    'merge' => ['bg' => 'bg-purple-100 dark:bg-purple-900', 'text' => 'text-purple-800 dark:text-purple-200', 'icon' => 'fas fa-code-merge'],
];
$config = $typeConfig[$type] ?? $typeConfig['selective'];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }}">
    <i class="{{ $config['icon'] }} me-1"></i>
    {{ __('backup.type_' . $type) }}
</span>
