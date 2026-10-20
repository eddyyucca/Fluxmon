import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

// Fluxa palette (fluxa.co.id): navy surfaces with sky and teal accents.
// The views use the "indigo" and "cyan" names for primary and accent, so those names are
// remapped. Primary sits one step darker (sky 700 for sky 600's role) to keep white text readable.
const sky = colors.sky;

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                indigo: {
                    50: sky[50], 100: sky[100], 200: sky[200], 300: sky[300], 400: sky[400],
                    500: sky[600], 600: sky[700], 700: sky[800], 800: sky[900], 900: sky[950],
                },
                cyan: colors.teal,
            },
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
