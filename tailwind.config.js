import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                },
                secondary: {
                    DEFAULT: '#6B7280',
                    dark: '#4B5563',
                    light: '#F3F4F6',
                },
                success: {
                    DEFAULT: '#10B981',
                    light: '#D1FAE5',
                },
                danger: {
                    DEFAULT: '#EF4444',
                    light: '#FEE2E2',
                },
            },
        },
    },

    plugins: [forms],
};
