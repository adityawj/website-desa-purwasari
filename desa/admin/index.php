<?php
$title = 'Dashboard'; $nav = 'dashboard';
require __DIR__ . '/_head.php';
$sql = "SELECT p.*,u.name author FROM posts p JOIN users u ON u.id=p.author_id";
if ($isAdmin) { $rows = $db->query("$sql ORDER BY p.created_at DESC,p.id DESC")->fetchAll(); }
else { $s = $db->prepare("$sql WHERE p.author_id=? ORDER BY p.created_at DESC,p.id DESC"); $s->execute([$u['id']]); $rows = $s->fetchAll(); }

$liveAnn = array_filter($rows, fn($r) => $r['type'] === 'pengumuman' && $r['status'] === 'publish' && !is_expired($r));
$news = array_filter($rows, fn($r) => $r['type'] === 'berita' && $r['status'] === 'publish');
$pending = array_filter($rows, fn($r) => $r['status'] === 'pending' && !is_expired($r));
$ending = array_filter($liveAnn, fn($r) => $r['expires_at']);
usort($ending, fn($a, $b) => strcmp($a['expires_at'], $b['expires_at']));
$cards = [
    ['Total tulisan', count($rows), 'primary', 'fa-newspaper'],
    ['Pengumuman aktif', count($liveAnn), 'success', 'fa-bullhorn'],
    ['Berita tayang', count($news), 'info', 'fa-camera-retro'],
    [$isAdmin ? 'Menunggu persetujuan' : 'Menunggu persetujuan', count($pending), 'warning', 'fa-hourglass-half'],
];
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
  <a href="form.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-pen fa-sm text-white-50"></i> Tulis baru</a>
</div>

<div class="row">
  <?php foreach ($cards as [$label, $n, $color, $icon]): ?>
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-<?= $color ?> shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
      <div class="col mr-2">
        <div class="text-xs font-weight-bold text-<?= $color ?> text-uppercase mb-1"><?= e($label) ?></div>
        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $n ?></div>
      </div>
      <div class="col-auto"><i class="fas <?= $icon ?> fa-2x text-gray-300"></i></div>
    </div></div></div>
  </div>
  <?php endforeach; ?>
</div>

<?php
$kM = kas_total($db, 'masuk'); $kK = kas_total($db, 'keluar');
$kasCards = [['Saldo kas aktif', $kM - $kK, 'primary', 'fa-wallet'], ['Total pemasukan', $kM, 'success', 'fa-arrow-down'], ['Total pengeluaran', $kK, 'danger', 'fa-arrow-up']];
?>
<div class="row">
  <?php foreach ($kasCards as [$label, $n, $color, $icon]): ?>
  <div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-<?= $color ?> shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
      <div class="col mr-2">
        <div class="text-xs font-weight-bold text-<?= $color ?> text-uppercase mb-1"><?= e($label) ?></div>
        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= rp($n) ?></div>
      </div>
      <div class="col-auto"><i class="fas <?= $icon ?> fa-2x text-gray-300"></i></div>
    </div></div></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <div class="col-lg-8 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Tulisan terbaru</h6>
        <a href="tulisan.php" class="small">Lihat semua</a>
      </div>
      <div class="card-body">
        <?php if (!$rows): ?><p class="text-gray-600 mb-0">Belum ada tulisan.</p><?php endif; ?>
        <?php foreach (array_slice($rows, 0, 6) as $r): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
          <div><a href="form.php?id=<?= $r['id'] ?>" class="font-weight-bold text-gray-800"><?= e($r['title']) ?></a>
            <div class="small text-gray-600"><?= $r['type'] === 'berita' ? 'Berita' : 'Pengumuman' ?> · <?= e($r['author']) ?> · <?= tgl($r['created_at']) ?></div></div>
          <?php if (is_expired($r)): ?><span class="badge badge-danger">Kedaluwarsa</span>
          <?php elseif ($r['status'] === 'pending'): ?><span class="badge badge-warning">Menunggu</span>
          <?php else: ?><span class="badge badge-success">Tayang</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4 mb-4">
    <div class="card shadow mb-4">
      <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Pengumuman segera berakhir</h6></div>
      <div class="card-body">
        <?php if (!$ending): ?><p class="text-gray-600 mb-0">Tidak ada pengumuman dengan batas waktu.</p><?php endif; ?>
        <?php foreach (array_slice($ending, 0, 5) as $r): ?>
        <div class="mb-3"><div class="font-weight-bold text-gray-800"><?= e($r['title']) ?></div>
          <span class="badge badge-warning"><?= e(berlaku($r)) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
