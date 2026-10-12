<?php
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Jakarta');

const SITE_NAME = 'Desa Purwasari';
const SITE_ADDRESS = 'RT 002 / RW 003';
const SITE_TAGLINE = 'Pusat informasi, pengumuman, dan transparansi kas untuk warga';
const KAS_PUBLIK = true;   // false = rincian kas hanya terlihat di panel pengurus

// Alamat dasar aplikasi (kosong jika di root domain, mis. "/desa" jika di subfolder).
// Dihitung dari lokasi skrip yang sedang berjalan sehingga tidak bergantung pada DOCUMENT_ROOT.
(function () {
    $root = str_replace('\\', '/', __DIR__);
    $sf = str_replace('\\', '/', (string)realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')));
    $sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = null;
    if ($sf !== '' && stripos($sf, $root) === 0) {
        $rel = substr($sf, strlen($root));   // contoh: "/konten.php" atau "/admin/form.php"
        if ($rel !== '' && strlen($sn) >= strlen($rel) && strcasecmp(substr($sn, -strlen($rel)), $rel) === 0) {
            $base = substr($sn, 0, strlen($sn) - strlen($rel));
        }
    }
    if ($base === null) {   // cadangan: hitung dari DOCUMENT_ROOT
        $dr = rtrim(str_replace('\\', '/', (string)realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''))), '/');
        $base = $dr === '' ? '' : (stripos($root, $dr) === 0 ? substr($root, strlen($dr)) : '');
    }
    define('BASE', rtrim($base, '/'));
})();
define('UPLOAD_DIR', __DIR__ . '/uploads/');

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) { mkdir($dataDir, 0755, true); file_put_contents($dataDir . '/.htaccess', "Require all denied\n"); }
if (!is_dir(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0755, true); }
if (!file_exists(UPLOAD_DIR . '.htaccess')) {
    file_put_contents(UPLOAD_DIR . '.htaccess', "<FilesMatch \"\\.(php|phtml|phar)$\">\nRequire all denied\n</FilesMatch>\n");
}

$db = new PDO('sqlite:' . $dataDir . '/desa.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE IF NOT EXISTS users(
    id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, name TEXT NOT NULL,
    password TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN('admin','rt')))");
$db->exec("CREATE TABLE IF NOT EXISTS posts(
    id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT NOT NULL CHECK(type IN('berita','pengumuman')),
    title TEXT NOT NULL, body TEXT NOT NULL, image TEXT,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN('publish','pending')),
    author_id INTEGER NOT NULL, created_at TEXT NOT NULL, expires_at TEXT)");

$db->exec("CREATE TABLE IF NOT EXISTS kas(
    id INTEGER PRIMARY KEY AUTOINCREMENT, jenis TEXT NOT NULL CHECK(jenis IN('masuk','keluar')),
    tanggal TEXT NOT NULL, kategori TEXT NOT NULL, keterangan TEXT NOT NULL,
    jumlah INTEGER NOT NULL CHECK(jumlah > 0), author_id INTEGER NOT NULL, created_at TEXT NOT NULL)");

$db->exec("CREATE TABLE IF NOT EXISTS kegiatan(
    id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT NOT NULL, deskripsi TEXT NOT NULL, image TEXT,
    tanggal TEXT NOT NULL, waktu TEXT, lokasi TEXT, berlaku_sampai TEXT,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN('publish','pending')),
    author_id INTEGER NOT NULL, created_at TEXT NOT NULL)");

// Migrasi: tambah kolom masa berlaku pada database lama
$cols = array_column($db->query("PRAGMA table_info(posts)")->fetchAll(), 'name');
if (!in_array('expires_at', $cols, true)) $db->exec("ALTER TABLE posts ADD COLUMN expires_at TEXT");

