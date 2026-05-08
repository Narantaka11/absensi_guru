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
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <!-- Logo/Brand Section -->
                    <div class="text-center mb-4">
                        <div class="mb-3" style="height: 80px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('images/tutwurihandayani.png') }}" alt="Logo Sekolah" class="img-fluid" style="max-height: 80px;">
                        </div>
                        <h1 class="h3 mb-1">{{ config('app.name', 'Absensi Guru') }}</h1>
                        <p class="text-muted">Sistem Absensi & Penggajian Guru</p>
                    </div>

                    <!-- Main Content -->
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4">
                            @yield('content')
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center mt-3">
                        <p class="text-muted small mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

