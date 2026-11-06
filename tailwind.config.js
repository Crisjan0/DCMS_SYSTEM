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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Soft mint / emerald brand palette
                clinic: {
                    50: '#f3fbf7',
                    100: '#e3f6ee',
                    200: '#c7ecdd',
                    300: '#9ddcc6',
                    400: '#63c8a7',
                    500: '#2eb385',
                    600: '#1a946b',
                    700: '#157656',
                    800: '#135e46',
                    900: '#114e3b',
                    950: '#0a2f25',
                },
            },
        },
    },

    plugins: [forms],
};
