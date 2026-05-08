@extends('layouts.admin')

@section('header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="h3 mb-1">Data Penggajian Guru</h1>
            <p class="text-muted mb-0">Kelola dan pantau status pembayaran guru.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">← Kembali ke Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.salary') }}" method="GET" class="row row-cols-lg-auto g-2 align-items-end">
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
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Guru</th>
                            <th class="text-end">Gaji Pokok</th>
                            <th class="text-center">Hari Hadir</th>
                            <th class="text-center">Hari Absen</th>
                            <th class="text-end">Potongan Absensi</th>
                            <th class="text-end">Potongan Terlambat</th>
                            <th class="text-end">Gaji Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salaries as $salary)
                            <tr>
                                <td>{{ $salary->user->name }}</td>
                                <td class="text-end">Rp {{ number_format($salary->base_salary, 0, ',', '.') }}</td>
                                <td class="text-center"><span class="badge bg-success">{{ $salary->total_present_days }}</span></td>
                                <td class="text-center"><span class="badge bg-danger">{{ $salary->total_absent_days }}</span></td>
                                <td class="text-end text-danger">-Rp {{ number_format($salary->deduction_for_absence, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-Rp {{ number_format($salary->deduction_for_late, 0, ',', '.') }}</td>
                                <td class="text-end text-success fw-bold">Rp {{ number_format($salary->total_salary, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge @if($salary->status == 'draft') bg-secondary @elseif($salary->status == 'approved') bg-info @else bg-success @endif text-white">{{ ucfirst($salary->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Tidak ada data penggajian</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $salaries->links() }}
            </div>
        </div>
    </div>

    @if($salaries->count() > 0)
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="border rounded-3 p-3 bg-light">
                            <p class="text-muted small mb-1">Total Gaji Pokok</p>
                            <p class="h5 mb-0">Rp {{ number_format($salaries->sum('base_salary'), 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded-3 p-3 bg-light">
                            <p class="text-muted small mb-1">Total Potongan</p>
                            <p class="h5 mb-0 text-danger">-Rp {{ number_format($salaries->sum('deduction_for_absence') + $salaries->sum('deduction_for_late'), 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded-3 p-3 bg-light">
                            <p class="text-muted small mb-1">Total Gaji Bersih</p>
                            <p class="h5 mb-0 text-success">Rp {{ number_format($salaries->sum('total_salary'), 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
