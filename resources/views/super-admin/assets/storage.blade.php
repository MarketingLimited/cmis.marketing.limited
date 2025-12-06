@extends('super-admin.layouts.app')

@section('title', __('super_admin.assets.storage_analytics'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.assets.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.assets.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.assets.storage_analytics') }}</span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.assets.storage_analytics') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.assets.storage_analytics_desc') }}</p>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.total_storage_used') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatBytes($overallStats['total_size']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-hdd text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.total_files') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($overallStats['total_assets']) }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-file text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.avg_file_size') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatBytes($overallStats['avg_asset_size'] ?? 0) }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <i class="fas fa-chart-bar text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Storage by Organization -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.storage_by_org') }}</h3>
            @if($storageByOrg->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="text-start pb-3">{{ __('super_admin.assets.organization') }}</th>
                            <th class="text-start pb-3">{{ __('super_admin.assets.files') }}</th>
                            <th class="text-start pb-3">{{ __('super_admin.assets.storage') }}</th>
                            <th class="text-start pb-3">{{ __('super_admin.assets.avg') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($storageByOrg as $org)
                        <tr>
                            <td class="py-3">
                                <a href="{{ route('super-admin.orgs.show', $org->org_id) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-red-600 dark:hover:text-red-400">
                                    {{ $org->org_name }}
                                </a>
                            </td>
                            <td class="py-3 text-sm text-gray-500 dark:text-gray-400">{{ number_format($org->asset_count) }}</td>
                            <td class="py-3 text-sm font-medium text-gray-900 dark:text-white">{{ formatBytes($org->total_size) }}</td>
                            <td class="py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatBytes($org->avg_size) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_data') }}</p>
            </div>
            @endif
        </div>

        <!-- Storage by Type -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.storage_by_type') }}</h3>
            @if($storageByType->count() > 0)
            <div class="space-y-4">
                @php $maxSize = $storageByType->max('total_size') ?: 1; @endphp
                @foreach($storageByType as $type)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            @php
                                $icon = match(strtolower($type->type ?? 'file')) {
                                    'image', 'png', 'jpg', 'jpeg', 'gif', 'webp' => 'fa-image',
                                    'video', 'mp4', 'mov', 'avi' => 'fa-video',
                                    'audio', 'mp3', 'wav' => 'fa-music',
                                    'pdf' => 'fa-file-pdf',
                                    default => 'fa-file'
                                };
                                $color = match(strtolower($type->type ?? 'file')) {
                                    'image', 'png', 'jpg', 'jpeg', 'gif', 'webp' => 'bg-blue-500',
                                    'video', 'mp4', 'mov', 'avi' => 'bg-purple-500',
                                    'audio', 'mp3', 'wav' => 'bg-green-500',
                                    'pdf' => 'bg-red-500',
                                    default => 'bg-gray-500'
                                };
                            @endphp
                            <i class="fas {{ $icon }} text-gray-500 dark:text-gray-400"></i>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($type->type ?? 'Unknown') }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ number_format($type->count) }} {{ __('super_admin.assets.files') }})</span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatBytes($type->total_size) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="{{ $color }} h-2 rounded-full" style="width: {{ ($type->total_size / $maxSize) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_data') }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Monthly Upload Trends -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.assets.upload_trends') }}</h3>
        @if($monthlyTrends->count() > 0)
        <div class="h-64">
            <canvas id="uploadTrendsChart"></canvas>
        </div>
        @else
        <div class="text-center py-8">
            <i class="fas fa-chart-line text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.assets.no_trends_data') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($monthlyTrends->count() > 0)
    const ctx = document.getElementById('uploadTrendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($monthlyTrends->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))) !!},
            datasets: [{
                label: '{{ __("super_admin.assets.uploads") }}',
                data: {!! json_encode($monthlyTrends->pluck('count')) !!},
                backgroundColor: 'rgba(239, 68, 68, 0.5)',
                borderColor: 'rgb(239, 68, 68)',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: '{{ __("super_admin.assets.storage_mb") }}',
                data: {!! json_encode($monthlyTrends->pluck('total_size')->map(fn($s) => round($s / 1024 / 1024, 2))) !!},
                type: 'line',
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                yAxisID: 'y1',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: '{{ __("super_admin.assets.file_count") }}' }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: '{{ __("super_admin.assets.size_mb") }}' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
    @endif
});
</script>
@endpush
