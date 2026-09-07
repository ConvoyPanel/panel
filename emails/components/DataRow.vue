<script setup lang="ts">
defineProps<{ label: string; value: string; last?: boolean }>()
</script>

<template>
  <tr>
    <td
      class="border-x-0 border-t-0 border-solid border-border py-2.5 pr-4 align-top text-sm text-muted-foreground dark:border-border-dark dark:text-muted-foreground-dark"
      :class="last ? 'border-b-0' : 'border-b'"
      >{{ label }}</td>
    <td
      class="border-x-0 border-t-0 border-solid border-border py-2.5 text-right align-top text-base text-card-foreground dark:border-border-dark dark:text-card-foreground-dark"
      :class="last ? 'border-b-0' : 'border-b'"
      >{{ value }}</td>
  </tr>
</template>
