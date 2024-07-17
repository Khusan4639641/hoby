<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | {{ config('app.name', 'test') }}</title>

    <!-- Styles -->
    @stack('css')

    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@200;300;400;600;700;900&display=swap"
        rel="stylesheet">
</head>

<body>
    <div id="collector-app" class="@yield('class')"></div>

    <!-- Scripts -->
    <script src="{{ mix('dist/collector/collectorApp.js') }}"></script>

    @stack('js')
</body>

</html>
