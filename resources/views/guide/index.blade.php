@extends('layouts.master')

@section('content')
<section class="content-header">
  <h1>
    USER GUIDE
    <small>Panduan lengkap penggunaan Asset Management (mode input manual)</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Home</a></li>
    <li class="active">User Guide</li>
  </ol>
</section>

<section class="content">
  @php
    $user = Auth::user();
  @endphp

  <div class="alert alert-primary">
    <strong>Akun aktif:</strong> {{ $user->name }} ({{ strtoupper($user->role) }}) |
    <strong>Tanggal akses:</strong> {{ now()->format('Y-m-d H:i:s') }} |
    <strong>Versi panduan:</strong> 1.0
  </div>

  <div class="alert alert-warning">
    <strong>Catatan mode saat ini:</strong> Discovery Center sedang di-hide sementara.
    Pengelolaan asset dilakukan melalui input manual di Asset Inventory.
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0">Daftar Isi Cepat</h5>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="start">1. Mulai Cepat</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="roles">2. Role & Hak Akses</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="menus">3. Fungsi Menu Sidebar</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="global-ui">4. Komponen Global UI</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="asset-ops">5. Asset Ops Center</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="inventory">6. Asset Inventory</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="asset-detail">7. Asset Detail</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="discovery">8. Discovery (Sementara Off)</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="policy">9. Policy Violations</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="approval">10. Approvals</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="users">11. User Management</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="workflows">12. Alur Kerja End-to-End</button>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start guide-nav-btn" data-guide-target="troubleshooting">13. Troubleshooting</button>
        </div>
      </div>
    </div>
  </div>

  <div id="start" class="card mb-3 guide-panel" data-guide-panel="start">
    <div class="card-header">
      <h5 class="mb-0">1) Mulai Cepat (Recommended)</h5>
    </div>
    <div class="card-body">
      <ol class="mb-3">
        <li>Masuk ke <strong>Asset Ops Center</strong> untuk melihat kondisi total asset, risiko, violation, dan approval.</li>
        <li>Buka <strong>Asset Inventory</strong> untuk mencari data asset spesifik.</li>
        <li>Tambahkan/ubah data melalui <strong>Asset Inventory</strong> karena Discovery Center sedang nonaktif.</li>
        <li>Review <strong>Policy Violations</strong> untuk masalah kepatuhan data.</li>
        <li>Jika role Anda auditor/admin/superadmin, proses permintaan di <strong>Approvals</strong>.</li>
        <li>Jika role Anda admin/superadmin, kelola user di <strong>User Management</strong>.</li>
      </ol>
      <p class="mb-0 text-muted">
        Catatan: Menu yang muncul menyesuaikan role. Jika menu tidak terlihat, berarti role Anda memang tidak memiliki izin pada fitur tersebut.
      </p>
    </div>
  </div>

  <div id="roles" class="card mb-3 guide-panel d-none" data-guide-panel="roles">
    <div class="card-header">
      <h5 class="mb-0">2) Role & Hak Akses</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
          <thead>
            <tr>
              <th>Role</th>
              <th>Visibility Data</th>
              <th>Menu Utama</th>
              <th>Aksi Utama</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>superadmin</strong></td>
              <td>Global (semua asset)</td>
              <td>Semua menu</td>
              <td>Kelola user, kelola asset, approve/reject, resolve violation</td>
            </tr>
            <tr>
              <td><strong>admin</strong></td>
              <td>Global (semua asset)</td>
              <td>Semua menu kecuali batasan superadmin-only pada akun superadmin</td>
              <td>Kelola user (dengan guardrail), kelola asset, approve/reject, resolve violation</td>
            </tr>
            <tr>
              <td><strong>auditor</strong></td>
              <td>Global (semua asset)</td>
              <td>Asset Ops, User Guide, Asset Inventory, Policies, Approvals</td>
              <td>Review compliance, approve/reject request, resolve violation</td>
            </tr>
            <tr>
              <td><strong>operator</strong></td>
              <td>Scope milik sendiri</td>
              <td>Asset Ops, User Guide, Asset Inventory, Policies</td>
              <td>Create/update/retire asset, kirim sensitive change request</td>
            </tr>
            <tr>
              <td><strong>viewer</strong></td>
              <td>Scope milik sendiri</td>
              <td>Asset Ops, User Guide, Asset Inventory, Policies</td>
              <td>Read-only (tanpa create/edit/retire, tanpa approval)</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="menus" class="card mb-3 guide-panel d-none" data-guide-panel="menus">
    <div class="card-header">
      <h5 class="mb-0">3) Fungsi Masing-Masing Menu Sidebar</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead>
            <tr>
              <th>Menu</th>
              <th>Route</th>
              <th>Fungsi</th>
              <th>Siapa yang lihat</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Asset Ops Center</td>
              <td><code>/dashboard</code></td>
              <td>Ringkasan operasional: KPI asset, komposisi, hygiene, dan top risk</td>
              <td>Semua user login aktif</td>
            </tr>
            <tr>
              <td>User Guide</td>
              <td><code>/user-guide</code></td>
              <td>Panduan penggunaan semua menu, tombol, alur proses, dan troubleshooting</td>
              <td>Semua user login aktif</td>
            </tr>
            <tr>
              <td>Asset Inventory</td>
              <td><code>/asset-inventory</code></td>
              <td>Daftar asset lengkap + filter, pencarian, view detail, edit/retire (jika diizinkan)</td>
              <td>Semua user login aktif</td>
            </tr>
            <tr>
              <td>Discovery Center (sementara off)</td>
              <td><code>/discovery</code></td>
              <td>Sedang di-hide sementara. Akses akan diarahkan ke dashboard.</td>
              <td>Tidak ditampilkan</td>
            </tr>
            <tr>
              <td>Policy Violations</td>
              <td><code>/asset-policies</code></td>
              <td>Pantau pelanggaran kebijakan data asset dan lakukan resolution</td>
              <td>Semua role (aksi resolve terbatas)</td>
            </tr>
            <tr>
              <td>Approvals</td>
              <td><code>/approvals</code></td>
              <td>Proses request perubahan sensitif (approve/reject)</td>
              <td>superadmin/admin/auditor</td>
            </tr>
            <tr>
              <td>User Management</td>
              <td><code>/users</code></td>
              <td>Ubah role dan status aktif/nonaktif user</td>
              <td>superadmin/admin</td>
            </tr>
            <tr>
              <td>Sign Out</td>
              <td><code>/logout</code></td>
              <td>Keluar dari sesi login</td>
              <td>Semua user login aktif</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="global-ui" class="card mb-3 guide-panel d-none" data-guide-panel="global-ui">
    <div class="card-header">
      <h5 class="mb-0">4) Komponen Global UI (Muncul di Banyak Halaman)</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead>
            <tr>
              <th>Komponen</th>
              <th>Arti</th>
              <th>Catatan Pakai</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Breadcrumb</td>
              <td>Menunjukkan posisi halaman saat ini</td>
              <td>Klik item sebelumnya untuk kembali cepat</td>
            </tr>
            <tr>
              <td>Alert hijau/merah</td>
              <td>Feedback sukses/gagal setelah aksi</td>
              <td>Selalu baca isi alert untuk tahu hasil proses backend</td>
            </tr>
            <tr>
              <td>Badge status</td>
              <td>Label cepat status/severity/risk</td>
              <td>Gunakan sebagai indikator prioritas kerja</td>
            </tr>
            <tr>
              <td>Table pagination</td>
              <td>Pemisahan data per halaman</td>
              <td>Kondisi aktif jika record banyak</td>
            </tr>
            <tr>
              <td>Confirmation dialog (Retire)</td>
              <td>Proteksi dari aksi destructive tidak sengaja</td>
              <td>Pilih OK hanya jika yakin</td>
            </tr>
            <tr>
              <td>User avatar dropdown (kanan atas)</td>
              <td>Info user aktif + tombol Sign Out</td>
              <td>Pastikan role benar sebelum melakukan aksi sensitif</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="asset-ops" class="card mb-3 guide-panel d-none" data-guide-panel="asset-ops">
    <div class="card-header">
      <h5 class="mb-0">5) Detail Halaman: Asset Ops Center</h5>
    </div>
    <div class="card-body">
      <h6>A. Tombol Action Bar</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <th>Tombol</th>
              <th>Fungsi</th>
              <th>Role yang bisa lihat</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>Add Asset</code></td>
              <td>Buka form registrasi asset baru</td>
              <td>superadmin/admin/operator</td>
            </tr>
            <tr>
              <td><code>Run Discovery</code></td>
              <td>Sementara tidak muncul karena Discovery Center di-hide.</td>
              <td>Conditional (hanya saat discovery diaktifkan)</td>
            </tr>
            <tr>
              <td><code>Policies</code></td>
              <td>Buka register policy violations</td>
              <td>Semua role</td>
            </tr>
            <tr>
              <td><code>Approvals</code></td>
              <td>Buka queue permintaan perubahan sensitif</td>
              <td>superadmin/admin/auditor</td>
            </tr>
            <tr>
              <td><code>Users</code></td>
              <td>Buka manajemen akun dan role</td>
              <td>superadmin/admin</td>
            </tr>
          </tbody>
        </table>
      </div>

      <h6>B. Arti KPI Cards</h6>
      <p class="mb-2">
        <strong>Total Assets:</strong> jumlah total asset dalam scope user.
        <strong>Active:</strong> status <code>active</code>.
        <strong>Critical Assets:</strong> asset dengan criticality <code>high/critical</code> yang diturunkan dari EOL OS/license.
        <strong>Stale &gt; 30 Days:</strong> asset yang lama tidak terlihat.
      </p>
      <p class="mb-2">
        <strong>No Owner:</strong> owner belum terisi.
        <strong>High Risk (&ge; 70):</strong> risk score tinggi.
        <strong>Open Policy Violations:</strong> masalah compliance yang belum selesai.
        <strong>Pending Change Approvals:</strong> request perubahan sensitif belum diputuskan.
      </p>

      <h6>C. Widget Operasional</h6>
      <ul>
        <li><strong>Asset Composition by Type:</strong> komposisi per jenis asset + persentase.</li>
        <li><strong>Inventory Data Hygiene:</strong> coverage owner, visibility last_seen, dan discovery coverage (jika discovery aktif).</li>
        <li><strong>Top Risk Assets:</strong> daftar asset prioritas tertinggi untuk mitigasi.</li>
        <li><strong>Recent Discovery Runs:</strong> panel ini hanya muncul jika discovery diaktifkan.</li>
      </ul>
    </div>
  </div>

  <div id="inventory" class="card mb-3 guide-panel d-none" data-guide-panel="inventory">
    <div class="card-header">
      <h5 class="mb-0">6) Detail Halaman: Asset Inventory</h5>
    </div>
    <div class="card-body">
      <h6>A. Fungsi Filter Inventory</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered mb-0">
          <thead>
            <tr>
              <th>Field Filter</th>
              <th>Isi yang diharapkan</th>
              <th>Kegunaan</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>q</code></td>
              <td>Keyword bebas</td>
              <td>Cari code/nama/IP/hostname/owner/tags</td>
            </tr>
            <tr>
              <td><code>asset_type</code></td>
              <td>application/application_server/database_server/network_peripheral/etc</td>
              <td>Filter berdasarkan tipe asset</td>
            </tr>
            <tr>
              <td><code>status</code></td>
              <td>active/maintenance/inactive/unknown/retired</td>
              <td>Fokus ke status lifecycle operasional</td>
            </tr>
            <tr>
              <td><code>criticality</code></td>
              <td>low/medium/high/critical</td>
              <td>Prioritisasi dampak bisnis</td>
            </tr>
            <tr>
              <td><code>environment</code></td>
              <td>production/staging/development/uat/dr/other</td>
              <td>Pemisahan environment operasional</td>
            </tr>
            <tr>
              <td><code>risk_min</code>, <code>risk_max</code></td>
              <td>0 - 100</td>
              <td>Batasi rentang risk score</td>
            </tr>
            <tr>
              <td><code>source</code></td>
              <td>manual/import</td>
              <td>Lacak sumber data asset</td>
            </tr>
            <tr>
              <td><code>stale_days</code></td>
              <td>1 - 365</td>
              <td>Temukan asset stale berdasarkan last_seen</td>
            </tr>
          </tbody>
        </table>
      </div>

      <h6>B. Tombol di Header Inventory</h6>
      <ul>
        <li><strong>Discovery Center:</strong> saat ini tidak ditampilkan (discovery di-hide).</li>
        <li><strong>Policies:</strong> shortcut ke policy violations.</li>
        <li><strong>Approvals:</strong> shortcut ke approval queue (role tertentu).</li>
        <li><strong>Add Asset:</strong> tambah asset baru (role tertentu).</li>
        <li><strong>Apply:</strong> terapkan filter.</li>
        <li><strong>Reset:</strong> bersihkan seluruh parameter filter.</li>
      </ul>

      <h6>C. Tombol per Baris Asset</h6>
      <ul>
        <li><strong>View:</strong> lihat detail lengkap asset.</li>
        <li><strong>Edit:</strong> ubah data asset (role manage asset).</li>
        <li><strong>Retire:</strong> ubah status menjadi retired (langsung atau via approval, tergantung role).</li>
      </ul>

      <h6>D. Form Add/Edit Asset: Arti Field</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th>Field</th>
              <th>Wajib</th>
              <th>Penjelasan</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>Name</td><td>Ya</td><td>Nama utama asset di inventory</td></tr>
            <tr><td>Asset Type</td><td>Ya</td><td>Klasifikasi jenis asset</td></tr>
            <tr><td>Environment</td><td>Ya</td><td>Lingkungan deployment</td></tr>
            <tr><td>Status</td><td>Ya</td><td>Kondisi operasional saat ini</td></tr>
            <tr><td>Lifecycle Stage</td><td>Ya</td><td>Tahap siklus hidup asset</td></tr>
            <tr><td>Source</td><td>Tidak</td><td>Hanya dua nilai: <code>manual</code> atau <code>import</code></td></tr>
            <tr><td>IP/Hostname/Port</td><td>Tidak</td><td>Identitas endpoint asset</td></tr>
            <tr><td>Bank/Business Unit</td><td>Tidak</td><td>Mapping organisasi unit pemilik</td></tr>
            <tr><td>Owner Name</td><td>Tidak</td><td>Penanggung jawab utama asset</td></tr>
            <tr><td>Server Detail</td><td>Kondisional</td><td>Host Type, OS, OS Version, OS EOL untuk tipe server</td></tr>
            <tr><td>Database License Detail</td><td>Kondisional</td><td>License Type (enterprise/community) dan License EOL untuk database server</td></tr>
            <tr><td>Hosted Services / Applications</td><td>Kondisional</td><td>Hanya untuk application server</td></tr>
            <tr><td>Criticality (Auto)</td><td>Otomatis</td><td>Dihitung otomatis dari EOL terdekat (OS dan/atau DB license)</td></tr>
            <tr><td>Last Seen At</td><td>Tidak</td><td>Waktu terakhir asset terdeteksi aktif</td></tr>
            <tr><td>Tags</td><td>Tidak</td><td>Kategori tambahan, pisahkan dengan koma</td></tr>
            <tr><td>Notes</td><td>Tidak</td><td>Catatan operasional/compliance/dependency</td></tr>
            <tr><td>Approval Reason</td><td>Kondisional</td><td>Muncul untuk operator saat perubahan sensitif butuh approval</td></tr>
          </tbody>
        </table>
      </div>
      <p class="mt-3 mb-1">
        <strong>Rule auto-criticality berbasis EOL:</strong>
        <code>critical</code> jika sudah expired, <code>high</code> jika <= 30 hari,
        <code>medium</code> jika <= 90 hari, <code>low</code> jika > 90 hari,
        dan fallback <code>medium</code> jika data EOL belum tersedia.
      </p>
      <p class="mb-0">
        <strong>Notifikasi EOL:</strong> banner peringatan muncul di Asset Ops Center, Asset Inventory,
        dan Asset Detail untuk OS/License yang expired atau mendekati 90 hari.
      </p>
    </div>
  </div>

  <div id="asset-detail" class="card mb-3 guide-panel d-none" data-guide-panel="asset-detail">
    <div class="card-header">
      <h5 class="mb-0">7) Detail Halaman: Asset Detail</h5>
    </div>
    <div class="card-body">
      <p class="mb-2">
        Halaman ini adalah pusat observabilitas satu asset. Semua informasi metadata, risk, policy violation, approval request,
        dan activity log dikumpulkan dalam satu layar.
      </p>
      <h6>Fungsi Tombol</h6>
      <ul>
        <li><strong>Edit:</strong> update atribut asset.</li>
        <li><strong>Retire:</strong> ubah status asset menjadi retired.</li>
        <li><strong>Approvals:</strong> lompat ke approval queue.</li>
        <li><strong>Back to Inventory:</strong> kembali ke list inventory.</li>
        <li><strong>Open Discovery Center:</strong> hanya muncul jika discovery diaktifkan.</li>
        <li><strong>Open Policies:</strong> lihat daftar violation lintas asset.</li>
        <li><strong>Review Queue:</strong> khusus approver untuk menilai request menunggu.</li>
      </ul>
      <h6>Panel yang Perlu Dipantau Rutin</h6>
      <ul>
        <li><strong>Risk Posture:</strong> score risiko dan faktor utamanya.</li>
        <li><strong>Open Policy Violations:</strong> policy code + severity + pesan masalah.</li>
        <li><strong>Pending Change Requests:</strong> permintaan belum diputuskan.</li>
        <li><strong>Activity Log:</strong> audit trail aktivitas perubahan.</li>
        <li><strong>Latest Discovery Findings:</strong> panel ini hanya muncul jika discovery diaktifkan.</li>
      </ul>
    </div>
  </div>

  <div id="discovery" class="card mb-3 guide-panel d-none" data-guide-panel="discovery">
    <div class="card-header">
      <h5 class="mb-0">8) Detail Halaman: Discovery Center</h5>
    </div>
    <div class="card-body">
      <div class="alert alert-warning mb-3">
        Discovery Center sedang di-hide sementara, sehingga menu/tombol terkait tidak ditampilkan.
      </div>
      <h6>A. Dampak ke Operasional</h6>
      <ul>
        <li>Onboarding dan pembaruan asset dilakukan manual dari <strong>Asset Inventory</strong>.</li>
        <li>Route <code>/discovery</code> tetap ada, namun akses akan diarahkan ke dashboard.</li>
        <li>Panel discovery di Asset Ops/Asset Detail hanya muncul jika fitur diaktifkan kembali.</li>
      </ul>
      <h6>B. Cara Mengaktifkan Kembali</h6>
      <p class="mb-0">
        Ubah nilai environment <code>ASSET_DISCOVERY_ENABLED=true</code> lalu reload aplikasi.
      </p>
    </div>
  </div>

  <div id="policy" class="card mb-3 guide-panel d-none" data-guide-panel="policy">
    <div class="card-header">
      <h5 class="mb-0">9) Detail Halaman: Policy Violations</h5>
    </div>
    <div class="card-body">
      <h6>A. Tujuan</h6>
      <p>Menjaga kualitas data asset dan kepatuhan standar governance secara berkelanjutan.</p>

      <h6>B. Tombol Utama</h6>
      <ul>
        <li><strong>Refresh Policy Scan:</strong> jalankan evaluasi policy manual dari UI (role tertentu).</li>
        <li><strong>Open Inventory:</strong> kembali ke daftar asset.</li>
        <li><strong>Mark Resolved:</strong> tandai violation selesai (role approver).</li>
      </ul>

      <h6>C. Arti Kolom Violation Register</h6>
      <ul>
        <li><strong>Asset:</strong> asset terdampak (klik untuk detail).</li>
        <li><strong>Policy:</strong> kode kebijakan yang dilanggar (misal <code>OWNER_MISSING</code>).</li>
        <li><strong>Severity:</strong> level dampak (<code>critical/high/medium/low</code>).</li>
        <li><strong>Status:</strong> <code>open</code> atau <code>resolved</code>.</li>
        <li><strong>Detected / Resolved:</strong> waktu deteksi dan penyelesaian.</li>
      </ul>
    </div>
  </div>

  <div id="approval" class="card mb-3 guide-panel d-none" data-guide-panel="approval">
    <div class="card-header">
      <h5 class="mb-0">10) Detail Halaman: Approvals</h5>
    </div>
    <div class="card-body">
      <h6>A. Tujuan</h6>
      <p>Dual-control process untuk perubahan sensitif agar ada jejak audit dan kontrol risiko.</p>

      <h6>B. Bagian Halaman</h6>
      <ul>
        <li><strong>KPI:</strong> pending queue, recently approved, recently rejected.</li>
        <li><strong>Pending Requests:</strong> request yang menunggu keputusan.</li>
        <li><strong>Recent Decisions:</strong> histori keputusan terbaru.</li>
      </ul>

      <h6>C. Tombol & Input</h6>
      <ul>
        <li><strong>Approve:</strong> set status request menjadi approved, lalu perubahan diterapkan ke asset.</li>
        <li><strong>Reject:</strong> set status request menjadi rejected, perubahan tidak diterapkan.</li>
        <li><strong>Approval note / Reject reason:</strong> catatan keputusan untuk audit trail.</li>
      </ul>

      <h6>D. Aturan Penting</h6>
      <ul>
        <li>Requester yang sama tidak boleh memutuskan request miliknya sendiri (kecuali superadmin).</li>
        <li>Setiap keputusan approval/reject tercatat ke activity log asset.</li>
      </ul>
    </div>
  </div>

  <div id="users" class="card mb-3 guide-panel d-none" data-guide-panel="users">
    <div class="card-header">
      <h5 class="mb-0">11) Detail Halaman: User Management</h5>
    </div>
    <div class="card-body">
      <h6>A. Fungsi</h6>
      <p>Pengaturan role dan status aktif user agar pemisahan kewenangan berjalan rapi.</p>

      <h6>B. Komponen Utama</h6>
      <ul>
        <li><strong>Summary cards:</strong> total, active, inactive, admin roles.</li>
        <li><strong>Accounts table:</strong> list user + form inline untuk update role/status.</li>
      </ul>

      <h6>C. Tombol/Form</h6>
      <ul>
        <li><strong>Role dropdown:</strong> ubah peran user.</li>
        <li><strong>Status dropdown:</strong> aktif/nonaktifkan akun (akun sendiri tidak bisa dinonaktifkan).</li>
        <li><strong>Save:</strong> simpan perubahan role/status.</li>
      </ul>

      <h6>D. Guardrail</h6>
      <ul>
        <li>Admin tidak boleh assign role superadmin.</li>
        <li>Admin tidak boleh mengubah akun superadmin.</li>
        <li>Non-superadmin tidak boleh mengubah akun superadmin.</li>
        <li>User tidak bisa menonaktifkan akun sendiri.</li>
      </ul>
    </div>
  </div>

  <div id="workflows" class="card mb-3 guide-panel d-none" data-guide-panel="workflows">
    <div class="card-header">
      <h5 class="mb-0">12) Alur Kerja End-to-End</h5>
    </div>
    <div class="card-body">
      <h6>Workflow A: Onboarding Asset Manual</h6>
      <ol>
        <li>Buka <strong>Asset Inventory</strong>.</li>
        <li>Klik <strong>Add Asset</strong>.</li>
        <li>Isi field mandatory, lalu <strong>Save Asset</strong>.</li>
        <li>Validasi asset muncul di list dan detail.</li>
      </ol>

      <h6>Workflow B: Update Data Manual (Discovery Off)</h6>
      <ol>
        <li>Buka <strong>Asset Inventory</strong>.</li>
        <li>Cari asset target dengan filter/search.</li>
        <li>Klik <strong>Edit</strong> lalu simpan perubahan.</li>
        <li>Verifikasi perubahan di halaman <strong>Asset Detail</strong> dan cek policy jika perlu.</li>
      </ol>

      <h6>Workflow C: Menangani Policy Violation</h6>
      <ol>
        <li>Buka <strong>Policy Violations</strong>.</li>
        <li>Filter prioritas berdasarkan severity/status.</li>
        <li>Perbaiki data asset terkait dari halaman detail/edit.</li>
        <li>Klik <strong>Refresh Policy Scan</strong> untuk sinkron status.</li>
      </ol>

      <h6>Workflow D: Sensitive Change via Approval</h6>
      <ol>
        <li>Operator edit asset sensitif (status/owner/bank/tipe server).</li>
        <li>Sistem membuat pending request ke <strong>Approvals</strong>.</li>
        <li>Auditor/Admin/Superadmin review lalu approve/reject.</li>
        <li>Keputusan otomatis tercatat di audit log asset.</li>
      </ol>
    </div>
  </div>

  <div id="troubleshooting" class="card mb-3 guide-panel d-none" data-guide-panel="troubleshooting">
    <div class="card-header">
      <h5 class="mb-0">Troubleshooting Singkat</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead>
            <tr>
              <th>Masalah</th>
              <th>Penyebab Umum</th>
              <th>Solusi</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Menu tertentu tidak muncul</td>
              <td>Role tidak punya akses menu tersebut</td>
              <td>Cek role di User Management</td>
            </tr>
            <tr>
              <td>Tidak bisa run discovery</td>
              <td>Fitur discovery sedang di-hide via konfigurasi</td>
              <td>Gunakan alur input manual atau aktifkan <code>ASSET_DISCOVERY_ENABLED=true</code></td>
            </tr>
            <tr>
              <td>Tidak bisa approve request sendiri</td>
              <td>Aturan separation-of-duty</td>
              <td>Minta approver lain memproses</td>
            </tr>
            <tr>
              <td>Tidak bisa nonaktifkan akun sendiri</td>
              <td>Guardrail keamanan sesi</td>
              <td>Gunakan akun admin lain untuk update status</td>
            </tr>
            <tr>
              <td>Data violation belum berubah</td>
              <td>Belum refresh scan policy</td>
              <td>Klik <strong>Refresh Policy Scan</strong> atau tunggu scheduler</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</section>
