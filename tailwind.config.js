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
                cream: '#F7F5F0',
                ink: '#142018',
                sage: '#E7EEE9',
                sand: '#F2EDE6',
                forest: {
                    DEFAULT: '#103018',
                    deep: '#0b2412',
                },
                leaf: '#183820',
                toast: '#D85000',
            },
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                menu: '0 22px 50px -28px rgba(16, 48, 24, 0.45)',
                'menu-hover': '0 32px 60px -24px rgba(16, 48, 24, 0.5)',
                soft: '0 10px 30px -18px rgba(16, 48, 24, 0.28)',
            },
            keyframes: {
                floaty: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                fadeUp: {
                    from: { opacity: '0', transform: 'translateY(18px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                floaty: 'floaty 5s ease-in-out infinite',
                'fade-up': 'fadeUp 0.6s ease both',
            },
        },
    },

    plugins: [forms],
};
