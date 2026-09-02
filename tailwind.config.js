import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#f0f7ed',
                    100: '#e8f3e2',
                    400: '#7cb46a',
                    500: '#5a9450',
                    600: '#4a7c3f',
                    700: '#3d6335',
                    800: '#2d4a2b',
                    900: '#1e3320',
                },
                accent: {
                    400: '#f09a52',
                    500: '#e28a3d',
                    600: '#d4763a',
                },
                cream: '#fdfaf3',
                sand:  '#f5ebd8',
            },
            boxShadow: {
                card:    '0 4px 20px rgba(45,74,43,0.10)',
                'card-lg': '0 10px 30px rgba(45,74,43,0.12)',
            },
        },
    },

    plugins: [forms],
};
