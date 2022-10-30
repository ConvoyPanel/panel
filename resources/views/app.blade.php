<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap">

        <!-- Inject Data -->
        @if(!is_null(Auth::user()))
                <script>
                    window.ConvoyUser = {!! json_encode(Auth::user()->toReactObject()) !!};
                </script>
            @endif

        <!-- Scripts -->
        @viteReactRefresh
        @vite('resources/scripts/main.tsx')
    </head>
    <body class="font-sans antialiased">
        <div id="root"></div>
    </body>
</html>
