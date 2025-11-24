<div x-data="{ langOpen: false }" class="relative">
    <button @click="langOpen = !langOpen"
            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition">
        <i class="fas fa-globe text-gray-600"></i>
        <span class="hidden lg:inline text-sm font-medium text-gray-700">{{ strtoupper(app()->getLocale()) }}</span>
        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
    </button>

    <div x-show="langOpen"
         @click.away="langOpen = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
         x-cloak>

        <!-- English -->
        <a href="{{ route('locale.switch', 'en') }}"
           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition {{ app()->getLocale() === 'en' ? 'bg-indigo-50' : '' }}">
            <div class="w-6 h-6 rounded-full overflow-hidden flex-shrink-0 border-2 {{ app()->getLocale() === 'en' ? 'border-indigo-500' : 'border-gray-200' }}">
                <svg viewBox="0 0 60 30" class="w-full h-full">
                    <clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
                    <clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
                    <g clip-path="url(#s)">
                        <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
                        <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
                        <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/>
                        <path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/>
                        <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
                    </g>
                </svg>
            </div>
            <div class="flex-1 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}">
                <p class="text-sm font-medium text-gray-900">English</p>
                <p class="text-xs text-gray-500">EN</p>
            </div>
            <i class="fas fa-check text-indigo-600 {{ app()->getLocale() === 'en' ? '' : 'invisible' }}"></i>
        </a>

        <!-- Arabic -->
        <a href="{{ route('locale.switch', 'ar') }}"
           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition {{ app()->getLocale() === 'ar' ? 'bg-indigo-50' : '' }}">
            <div class="w-6 h-6 rounded-full overflow-hidden flex-shrink-0 border-2 {{ app()->getLocale() === 'ar' ? 'border-indigo-500' : 'border-gray-200' }}">
                <svg viewBox="0 0 900 600" class="w-full h-full">
                    <rect width="900" height="600" fill="#ce1126"/>
                    <rect width="900" height="400" fill="#fff"/>
                    <rect width="900" height="200" fill="#000"/>
                </svg>
            </div>
            <div class="flex-1 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}">
                <p class="text-sm font-medium text-gray-900">العربية</p>
                <p class="text-xs text-gray-500">AR</p>
            </div>
            <i class="fas fa-check text-indigo-600 {{ app()->getLocale() === 'ar' ? '' : 'invisible' }}"></i>
        </a>
    </div>
</div>
