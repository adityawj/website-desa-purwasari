<?php
$title = 'Berita & Pengumuman'; $nav = 'tulisan'; $dt = true;
require __DIR__ . '/_head.php';
$sql = "SELECT p.*,u.name author FROM posts p JOIN users u ON u.id=p.author_id";
if ($isAdmin) { $rows = $db->query("$sql ORDER BY p.created_at DESC,p.id DESC")->fetchAll(); }
else { $s = $db->prepare("$sql WHERE p.author_id=? ORDER BY p.created_at DESC,p.id DESC"); $s->execute([$u['id']]); $rows = $s->fetchAll(); }

$isActive = fn(array $r) => $r['status'] === 'publish' && !is_expired($r);
$tab = in_array($_GET['tab'] ?? '', ['aktif', 'kedaluwarsa'], true) ? $_GET['tab'] : 'semua';
$active = array_filter($rows, $isActive);
$expired = array_filter($rows, 'is_expired');
$shown = $tab === 'aktif' ? $active : ($tab === 'kedaluwarsa' ? $expired : $rows);
$pending = count(array_filter($rows, fn($r) => $r['status'] === 'pending' && !is_expired($r)));
$pageScript = <<<'JS'
<script>
$(function () {
  if (!$.fn.DataTable) return;
  $('#dataTable').DataTable({
    order: [], columnDefs: [{ orderable: false, targets: -1 }],
    language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
      infoEmpty: 'Tidak ada data', zeroRecords: 'Tidak ada tulisan pada filter ini', emptyTable: 'Tidak ada tulisan pada filter ini',
      paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } }
  });
});
</script>
JS;
?>
<h1 class="h3 mb-2 text-gray-800">Berita &amp; Pengumuman</h1>
<p class="mb-4"><?= $isAdmin ? 'Kelola seluruh tulisan desa.' : 'Tulisan Anda tampil di situs setelah disetujui administrator.' ?></p>
<?php if ($isAdmin && $pending): ?><div class="alert alert-warning"><i class="fas fa-hourglass-half"></i> <?= $pending ?> tulisan menunggu persetujuan Anda.</div><?php endif; ?>

<div class="card shadow mb-4">
  <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'semua' ? ' active' : '' ?>" href="?tab=semua">Semua (<?= count($rows) ?>)</a></li>
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'aktif' ? ' active' : '' ?>" href="?tab=aktif">🟢 Aktif di Warga (<?= count($active) ?>)</a></li>
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'kedaluwarsa' ? ' active' : '' ?>" href="?tab=kedaluwarsa">🔴 Kedaluwarsa (<?= count($expired) ?>)</a></li>
    </ul>
    <a href="form.php" class="btn btn-primary btn-sm mt-2 mt-sm-0"><i class="fas fa-pen fa-sm"></i> Tulis baru</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
        <thead><tr><th>Judul</th><th>Jenis</th><th>Status</th><th>Penulis</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($shown as $r): ?>
          <tr>
            <td><?= e($r['title']) ?></td>
            <td><?= $r['type'] === 'berita' ? 'Berita' : 'Pengumuman' ?></td>
            <td>
              <?php if (is_expired($r)): ?><span class="badge badge-danger">Kedaluwarsa (Disembunyikan dari Warga)</span>
              <?php elseif ($r['status'] === 'pending'): ?><span class="badge badge-warning">Menunggu persetujuan</span>
              <?php elseif ($r['type'] === 'pengumuman'): ?><span class="badge badge-success">Live di Papan Warga</span>
              <?php else: ?><span class="badge badge-success">Tayang</span><?php endif; ?>
              <?php if ($r['expires_at']): ?><div class="small text-gray-600 mt-1"><?= e(berlaku($r)) ?></div><?php endif; ?>
            </td>
            <td><?= e($r['author']) ?></td>
            <td data-order="<?= e($r['created_at']) ?>"><?= tgl($r['created_at']) ?></td>
            <td class="text-nowrap">
              <a href="form.php?id=<?= $r['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i> <?= is_expired($r) ? 'Ubah / perpanjang' : 'Ubah' ?></a>
              <?php if ($isAdmin && $r['status'] === 'pending'): ?>
              <form method="post" action="aksi.php" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-success btn-sm" name="do" value="approve"><i class="fas fa-check"></i> Setujui</button></form>
              <?php endif; ?>
              <form method="post" action="aksi.php" class="d-inline" onsubmit="return confirm('Hapus tulisan ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-danger btn-sm" name="do" value="delete"><i class="fas fa-trash"></i> Hapus</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
