<template>
  <!--
    The panel's `<dl>` density pattern, turned sideways. Stacked dt-over-dd
    works in a 400px card beside three others; in a single-column email eight
    stacked pairs is a scroll, so the label takes the left column and the value
    the right. Same tokens, same muted-label/solid-value relationship.
  -->
  <table class="w-full" role="none" cellpadding="0" cellspacing="0">
    <slot />
  </table>
</template>
