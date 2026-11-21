<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        <title>{{ config('app.name', 'Wallet App') }}</title>

        @fonts

        @viteReactRefresh
        @vite('resources/js/app.tsx')
    </head>

    <body>
        <div id="root"></div>
    </body>
</html>