@endsection

@push('scripts_body')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var navButtons = Array.prototype.slice.call(document.querySelectorAll('.guide-nav-btn'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.guide-panel'));

    if (!navButtons.length || !panels.length) {
      return;
    }

    function activateSection(sectionId) {
      var hasMatch = false;

      panels.forEach(function (panel) {
        var isActive = panel.getAttribute('data-guide-panel') === sectionId;
        panel.classList.toggle('d-none', !isActive);
        if (isActive) {
          hasMatch = true;
        }
      });

      navButtons.forEach(function (button) {
        var isActive = button.getAttribute('data-guide-target') === sectionId;
        button.classList.toggle('btn-primary', isActive);
        button.classList.toggle('btn-outline-primary', !isActive);
      });

      if (hasMatch) {
        window.location.hash = sectionId;
      }
    }

    var initialSection = window.location.hash ? window.location.hash.replace('#', '') : navButtons[0].getAttribute('data-guide-target');
    var targetExists = panels.some(function (panel) {
      return panel.getAttribute('data-guide-panel') === initialSection;
    });

    activateSection(targetExists ? initialSection : navButtons[0].getAttribute('data-guide-target'));

    navButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        activateSection(button.getAttribute('data-guide-target'));
      });
    });
  });
</script>
@endpush
