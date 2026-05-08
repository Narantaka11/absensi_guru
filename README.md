
# Absensi Guru

Project sistem absensi guru + payroll berbasis Laravel 12 dengan fitur GPS geofencing, manajemen kehadiran, dan penggajian otomatis.

---

## 🚀 Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum (API Authentication)
- Laravel Breeze (Web Authentication)
- Vite + Tailwind CSS + Alpine.js
- SQLite (default) / MySQL (opsional)

---

## 📌 Fitur Utama

###  Absensi Guru (Mobile API)
- Login menggunakan token (Sanctum)
- Check-in & check-out dengan GPS
- Validasi radius sekolah (geofencing)
- Upload foto bukti absensi
- Riwayat & rekap absensi guru

###  Payroll System
- Perhitungan gaji otomatis berdasarkan absensi
- Potongan keterlambatan (Rp 1.000 / menit)
- Potongan alfa / tidak hadir
- Status payroll: `draft → approved → paid`
- Slip gaji per bulan per guru

###  Admin Dashboard (Web)
- Manajemen data guru
- Monitoring absensi semua guru
- Generate payroll bulanan
- Approve & mark payroll sebagai paid
- Rekap absensi harian / mingguan / bulanan

---

##  Role User

- `admin` → full access (dashboard + payroll + monitoring)
- `teacher` → absensi + lihat data pribadi (presence & payroll)

---

##  API Base

Semua endpoint API menggunakan prefix: /api/v1
Auth menggunakan: Authorization: Bearer {token}

---

## 📱 Endpoint Utama

###  Auth
- POST `/auth/login`
- POST `/auth/logout`
- GET `/auth/me`

---

###  Presence (Guru)
- POST `/presence/check-in`
- POST `/presence/check-out`
- GET `/presence/today`
- GET `/presence/history`
- GET `/presence/summary`

---

###  Payroll (Guru)
- GET `/payroll/me`
- GET `/payroll/history`

---

###  Admin Payroll
- GET `/admin/payroll`
- GET `/admin/payroll/{salary}`
- POST `/admin/payroll/generate`
- POST `/admin/payroll/{salary}/approve`
- POST `/admin/payroll/{salary}/paid`
- POST `/admin/payroll/{salary}/revert`

---

###  Admin Presence & Recap
- GET `/admin/presences`
- GET `/admin/presences/{id}`
- GET `/admin/teachers`
- GET `/admin/recap/daily`
- GET `/admin/recap/weekly`
- GET `/admin/recap/monthly`

---

##  Instalasi Project

Clone repository lalu install dependency:

```bash
composer install
npm install

Buat file environment:
```bash
cp .env.example .env
php artisan key:generate

---

## Setup Database
Default menggunakan SQLite:

```bash
touch database/database.sqlite
php artisan migrate --seed
 
Jika pakai MySQL, ubah .env sesuai konfigurasi database.

## Menjalankan Project

Jalankan backend:

```bash 
php artisan serve
 
Jalankan frontend asset:

```bash 
npm run dev
 
Atau mode development:

```bash
composer run dev

### Default Akun (Seeder)
Admin
Email: admin@sekolah.com
Password: password

Teacher
Email: teacher@sekolah.com
Password: password

#### Struktur Project

app/Http/Controllers/API → API Controller (Auth, Presence, Payroll)
app/Services → Business logic (Attendance, Payroll, Recap)
app/Models → Model database
database/migrations → Struktur tabel
database/seeders → Data dummy awal
routes/api.php → API routing
routes/web.php → Web dashboard admin