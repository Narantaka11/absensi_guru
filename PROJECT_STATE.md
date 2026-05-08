# Ringkasan State Proyek (PROJECT_STATE) - Sistem Absensi & Penggajian Guru

## 1. Arsitektur Backend
*   **Framework:** Laravel (PHP).
*   **Pola Desain (Design Pattern):** Model-View-Controller (MVC) yang diperkuat dengan **Service Pattern** (Controller - Service - Model) untuk mengekstrak logika bisnis berat (seperti geofencing dan payroll) agar tidak menumpuk di Controller.
*   **Otentikasi:** Laravel Sanctum (berbasis Token API untuk aplikasi Mobile/Flutter) dan Session-based (untuk Web Dashboard Admin).
*   **Penyimpanan Media:** Local Storage `public/presences` (menyimpan foto bukti check-in/out).

## 2. Alur Absensi (Presence Flow)
1.  **Otentikasi:** Guru melakukan *Login* di aplikasi mobile untuk mendapatkan *Bearer Token* Sanctum.
2.  **Persiapan:** Aplikasi mobile mengambil referensi titik lokasi sekolah via API `Location`.
3.  **Check-In (Pagi):**
    *   Mengirim titik koordinat (GPS) aktual HP dan foto *selfie*.
    *   Sistem memvalidasi *Geofence* (jarak koordinat HP tidak boleh melebihi radius lokasi sekolah yang aktif).
    *   Sistem menghitung selisih jam server dengan batas maksimal (07:00:00).
    *   Rekam data ke database sebagai row baru untuk hari tersebut.
4.  **Check-Out (Sore):**
    *   Mengirim koordinat GPS dan foto bukti kepulangan.
    *   Sistem memvalidasi *Geofence* ulang untuk memastikan masih di area sekolah.
    *   Melakukan *Update* data absen hari tersebut (mengisi kolom `check_out_time`, `check_out_photo`).

## 3. Alur Penggajian (Payroll Flow)
1.  **Kalkulasi (Otomatis/Manual):** Admin memicu pembentukan slip gaji. Sistem menghitung jumlah hari kerja valid (Senin-Jumat) pada bulan tersebut.
2.  **Agregasi Absensi:** Sistem menjumlahkan hari masuk, sakit, izin, mangkir/alfa, dan *total menit keterlambatan* guru tersebut.
3.  **Potongan:** Menghitung potongan mangkir (gaji harian x alfa) dan potongan telat (menit telat x tarif telat).
4.  **Drafting:** Sistem mengurangi Gaji Pokok dengan total potongan. Hasilnya disimpan ke database `salaries` dengan status awal `draft`.
5.  **Review Admin:** Admin dapat melakukan re-kalkulasi ulang jika ada ralat absen, selama status masih `draft`.
6.  **Persetujuan & Pembayaran:** Admin menyetujui (`approved`) dan menandai telah dibayar (`paid`).

## 4. Aturan Bisnis Utama (Core Business Rules)
*   **Validasi Kehadiran Wajib Bukti Fisik:** Setiap Check-in dan Check-out WAJIB menyertakan unggahan Foto.
*   **Keamanan Titik Absen (Geofencing):** Absensi otomatis ditolak jika koordinat guru berada di luar radius meter sekolah yang ditentukan.
*   **Ketepatan Waktu Server:** Acuan jam masuk (07:00:00) dan jam pulang sepenuhnya menggunakan *Jam Server*, bukan jam dari *Device* (HP) guru untuk mencegah kecurangan *time-spoofing*.
*   **Hari Kerja Definitif:** Hari Sabtu dan Minggu tidak dihitung sebagai hari kerja pembagi gaji (hanya Senin-Jumat).
*   **Tarif Potongan Tetap:** Setiap 1 menit keterlambatan akan memotong gaji sebesar Rp 1.000.
*   **Perlindungan Data Gaji:** Slip gaji yang sudah berstatus `paid` (lunas) dikunci permanen sistem dan tidak boleh diubah/dikalkulasi ulang.

## 5. Status Lifecycle Data
**Lifecycle Kehadiran (Tabel `presences` - kolom `status`):**
*   `hadir`: Datang tepat waktu sebelum 07:00:00.
*   `terlambat`: Datang setelah jam 07:00:00 (memicu perhitungan `late_minutes`).
*   `sakit` / `izin` / `tidak_hadir` (Alfa).

**Lifecycle Slip Gaji (Tabel `salaries` - kolom `status`):**
*   `draft`: Baru dihitung, dapat diperbarui otomatis jika absen berubah.
*   `approved`: Disetujui Kepala Sekolah/Admin (Bisa dibatalkan/revert ke `draft`).
*   `paid`: Sudah ditransfer/dibayar (Bentuk Final, tidak bisa direvisi).

## 6. Daftar API Endpoints Utama
Semua rute di bawah ini memiliki awalan (prefix) `/api/v1` dan mengharuskan Header `Authorization: Bearer {token}` (kecuali login).

**Autentikasi:**
*   `POST /auth/login` - Menukar email/password dengan Token.
*   `POST /auth/logout` - Menghancurkan token sesi saat ini.
*   `GET /auth/me` - Menarik data profil diri guru.

**Absensi (Guru):**
*   `POST /presence/check-in` - Mengirim GPS & Foto absensi pagi.
*   `POST /presence/check-out` - Mengirim GPS & Foto absensi sore.
*   `GET /presence/today` - Melihat status tombol (sudah absen/belum) hari ini.
*   `GET /presence/history` - Melihat rekam jejak absen bulanan berhalaman (paginated).
*   `GET /presence/summary` - Rekap jumlah hadir/alfa/sakit bulan ini.

**Lainnya:**
*   Terdapat Endpoint Admin seperti `/admin/presence/*` dan `/admin/recap/*` untuk mengelola data dari aplikasi pengurus.
*   Terdapat Endpoint `LocationController` untuk sinkronisasi titik sekolah.
*   Terdapat Endpoint `PayrollController` (Kemungkinan GET `/payroll/*`) untuk melihat slip bulanan.
