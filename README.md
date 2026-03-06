# Asset Management System

Aplikasi web untuk inventarisasi aset TI (CMDB-lite), governance perubahan, dan monitoring compliance.

## Status Fitur Saat Ini

- Discovery Center disembunyikan dari tombol/menu UI.
- Input dan update aset difokuskan ke form manual di Asset Inventory.
- Field `source` dibatasi menjadi `manual` dan `import`.
- `criticality` dihitung otomatis dari EOL terdekat (OS dan/atau license DB).
- Notifikasi EOL tampil di Asset Ops Center, Asset Inventory, dan Asset Detail.

## Modul Utama

| Modul | Endpoint | Fungsi |
|---|---|---|
| Dashboard | `/dashboard` | Ringkasan operasional aset, risiko, dan notifikasi EOL |
| Asset Inventory | `/asset-inventory` | CRUD aset, filter, detail, retire |
| Approvals | `/approvals` | Approve/reject perubahan sensitif |
| Asset Policies | `/asset-policies` | Monitoring violation dan resolve |
| User Management | `/users` | Kelola user, role, status |
| User Guide | `/user-guide` | Panduan operasional aplikasi |
| Discovery (hidden) | `/discovery` | Route tetap ada, tetapi tidak ditampilkan di UI |

## Cakupan Data Aset

Asset type yang dipakai:

- `application`
- `application_server`
- `database_server`
- `network_peripheral`
- `etc`

Contoh data penting per aset:

- Metadata umum: name, environment, status, lifecycle, owner, tags.
- Server: host type, OS, OS version, OS EOL.
- Database server: license type (`enterprise/community`), license EOL.
- Application server: hosted services/applications.

## Aturan Auto Criticality (Berbasis EOL)

- `critical`: EOL sudah lewat (expired)
- `high`: EOL <= 30 hari
- `medium`: EOL <= 90 hari
- `low`: EOL > 90 hari
- fallback `medium` jika data EOL belum tersedia

## Notifikasi EOL

Sistem memberi notifikasi untuk:

- OS EOL expired
- OS EOL <= 90 hari
- License EOL expired (database server)
- License EOL <= 90 hari (database server)

## Role Matrix

| Role | Visibility | Manage Asset | Approve Changes | Manage Users |
|---|---|---|---|---|
| superadmin | Global | Ya | Ya | Ya |
| admin | Global | Ya | Ya | Ya |
| auditor | Global | Tidak | Ya | Tidak |
| operator | Own Scope | Ya | Tidak | Tidak |
| viewer | Own Scope | Tidak | Tidak | Tidak |

## Tech Stack

- PHP `^8.2`
- Laravel `^12`
- MySQL/MariaDB
- Blade + Bootstrap/Sneat assets
- Vite

## Instalasi Lokal

```bash
git clone https://github.com/lintanggraha/assetmanagement.git
cd assetmanagement
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Opsional seed role awal:

```sql
UPDATE users SET role = 'superadmin', is_active = 1 WHERE id = 1;
```

Jalankan aplikasi:

```bash
php artisan serve
npm run dev
```

## Konfigurasi `.env`

Set minimal:

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

Jika ingin mengaktifkan discovery kembali dari sisi controller, set:

```env
ASSET_DISCOVERY_ENABLED=true
```

## Scheduler & Command

Scheduler saat ini:

- `asset:discover-scheduled` harian jam `02:00` (backend command tetap ada)
- `asset:scan-policies` setiap jam

Manual run:

```bash
php artisan asset:discover-scheduled
php artisan asset:scan-policies
php artisan test
```

## Catatan

- Semua route utama diproteksi middleware `auth` + `active`.
- Perubahan sensitif diproses melalui approval queue.
- Pelanggaran policy disinkronkan otomatis (`open`/`resolved`) berdasarkan kondisi aset terbaru.
