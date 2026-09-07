<script setup lang="ts">
const change = {
  ip: '{{ $ipAddress }}',
}
</script>

<template>
  <Layout
    title="Your password was changed"
    preheader="Every other signed-in device was signed out."
  >
    <CardHeader title="Your password was changed">
      The password on your account was just changed, and every other signed-in device was
      signed out.
    </CardHeader>

    <CardContent>
      <!--
        The IP is optional, so the whole list is. Blade control flow survives
        the build as plain text: `@` is only special to Vue in attribute
        position, and Maizzle's minifier leaves text nodes alone.
      -->
      @if ($ipAddress)
      <DataList>
        <DataRow label="Requested from" :value="change.ip" last />
      </DataList>
      @endif

      <p class="m-0 pt-4 text-base leading-relaxed text-card-foreground dark:text-card-foreground-dark">
        If you made this change, you can ignore this email.
      </p>
    </CardContent>

    <CardFooter>
      If you did not, someone else has your account: reset your password now, and review your
      active sessions and two-factor settings.
    </CardFooter>

    <template #footer>
      You received this because your Convoy password changed. It is sent even when the change
      was expected.
    </template>
  </Layout>
</template>
