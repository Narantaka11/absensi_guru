@extends('layouts.admin')

@section('header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="h3 mb-1">Detail Absensi: {{ $user->name }}</h1>
            <p class="text-muted mb-0">Ringkasan kehadiran dan penggajian guru untuk bulan ini.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">← Kembali</a>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Hadir</h5>
                    <p class="display-6 text-success mb-0">{{ $summary['present'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Terlambat</h5>
                    <p class="display-6 text-warning mb-0">{{ $summary['late'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Tidak Hadir</h5>
                    <p class="display-6 text-danger mb-0">{{ $summary['absent'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Sakit</h5>
                    <p class="display-6 text-primary mb-0">{{ $summary['sick'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Izin</h5>
                    <p class="display-6 text-secondary mb-0">{{ $summary['permission'] }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($salary)
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Data Penggajian</h5>
                    <span class="badge bg-success">{{ ucfirst($salary->status) }}</span>
                </div>
                <div class="row gy-3">
                    <div class="col-12 col-md-6">
                        <p class="text-muted small mb-1">Gaji Pokok</p>
                        <p class="h5 mb-0">Rp {{ number_format($salary->base_salary, 0, ',', '.') }}</p>
                    </div>
                    <div class="col-12 col-md-6">
                        <p class="text-muted small mb-1">Gaji Total</p>
                        <p class="h5 text-success mb-0">Rp {{ number_format($salary->total_salary, 0, ',', '.') }}</p>
                    </div>
                    <div class="col-12 col-md-6">
                        <p class="text-muted small mb-1">Potongan Absensi</p>
                        <p class="h5 text-danger mb-0">Rp {{ number_format($salary->deduction_for_absence, 0, ',', '.') }}</p>
                    </div>
                    <div class="col-12 col-md-6">
                        <p class="text-muted small mb-1">Potongan Keterlambatan</p>
                        <p class="h5 text-danger mb-0">Rp {{ number_format($salary->deduction_for_late, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Riwayat Absensi Bulan Ini</h5>
                <span class="text-muted">{{ $currentMonth }}/{{ $currentYear }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-center">Jam Masuk</th>
                            <th class="text-center">Jam Keluar</th>
                            <th class="text-center">Terlambat</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($presences as $presence)
                            <tr>
                                <td>{{ $presence->presence_date->format('d M Y') }}</td>
                                <td class="text-center">{{ $presence->check_in_time ? $presence->check_in_time->format('H:i') : '-' }}</td>
                                <td class="text-center">{{ $presence->check_out_time ? $presence->check_out_time->format('H:i') : '-' }}</td>
                                <td class="text-center">{{ $presence->late_minutes > 0 ? $presence->late_minutes . ' menit' : '-' }}</td>
                                <td class="text-center">
                                    <span class="badge @if($presence->status == 'hadir') bg-success @elseif($presence->status == 'terlambat') bg-warning text-dark @elseif($presence->status == 'tidak_hadir') bg-danger @elseif($presence->status == 'sakit') bg-info text-dark @else bg-secondary @endif">
                                        {{ ucfirst(str_replace('_', ' ', $presence->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('presence.detail', $presence) }}" class="btn btn-outline-primary btn-sm">Lihat</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada data absensi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $presences->links() }}
            </div>
        </div>
    </div>
@endsection
