<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Absensi Guru') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/admin.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('admin.dashboard') }}">
                {{ config('app.name', 'Absensi Guru') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.salary')) active @endif" href="{{ route('admin.salary') }}">Penggajian</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-white small d-none d-lg-inline">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <aside class="sidebar bg-dark text-white p-3 d-none d-lg-block">
            <div class="mb-4">
                <h5 class="text-uppercase fw-bold mb-1">Admin Panel</h5>
                <p class="small text-muted mb-0">Absensi & Penggajian</p>
            </div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link px-3 py-2 rounded @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="nav-link px-3 py-2 rounded @if(request()->routeIs('admin.salary')) active @endif" href="{{ route('admin.salary') }}">Penggajian</a>
            </nav>
        </aside>

        <main class="flex-fill p-4" style="min-height: calc(100vh - 56px);">
            <div class="container-fluid">
                @isset($header)
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                        {{ $header }}
                    </div>
                @endisset

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
