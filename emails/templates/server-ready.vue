<script setup lang="ts">
const server = {
  name: '{{ $name }}',
  serverName: '{{ $serverName }}',
  hostname: '{{ $hostname }}',
  ipv4: '{{ $ipv4 }}',
  ipv6: '{{ $ipv6 }}',
  link: '{{ $link }}',
}
</script>

<template>
  <Layout :title="`${server.serverName} is ready`" preheader="Your new server has finished installing.">
    <CardHeader :title="`Hello ${server.name},`">
      {{ server.serverName }} has finished installing and is ready to use.
    </CardHeader>

    <CardContent>
      <!--
        Either address can be missing, so each row is wrapped in Blade's @if.
        The hostname is always there, which is why it is the last row: `last`
        drops the bottom border, and it can't follow a row that may not render.
      -->
      <DataList>
        @if ($ipv4)
        <DataRow label="IPv4 address" :value="server.ipv4" />
        @endif
        @if ($ipv6)
        <DataRow label="IPv6 address" :value="server.ipv6" />
        @endif
        <DataRow label="Hostname" :value="server.hostname" last />
      </DataList>

      <div class="pt-5">
        <Action :href="server.link">Open in Convoy</Action>
      </div>
    </CardContent>

    <template #footer>
      You received this because a server was created for your Convoy account.
    </template>
  </Layout>
</template>
