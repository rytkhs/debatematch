import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Noto Sans JP"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#4F46E5',
                    dark: '#4338CA',
                    light: '#E0E7FF',
                    '700': '#3730a3',
                },
                secondary: {
                    DEFAULT: '#6B7280',
                    dark: '#4B5563',
                    light: '#F3F4F6',
                },
                success: {
                    DEFAULT: '#10B981',
                    light: '#D1FAE5',
                    '700': '#047857',
                },
                danger: {
                    DEFAULT: '#EF4444',
                    light: '#FEE2E2',
                    '700': '#b91c1c',
                },
            },
            boxShadow: {
                'form': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
            },
        },
    },

    plugins: [forms, typography],
};
