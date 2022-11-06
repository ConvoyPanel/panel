const defaultTheme = require('tailwindcss/defaultTheme')

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/scripts/**/*.jsx',
        './resources/scripts/**/*.tsx',
    ],

    theme: {
        extend: {
            colors: {
                primary: '#111111',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                'light': '0 4px 4px 0 rgba(0,0,0,.02)'
            }
        },
    },

    plugins: [require('@tailwindcss/forms')],
}
