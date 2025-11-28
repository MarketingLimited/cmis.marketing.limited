{{--
    JavaScript Translations Component

    Injects translation strings into JavaScript for Alpine.js components

    Usage:
    <x-js-translations />  <!-- Loads all common translations -->
    <x-js-translations keys="javascript" />  <!-- Load specific translation file -->
    <x-js-translations :keys="['javascript', 'messages']" />  <!-- Load multiple files -->
--}}

<script>
    /**
     * Global translations object for JavaScript
     * Access via: window.__translations['javascript.confirm_delete']
     * Or use helper: __(key)
     */
    window.__translations = window.__translations || {};

    @php
        $translations = $getTranslations($keys ?? null);
    @endphp

    // Inject PHP translations into JavaScript
    window.__translations = Object.assign(window.__translations, @json($translations));

    /**
     * Translation helper function (similar to Laravel's __() helper)
     *
     * @param {string} key - Translation key (e.g., 'javascript.confirm_delete')
     * @param {object} replace - Key-value pairs for placeholder replacement
     * @returns {string} Translated string
     *
     * Example:
     * __('javascript.confirm_delete')
     * __('javascript.forecasts_generated', { count: 5 })
     */
    window.__ = function(key, replace = {}) {
        // Split key into parts (e.g., 'javascript.confirm_delete' -> ['javascript', 'confirm_delete'])
        const parts = key.split('.');
        let translation = window.__translations;

        // Navigate through nested object
        for (const part of parts) {
            if (translation && typeof translation === 'object' && part in translation) {
                translation = translation[part];
            } else {
                // Key not found, return the key itself
                console.warn(`Translation key not found: ${key}`);
                return key;
            }
        }

        // If translation is not a string, return the key
        if (typeof translation !== 'string') {
            console.warn(`Translation is not a string for key: ${key}`);
            return key;
        }

        // Replace placeholders (e.g., :count, :name)
        let result = translation;
        for (const [placeholder, value] of Object.entries(replace)) {
            result = result.replace(new RegExp(`:${placeholder}`, 'g'), value);
        }

        return result;
    };

    /**
     * Get current locale
     */
    window.getCurrentLocale = function() {
        return document.documentElement.lang || 'ar';
    };

    /**
     * Check if current locale is RTL
     */
    window.isRTL = function() {
        return window.getCurrentLocale() === 'ar';
    };
</script>
