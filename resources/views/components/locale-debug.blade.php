{{-- Locale Debug Component - Shows debug info in browser console --}}
<script>
    // Debug Information
    console.group('🔍 LOCALE DEBUG INFO');
    console.log('📍 App Locale (from server):', '{{ app()->getLocale() }}');
    console.log('🆔 Session ID:', '{{ session()->getId() }}');
    console.log('👤 Authenticated:', {{ auth()->check() ? 'true' : 'false' }});
    @if(auth()->check())
    console.log('👤 User Email:', '{{ auth()->user()->email }}');
    console.log('👤 User Locale (DB):', '{{ auth()->user()->locale ?? "NULL" }}');
    @endif
    console.log('🍪 All Cookies:', document.cookie);
    console.log('🍪 app_locale Cookie:', (function() {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; app_locale=`);
        return parts.length === 2 ? parts.pop().split(';').shift() : 'NOT SET';
    })());
    console.log('🌐 Browser Language:', navigator.language);
    console.log('🌐 Accept-Language:', navigator.languages);
    console.groupEnd();
</script>
