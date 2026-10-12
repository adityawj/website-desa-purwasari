<?php
$title = 'Kegiatan Desa'; $nav = 'kegiatan'; $dt = true;
require __DIR__ . '/_head.php';
$sql = "SELECT k.*,u.name author FROM kegiatan k JOIN users u ON u.id=k.author_id";
if ($isAdmin) { $rows = $db->query("$sql ORDER BY k.tanggal DESC,k.id DESC")->fetchAll(); }
else { $s = $db->prepare("$sql WHERE k.author_id=? ORDER BY k.tanggal DESC,k.id DESC"); $s->execute([$u['id']]); $rows = $s->fetchAll(); }

$isActive = fn(array $r) => $r['status'] === 'publish' && !kegiatan_expired($r);
$tab = in_array($_GET['tab'] ?? '', ['aktif', 'kedaluwarsa'], true) ? $_GET['tab'] : 'semua';
$active = array_filter($rows, $isActive);
$expired = array_filter($rows, 'kegiatan_expired');
$shown = $tab === 'aktif' ? $active : ($tab === 'kedaluwarsa' ? $expired : $rows);
$pending = count(array_filter($rows, fn($r) => $r['status'] === 'pending' && !kegiatan_expired($r)));
$pageScript = <<<'JS'
<script>
$(function () {
  if (!$.fn.DataTable) return;
  $('#dataTable').DataTable({
    order: [], columnDefs: [{ orderable: false, targets: [0, -1] }],
    language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_–_END_ dari _TOTAL_ kegiatan',
      infoEmpty: 'Tidak ada data', zeroRecords: 'Tidak ada kegiatan', emptyTable: 'Tidak ada kegiatan pada filter ini',
      paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } }
  });
});
</script>
JS;
?>
<div class="d-sm-flex align-items-center justify-content-between mb-2">
  <h1 class="h3 mb-0 text-gray-800">Kegiatan Desa</h1>
  <a href="kegiatan_form.php" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Tambah kegiatan</a>
</div>
<p class="mb-4"><?= $isAdmin ? 'Kelola seluruh kegiatan desa: tambah, ubah, setujui, dan hapus.' : 'Kegiatan yang Anda tambahkan tampil di situs setelah disetujui administrator.' ?></p>
<?php if ($isAdmin && $pending): ?><div class="alert alert-warning"><i class="fas fa-hourglass-half"></i> <?= $pending ?> kegiatan menunggu persetujuan Anda.</div><?php endif; ?>

<div class="card shadow mb-4">
  <div class="card-header py-3">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'semua' ? ' active' : '' ?>" href="?tab=semua">Semua (<?= count($rows) ?>)</a></li>
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'aktif' ? ' active' : '' ?>" href="?tab=aktif">Tampil di Warga (<?= count($active) ?>)</a></li>
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'kedaluwarsa' ? ' active' : '' ?>" href="?tab=kedaluwarsa">Kedaluwarsa (<?= count($expired) ?>)</a></li>
    </ul>
  </div>
  <div class="card-body"><div class="table-responsive">
    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
      <thead><tr><th>Gambar</th><th>Kegiatan</th><th>Tanggal kegiatan</th><th>Status</th><th>Penulis</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($shown as $r): ?>
        <tr>
          <td style="width:90px"><?php if ($r['image']): ?><img src="<?= img($r['image']) ?>" alt="" class="img-thumbnail" style="width:80px;height:56px;object-fit:cover"><?php else: ?><span class="text-gray-500 small">Tanpa gambar</span><?php endif; ?></td>
          <td><div class="font-weight-bold text-gray-800"><?= e($r['judul']) ?></div>
            <?php if ($r['lokasi']): ?><div class="small text-gray-600"><i class="fas fa-map-marker-alt"></i> <?= e($r['lokasi']) ?></div><?php endif; ?></td>
          <td data-order="<?= e($r['tanggal']) ?>"><?= e(tgl_lengkap($r['tanggal'])) ?><?php if ($r['waktu']): ?><div class="small text-gray-600"><?= e($r['waktu']) ?></div><?php endif; ?></td>
          <td>
            <?php if (kegiatan_expired($r)): ?><span class="badge badge-danger">Kedaluwarsa (Disembunyikan dari Warga)</span>
            <?php elseif ($r['status'] === 'pending'): ?><span class="badge badge-warning">Menunggu persetujuan</span>
            <?php else: ?><span class="badge badge-success">Tampil di Warga</span><?php endif; ?>
            <div class="small text-gray-600 mt-1"><?= $r['berlaku_sampai'] ? 'Berlaku s/d: ' . e(tgl($r['berlaku_sampai'])) : 'Tanpa batas waktu' ?></div>
          </td>
          <td><?= e($r['author']) ?></td>
          <td class="text-nowrap">
            <a href="kegiatan_form.php?id=<?= $r['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i> <?= kegiatan_expired($r) ? 'Ubah / perpanjang' : 'Ubah' ?></a>
            <?php if ($isAdmin && $r['status'] === 'pending'): ?>
            <form method="post" action="kegiatan_aksi.php" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-success btn-sm" name="do" value="approve"><i class="fas fa-check"></i> Setujui</button></form>
            <?php endif; ?>
            <form method="post" action="kegiatan_aksi.php" class="d-inline" onsubmit="return confirm('Hapus kegiatan ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-danger btn-sm" name="do" value="delete"><i class="fas fa-trash"></i> Hapus</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
