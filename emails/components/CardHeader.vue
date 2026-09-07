<script setup lang="ts">
/**
 * CardHeader.tsx is `flex flex-col space-y-1 p-4` with a `text-base
 * leading-snug font-medium` title over a `text-sm text-muted-foreground`
 * description.
 *
 * Two things scale on the way into an inbox. Body text goes 14px -> 16px,
 * because iOS Mail and Gmail silently upscale anything smaller and would undo
 * the type scale for us; padding follows at 16px -> 24px so the ratio between
 * them stays Nova's even though neither number does.
 */
defineProps<{ title: string }>()
</script>

<template>
  <div class="px-6 pt-6 pb-0">
    <h1 class="m-0 text-lg font-medium leading-snug text-card-foreground dark:text-card-foreground-dark">{{ title }}</h1>
    <p
      v-if="$slots.default"
      class="m-0 mt-1 text-base leading-relaxed text-muted-foreground dark:text-muted-foreground-dark"
    >
      <slot />
    </p>
  </div>
</template>
