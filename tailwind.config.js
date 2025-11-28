/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
            },
        },
    },
    plugins: [
        // RTL Support Plugin - Adds logical property utilities
        function({ addUtilities, theme, e }) {
            // Generate margin-inline utilities (ms-*, me-*)
            const spacing = theme('spacing');
            const marginInline = {};

            Object.keys(spacing).forEach(key => {
                marginInline[`.${e(`ms-${key}`)}`] = {
                    'margin-inline-start': spacing[key],
                };
                marginInline[`.${e(`me-${key}`)}`] = {
                    'margin-inline-end': spacing[key],
                };
                marginInline[`.${e(`-ms-${key}`)}`] = {
                    'margin-inline-start': `-${spacing[key]}`,
                };
                marginInline[`.${e(`-me-${key}`)}`] = {
                    'margin-inline-end': `-${spacing[key]}`,
                };
            });

            // Generate padding-inline utilities (ps-*, pe-*)
            const paddingInline = {};

            Object.keys(spacing).forEach(key => {
                paddingInline[`.${e(`ps-${key}`)}`] = {
                    'padding-inline-start': spacing[key],
                };
                paddingInline[`.${e(`pe-${key}`)}`] = {
                    'padding-inline-end': spacing[key],
                };
            });

            // Text alignment utilities
            const textAlignment = {
                '.text-start': {
                    'text-align': 'start',
                },
                '.text-end': {
                    'text-align': 'end',
                },
            };

            // Border radius logical properties
            const borderRadius = {
                '.rounded-s': {
                    'border-start-start-radius': '0.25rem',
                    'border-end-start-radius': '0.25rem',
                },
                '.rounded-e': {
                    'border-start-end-radius': '0.25rem',
                    'border-end-end-radius': '0.25rem',
                },
                '.rounded-s-lg': {
                    'border-start-start-radius': '0.5rem',
                    'border-end-start-radius': '0.5rem',
                },
                '.rounded-e-lg': {
                    'border-start-end-radius': '0.5rem',
                    'border-end-end-radius': '0.5rem',
                },
            };

            addUtilities(marginInline);
            addUtilities(paddingInline);
            addUtilities(textAlignment);
            addUtilities(borderRadius);
        }
    ],
}