// Data awal (hanya saat database masih kosong)
if (!(int)$db->query("SELECT COUNT(*) c FROM users")->fetch()['c']) {
    $ins = $db->prepare("INSERT INTO users(username,name,password,role) VALUES(?,?,?,?)");
    $ins->execute(['admin', 'Administrator', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    $ins->execute(['rt01', 'Ketua RT 01', password_hash('rt012345', PASSWORD_DEFAULT), 'rt']);
    $p = $db->prepare("INSERT INTO posts(type,title,body,status,author_id,created_at) VALUES(?,?,?,?,?,?)");
    $p->execute(['berita', 'Kerja bakti membersihkan saluran irigasi', "Warga RT 01 sampai RT 05 bergotong royong membersihkan saluran irigasi persawahan pada Minggu pagi.\n\nKegiatan ini rutin dilakukan menjelang musim tanam agar air mengalir lancar ke seluruh petak sawah.", 'publish', 1, date('Y-m-d H:i:s', time() - 86400 * 2)]);
    $p->execute(['berita', 'Posyandu bulan ini berjalan lancar', "Posyandu balita dan lansia dilaksanakan di balai desa. Puluhan warga hadir untuk menimbang, mengukur tinggi badan, dan memeriksa tekanan darah.", 'publish', 2, date('Y-m-d H:i:s', time() - 86400)]);
    $p->execute(['pengumuman', 'Rapat warga membahas anggaran dana desa', "Seluruh ketua RT dan tokoh masyarakat diundang hadir di balai desa pada Sabtu pukul 19.30 WIB. Mohon membawa usulan kegiatan dari masing-masing RT.", 'publish', 1, date('Y-m-d H:i:s')]);
    $p->execute(['pengumuman', 'Pembayaran iuran kebersihan', "Iuran kebersihan bulan ini dapat dibayarkan kepada bendahara RT paling lambat tanggal 10.", 'publish', 2, date('Y-m-d H:i:s', time() - 3600)]);
    $db->prepare("UPDATE posts SET expires_at=? WHERE type='pengumuman'")->execute([date('Y-m-d', time() + 86400 * 14)]);
    $k = $db->prepare("INSERT INTO kas(jenis,tanggal,kategori,keterangan,jumlah,author_id,created_at) VALUES(?,?,?,?,?,?,?)");
    $d = fn(int $n) => date('Y-m-d', strtotime("-$n days"));
    foreach ([
        ['masuk',  $d(45), 'Saldo awal',          'Saldo kas awal pembukuan',          2500000, 1],
        ['masuk',  $d(28), 'Iuran warga',         'Iuran warga bulan lalu',            1800000, 2],
        ['masuk',  $d(14), 'Sumbangan / donasi',  'Sumbangan sukarela warga',           500000, 2],
        ['masuk',  $d(6),  'Iuran warga',         'Iuran warga bulan ini',             1650000, 2],
        ['keluar', $d(30), 'Kebersihan',          'Upah petugas kebersihan lingkungan', 450000, 2],
        ['keluar', $d(21), 'Keamanan',            'Honor petugas ronda',                600000, 2],
        ['keluar', $d(12), 'Perbaikan fasilitas', 'Perbaikan lampu jalan gang utama',   350000, 2],
        ['keluar', $d(4),  'Konsumsi rapat',      'Konsumsi rapat warga',               275000, 2],
    ] as $r) $k->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], date('Y-m-d H:i:s')]);
    $g = $db->prepare("INSERT INTO kegiatan(judul,deskripsi,image,tanggal,waktu,lokasi,berlaku_sampai,status,author_id,created_at) VALUES(?,?,NULL,?,?,?,?,'publish',?,?)");
    foreach ([
        ['Kerja bakti membersihkan lingkungan', "Seluruh warga diajak bergotong royong membersihkan selokan, jalan lingkungan, dan area balai warga.\n\nMohon membawa peralatan kebersihan masing-masing seperti sapu, cangkul, dan kantong sampah. Minuman akan disediakan oleh pengurus.", date('Y-m-d', strtotime('+5 days')), '07.00 WIB - selesai', 'Balai warga RT 002 / RW 003', date('Y-m-d', strtotime('+5 days')), 1],
        ['Pemeriksaan kesehatan balita dan lansia', "Kegiatan posyandu rutin berupa penimbangan, pengukuran tinggi badan, dan pemeriksaan tekanan darah.\n\nWarga yang memiliki balita atau lansia dimohon hadir tepat waktu dengan membawa buku kesehatan.", date('Y-m-d', strtotime('+12 days')), '08.30 - 11.30 WIB', 'Balai warga RT 002 / RW 003', date('Y-m-d', strtotime('+12 days')), 2],
        ['Rapat warga bulanan', "Rapat membahas laporan kas, rencana kegiatan bulan depan, dan usulan dari warga.\n\nSeluruh kepala keluarga diharapkan hadir.", date('Y-m-d', strtotime('+2 days')), '19.30 WIB', 'Rumah Ketua RT 002', date('Y-m-d', strtotime('+2 days')), 2],
    ] as $r) $g->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], date('Y-m-d H:i:s')]);
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tgl(string $d): string {
    $b = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $b[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}
function excerpt(string $t, int $n = 130): string {
    $t = trim(preg_replace('/\s+/', ' ', $t));
    if (function_exists('mb_strlen')) return mb_strlen($t) > $n ? mb_substr($t, 0, $n) . '…' : $t;
    return strlen($t) > $n ? substr($t, 0, $n) . '…' : $t;
}
function img(?string $f): ?string { return $f ? BASE . '/uploads/' . rawurlencode($f) : null; }

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.'); }
}
function me(): ?array { return $_SESSION['user'] ?? null; }
function need_login(): void { if (!me()) { header('Location: ' . BASE . '/admin/login.php'); exit; } }
function need_admin(): void { need_login(); if (me()['role'] !== 'admin') { http_response_code(403); exit('Halaman ini khusus administrator.'); } }
function flash(string $m): void { $_SESSION['flash'] = $m; }
function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . e($_SESSION['flash'])
           . '<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>';
        unset($_SESSION['flash']);
    }
}

