@php
$statusClasses = [
    'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'expired' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
];

$statusIcons = [
    'pending' => 'fas fa-clock',
    'processing' => 'fas fa-spinner fa-spin',
    'completed' => 'fas fa-check',
    'failed' => 'fas fa-times',
    'expired' => 'fas fa-hourglass-end',
];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$status] ?? $statusClasses['pending'] }}">
    <i class="{{ $statusIcons[$status] ?? $statusIcons['pending'] }} me-1"></i>
    {{ __('backup.status_' . $status) }}
</span>
