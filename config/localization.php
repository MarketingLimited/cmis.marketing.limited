<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Localization Configuration
    |--------------------------------------------------------------------------
    |
    | Configure supported languages, RTL support, and locale settings
    |
    */

    /**
     * Supported locales
     */
    'supported_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇬🇧',
            'direction' => 'ltr',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'flag' => '🇸🇦',
            'direction' => 'rtl',
        ],
    ],

    /**
     * Default locale
     */
    'default' => env('APP_LOCALE', 'en'),

    /**
     * Fallback locale
     */
    'fallback' => 'en',

    /**
     * Enable locale detection from browser
     */
    'detect_browser_locale' => true,

    /**
     * Enable user preference override
     */
    'user_preference' => true,

    /**
     * Date/Time formatting per locale
     */
    'date_formats' => [
        'en' => [
            'date' => 'm/d/Y',
            'time' => 'h:i A',
            'datetime' => 'm/d/Y h:i A',
        ],
        'ar' => [
            'date' => 'd/m/Y',
            'time' => 'h:i A',
            'datetime' => 'd/m/Y h:i A',
        ],
    ],

    /**
     * Number formatting per locale
     */
    'number_formats' => [
        'en' => [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'currency_position' => 'before', // $100
        ],
        'ar' => [
            'decimal_separator' => '٫',
            'thousands_separator' => '٬',
            'currency_position' => 'after', // 100 ر.س
            'use_arabic_numerals' => false, // Use Western numerals for compatibility
        ],
    ],

    /**
     * Currency symbols per locale
     */
    'currency_symbols' => [
        'USD' => '$',
        'SAR' => 'ر.س',
        'AED' => 'د.إ',
        'EGP' => 'ج.م',
    ],

    /**
     * RTL-specific CSS class
     */
    'rtl_class' => 'rtl',

    /**
     * Translation caching
     */
    'cache' => [
        'enabled' => env('TRANSLATION_CACHE', true),
        'ttl' => 3600, // 1 hour
    ],

];
