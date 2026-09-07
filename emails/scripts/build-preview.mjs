/**
 * Builds the design-review page from the shipped Blade views.
 *
 * It reads resources/views/mail, not a preview build, so the page cannot drift
 * from what Laravel sends: the sample data below is substituted into the real
 * template on the way in. A mockup of an email is worth very little, because
 * the whole question is what survives compilation.
 *
 * The dark previews take the built file and re-emit the rules from inside
 * `@media (prefers-color-scheme: dark)` unconditionally. An iframe inherits the
 * host's colour scheme and there is no way to force the query per-frame, so
 * this is the only way to show both palettes on one page. The rules already
 * carry `!important` (they have to beat juice's inline styles), so they land
 * exactly as they would in a dark client.
 */
import { readFileSync, writeFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const here = dirname(fileURLToPath(import.meta.url))
const views = resolve(here, '../../resources/views/mail')
const OUT = resolve(here, '../preview/index.html')

const EMAILS = [
    {
        file: 'test.blade.php',
        name: 'Mail is working',
        trigger: 'Settings → Mail → Send test email',
        sent_by: 'TestMailMessage',
        height: 520,
        data: {
            host: 'smtp.eu.mailgun.org',
            port: '587',
            encryption: 'STARTTLS',
            fromAddress: 'convoy@byeric.wang',
        },
        note: 'The definition list is the panel’s density rule carried into the inbox: the useful answer is not that something arrived but that <em>these</em> credentials are the ones that worked. Stacked dt/dd pairs would be four scroll-lengths in a single column, so the label takes the left track and the value the right.',
    },
    {
        file: 'user-invited.blade.php',
        name: 'Set up your account',
        trigger: 'An admin creates a user',
        sent_by: 'UserInvited',
        height: 520,
        data: {
            name: 'Eric',
            link: 'https://convoy.byeric.wang/auth/invite/9f2c1ab4e77d40c8b1e5a0d3f6b84be2c1a77d40',
            expiry: '3 days',
        },
        note: 'The only template with a real action, so it is the only one with a button. The URL is repeated in full underneath because a link-scanning gateway that rewrites the button leaves the reader with nothing, and it breaks on any character rather than pushing the card sideways.',
    },
    {
        file: 'password-changed.blade.php',
        name: 'Password changed',
        trigger: 'A user changes their password',
        sent_by: 'PasswordChanged',
        height: 460,
        data: {
            ipAddress: '203.0.113.42',
        },
        note: 'A security notice, so the tinted footer carries the one instruction that matters and the body stays short. The address row is wrapped in Blade’s @if: when no IP was recorded the list is not rendered empty, it is not rendered.',
    },
]

/**
 * Render a Blade view against the sample data. Only the two constructs these
 * templates actually use are supported, deliberately: anything more would be a
 * second Blade engine to keep honest.
 */
const renderBlade = (source, data) =>
    source
        .replace(
            /@if \(\$(\w+)\)([\s\S]*?)@endif/g,
            (_, name, body) => (data[name] ? body : '')
        )
        .replace(/\{\{\s*\$(\w+)\s*\}\}/g, (match, name) =>
            name in data ? data[name] : match
        )

/** srcdoc parses entities, so escaping these four is enough to embed a whole document. */
const escapeAttr = (html) =>
    html
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')

/** Lift the dark rules out of their media query so an iframe can show them. */
const forceDark = (html) => {
    const start = html.indexOf('@media (prefers-color-scheme: dark)')

    if (start === -1) return html

    // Walk braces from the query's opening `{` to find its matching close.
    let depth = 0
    let i = html.indexOf('{', start)
    const open = i

    for (; i < html.length; i++) {
        if (html[i] === '{') depth++
        else if (html[i] === '}' && --depth === 0) break
    }

    const inner = html.slice(open + 1, i)

    return html.replace('</head>', `<style>${inner}</style></head>`)
}

const previews = EMAILS.map((email) => {
    const html = renderBlade(
        readFileSync(resolve(views, email.file), 'utf8'),
        email.data
    )

    return {
        ...email,
        light: escapeAttr(html),
        dark: escapeAttr(forceDark(html)),
        bytes: Buffer.byteLength(html),
    }
})

const frame = (preview, mode) => `
        <iframe
          class="frame"
          data-mode="${mode}"
          title="${preview.name}, ${mode} mode"
          loading="eager"
          style="height:${preview.height}px"
          srcdoc="${preview[mode]}"
        ></iframe>`

const section = (preview, index) => `
      <section class="email" id="email-${index}">
        <div class="email__meta">
          <p class="eyebrow">${preview.trigger}</p>
          <h2>${preview.name}</h2>
          <p class="note">${preview.note}</p>
          <dl class="stat">
            <dt>Sent by</dt>
            <dd>${preview.sent_by}</dd>
            <dt>Built</dt>
            <dd>${(preview.bytes / 1024).toFixed(1)} KB</dd>
          </dl>
        </div>
        <div class="email__frames">${frame(preview, 'light')}${frame(preview, 'dark')}</div>
      </section>`

/*
 * Token translation table. Hand-written rather than generated: the interesting
 * column is *why* a value changed, and only the source files know that.
 */
const TRANSLATIONS = [
    [
        'Card edge',
        'ring-1 ring-foreground/10',
        '1px solid #e7e7e6',
        'box-shadow does nothing in Outlook, so the ring is redrawn as a border in the composited colour',
    ],
    [
        'Card fill',
        'bg-card',
        '#ffffff',
        'straight port; oklch(1 0 0) resolved to hex',
    ],
    [
        'Footer tint',
        'bg-muted/50',
        '#f9f9f8',
        'Outlook renders rgba() fully opaque, so the 50% is composited over the card at build time',
    ],
    [
        'Body text',
        'text-sm (14px)',
        '16px',
        'iOS Mail and Gmail silently upscale anything smaller, which would undo the scale anyway',
    ],
    [
        'Card padding',
        'p-4 (16px)',
        '24px',
        'follows the type up so the ratio stays Nova’s, even though neither number does',
    ],
    [
        'Button',
        'h-8 px-2.5 (32px tall)',
        '~46px tall',
        'a 32px target is a mouse in a dense panel; 44px is the iOS minimum for a thumb',
    ],
    [
        'Corner radius',
        'rounded-xl (12px)',
        '12px',
        'ported as-is, and simply ignored by Outlook on Windows',
    ],
    [
        'Dark mode',
        '.dark class',
        'prefers-color-scheme',
        'no class to toggle in an inbox; the dark palette ships as its own token set',
    ],
]

const CLIENTS = [
    ['Apple Mail', 'full', 'Both palettes, radius, webfont'],
    ['Gmail web', 'most', 'Light palette, radius; no webfont'],
    ['Gmail app', 'partial', 'Force-inverts regardless of the dark tokens'],
    ['Outlook mac / iOS', 'full', 'Both palettes'],
    ['Outlook Windows', 'partial', 'Light palette, square corners, Segoe UI'],
]

const page = `<title>Nova in the Inbox</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  /*
   * The review page wears the same palette it is reviewing — these are the
   * panel's own tokens from resources/scripts/app.css, resolved to hex the same
   * way the email build resolves them.
   */
  :root {
    --bg: #ffffff;
    --card: #ffffff;
    --fg: #0c0c09;
    --muted: #f4f4f0;
    --muted-fg: #70705c;
    --border: #e8e8e3;
    --ring: #e7e7e6;
    --primary: #0069a8;
    --canvas: #f5f5f5;
    --radius: 12px;
    --sans: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --mono: 'Geist Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
  }

  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      --bg: #0c0c09;
      --card: #1d1d16;
      --fg: #fbfbf9;
      --muted: #2b2b22;
      --muted-fg: #abab9c;
      --border: #242422;
      --ring: #33332d;
      --primary: #4aa8dd;
      --canvas: #161613;
    }
  }

  :root[data-theme="dark"] {
    --bg: #0c0c09;
    --card: #1d1d16;
    --fg: #fbfbf9;
    --muted: #2b2b22;
    --muted-fg: #abab9c;
    --border: #242422;
    --ring: #33332d;
    --primary: #4aa8dd;
    --canvas: #161613;
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    background: var(--bg);
    color: var(--fg);
    font-family: var(--sans);
    font-size: 15px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
  }

  .wrap {
    max-width: 1180px;
    margin: 0 auto;
    padding: 40px 24px 96px;
    display: flex;
    flex-direction: column;
    gap: 56px;
  }

  h1, h2, h3 { margin: 0; font-weight: 500; letter-spacing: -0.02em; text-wrap: balance; }
  h1 { font-size: 28px; line-height: 1.2; }
  h2 { font-size: 19px; line-height: 1.3; }
  h3 { font-size: 15px; }
  p { margin: 0; }

  .eyebrow {
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--muted-fg);
  }

  /* Header ----------------------------------------------------------- */
  header { display: flex; flex-direction: column; gap: 20px; }

  .header__lede {
    max-width: 62ch;
    color: var(--muted-fg);
  }

  .header__bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
  }

  .pipeline {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    font-family: var(--mono);
    font-size: 12px;
    color: var(--muted-fg);
  }
  .pipeline span { padding: 3px 8px; background: var(--muted); border-radius: 6px; }
  .pipeline i { font-style: normal; opacity: 0.5; }

  /* Theme toggle ----------------------------------------------------- */
  .toggle {
    display: inline-flex;
    padding: 2px;
    background: var(--muted);
    border-radius: 10px;
    gap: 2px;
  }
  .toggle button {
    appearance: none;
    border: 1px solid transparent;
    background: transparent;
    color: var(--muted-fg);
    font: 500 13px/1 var(--sans);
    padding: 7px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 120ms, color 120ms;
  }
  .toggle button[aria-pressed="true"] {
    background: var(--card);
    color: var(--fg);
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.06);
  }
  .toggle button:focus-visible { outline: 3px solid color-mix(in srgb, var(--primary) 45%, transparent); outline-offset: 1px; }

  /* Email sections --------------------------------------------------- */
  .email {
    display: grid;
    grid-template-columns: 600px minmax(0, 1fr);
    gap: 40px;
    align-items: start;
  }
  .email__frames { order: -1; }

  .frame {
    width: 600px;
    max-width: 100%;
    border: 1px solid var(--ring);
    border-radius: var(--radius);
    background: var(--canvas);
    display: block;
  }
  .frame[data-mode="dark"] { display: none; }
  body[data-preview="dark"] .frame[data-mode="light"] { display: none; }
  body[data-preview="dark"] .frame[data-mode="dark"] { display: block; }

  .email__meta { display: flex; flex-direction: column; gap: 10px; position: sticky; top: 32px; }
  .note { color: var(--muted-fg); font-size: 14px; max-width: 46ch; }

  .stat { margin: 4px 0 0; display: flex; gap: 8px; align-items: baseline; }
  .stat dt { font-size: 12px; color: var(--muted-fg); }
  .stat dd { margin: 0; font-family: var(--mono); font-size: 12px; font-variant-numeric: tabular-nums; }

  /* Tables ----------------------------------------------------------- */
  .panel {
    border: 1px solid var(--ring);
    border-radius: var(--radius);
    background: var(--card);
    overflow: hidden;
  }
  .panel__head { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 2px; }
  .panel__head p { color: var(--muted-fg); font-size: 13px; }
  .scroll { overflow-x: auto; }

  table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  th {
    text-align: left;
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--muted-fg);
    padding: 10px 20px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
  }
  td { padding: 12px 20px; border-bottom: 1px solid var(--border); vertical-align: top; }
  tr:last-child td { border-bottom: 0; }
  td.code { font-family: var(--mono); font-size: 12.5px; white-space: nowrap; }
  td.why { color: var(--muted-fg); min-width: 30ch; }

  .swatch {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 3px;
    border: 1px solid var(--ring);
    margin-right: 7px;
    vertical-align: baseline;
  }

  /* Support pills ---------------------------------------------------- */
  .pill {
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 999px;
    border: 1px solid transparent;
  }
  .pill--full { background: color-mix(in srgb, #009966 14%, transparent); color: #007a52; border-color: color-mix(in srgb, #009966 30%, transparent); }
  .pill--most { background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary); border-color: color-mix(in srgb, var(--primary) 28%, transparent); }
  .pill--partial { background: color-mix(in srgb, #c07600 14%, transparent); color: #9a5e00; border-color: color-mix(in srgb, #c07600 30%, transparent); }
  :root[data-theme="dark"] .pill--full, :root[data-theme="dark"] .pill--partial { color: #fbfbf9; }

  .grid2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; align-items: start; }

  @media (max-width: 1080px) {
    .email { grid-template-columns: minmax(0, 1fr); gap: 20px; }
    .email__meta { position: static; }
    .email__frames { order: 0; }
  }

  @media (prefers-reduced-motion: reduce) {
    * { transition: none !important; }
  }
</style>

<div class="wrap">
  <header>
    <p class="eyebrow">Convoy · transactional mail</p>
    <h1>Nova in the inbox</h1>
    <p class="header__lede">
      The panel’s three transactional emails, built with Maizzle from its own tokens. Each preview
      below is the shipped Blade view rendered with sample data in an iframe, not a mockup, so what
      you are looking at is what a client receives.
    </p>
    <div class="header__bar">
      <div class="pipeline">
        <span>app.css</span><i>&rarr;</i>
        <span>oklch to hex</span><i>&rarr;</i>
        <span>alpha composited</span><i>&rarr;</i>
        <span>@theme</span><i>&rarr;</i>
        <span>Maizzle</span><i>&rarr;</i>
        <span>resources/views/mail</span>
      </div>
      <div class="toggle" role="group" aria-label="Preview palette">
        <button type="button" data-preview="light" aria-pressed="true">Light</button>
        <button type="button" data-preview="dark" aria-pressed="false">Dark</button>
      </div>
    </div>
  </header>
${previews.map(section).join('\n')}

  <div class="grid2">
    <div class="panel">
      <div class="panel__head">
        <h3>What changed on the way in</h3>
        <p>Everything else is a straight port.</p>
      </div>
      <div class="scroll">
        <table>
          <thead>
            <tr><th>Element</th><th>Panel</th><th>Email</th><th>Why</th></tr>
          </thead>
          <tbody>
${TRANSLATIONS.map(
    ([el, panel, email, why]) => `            <tr>
              <td>${el}</td>
              <td class="code">${panel}</td>
              <td class="code">${
                  email.startsWith('#') || email.includes('#')
                      ? `<span class="swatch" style="background:${email.match(/#[0-9a-f]{6}/i)?.[0] ?? 'transparent'}"></span>${email}`
                      : email
              }</td>
              <td class="why">${why}</td>
            </tr>`
).join('\n')}
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head">
        <h3>Where it lands</h3>
        <p>The light palette is the real design; dark is an improvement where it is honoured.</p>
      </div>
      <div class="scroll">
        <table>
          <thead>
            <tr><th>Client</th><th>Support</th><th>Gets</th></tr>
          </thead>
          <tbody>
${CLIENTS.map(
    ([client, level, gets]) => `            <tr>
              <td>${client}</td>
              <td><span class="pill pill--${level}">${level}</span></td>
              <td class="why">${gets}</td>
            </tr>`
).join('\n')}
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  // Palette switch for the previews. The page's own theme follows the viewer's
  // client; this only swaps which iframe is shown.
  const buttons = document.querySelectorAll('.toggle button')

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const mode = button.dataset.preview
      document.body.dataset.preview = mode
      buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === button)))
    })
  })

  // Fit each frame to its content. The inline heights above are close enough
  // that nothing jumps, but the real height depends on how the webfont lands.
  const fit = (frame) => {
    try {
      const doc = frame.contentDocument
      if (doc) frame.style.height = doc.documentElement.scrollHeight + 'px'
    } catch {
      /* Same-origin srcdoc, but never let a measurement break the page. */
    }
  }

  document.querySelectorAll('.frame').forEach((frame) => {
    frame.addEventListener('load', () => fit(frame))
    fit(frame)
  })

  // Webfonts settle after first paint and change the height underneath us.
  if (document.fonts) {
    document.fonts.ready.then(() =>
      setTimeout(() => document.querySelectorAll('.frame').forEach(fit), 60)
    )
  }
</script>
`

writeFileSync(OUT, page)

console.log(
    `preview: ${previews.length} emails embedded, ${(Buffer.byteLength(page) / 1024).toFixed(0)} KB`
)
