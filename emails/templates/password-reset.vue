<script setup lang="ts">
const reset = {
  name: '{{ $name }}',
  link: '{{ $link }}',
  expiry: '{{ $expiry }}',
}
</script>

<template>
  <Layout title="Reset your password" preheader="Someone asked to reset the password on your Convoy account.">
    <CardHeader :title="`Hello ${reset.name},`">
      Someone asked to reset the password on your Convoy account.
    </CardHeader>

    <CardContent>
      <Action :href="reset.link">Choose a new password</Action>

      <p class="m-0 mt-5 text-sm leading-relaxed text-muted-foreground dark:text-muted-foreground-dark">
        This link works once and expires in {{ reset.expiry }}. If the button doesn't work,
        paste this into your browser:
      </p>
      <p class="m-0 mt-2 text-sm leading-relaxed break-all text-primary dark:text-primary-dark">
        {{ reset.link }}
      </p>
    </CardContent>

    <CardFooter>
      If you didn't ask for this, ignore it. Your password stays the same unless the link is used.
    </CardFooter>

    <template #footer>
      You received this because a password reset was requested for this address on Convoy.
    </template>
  </Layout>
</template>
