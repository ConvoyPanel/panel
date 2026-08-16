const defaultTheme = require('tailwindcss/defaultTheme')

/** @type {import('tailwindcss').Config} */
const config = {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/scripts/**/*.jsx',
        './resources/scripts/**/*.tsx',
        './resources/scripts/**/*.js',
        './resources/scripts/**/*.ts',
    ],
    theme: {
        container: {
            center: true,
            padding: '2rem',
            screens: {
                '2xl': '1400px',
            },
        },
        extend: {
            colors: {
                border: 'var(--border)',
                input: 'var(--input)',
                ring: 'var(--ring)',
                background: 'var(--background)',
                foreground: 'var(--foreground)',
                label: 'var(--label)',
                primary: {
                    DEFAULT: 'var(--primary)',
                    foreground: 'var(--primary-foreground)',
                },
                secondary: {
                    DEFAULT: 'var(--secondary)',
                    foreground: 'var(--secondary-foreground)',
                },
                destructive: {
                    DEFAULT: 'var(--destructive)',
                    foreground: 'var(--destructive-foreground)',
                },
                success: 'var(--success)',
                muted: {
                    DEFAULT: 'var(--muted)',
                    foreground: 'var(--muted-foreground)',
                },
                accent: {
                    DEFAULT: 'var(--accent)',
                    foreground: 'var(--accent-foreground)',
                },
                popover: {
                    DEFAULT: 'var(--popover)',
                    foreground: 'var(--popover-foreground)',
                },
                card: {
                    DEFAULT: 'var(--card)',
                    foreground: 'var(--card-foreground)',
                },
                sidebar: {
                    'DEFAULT': 'var(--sidebar)',
                    'foreground': 'var(--sidebar-foreground)',
                    'primary': 'var(--sidebar-primary)',
                    'primary-foreground': 'var(--sidebar-primary-foreground)',
                    'accent': 'var(--sidebar-accent)',
                    'accent-foreground': 'var(--sidebar-accent-foreground)',
                    'border': 'var(--sidebar-border)',
                    'ring': 'var(--sidebar-ring)',
                },
                chart: {
                    1: 'var(--chart-1)',
                    2: 'var(--chart-2)',
                    3: 'var(--chart-3)',
                    4: 'var(--chart-4)',
                    5: 'var(--chart-5)',
                    cpu: 'var(--chart-cpu)',
                    memory: 'var(--chart-memory)',
                    disk: 'var(--chart-disk)',
                    network: 'var(--chart-network)',
                },
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
            keyframes: {
                'accordion-down': {
                    from: {
                        height: '0',
                    },
                    to: {
                        height: 'var(--radix-accordion-content-height)',
                    },
                },
                'accordion-up': {
                    from: {
                        height: 'var(--radix-accordion-content-height)',
                    },
                    to: {
                        height: '0',
                    },
                },
                'spin-ease': {
                    '0%': { transform: 'rotate(0deg)' },
                    '100%': { transform: 'rotate(360deg)' },
                },
                // A single sliver crossing the whole segmented row. It is one
                // element laid over the segments rather than one per segment:
                // a sliver clipped inside each segment shrinks to nothing at
                // every boundary and swells again on the other side, which is
                // the opposite of what an indeterminate bar should read as.
                // `left` is a percentage of the row, so the width stays put.
                // Travel for the first three quarters of the cycle, then hold
                // off the right edge for the last quarter — a beat between
                // passes, so it reads as a repeating sweep rather than a belt
                // running continuously.
                'pip-sweep': {
                    '0%': { left: '-22%' },
                    '75%': { left: '100%' },
                    '100%': { left: '100%' },
                },
            },
            animation: {
                'accordion-down': 'accordion-down 0.2s ease-out',
                'accordion-up': 'accordion-up 0.2s ease-out',
                'spin-ease': 'spin-ease 1s ease-in-out infinite',
                'pip-sweep': 'pip-sweep 2.4s linear infinite',
            },
            fontFamily: {
                sans: ['Geist Sans', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
}

module.exports = config
