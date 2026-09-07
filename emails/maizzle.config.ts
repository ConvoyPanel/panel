import { defineConfig } from '@maizzle/framework'

export default defineConfig({
    content: ['templates/**/*.vue'],
    /*
     * Straight into the Laravel view path, as Blade. There is no intermediate
     * `dist/` to copy from and nothing to keep in sync: the file Laravel
     * renders is the build output, and the design review reads the same file.
     *
     * The result is committed so a deploy never needs Node — this whole
     * directory is a build-time dependency of the design, not of the app.
     */
    output: {
        path: '../resources/views/mail',
        extension: 'blade.php',
    },
    components: {
        folders: ['components'],
    },
    css: {
        inline: true,
        purge: true,
        shorthand: true,
        sixHex: true,
    },
    /*
     * Lint the built HTML against caniemail. Worth keeping on: it is the only
     * thing in the toolchain that knows `box-shadow` and `border-radius` do
     * nothing in Outlook, which is exactly where a design ported from a web app
     * goes wrong. Warnings only — plenty of what it flags is a deliberate
     * progressive enhancement.
     */
    checks: {
        clients: ['gmail', 'outlook', 'apple-mail', 'yahoo', 'protonmail'],
        level: 'warning',
    },
})
