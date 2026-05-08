<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Admin Dashboard - Rekap Absensi Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('admin.dashboard') }}" method="GET" class="flex gap-4 flex-wrap items-end">
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

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Presensi</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $statistics['total'] }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-green-600 dark:text-green-400 text-sm font-medium">Hadir</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ $statistics['present'] }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-yellow-600 dark:text-yellow-400 text-sm font-medium">Terlambat</div>
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">{{ $statistics['late'] }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-red-600 dark:text-red-400 text-sm font-medium">Tidak Hadir</div>
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">{{ $statistics['absent'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Average Attendance Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="text-gray-600 dark:text-gray-400 text-sm font-medium">Rata-rata Kehadiran</div>
                    <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        {{ $statistics['average_attendance'] }}%
                    </div>
                </div>
            </div>

            <!-- Teachers Attendance Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Rekap Absensi Guru</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Nama Guru</th>
                                    <th class="px-4 py-3 text-center font-semibold">Hadir</th>
                                    <th class="px-4 py-3 text-center font-semibold">Terlambat</th>
                                    <th class="px-4 py-3 text-center font-semibold">Tidak Hadir</th>
                                    <th class="px-4 py-3 text-center font-semibold">Sakit</th>
                                    <th class="px-4 py-3 text-center font-semibold">Izin</th>
                                    <th class="px-4 py-3 text-center font-semibold">Total</th>
                                    <th class="px-4 py-3 text-center font-semibold">Persentase</th>
                                    <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($attendanceSummary as $record)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3">{{ $record['teacher']->name }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded">
                                                {{ $record['present'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded">
                                                {{ $record['late'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded">
                                                {{ $record['absent'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ $record['sick'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $record['permission'] }}</td>
                                        <td class="px-4 py-3 text-center font-semibold">{{ $record['total'] }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="font-semibold">{{ $record['percentage'] }}%</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="{{ route('teacher.detail', ['user' => $record['teacher']->id, 'month' => $currentMonth, 'year' => $currentYear]) }}"
                                               class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-3 text-center text-gray-500">
                                            Tidak ada data guru
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Links -->
            <div class="mt-6 flex gap-4">
                <a href="{{ route('admin.salary') }}" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Lihat Data Penggajian
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
