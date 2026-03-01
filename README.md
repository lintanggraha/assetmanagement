# Asset Management System

Aplikasi web untuk inventarisasi aset TI, discovery aset, kontrol perubahan berbasis approval, dan monitoring pelanggaran policy.

Repository ini dibangun dengan Laravel dan digunakan untuk mengelola siklus hidup aset (aplikasi, database, server, network, storage, endpoint) dalam satu dashboard operasional.

## Highlights

- Dashboard operasional aset dengan metrik risiko, stale visibility, coverage, dan ringkasan discovery.
- Asset Inventory lengkap: filter/search, detail aset, activity log, dan history discovery.
- Discovery Center:
  - Sinkronisasi otomatis dari katalog legacy (`aplikasi` dan `database`).
  - Ingest manual via payload CSV-like.
  - Mode `catalog_sync`, `manual_seed`, dan `hybrid`.
- Approval workflow untuk perubahan sensitif aset (`approve/reject`).
- Policy Violation Center dengan scanner otomatis dan resolve manual.
- Manajemen user + role + status aktif/nonaktif.
- Role-based access control (`superadmin`, `admin`, `auditor`, `operator`, `viewer`).

## Modul Utama

| Modul | Endpoint | Fungsi |
|---|---|---|
| Dashboard | `/dashboard` | Ringkasan posture aset dan risiko |
| Asset Inventory | `/asset-inventory` | CRUD aset, filter, detail, retire |
| Discovery Center | `/discovery` | Menjalankan dan melihat hasil discovery |
| Approvals | `/approvals` | Approve/reject request perubahan aset |
| Asset Policies | `/asset-policies` | Lihat pelanggaran policy dan resolve |
| User Management | `/users` | Kelola role dan status user |
| User Guide | `/user-guide` | Panduan penggunaan aplikasi |

## Role Matrix

| Role | Visibility | Manage Asset | Run Discovery | Approve Changes | Manage Users |
|---|---|---|---|---|---|
| superadmin | Global | Ya | Ya | Ya | Ya |
| admin | Global | Ya | Ya | Ya | Ya |
| auditor | Global | Tidak | Tidak | Ya | Tidak |
| operator | Own Scope | Ya | Ya | Tidak | Tidak |
| viewer | Own Scope | Tidak | Tidak | Tidak | Tidak |

## Tech Stack

- PHP `^8.2`
- Laravel `^12`
- MySQL/MariaDB
- Blade + Bootstrap/Sneat assets
- Vite (frontend build)

## Prasyarat

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL/MariaDB

## Instalasi Lokal

```bash
git clone https://github.com/lintanggraha/assetmanagement.git
cd assetmanagement
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Konfigurasi `.env`

Set minimal parameter berikut:

```env
APP_NAME="Asset Management System"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=assetmanagement
DB_USERNAME=root
DB_PASSWORD=
```

### Inisialisasi Database

```bash
php artisan migrate
```

Jika diperlukan, atur role user awal secara manual:

```sql
UPDATE users SET role = 'superadmin', is_active = 1 WHERE id = 1;
```

### Jalankan Aplikasi

```bash
php artisan serve
npm run dev
```

## Scheduler & Automation

Kernel menjadwalkan:

- `asset:discover-scheduled` setiap hari jam `02:00`
- `asset:scan-policies` setiap jam

Tambahkan cron (Linux):

```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

Manual run:

```bash
php artisan asset:discover-scheduled
php artisan asset:scan-policies
```

## Format Manual Discovery Payload

Mode `manual_seed`/`hybrid` menerima baris CSV dengan urutan:

```text
name,asset_type,ip_address,port,hostname,environment,criticality,owner_email,owner_name,bank_id
```

Contoh:

```text
Core API,application,10.10.10.15,443,core-api.prod,production,high,owner@company.com,Platform Team,1
Payment DB,database,10.10.20.12,5432,payment-db.prod,production,critical,dba@company.com,DBA Team,2
```

## Command Penting

```bash
php artisan route:list
php artisan test
php artisan optimize:clear
```

## Catatan

- Semua route utama diproteksi middleware `auth` + `active`.
- Akses endpoint sensitif menggunakan middleware `role`.
- Scanner policy menandai otomatis `open/resolved` violation berdasarkan kondisi aset terbaru.

## License

Project ini mengikuti lisensi framework Laravel (MIT), kecuali jika ada kebijakan internal lain di organisasi.
