# Website Desa Purwasari — RT 002 / RW 003 (PHP + SQLite)

## Kebutuhan
PHP 8.0 atau lebih baru dengan ekstensi `pdo_sqlite` dan `fileinfo` (aktif di sebagian besar hosting).

## Menjalankan
- Lokal: `php -S localhost:8000` di dalam folder ini, lalu buka http://localhost:8000
- Hosting: unggah seluruh folder ke `public_html` (atau subfolder). Pastikan folder `data/` dan `uploads/` dapat ditulis (chmod 755 atau 775).
Database dan data contoh dibuat otomatis saat halaman pertama dibuka.

## Akun awal (GANTI SEGERA lewat menu Pengguna)
| Peran | Pengguna | Kata sandi |
|---|---|---|
| Administrator | admin | admin123 |
| Ketua RT / Bendahara | rt01 | rt012345 |

## Hak akses
- **Administrator**: semua tulisan (tambah, ubah, hapus, setujui), kelola pengguna.
- **Ketua RT / Bendahara**: menulis berita dan pengumuman beserta foto, mengubah dan menghapus tulisan sendiri. Tulisan tayang setelah disetujui administrator.

## Masa berlaku pengumuman
- Saat menulis pengumuman, isi **Tanggal berlaku** (tombol cepat +7, +14, +30 hari, atau tanpa batas waktu).
- Setelah tanggal tersebut lewat, pengumuman otomatis hilang dari beranda dan halaman publik (tanggal berlaku masih dihitung aktif pada hari itu).
- Di panel pengurus tersedia filter Semua, Aktif di Warga, dan Kedaluwarsa. Pengumuman kedaluwarsa tetap tersimpan sebagai arsip; ubah tanggalnya untuk menayangkan kembali, atau hapus.
- Database lama otomatis diperbarui (kolom `expires_at` ditambahkan saat halaman dibuka).

## Tampilan publik
Font **Roboto** (Google Fonts) dipakai di situs dan panel. Efek dinamis diatur oleh `assets/app.js` dan `assets/style.css`: animasi muncul saat digulir, angka ringkasan menghitung naik, header yang menyusut saat digulir, bar progres baca, tombol kembali ke atas, teks berjalan pengumuman di atas halaman, gambar yang muncul halus, dan filter kegiatan. Semua animasi otomatis dimatikan bagi pengunjung yang mengaktifkan "kurangi gerakan" di perangkatnya.

## Kegiatan desa
- Halaman publik `kegiatan.php` (dan bagian "Kegiatan desa" di beranda) menampilkan gambar, nama kegiatan, **tanggal lengkap** (mis. Senin, 12 Oktober 2026), waktu, lokasi, dan deskripsi.
- Panel pengurus: menu **Kegiatan Desa**. Isian: nama, deskripsi, gambar, tanggal, waktu, lokasi, dan **tanggal berlaku** (kegiatan otomatis disembunyikan setelah tanggal itu).
- **Administrator** punya CRUD penuh atas semua kegiatan (tambah, lihat, ubah, hapus, setujui). **Ketua RT / Bendahara** menambah dan mengelola kegiatan miliknya sendiri; tampil di situs setelah disetujui administrator.
- Database lama otomatis mendapat tabel kegiatan (kosong). Database baru berisi 3 kegiatan contoh.

## Kas desa
- Panel pengurus: menu **Kas Desa** (saldo aktif, total pemasukan, total pengeluaran, tabel rincian dengan pencarian) dan **Catat transaksi**.
- Saldo aktif = seluruh pemasukan dikurangi seluruh pengeluaran. Saldo awal dicatat sebagai pemasukan berkategori "Saldo awal".
- Administrator dapat mengubah/menghapus semua transaksi. Ketua RT / Bendahara melihat semua transaksi tetapi hanya mengubah/menghapus yang ia catat sendiri. Transaksi kas langsung tercatat tanpa persetujuan.
- Halaman publik `kas.php` menampilkan saldo, total, rincian per kategori, dan tabel transaksi, dengan filter bulan dan tombol cetak. Untuk menyembunyikan dari warga, ubah `KAS_PUBLIK` menjadi `false` di `config.php`.

## Tampilan panel pengurus
Panel `/admin` memakai template **SB Admin 2** (Start Bootstrap, lisensi MIT, Bootstrap 4): sidebar gradien, topbar, kartu dashboard, tabel DataTables, dan halaman login bawaan template.
Berkas CSS/JS template dimuat dari CDN (jsDelivr), jadi server dan peramban perlu akses internet. Untuk memakai berkas lokal, unduh paket `startbootstrap-sb-admin-2` (npm atau situs Start Bootstrap), taruh di folder `admin/assets/`, lalu ubah alamat `<link>` dan `<script>` di `admin/_head.php`, `admin/_foot.php`, dan `admin/login.php`.
Warna biru bawaan template diganti hijau lewat `assets/admin-green.css` (ubah variabel `--g-main` dan `--g-dark` di bagian atas file untuk menyesuaikan).
Halaman: Dashboard (`admin/index.php`), Berita & Pengumuman (`admin/tulisan.php`), Tulis baru (`admin/form.php`), Pengguna (`admin/users.php`, khusus administrator).

## Pengaturan
Nama desa, alamat RT/RW, dan slogan: ubah `SITE_NAME`, `SITE_ADDRESS`, `SITE_TAGLINE` di `config.php`.

## Struktur
- `index.php`, `konten.php`: halaman publik (beranda, daftar, dan detail berita/pengumuman)
- `admin/`: panel pengelola (login, tulisan, form, pengguna)
- `config.php`: koneksi database, fungsi bantu, keamanan (CSRF, upload foto tervalidasi)
- `data/`: database SQLite · `uploads/`: foto unggahan
