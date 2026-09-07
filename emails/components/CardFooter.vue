<template>
  <!--
    CardFooter.tsx is `bg-muted/50 border-t p-4 last:rounded-b-xl`. The tint is
    composited into --color-card-footer at build time because Outlook renders
    rgba() fully opaque, which would put a hard grey slab under every message.

    The border is written as four explicit widths rather than `border-0
    border-t`: Maizzle's shorthand optimiser folds a uniform width + style +
    colour back into `border: 0 solid …` and emits it *after* the longhand,
    which silently zeroes the edge again. Unequal widths cannot be folded.
  -->
  <div
    class="rounded-b-card border-x-0 border-t border-b-0 border-solid border-border bg-card-footer px-6 py-4 text-sm leading-relaxed text-muted-foreground dark:border-border-dark dark:bg-card-footer-dark dark:text-muted-foreground-dark"
  >
    <slot />
  </div>
</template>
