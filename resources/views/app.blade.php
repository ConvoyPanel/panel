<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="favicon.svg" sizes="any" type="image/svg+xml">

    <!-- Fonts -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

    <!-- Inject Data -->
    @if(!is_null(Auth::user()))
        <script>
            window.ConvoyUser = {!! json_encode(Auth::user()->toReactObject()) !!};
        </script>
    @endif

    @if(!empty($siteConfiguration))
        <script>
            window.SiteConfiguration = {!! json_encode($siteConfiguration) !!};
        </script>
    @endif

    <!-- Scripts -->
    @viteReactRefresh
    @vite('resources/scripts/app.tsx')

    <!-- Analytics -->
    <script defer data-domain="hosted.convoypanel.com"
            src="https://beacon.performave.com/js/script.local.js"></script>
    <script>
        window.plausible = window.plausible || function() {
            (window.plausible.q = window.plausible.q || []).push(arguments)
        }
    </script>
    <script>
        plausible('meta', {
            props: {
                version: '{{ config('app.version') }}',
            },
        })
    </script>
</head>
<body class="font-sans antialiased">
<div id="root"></div>
</body>
</html>
