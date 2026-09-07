<script setup lang="ts">
/**
 * Button.variants.ts default: `bg-primary text-primary-foreground rounded-lg
 * h-8 px-2.5 text-sm font-medium`.
 *
 * The fill, the radius and the weight port verbatim. The 32px height does not:
 * that is a pointer target in a dense admin panel, and the iOS minimum for a
 * thumb is 44px. Padding is opened up to clear it and the label takes body
 * size, so the button reads as the same button, sized for the device that will
 * actually receive it.
 */
defineProps<{ href: string }>()
</script>

<template>
  <Button
    :href="href"
    class="rounded-button bg-primary px-5 py-3.5 text-base font-medium leading-none text-primary-foreground dark:bg-primary-dark dark:text-primary-foreground-dark"
  >
    <slot />
  </Button>
</template>
