@php
$statusConfig = [
    'pending' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-800 dark:text-gray-200', 'icon' => 'fas fa-clock'],
    'analyzing' => ['bg' => 'bg-blue-100 dark:bg-blue-900', 'text' => 'text-blue-800 dark:text-blue-200', 'icon' => 'fas fa-search'],
    'awaiting_confirmation' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900', 'text' => 'text-yellow-800 dark:text-yellow-200', 'icon' => 'fas fa-exclamation-triangle'],
    'processing' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900', 'text' => 'text-indigo-800 dark:text-indigo-200', 'icon' => 'fas fa-spinner fa-spin'],
    'completed' => ['bg' => 'bg-green-100 dark:bg-green-900', 'text' => 'text-green-800 dark:text-green-200', 'icon' => 'fas fa-check-circle'],
    'failed' => ['bg' => 'bg-red-100 dark:bg-red-900', 'text' => 'text-red-800 dark:text-red-200', 'icon' => 'fas fa-times-circle'],
    'rolled_back' => ['bg' => 'bg-orange-100 dark:bg-orange-900', 'text' => 'text-orange-800 dark:text-orange-200', 'icon' => 'fas fa-undo'],
];
$config = $statusConfig[$status] ?? $statusConfig['pending'];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }}">
    <i class="{{ $config['icon'] }} me-1"></i>
    {{ __('backup.restore_status_' . $status) }}
</span>
