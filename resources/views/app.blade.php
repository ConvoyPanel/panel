<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Manage virtual servers, infrastructure, networking, and backups with Convoy.">

    <title>{{ config('app.name', 'Convoy') }}</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" sizes="any" type="image/svg+xml">

    @if(!empty($siteConfiguration))
        <script>
            window.SiteConfiguration = {!! json_encode($siteConfiguration) !!};
        </script>
    @endif

    <!-- Scripts -->
    @viteReactRefresh
    @vite('resources/scripts/app.tsx')
</head>
<body class="font-sans antialiased">
<div id="root"></div>
</body>
</html>
