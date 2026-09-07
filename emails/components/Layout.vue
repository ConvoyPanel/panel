<script setup lang="ts">
/**
 * The Nova email shell: document head, canvas, brand line, card, footer.
 *
 * Deliberately not Maizzle's built-in `<Layout>` — that one hardcodes Inter
 * from Google Fonts and imports the stock Tailwind theme, and we need Geist
 * and the panel's generated tokens instead. `<Head>` still supplies charset,
 * viewport and the Apple reformatting opt-out, so none of those are repeated
 * here.
 */
defineProps<{
    /** The inbox preview line. Always set one; clients fall back to body text. */
    preheader: string
    /** Accessible name for the message, read before the body. */
    title: string
}>()
</script>

<template>
    <Html>
        <Head>
            <meta
                name="format-detection"
                content="telephone=no, date=no, address=no, email=no, url=no"
            />
            <!--
              Opt in to both schemes so Apple Mail and Outlook mac/iOS use the
              dark palette instead of running their own inversion over the
              light one, which is what mangles a tinted-neutral palette.
            -->
            <meta name="color-scheme" content="light dark" />
            <meta name="supported-color-schemes" content="light dark" />
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link
                rel="preconnect"
                href="https://fonts.gstatic.com"
                crossorigin="anonymous"
            />
            <link
                href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&display=swap"
                rel="stylesheet"
                media="screen"
            />
            <!--[if mso]>
                <style>
                    td,th,div,p,a,h1,h2,h3,h4,h5,h6 { font-family: 'Segoe UI', sans-serif; mso-line-height-rule: exactly; }
                </style>
            <![endif]-->
        </Head>
        <body
            class="m-0 w-full bg-canvas p-0 [word-break:break-word] dark:bg-canvas-dark"
        >
            <Tailwind>
                <template #config>
                    @import '@maizzle/tailwindcss'; @import '../css/tokens.css';
                </template>

                <div
                    role="article"
                    aria-roledescription="email"
                    :aria-label="title"
                    lang="en"
                    class="bg-canvas font-sans text-base text-foreground dark:bg-canvas-dark dark:text-foreground-dark"
                >
                    <Preheader>{{ preheader }}</Preheader>

                    <Container class="px-2 py-8">
                        <!--
                          The brand line sits outside the card, quiet and small,
                          the way the panel's sidebar wordmark does. A big
                          centred logo lockup would be the one element in the
                          message with no counterpart in the product.
                        -->
                        <p
                            class="m-0 mb-3 px-1 text-sm font-medium tracking-tight text-muted-foreground dark:text-muted-foreground-dark"
                        >
                            Convoy
                        </p>

                        <!--
                          Card.tsx is `rounded-xl bg-card ring-1
                          ring-foreground/10`. The ring is a box-shadow, which
                          Outlook drops entirely, so it is redrawn here as a
                          real 1px border in the composited ring colour.
                        -->
                        <table
                            class="w-full rounded-card border border-solid border-card-ring bg-card dark:border-card-ring-dark dark:bg-card-dark"
                            role="none"
                            cellpadding="0"
                            cellspacing="0"
                        >
                            <tr>
                                <td>
                                    <slot />
                                </td>
                            </tr>
                        </table>

                        <div
                            class="px-1 pt-5 text-[13px] leading-relaxed text-muted-foreground dark:text-muted-foreground-dark"
                        >
                            <slot name="footer" />
                        </div>
                    </Container>
                </div>
            </Tailwind>
        </body>
    </Html>
</template>
