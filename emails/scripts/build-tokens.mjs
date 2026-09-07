/**
 * Generates the email theme from the panel's own tokens.
 *
 * `resources/scripts/app.css` is the single source of truth for Nova's palette,
 * but nothing in it can be handed to an email client as-is: the values are
 * OKLCH (unsupported outside WebKit clients), they are referenced through
 * `var()` (Gmail strips custom properties), and several are translucent
 * (`ring-foreground/10`, `bg-muted/50`), which cannot be relied on to composite
 * over a table cell. So the panel's tokens are read, resolved, flattened
 * against their known surface, and written out as literal hex.
 *
 * Run via `npm run tokens` in this directory; the output is committed so a
 * plain `maizzle build` never depends on the panel's CSS being present.
 */
import { formatHex, converter, parse } from 'culori'
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const here = dirname(fileURLToPath(import.meta.url))
const APP_CSS = resolve(here, '../../resources/scripts/app.css')
const OUT = resolve(here, '../css/tokens.css')

const toRgb = converter('rgb')

/** Pull one `:root`-style block's custom properties into a flat map. */
const readBlock = (css, selector) => {
    // The block we want is the first `<selector> {` at the top of a @layer, and
    // it contains no nested braces, so a non-greedy match to the first `}` is
    // enough — a real CSS parser here would only buy us trouble.
    const match = css.match(
        new RegExp(`${selector}\\s*\\{([\\s\\S]*?)\\n\\s*\\}`, 'm')
    )

    if (!match) throw new Error(`No ${selector} block in app.css`)

    return Object.fromEntries(
        [...match[1].matchAll(/(--[\w-]+)\s*:\s*([^;]+);/g)].map(
            ([, name, value]) => [name, value.trim()]
        )
    )
}

/** `--label: var(--muted-foreground)` — follow the chain to a real colour. */
const deref = (vars, value, depth = 0) => {
    if (depth > 10) throw new Error(`Cyclic var() chain at ${value}`)

    const ref = value.match(/^var\((--[\w-]+)\)$/)

    return ref ? deref(vars, vars[ref[1]], depth + 1) : value
}

/**
 * Flatten alpha against an opaque backdrop. Email gets one composited hex
 * instead of a colour that needs the client to blend correctly — Outlook
 * renders `rgba()` as fully opaque, so a 10% ring would arrive at 100%.
 */
const over = (fg, bg) => {
    const f = toRgb(fg)
    const b = toRgb(bg)
    const a = f.alpha ?? 1

    return formatHex({
        mode: 'rgb',
        r: f.r * a + b.r * (1 - a),
        g: f.g * a + b.g * (1 - a),
        b: f.b * a + b.b * (1 - a),
    })
}

/** Same colour, forced to a given alpha, then composited. */
const mix = (fg, bg, alpha) => over({ ...toRgb(fg), alpha }, bg)

const buildTheme = (vars) => {
    const get = (name) => {
        const raw = vars[name]

        if (!raw) throw new Error(`Missing token ${name}`)

        const parsed = parse(deref(vars, raw))

        if (!parsed) throw new Error(`Unparseable token ${name}: ${raw}`)

        return parsed
    }

    const background = get('--background')
    const card = get('--card')
    const foreground = get('--foreground')
    const muted = get('--muted')

    return {
        // Straight ports — every one of these is opaque in app.css.
        '--color-background': formatHex(background),
        '--color-foreground': formatHex(foreground),
        '--color-card': formatHex(card),
        '--color-card-foreground': formatHex(get('--card-foreground')),
        '--color-muted': formatHex(muted),
        '--color-muted-foreground': formatHex(get('--muted-foreground')),
        '--color-primary': formatHex(get('--primary')),
        '--color-primary-foreground': formatHex(get('--primary-foreground')),
        '--color-destructive': formatHex(get('--destructive')),
        '--color-success': formatHex(get('--success')),

        // Derived, because the panel expresses these as alpha over a surface
        // and email needs the result rather than the recipe.
        //
        // `--border` is opaque in light mode but `oklch(1 0 0 / 10%)` in dark,
        // so it goes through the same compositing path in both — flattening an
        // already-opaque colour is a no-op.
        '--color-border': over(get('--border'), background),

        // Card.tsx's `ring-1 ring-foreground/10`. Outlook drops box-shadow, so
        // this lands as a real 1px border; the colour still has to match.
        '--color-card-ring': mix(foreground, card, 0.1),

        // CardFooter.tsx's `bg-muted/50`, over the card rather than the page.
        '--color-card-footer': mix(muted, card, 0.5),

        // The page gutter around a 600px email. The panel has no token for it
        // (nothing in the app sits outside `--background`), so it is derived
        // the same way the app's own surfaces are: one step of foreground into
        // the background, which keeps the card reading as raised in both modes.
        '--color-canvas': mix(foreground, background, 0.04),
    }
}

const css = readFileSync(APP_CSS, 'utf8')
const light = buildTheme(readBlock(css, ':root'))
const dark = buildTheme(readBlock(css, '\\.dark'))

const declarations = (theme, indent) =>
    Object.entries(theme)
        .map(([name, value]) => `${indent}${name}: ${value};`)
        .join('\n')

/*
 * Dark mode cannot be done the way the panel does it.
 *
 * In the app, `.dark` swaps the value behind `--card` and every utility follows
 * because the utilities reference the variable at runtime. Maizzle resolves
 * those variables to literal hex at build time — that resolution is the entire
 * reason Nova's OKLCH palette is usable in an inbox at all — so by the time the
 * email exists there is no variable left to swap. Redefining `--color-card`
 * inside a media query compiles to nothing.
 *
 * So the dark palette ships as its own set of named tokens and is applied with
 * Tailwind's `dark:` variant, which in v4 is already `prefers-color-scheme`.
 * Media-query rules cannot be inlined, so juice leaves them in a `<style>`
 * block, which is exactly where they need to be.
 */
const suffixed = (theme) =>
    Object.fromEntries(
        Object.entries(theme).map(([name, value]) => [`${name}-dark`, value])
    )

writeFileSync(
    OUT,
    `/*
 * GENERATED — do not edit. Run \`npm run tokens\` from emails/ to refresh.
 *
 * Source: resources/scripts/app.css (:root and .dark). OKLCH resolved to hex
 * and alpha composited against its surface, because neither survives the trip
 * to an email client. See scripts/build-tokens.mjs for why each derived token
 * exists and why the dark set is a parallel palette rather than an override.
 *
 * Use as \`bg-card dark:bg-card-dark\`. Apple Mail and Outlook mac/iOS honour
 * the variant; Outlook Windows ignores it and gets the light palette; the
 * Gmail app force-inverts regardless. The light palette is the real design.
 */
@theme {
${declarations(light, '    ')}

${declarations(suffixed(dark), '    ')}

    --font-sans:
        'Geist Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
        'Helvetica Neue', Arial, sans-serif;

    /* Card.tsx is rounded-xl (Tailwind's default 0.75rem — the panel only
       overrides lg/md/sm), Button.variants.ts is rounded-lg (--radius). */
    --radius-card: 12px;
    --radius-button: 10px;
}
`
)

const changed = Object.entries(light).filter(
    ([name, value]) => dark[name] !== value
).length

console.log(
    `tokens: ${Object.keys(light).length} light + ${Object.keys(dark).length} dark written, ${changed} differ between modes`
)
