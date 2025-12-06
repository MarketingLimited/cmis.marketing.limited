@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<footer class="bg-slate-900 text-slate-300">
    <!-- Main Footer -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <!-- Brand -->
            <div class="lg:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">C</span>
                    </div>
                    <span class="text-xl font-bold text-white">CMIS</span>
                </div>
                <p class="text-slate-400 mb-6">{{ __('marketing.footer.tagline') }}</p>
                <div class="flex items-center gap-4">
                    <a href="#" class="text-slate-400 hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-slate-400 hover:text-white transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-slate-400 hover:text-white transition"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="text-slate-400 hover:text-white transition"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Product -->
            <div>
                <h4 class="text-white font-semibold mb-4">{{ __('marketing.footer.product') }}</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('marketing.features') }}" class="hover:text-white transition">{{ __('marketing.nav.features') }}</a></li>
                    <li><a href="{{ route('marketing.pricing') }}" class="hover:text-white transition">{{ __('marketing.nav.pricing') }}</a></li>
                    <li><a href="{{ route('marketing.case-studies.index') }}" class="hover:text-white transition">{{ __('marketing.nav.case_studies') }}</a></li>
                    <li><a href="{{ route('marketing.demo') }}" class="hover:text-white transition">{{ __('marketing.nav.demo') }}</a></li>
                </ul>
            </div>

            <!-- Company -->
            <div>
                <h4 class="text-white font-semibold mb-4">{{ __('marketing.footer.company') }}</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('marketing.about') }}" class="hover:text-white transition">{{ __('marketing.nav.about') }}</a></li>
                    <li><a href="{{ route('marketing.blog.index') }}" class="hover:text-white transition">{{ __('marketing.nav.blog') }}</a></li>
                    <li><a href="{{ route('marketing.contact') }}" class="hover:text-white transition">{{ __('marketing.nav.contact') }}</a></li>
                    <li><a href="{{ route('marketing.faq') }}" class="hover:text-white transition">{{ __('marketing.nav.faq') }}</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h4 class="text-white font-semibold mb-4">{{ __('marketing.footer.legal') }}</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('marketing.terms') }}" class="hover:text-white transition">{{ __('marketing.nav.terms') }}</a></li>
                    <li><a href="{{ route('marketing.privacy') }}" class="hover:text-white transition">{{ __('marketing.nav.privacy') }}</a></li>
                    <li><a href="{{ route('marketing.cookies') }}" class="hover:text-white transition">{{ __('marketing.nav.cookies') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-slate-400 text-sm">
                    &copy; {{ date('Y') }} CMIS. {{ __('marketing.footer.rights') }}
                </p>
                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ route('locale.switch', 'en') }}" class="hover:text-white transition {{ app()->getLocale() === 'en' ? 'text-white' : '' }}">English</a>
                    <a href="{{ route('locale.switch', 'ar') }}" class="hover:text-white transition {{ app()->getLocale() === 'ar' ? 'text-white' : '' }}">العربية</a>
                </div>
            </div>
        </div>
    </div>
</footer>
