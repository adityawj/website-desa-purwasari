<?php
require __DIR__ . '/config.php';
$act = "p.status='publish' AND (p.expires_at IS NULL OR p.expires_at >= ?)";
$cols = "p.id,p.title,p.body,p.image,p.created_at,p.expires_at";
$s = $db->prepare("SELECT $cols FROM posts p WHERE p.type='pengumuman' AND $act ORDER BY p.created_at DESC,p.id DESC LIMIT 4");
$s->execute([today()]); $ann = $s->fetchAll();
$s = $db->prepare("SELECT $cols FROM posts p WHERE p.type='berita' AND $act ORDER BY p.created_at DESC,p.id DESC LIMIT 4");
$s->execute([today()]); $news = $s->fetchAll();
$s = $db->prepare("SELECT COUNT(*) c FROM posts p WHERE p.type='pengumuman' AND $act"); $s->execute([today()]); $totalAnn = (int)$s->fetch()['c'];
$keg = kegiatan_aktif($db, 3); $totalKeg = count(kegiatan_aktif($db, 500));
$ticker = array_map(fn($a) => ['id' => $a['id'], 't' => $a['title']], $ann);
if (KAS_PUBLIK) {
    $masuk = kas_total($db, 'masuk'); $keluar = kas_total($db, 'keluar'); $saldo = $masuk - $keluar;
    $recent = function (string $j) use ($db) {
        $q = $db->prepare("SELECT tanggal,kategori,keterangan,jumlah FROM kas WHERE jenis=? ORDER BY tanggal DESC,id DESC LIMIT 5");
        $q->execute([$j]); return $q->fetchAll();
    };
}
$title = 'Beranda'; $page = 'home';
include __DIR__ . '/inc_head.php';
?>
<section class="hero"><div class="wrap">
  <span class="pill"><?= icon('pin', 15) ?> <?= e(SITE_ADDRESS) ?></span>
  <h1>Selamat datang di <?= e(SITE_NAME) ?></h1>
  <p><?= e(SITE_TAGLINE) ?>.</p>
  <div class="actions">
    <a class="btn gold" href="<?= BASE ?>/konten.php?t=pengumuman">Lihat pengumuman</a>
    <?php if (KAS_PUBLIK): ?><a class="btn ghost" href="<?= BASE ?>/kas.php">Transparansi kas</a><?php endif; ?>
  </div>
</div></section>

<div class="wrap stats">
  <?php if (KAS_PUBLIK): ?>
  <div class="stat saldo"><span class="ic"><?= icon('wallet') ?></span><div><small>Saldo kas aktif</small><b data-count="<?= $saldo ?>" data-rp="1"><?= rp($saldo) ?></b></div></div>
  <div class="stat in"><span class="ic"><?= icon('in') ?></span><div><small>Total pemasukan</small><b data-count="<?= $masuk ?>" data-rp="1"><?= rp($masuk) ?></b></div></div>
  <div class="stat out"><span class="ic"><?= icon('out') ?></span><div><small>Total pengeluaran</small><b data-count="<?= $keluar ?>" data-rp="1"><?= rp($keluar) ?></b></div></div>
  <?php endif; ?>
  <div class="stat"><span class="ic"><?= icon('bell') ?></span><div><small>Pengumuman aktif</small><b data-count="<?= $totalAnn ?>"><?= $totalAnn ?></b></div></div>
  <div class="stat"><span class="ic"><?= icon('calendar') ?></span><div><small>Kegiatan aktif</small><b data-count="<?= $totalKeg ?>"><?= $totalKeg ?></b></div></div>
</div>

<section class="sec wrap">
  <div class="sec-head"><div><h2>Pengumuman warga</h2><p class="sub">Informasi yang masih berlaku saat ini.</p></div><a href="<?= BASE ?>/konten.php?t=pengumuman">Semua pengumuman</a></div>
  <?php if (!$ann): ?><p class="empty">Belum ada pengumuman aktif.</p><?php endif; ?>
  <div class="grid2"><?php foreach ($ann as $a) echo card_ann($a); ?></div>
</section>

<section class="sec wrap">
  <div class="sec-head"><div><h2>Kegiatan desa</h2><p class="sub">Agenda dan kegiatan yang akan dan telah berlangsung.</p></div><a href="<?= BASE ?>/kegiatan.php">Semua kegiatan</a></div>
  <?php if (!$keg): ?><p class="empty">Belum ada kegiatan yang ditampilkan.</p><?php endif; ?>
  <div class="grid"><?php foreach ($keg as $k) echo card_keg($k); ?></div>
</section>

<section class="sec wrap">
  <div class="sec-head"><div><h2>Berita terbaru</h2><p class="sub">Kabar dan kegiatan di lingkungan kita.</p></div><a href="<?= BASE ?>/konten.php?t=berita">Semua berita</a></div>
  <?php if (!$news): ?><p class="empty">Belum ada berita.</p><?php endif; ?>
  <?php if ($news): $f = array_shift($news); ?>
  <a class="feat" href="<?= BASE ?>/konten.php?t=berita&id=<?= $f['id'] ?>">
    <?php if ($f['image']): ?><img src="<?= img($f['image']) ?>" alt=""><?php else: ?><div class="noimg"><?= icon('leaf', 48) ?></div><?php endif; ?>
    <div class="fb"><small><?= tgl($f['created_at']) ?></small><h3><?= e($f['title']) ?></h3><p><?= e(excerpt($f['body'], 240)) ?></p><span class="more">Baca selengkapnya</span></div>
  </a>
  <?php if ($news): ?><div class="grid"><?php foreach ($news as $n) echo card_news($n); ?></div><?php endif; endif; ?>
</section>

<?php if (KAS_PUBLIK): ?>
<section class="sec wrap">
  <div class="sec-head"><div><h2>Transaksi kas terbaru</h2><p class="sub">Laporan terbuka untuk seluruh warga.</p></div><a href="<?= BASE ?>/kas.php">Rincian lengkap</a></div>
  <div class="two">
    <?php foreach (['masuk' => 'Pemasukan terakhir', 'keluar' => 'Pengeluaran terakhir'] as $j => $lbl): $rows = $recent($j); ?>
    <div class="panel <?= $j ?>">
      <h3><?= $lbl ?></h3>
      <?php if (!$rows): ?><p class="empty">Belum ada data.</p><?php endif; ?>
      <?php foreach ($rows as $r): ?>
      <div class="li"><div><b><?= e($r['keterangan']) ?></b><small><?= e($r['kategori']) ?> · <?= tgl($r['tanggal']) ?></small></div><span class="amt"><?= $j === 'masuk' ? '+' : '−' ?> <?= rp($r['jumlah']) ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="sec wrap">
  <div class="about">
    <div><h2>Tentang <?= e(SITE_NAME) ?></h2>
    <p>Portal ini dikelola oleh pengurus <?= e(SITE_ADDRESS) ?> agar informasi lingkungan mudah diakses warga kapan saja. Pengumuman yang sudah berakhir otomatis disembunyikan, dan laporan kas dibuka agar penggunaan iuran dapat dipantau bersama.</p></div>
    <div class="loc"><?= icon('pin', 28) ?><b><?= e(SITE_NAME) ?></b><span><?= e(SITE_ADDRESS) ?></span></div>
  </div>
</section>
<?php include __DIR__ . '/inc_foot.php'; ?>
