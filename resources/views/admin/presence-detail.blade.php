@extends('layouts.admin')

@section('header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="h3 mb-1">Detail Absensi {{ $presence->user->name }}</h1>
            <p class="text-muted mb-0">Detail lengkap untuk absensi guru.</p>
        </div>
        <a href="{{ route('teacher.detail', ['user' => $presence->user_id, 'month' => $presence->presence_date->month, 'year' => $presence->presence_date->year]) }}" class="btn btn-outline-primary btn-sm">← Kembali</a>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Informasi Dasar</h5>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <p class="text-muted small mb-1">Nama</p>
                            <p class="fw-semibold mb-0">{{ $presence->user->name }}</p>
                        </div>
                        <div class="col-12 col-md-6">
                            <p class="text-muted small mb-1">Tanggal</p>
                            <p class="fw-semibold mb-0">{{ $presence->presence_date->format('d M Y') }}</p>
                        </div>
                        <div class="col-12 col-md-6">
                            <p class="text-muted small mb-1">Status</p>
                            <span class="badge @if($presence->status == 'hadir') bg-success @elseif($presence->status == 'terlambat') bg-warning text-dark @elseif($presence->status == 'tidak_hadir') bg-danger @elseif($presence->status == 'sakit') bg-info text-dark @else bg-secondary @endif">{{ ucfirst(str_replace('_', ' ', $presence->status)) }}</span>
                        </div>
                        <div class="col-12 col-md-6">
                            <p class="text-muted small mb-1">Lokasi Valid</p>
                            <p class="mb-0">@if($isWithinRadius) <span class="text-success">✓ Valid</span> @else <span class="text-danger">✗ Tidak Valid</span> @endif</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Informasi Check-In</h5>
                    <p class="text-muted small mb-1">Jam Masuk</p>
                    <p class="h4 mb-2">{{ $presence->check_in_time ? $presence->check_in_time->format('H:i:s') : '-' }}</p>
                    @if($presence->late_minutes > 0)
                        <p class="text-danger small mb-0">Terlambat: {{ $presence->late_minutes }} menit</p>
                    @endif
                    <hr>
                    <p class="text-muted small mb-1">Lokasi (GPS)</p>
                    @if($presence->check_in_latitude && $presence->check_in_longitude)
                        <p class="mb-1">{{ $presence->check_in_latitude }}, {{ $presence->check_in_longitude }}</p>
                        <a href="https://maps.google.com/?q={{ $presence->check_in_latitude }},{{ $presence->check_in_longitude }}" target="_blank" class="link-primary small">Lihat di Google Maps →</a>
                    @else
                        <p class="text-muted mb-0">-</p>
                    @endif
                </div>
            </div>
        </div>

        @if($presence->check_out_time)
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Informasi Check-Out</h5>
                        <p class="text-muted small mb-1">Jam Keluar</p>
                        <p class="h4 mb-2">{{ $presence->check_out_time->format('H:i:s') }}</p>
                        @if($workHours)
                            <p class="text-info small mb-0">Jam Kerja: {{ number_format($workHours, 2) }} jam</p>
                        @endif
                        <hr>
                        <p class="text-muted small mb-1">Lokasi (GPS)</p>
                        @if($presence->check_out_latitude && $presence->check_out_longitude)
                            <p class="mb-1">{{ $presence->check_out_latitude }}, {{ $presence->check_out_longitude }}</p>
                            <a href="https://maps.google.com/?q={{ $presence->check_out_latitude }},{{ $presence->check_out_longitude }}" target="_blank" class="link-primary small">Lihat di Google Maps →</a>
                        @else
                            <p class="text-muted mb-0">-</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($presence->check_in_photo)
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Foto Check-In</h5>
                        <img src="{{ asset('storage/' . $presence->check_in_photo) }}" alt="Check-in photo" class="img-fluid rounded">
                    </div>
                </div>
            </div>
        @endif

        @if($presence->check_out_time && $presence->check_out_photo)
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Foto Check-Out</h5>
                        <img src="{{ asset('storage/' . $presence->check_out_photo) }}" alt="Check-out photo" class="img-fluid rounded">
                    </div>
                </div>
            </div>
        @endif

        @if($presence->notes)
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Catatan</h5>
                        <p class="mb-0">{{ $presence->notes }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
