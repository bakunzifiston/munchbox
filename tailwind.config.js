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
            colors: {
                // MunchBox brand palette (from logo)
                munch: {
                    DEFAULT: '#103018',
                    deep: '#0b2412',
                    soft: '#183820',
                },
                box: {
                    DEFAULT: '#D85000',
                    dark: '#B84400',
                    soft: '#FF7A33',
                },
                cream: '#FAFAF8',
                ink: '#142018',
                sage: '#E7EEE9',
                sand: '#F2EDE6',
                // Keep aliases so existing views keep working while we migrate
                forest: {
                    DEFAULT: '#103018',
                    deep: '#0b2412',
                },
                leaf: '#183820',
                toast: '#D85000',
            },
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                display: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
