{{-- AI Quota Widget Component --}}
@if($quota)
    <div class="ai-quota-widget {{ $compact ? 'compact' : '' }}"
         data-service="{{ $service }}"
         x-data="aiQuotaWidget(@js($quota))">

        @if(!$compact)
            {{-- Full Widget --}}
            <div class="quota-header">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <i class="fa-solid fa-sparkles text-purple-500 mr-2"></i>
                        {{ $getServiceName() }}
                    </h3>
                    <span class="text-xs px-2 py-1 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300">
                        {{ ucfirst($quota['tier']) }}
                    </span>
                </div>
            </div>

            {{-- Daily Usage --}}
            <div class="quota-section mb-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ __('ui.quota.daily_usage') }}
                    </span>
                    <span class="text-xs font-medium text-gray-900 dark:text-gray-100">
                        {{ $quota['daily']['used'] }} / {{ $quota['daily']['limit'] }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300"
                         :class="{
                             'bg-green-500': {{ $quota['daily']['percentage'] }} < 50,
                             'bg-yellow-500': {{ $quota['daily']['percentage'] }} >= 50 && {{ $quota['daily']['percentage'] }} < 80,
                             'bg-orange-500': {{ $quota['daily']['percentage'] }} >= 80 && {{ $quota['daily']['percentage'] }} < 95,
                             'bg-red-500': {{ $quota['daily']['percentage'] }} >= 95
                         }"
                         style="width: {{ min($quota['daily']['percentage'], 100) }}%">
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $quota['daily']['remaining'] }} {{ __('ui.quota.remaining') }}
                </p>
            </div>

            {{-- Monthly Usage --}}
            <div class="quota-section mb-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ __('ui.quota.monthly_usage') }}
                    </span>
                    <span class="text-xs font-medium text-gray-900 dark:text-gray-100">
                        {{ $quota['monthly']['used'] }} / {{ $quota['monthly']['limit'] }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300"
                         :class="{
                             'bg-green-500': {{ $quota['monthly']['percentage'] }} < 50,
                             'bg-yellow-500': {{ $quota['monthly']['percentage'] }} >= 50 && {{ $quota['monthly']['percentage'] }} < 80,
                             'bg-orange-500': {{ $quota['monthly']['percentage'] }} >= 80 && {{ $quota['monthly']['percentage'] }} < 95,
                             'bg-red-500': {{ $quota['monthly']['percentage'] }} >= 95
                         }"
                         style="width: {{ min($quota['monthly']['percentage'], 100) }}%">
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $quota['monthly']['remaining'] }} {{ __('ui.quota.remaining') }}
                </p>
            </div>

            {{-- Cost (if available) --}}
            @if(isset($quota['cost']['used']) && $quota['cost']['used'] > 0)
                <div class="quota-cost mb-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ __('ui.quota.cost_this_month') }}
                        </span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                            ${{ number_format($quota['cost']['used'], 2) }}
                            @if($quota['cost']['limit'])
                                <span class="text-gray-500">/ ${{ number_format($quota['cost']['limit'], 2) }}</span>
                            @endif
                        </span>
                    </div>
                </div>
            @endif

            {{-- Upgrade CTA --}}
            @if($shouldShowUpgrade())
                <div class="quota-upgrade mt-4 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <p class="text-xs text-purple-800 dark:text-purple-300 mb-2">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        {{ __('ui.quota.upgrade_needed') }}
                    </p>
                    <a href="{{ route('billing.upgrade') }}"
                       class="block w-full text-center px-3 py-2 text-xs font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-md transition-colors">
                        {{ __('common.upgrade_plan') }}
                    </a>
                </div>
            @endif

        @else
            {{-- Compact Widget --}}
            <div class="quota-compact flex items-center space-x-2">
                <div class="flex-1">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full bg-gradient-to-r from-purple-500 to-pink-500"
                             style="width: {{ min($quota['daily']['percentage'], 100) }}%">
                        </div>
                    </div>
                </div>
                <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    {{ $quota['daily']['remaining'] }}/{{ $quota['daily']['limit'] }}
                </span>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function aiQuotaWidget(initialQuota) {
            return {
                quota: initialQuota,

                init() {
                    // Refresh quota every 5 minutes
                    setInterval(() => {
                        this.refreshQuota();
                    }, 5 * 60 * 1000);
                },

                async refreshQuota() {
                    try {
                        const response = await fetch('/api/ai/quota');
                        if (response.ok) {
                            const data = await response.json();
                            this.quota = data['{{ $service }}'] || this.quota;
                        }
                    } catch (error) {
                        console.error('Failed to refresh quota:', error);
                    }
                }
            }
        }
    </script>
    @endpush

@else
    {{-- No quota data available --}}
    <div class="ai-quota-widget-empty text-center py-4">
        <p class="text-xs text-gray-500">
            {{ __('common.no_data') }}
        </p>
    </div>
@endif