/** Simpan foto upload. Mengembalikan nama file, atau null jika tidak ada file. */
function upload_image(array $f): ?string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload foto gagal. Coba lagi.');
    if ($f['size'] > 2 * 1024 * 1024) throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Foto tidak dapat disimpan. Periksa izin folder uploads.');
    return $name;
}

// ---- Masa berlaku pengumuman (tanggal berlaku bersifat inklusif) ----
function today(): string { return date('Y-m-d'); }
function is_expired(array $p): bool { return !empty($p['expires_at']) && $p['expires_at'] < today(); }
function berlaku(array $p): string { return !empty($p['expires_at']) ? 'Berlaku s/d: ' . tgl($p['expires_at']) : ''; }

// ---- Kas desa ----
const KAS_KATEGORI = [
    'masuk'  => ['Saldo awal', 'Iuran warga', 'Sumbangan / donasi', 'Bantuan pemerintah', 'Lainnya'],
    'keluar' => ['Kebersihan', 'Keamanan', 'Kegiatan sosial', 'Perbaikan fasilitas', 'Konsumsi rapat', 'Administrasi', 'Lainnya'],
];
function rp($n): string { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }
function kas_total(PDO $db, string $jenis, ?string $ym = null): int {
    $sql = "SELECT COALESCE(SUM(jumlah),0) s FROM kas WHERE jenis=?"; $a = [$jenis];
    if ($ym) { $sql .= " AND substr(tanggal,1,7)=?"; $a[] = $ym; }
    $s = $db->prepare($sql); $s->execute($a);
    return (int)$s->fetch()['s'];
}
function kas_saldo(PDO $db): int { return kas_total($db, 'masuk') - kas_total($db, 'keluar'); }
function bulan_label(string $ym): string {
    $b = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $b[(int)substr($ym, 5, 2) - 1] . ' ' . substr($ym, 0, 4);
}
function tgl_parts(string $d): array {
    $b = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($d);
    return [date('j', $t), $b[(int)date('n', $t) - 1]];
}
function icon(string $n, int $s = 22): string {
    $p = [
        'wallet' => '<path d="M3 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/><path d="M3 7l12-4v4"/><path d="M17 14h2"/>',
        'in'     => '<path d="M12 4v14"/><path d="M5 12l7 7 7-7"/>',
        'out'    => '<path d="M12 20V6"/><path d="M5 12l7-7 7 7"/>',
        'bell'   => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'leaf'   => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.4 5.7-7"/>',
        'pin'    => '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'news'   => '<path d="M4 4h13a2 2 0 0 1 2 2v14H6a2 2 0 0 1-2-2V4z"/><path d="M8 9h7M8 13h7M8 17h4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    ][$n] ?? '';
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

// ---- Kegiatan desa ----
function tgl_lengkap(string $d): string {
    $h = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $h[(int)date('w', strtotime($d))] . ', ' . tgl($d);
}
function valid_date(string $d): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4));
}
function kegiatan_expired(array $k): bool { return !empty($k['berlaku_sampai']) && $k['berlaku_sampai'] < today(); }
function kegiatan_label(array $k): array {
    $t = today();
    if ($k['tanggal'] > $t) return ['Akan datang', 'soon'];
    if ($k['tanggal'] === $t) return ['Hari ini', 'today'];
    return ['Telah berlangsung', 'past'];
}
/** Kegiatan yang tayang dan masih berlaku; yang akan datang diurutkan lebih dulu. */
function kegiatan_aktif(PDO $db, int $limit = 60): array {
    $t = today();
    $s = $db->prepare("SELECT * FROM kegiatan WHERE status='publish' AND (berlaku_sampai IS NULL OR berlaku_sampai >= ?)
        ORDER BY CASE WHEN tanggal >= ? THEN 0 ELSE 1 END, CASE WHEN tanggal >= ? THEN tanggal END ASC, tanggal DESC LIMIT " . (int)$limit);
    $s->execute([$t, $t, $t]);
    return $s->fetchAll();
}
/** Ubah teks berparagraf (pisah baris kosong) menjadi HTML yang aman dan mudah dibaca. */
function paras(string $t): string {
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($t)) as $p) { if (trim($p) !== '') $out .= '<p>' . nl2br(e(trim($p))) . '</p>'; }
    return $out;
}
