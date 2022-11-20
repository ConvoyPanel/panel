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
                error: {
                    lighter: 'var(--color-error-lighter)',
                    light: 'var(--color-error-light)',
                    DEFAULT: 'var(--color-error)',
                    dark: 'var(--color-error-dark)',
                },
                success: {
                    lighter: 'var(--color-success-lighter)',
                    light: 'var(--color-success-light)',
                    DEFAULT: 'var(--color-success)',
                    dark: 'var(--color-success-dark)',
                },
                foreground: 'var(--color-foreground)',
                background: 'var(--color-background)',
                accent: {
                    100: 'var(--color-accent-1)',
                    200: 'var(--color-accent-2)',
                    300: 'var(--color-accent-3)',
                    400: 'var(--color-accent-4)',
                    500: 'var(--color-accent-5)',
                    600: 'var(--color-accent-6)',
                    700: 'var(--color-accent-7)',
                    800: 'var(--color-accent-8)',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                'light': '0 4px 4px 0 rgba(0,0,0,.02)'
            },
            screens: {
                'xs': '512px'
            },
            borderRadius: {
                DEFAULT: '5px',
            }
        },
    },

    plugins: [require('@tailwindcss/forms')],
}
