<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mt-8">
    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl md:text-3xl font-bold" x-text="stats.totalImported">0</p>
                <p class="text-blue-200 text-xs md:text-sm">{{ __("social.total_imported") }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl md:text-3xl font-bold" x-text="stats.totalAnalyzed">0</p>
                <p class="text-blue-200 text-xs md:text-sm">{{ __("social.total_analyzed") }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl md:text-3xl font-bold" x-text="stats.inKB">0</p>
                <p class="text-blue-200 text-xs md:text-sm">{{ __("social.in_kb") }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl md:text-3xl font-bold" x-text="stats.highPerformers">0</p>
                <p class="text-blue-200 text-xs md:text-sm">{{ __("social.high_performers") }}</p>
            </div>
        </div>
    </div>
</div>
