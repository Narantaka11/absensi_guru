<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detail Absensi {{ $presence->user->name }}
            </h2>
            <a href="{{ route('teacher.detail', ['user' => $presence->user_id, 'month' => $presence->presence_date->month, 'year' => $presence->presence_date->year]) }}" class="text-blue-600 hover:text-blue-900">
                ← Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Basic Info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Informasi Dasar</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Nama</p>
                            <p class="text-gray-900 dark:text-white font-semibold">{{ $presence->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Tanggal</p>
                            <p class="text-gray-900 dark:text-white font-semibold">{{ $presence->presence_date->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Status</p>
                            <p class="text-gray-900 dark:text-white font-semibold">
                                <span class="inline-block px-3 py-1 rounded text-white text-sm
                                    @if($presence->status == 'hadir') bg-green-600
                                    @elseif($presence->status == 'terlambat') bg-yellow-600
                                    @elseif($presence->status == 'tidak_hadir') bg-red-600
                                    @elseif($presence->status == 'sakit') bg-blue-600
                                    @else bg-gray-600
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $presence->status)) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Lokasi Valid (Radius Sekolah)</p>
                            <p class="text-gray-900 dark:text-white font-semibold">
                                @if($isWithinRadius)
                                    <span class="text-green-600">✓ Valid</span>
                                @else
                                    <span class="text-red-600">✗ Tidak Valid</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check-in Information -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Informasi Check-In</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Jam Masuk</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $presence->check_in_time ? $presence->check_in_time->format('H:i:s') : '-' }}
                            </p>
                            @if($presence->late_minutes > 0)
                                <p class="text-red-600 text-sm mt-1">Terlambat: {{ $presence->late_minutes }} menit</p>
                            @endif
                        </div>

                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Lokasi (GPS)</p>
                            @if($presence->check_in_latitude && $presence->check_in_longitude)
                                <p class="text-gray-900 dark:text-white font-mono text-sm">
                                    {{ $presence->check_in_latitude }}, {{ $presence->check_in_longitude }}
                                </p>
                                <a href="https://maps.google.com/?q={{ $presence->check_in_latitude }},{{ $presence->check_in_longitude }}"
                                   target="_blank" class="text-blue-600 hover:text-blue-900 text-sm mt-1">
                                    Lihat di Google Maps →
                                </a>
                            @else
                                <p class="text-gray-500">-</p>
                            @endif
                        </div>
                    </div>

                    <!-- Check-in Photo -->
                    @if($presence->check_in_photo)
                        <div class="mt-6">
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Foto Check-In</p>
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $presence->check_in_photo) }}"
                                     alt="Check-in photo"
                                     class="max-w-md h-auto rounded-lg shadow">
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Check-out Information -->
            @if($presence->check_out_time)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Informasi Check-Out</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Jam Keluar</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $presence->check_out_time->format('H:i:s') }}
                                </p>
                                @if($workHours)
                                    <p class="text-blue-600 text-sm mt-1">Jam Kerja: {{ number_format($workHours, 2) }} jam</p>
                                @endif
                            </div>

                            <div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Lokasi (GPS)</p>
                                @if($presence->check_out_latitude && $presence->check_out_longitude)
                                    <p class="text-gray-900 dark:text-white font-mono text-sm">
                                        {{ $presence->check_out_latitude }}, {{ $presence->check_out_longitude }}
                                    </p>
                                    <a href="https://maps.google.com/?q={{ $presence->check_out_latitude }},{{ $presence->check_out_longitude }}"
                                       target="_blank" class="text-blue-600 hover:text-blue-900 text-sm mt-1">
                                        Lihat di Google Maps →
                                    </a>
                                @else
                                    <p class="text-gray-500">-</p>
                                @endif
                            </div>
                        </div>

                        <!-- Check-out Photo -->
                        @if($presence->check_out_photo)
                            <div class="mt-6">
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Foto Check-Out</p>
                                <div class="relative group">
                                    <img src="{{ asset('storage/' . $presence->check_out_photo) }}"
                                         alt="Check-out photo"
                                         class="max-w-md h-auto rounded-lg shadow">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if($presence->notes)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Catatan</h3>
                        <p class="text-gray-900 dark:text-white">{{ $presence->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
