<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Data Penggajian Guru') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-900">
                ← Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('admin.salary') }}" method="GET" class="flex gap-4 flex-wrap items-end">
                        <div>
                            <label class="block text-sm font-medium mb-1">Bulan</label>
                            <select name="month" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ $num == $currentMonth ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tahun</label>
                            <select name="year" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                    <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Filter
                        </button>
                    </form>
                </div>
            </div>

            <!-- Salary Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Daftar Gaji Guru</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Nama Guru</th>
                                    <th class="px-4 py-3 text-right font-semibold">Gaji Pokok</th>
                                    <th class="px-4 py-3 text-center font-semibold">Hari Hadir</th>
                                    <th class="px-4 py-3 text-center font-semibold">Hari Absen</th>
                                    <th class="px-4 py-3 text-right font-semibold">Potongan Absensi</th>
                                    <th class="px-4 py-3 text-right font-semibold">Potongan Terlambat</th>
                                    <th class="px-4 py-3 text-right font-semibold">Gaji Total</th>
                                    <th class="px-4 py-3 text-center font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($salaries as $salary)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3">{{ $salary->user->name }}</td>
                                        <td class="px-4 py-3 text-right">
                                            Rp {{ number_format($salary->base_salary, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded">
                                                {{ $salary->total_present_days }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded">
                                                {{ $salary->total_absent_days }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-red-600 font-semibold">
                                                -Rp {{ number_format($salary->deduction_for_absence, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-red-600 font-semibold">
                                                -Rp {{ number_format($salary->deduction_for_late, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-bold text-lg text-green-600">
                                                Rp {{ number_format($salary->total_salary, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-3 py-1 rounded text-white text-xs font-semibold
                                                @if($salary->status == 'draft') bg-gray-600
                                                @elseif($salary->status == 'approved') bg-blue-600
                                                @else bg-green-600
                                                @endif
                                            ">
                                                {{ ucfirst($salary->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-3 text-center text-gray-500">
                                            Tidak ada data penggajian
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $salaries->links() }}
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            @if($salaries->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Ringkasan Penggajian</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Gaji Pokok</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($salaries->sum('base_salary'), 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Potongan</p>
                                <p class="text-2xl font-bold text-red-600">
                                    -Rp {{ number_format($salaries->sum('deduction_for_absence') + $salaries->sum('deduction_for_late'), 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Gaji Bersih</p>
                                <p class="text-2xl font-bold text-green-600">
                                    Rp {{ number_format($salaries->sum('total_salary'), 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
