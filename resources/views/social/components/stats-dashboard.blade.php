{{-- Stats Dashboard Component --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-6">
    <!-- Scheduled Posts Card -->
    <div class="group bg-gradient-to-br from-yellow-400 to-orange-500 dark:from-yellow-500 dark:to-orange-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
         @click="statusFilter = 'scheduled'">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-xs sm:text-sm font-medium">{{ __('social.scheduled_status') }}</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="scheduledCount">0</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                <i class="fas fa-clock text-xl sm:text-2xl"></i>
            </div>
        </div>
        <div class="mt-3 flex items-center text-yellow-100 text-xs opacity-80 group-hover:opacity-100 transition-opacity">
            <i class="fas fa-calendar-alt ms-1"></i>
            <span>{{ __('social.waiting_publish') }}</span>
        </div>
    </div>

    <!-- Published Posts Card -->
    <div class="group bg-gradient-to-br from-green-400 to-emerald-500 dark:from-green-500 dark:to-emerald-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-emerald-500/20 hover:shadow-xl hover:shadow-emerald-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
         @click="statusFilter = 'published'">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-xs sm:text-sm font-medium">{{ __('social.published_status') }}</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="publishedCount">0</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                <i class="fas fa-check-circle text-xl sm:text-2xl"></i>
            </div>
        </div>
        <div class="mt-3 flex items-center text-green-100 text-xs opacity-80 group-hover:opacity-100 transition-opacity">
            <i class="fas fa-chart-line ms-1"></i>
            <span>{{ __('social.published_successfully') }}</span>
        </div>
    </div>

    <!-- Draft Posts Card -->
    <div class="group bg-gradient-to-br from-slate-400 to-slate-500 dark:from-slate-500 dark:to-slate-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-slate-500/20 hover:shadow-xl hover:shadow-slate-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
         @click="statusFilter = 'draft'">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-slate-100 text-xs sm:text-sm font-medium">{{ __('social.draft_status') }}</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="draftCount">0</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                <i class="fas fa-file-alt text-xl sm:text-2xl"></i>
            </div>
        </div>
        <div class="mt-3 flex items-center text-slate-100 text-xs opacity-80 group-hover:opacity-100 transition-opacity">
            <i class="fas fa-edit ms-1"></i>
            <span>{{ __('social.ready_edit') }}</span>
        </div>
    </div>

    <!-- Failed Posts Card -->
    <div class="group bg-gradient-to-br from-red-400 to-rose-500 dark:from-red-500 dark:to-rose-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-rose-500/20 hover:shadow-xl hover:shadow-rose-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
         @click="statusFilter = 'failed'">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-100 text-xs sm:text-sm font-medium">{{ __('social.failed_status') }}</p>
                <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="failedCount">0</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                <i class="fas fa-exclamation-triangle text-xl sm:text-2xl"></i>
            </div>
        </div>
        <div class="mt-3 flex items-center text-red-100 text-xs cursor-pointer hover:text-white transition-colors"
             x-show="failedCount > 0" @click.stop="deleteAllFailed()">
            <i class="fas fa-trash ms-1"></i>
            <span>{{ __('social.delete_all') }}</span>
        </div>
        <div class="mt-3 flex items-center text-red-100 text-xs opacity-80" x-show="failedCount === 0">
            <i class="fas fa-smile ms-1"></i>
            <span>{{ __('social.no_errors') }}</span>
        </div>
    </div>
</div>
