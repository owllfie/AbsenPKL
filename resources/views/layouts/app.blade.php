<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PKL App')</title>
    @include('layouts.styles')
</head>
<body class="@yield('body_class')">
    <div class="page-shell">
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>

        <main class="content-wrap">
            @yield('content')
        </main>
    </div>
</body>
</html>
