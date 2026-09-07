<script setup lang="ts">
/*
 * Blade expressions, not preview data. The build emits a .blade.php straight
 * into resources/views/mail, so what the design review renders and what
 * Laravel sends are the same file; scripts/build-preview.mjs substitutes
 * sample values on its way into the review page rather than the other way
 * round.
 *
 * Every binding is a flat scalar on purpose. `{{ $server->name }}` would put a
 * `>` inside a Vue text interpolation and come out entity-encoded, so the
 * Mailable flattens its view data instead.
 */
const mail = {
  host: '{{ $host }}',
  port: '{{ $port }}',
  encryption: '{{ $encryption }}',
  from: '{{ $fromAddress }}',
}
</script>

<template>
  <Layout title="Mail is working" preheader="Convoy sent this using the SMTP settings you just saved.">
    <CardHeader title="Mail is working">
      Convoy sent this using the SMTP settings you just saved.
    </CardHeader>

    <CardContent>
      <DataList>
        <DataRow label="Host" :value="mail.host" />
        <DataRow label="Port" :value="mail.port" />
        <DataRow label="Encryption" :value="mail.encryption" />
        <DataRow label="From" :value="mail.from" last />
      </DataList>
    </CardContent>

    <CardFooter>
      Sent from Settings &rarr; Mail. Nothing was changed.
    </CardFooter>

    <template #footer>
      You received this because you have administrator access to Convoy.
    </template>
  </Layout>
</template>
