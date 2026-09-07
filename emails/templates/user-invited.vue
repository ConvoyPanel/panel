<script setup lang="ts">
const invite = {
  name: '{{ $name }}',
  link: '{{ $link }}',
  expiry: '{{ $expiry }}',
}
</script>

<template>
  <Layout title="Set up your account" preheader="An account has been created for you on Convoy.">
    <CardHeader :title="`Hello ${invite.name},`">
      An account has been created for you on Convoy.
    </CardHeader>

    <CardContent>
      <Action :href="invite.link">Set your password</Action>

      <p class="m-0 mt-5 text-sm leading-relaxed text-muted-foreground dark:text-muted-foreground-dark">
        This link works once and expires in {{ invite.expiry }}. If the button does not work,
        paste this into your browser:
      </p>
      <p class="m-0 mt-2 text-sm leading-relaxed break-all text-primary dark:text-primary-dark">
        {{ invite.link }}
      </p>
    </CardContent>

    <CardFooter>
      If you weren't expecting this, ignore it. The account can't be used until the link is.
    </CardFooter>

    <template #footer>
      You received this because an administrator created a Convoy account for this address.
    </template>
  </Layout>
</template>
