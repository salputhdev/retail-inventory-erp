<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5" style="max-width: 28rem">
        <h1 class="h4 mb-4 text-center">{{ config('app.name') }}</h1>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                @if (session('status'))
                    <div class="alert alert-info" role="status">{{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>
</body>
</html>
