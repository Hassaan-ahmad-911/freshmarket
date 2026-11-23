import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // FreshMarket green
                brand: {
                    50: '#effdf4',
                    100: '#d9fbe6',
                    200: '#b5f5cf',
                    300: '#7beaab',
                    400: '#3cd57f',
                    500: '#15bc61',
                    600: '#0a9b4d',
                    700: '#0b7a40',
                    800: '#0e6036',
                    900: '#0d4f2f',
                    950: '#032c18',
                },
                ink: '#0f1a14',
            },
            boxShadow: {
                soft: '0 1px 2px rgba(15, 26, 20, 0.04), 0 2px 8px rgba(15, 26, 20, 0.06)',
                lift: '0 18px 40px -12px rgba(15, 26, 20, 0.22)',
                glow: '0 10px 30px -10px rgba(10, 155, 77, 0.55)',
            },
            borderRadius: {
                '4xl': '2rem',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(18px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                'slow-zoom': { '0%': { transform: 'scale(1.12)' }, '100%': { transform: 'scale(1)' } },
                pop: {
                    '0%': { transform: 'scale(1)' },
                    '40%': { transform: 'scale(1.45)' },
                    '70%': { transform: 'scale(0.9)' },
                    '100%': { transform: 'scale(1)' },
                },
                shimmer: { '100%': { transform: 'translateX(100%)' } },
                'toast-in': {
                    '0%': { opacity: '0', transform: 'translateX(110%)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.7s cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fade-in 0.5s ease-out both',
                'slow-zoom': 'slow-zoom 14s ease-out both',
                pop: 'pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)',
                shimmer: 'shimmer 1.4s infinite',
                'toast-in': 'toast-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) both',
            },
        },
    },

    plugins: [forms],
};
