@extends('layouts.admin')

@section('header')
    <div>
        <h1 class="h3 mb-1">Admin Dashboard - Rekap Absensi Guru</h1>
        <p class="text-muted mb-0">Pantau kehadiran dan penggajian guru dengan cepat.</p>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-12 col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-secondary mb-3">Total Presensi</h6>
                    <div class="display-6 fw-bold">{{ $statistics['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-secondary mb-3">Hadir</h6>
                    <div class="display-6 text-success fw-bold">{{ $statistics['present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-secondary mb-3">Terlambat</h6>
                    <div class="display-6 text-warning fw-bold">{{ $statistics['late'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-secondary mb-3">Tidak Hadir</h6>
                    <div class="display-6 text-danger fw-bold">{{ $statistics['absent'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                <div>
                    <h5 class="mb-1">Rata-rata Kehadiran</h5>
                    <p class="text-muted mb-0">Persentase kehadiran guru bulan ini.</p>
                </div>
                <span class="badge bg-primary fs-5">{{ $statistics['average_attendance'] }}%</span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title mb-1">Rekap Absensi Guru</h5>
                    <p class="text-muted mb-0">Filter bulan dan tahun untuk melihat data detail.</p>
                </div>
                <form action="{{ route('admin.dashboard') }}" method="GET" class="row row-cols-lg-auto g-2 align-items-center mb-0">
                    <div class="col-12">
                        <label class="form-label small mb-1">Bulan</label>
                        <select name="month" class="form-select form-select-sm">
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}" {{ $num == $currentMonth ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Tahun</label>
                        <select name="year" class="form-select form-select-sm">
                            @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Guru</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Terlambat</th>
                            <th class="text-center">Tidak Hadir</th>
                            <th class="text-center">Sakit</th>
                            <th class="text-center">Izin</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Persentase</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceSummary as $record)
                            <tr>
                                <td>{{ $record['teacher']->name }}</td>
                                <td class="text-center"><span class="badge bg-success">{{ $record['present'] }}</span></td>
                                <td class="text-center"><span class="badge bg-warning text-dark">{{ $record['late'] }}</span></td>
                                <td class="text-center"><span class="badge bg-danger">{{ $record['absent'] }}</span></td>
                                <td class="text-center">{{ $record['sick'] }}</td>
                                <td class="text-center">{{ $record['permission'] }}</td>
                                <td class="text-center fw-semibold">{{ $record['total'] }}</td>
                                <td class="text-center"><span class="fw-semibold">{{ $record['percentage'] }}%</span></td>
                                <td class="text-center">
                                    <a href="{{ route('teacher.detail', ['user' => $record['teacher']->id, 'month' => $currentMonth, 'year' => $currentYear]) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Tidak ada data guru</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.salary') }}" class="btn btn-success">Lihat Data Penggajian</a>
    </div>
@endsection
