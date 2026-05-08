<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detail Absensi: {{ $user->name }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-900">
                ← Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-green-600 text-sm font-medium">Hadir</div>
                        <div class="text-3xl font-bold text-green-600 mt-2">{{ $summary['present'] }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-yellow-600 text-sm font-medium">Terlambat</div>
                        <div class="text-3xl font-bold text-yellow-600 mt-2">{{ $summary['late'] }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-red-600 text-sm font-medium">Tidak Hadir</div>
                        <div class="text-3xl font-bold text-red-600 mt-2">{{ $summary['absent'] }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-blue-600 text-sm font-medium">Sakit</div>
                        <div class="text-3xl font-bold text-blue-600 mt-2">{{ $summary['sick'] }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-600 text-sm font-medium">Izin</div>
                        <div class="text-3xl font-bold text-gray-600 mt-2">{{ $summary['permission'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Salary Info if exists -->
            @if($salary)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Data Penggajian</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Gaji Pokok</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($salary->base_salary, 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Gaji Total</p>
                                <p class="text-xl font-bold text-green-600">
                                    Rp {{ number_format($salary->total_salary, 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Potongan Absensi</p>
                                <p class="text-xl font-bold text-red-600">
                                    Rp {{ number_format($salary->deduction_for_absence, 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Potongan Keterlambatan</p>
                                <p class="text-xl font-bold text-red-600">
                                    Rp {{ number_format($salary->deduction_for_late, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded">
                            <p class="text-sm">Status: <span class="font-semibold">{{ ucfirst($salary->status) }}</span></p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Attendance Records -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Riwayat Absensi Bulan ini</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                                    <th class="px-4 py-3 text-center font-semibold">Jam Masuk</th>
                                    <th class="px-4 py-3 text-center font-semibold">Jam Keluar</th>
                                    <th class="px-4 py-3 text-center font-semibold">Terlambat (Menit)</th>
                                    <th class="px-4 py-3 text-center font-semibold">Status</th>
                                    <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($presences as $presence)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3">{{ $presence->presence_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            {{ $presence->check_in_time ? $presence->check_in_time->format('H:i') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            {{ $presence->check_out_time ? $presence->check_out_time->format('H:i') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($presence->late_minutes > 0)
                                                <span class="text-red-600 font-semibold">{{ $presence->late_minutes }}</span>
                                            @else
                                                <span class="text-gray-600">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-3 py-1 rounded text-white text-xs font-semibold
                                                @if($presence->status == 'hadir') bg-green-600
                                                @elseif($presence->status == 'terlambat') bg-yellow-600
                                                @elseif($presence->status == 'tidak_hadir') bg-red-600
                                                @elseif($presence->status == 'sakit') bg-blue-600
                                                @else bg-gray-600
                                                @endif
                                            ">
                                                {{ ucfirst(str_replace('_', ' ', $presence->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="{{ route('presence.detail', $presence) }}" class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400">
                                                Lihat
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-3 text-center text-gray-500">
                                            Tidak ada data absensi
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $presences->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
