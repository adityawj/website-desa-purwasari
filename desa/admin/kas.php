<?php
$title = 'Kas Desa'; $nav = 'kas'; $dt = true;
require __DIR__ . '/_head.php';
$rows = $db->query("SELECT k.*,u.name author FROM kas k JOIN users u ON u.id=k.author_id ORDER BY k.tanggal DESC,k.id DESC")->fetchAll();
$tab = in_array($_GET['jenis'] ?? '', ['masuk', 'keluar'], true) ? $_GET['jenis'] : 'semua';
$sumM = $sumK = $nM = $nK = 0;
foreach ($rows as $r) { if ($r['jenis'] === 'masuk') { $sumM += $r['jumlah']; $nM++; } else { $sumK += $r['jumlah']; $nK++; } }
$shown = $tab === 'semua' ? $rows : array_filter($rows, fn($r) => $r['jenis'] === $tab);
$pageScript = <<<'JS'
<script>
$(function () {
  if (!$.fn.DataTable) return;
  $('#dataTable').DataTable({
    order: [], columnDefs: [{ orderable: false, targets: -1 }],
    language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_–_END_ dari _TOTAL_ transaksi',
      infoEmpty: 'Tidak ada data', zeroRecords: 'Tidak ada transaksi', emptyTable: 'Tidak ada transaksi',
      paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } }
  });
});
</script>
JS;
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0 text-gray-800">Kas Desa</h1>
  <a href="kas_form.php" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Catat transaksi</a>
</div>

<div class="row">
  <div class="col-xl-4 col-md-6 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
    <div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Saldo kas aktif</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?= rp($sumM - $sumK) ?></div></div>
    <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div></div></div></div></div>
  <div class="col-xl-4 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
    <div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total pemasukan (<?= $nM ?> transaksi)</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?= rp($sumM) ?></div></div>
    <div class="col-auto"><i class="fas fa-arrow-down fa-2x text-gray-300"></i></div></div></div></div></div>
  <div class="col-xl-4 col-md-6 mb-4"><div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
    <div class="col mr-2"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total pengeluaran (<?= $nK ?> transaksi)</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?= rp($sumK) ?></div></div>
    <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-gray-300"></i></div></div></div></div></div>
</div>

<div class="card shadow mb-4">
  <div class="card-header py-3">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'semua' ? ' active' : '' ?>" href="?jenis=semua">Semua (<?= count($rows) ?>)</a></li>
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'masuk' ? ' active' : '' ?>" href="?jenis=masuk">Pemasukan (<?= $nM ?>)</a></li>
      <li class="nav-item"><a class="nav-link py-1<?= $tab === 'keluar' ? ' active' : '' ?>" href="?jenis=keluar">Pengeluaran (<?= $nK ?>)</a></li>
    </ul>
  </div>
  <div class="card-body"><div class="table-responsive">
    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
      <thead><tr><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Keterangan</th><th class="text-right">Jumlah</th><th>Dicatat oleh</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($shown as $r): $can = $isAdmin || (int)$r['author_id'] === $u['id']; ?>
        <tr>
          <td data-order="<?= e($r['tanggal']) ?>"><?= tgl($r['tanggal']) ?></td>
          <td><span class="badge badge-<?= $r['jenis'] === 'masuk' ? 'success' : 'danger' ?>"><?= $r['jenis'] === 'masuk' ? 'Pemasukan' : 'Pengeluaran' ?></span></td>
          <td><?= e($r['kategori']) ?></td>
          <td><?= e($r['keterangan']) ?></td>
          <td class="text-right font-weight-bold text-<?= $r['jenis'] === 'masuk' ? 'success' : 'danger' ?>" data-order="<?= (int)$r['jumlah'] ?>"><?= $r['jenis'] === 'masuk' ? '+' : '−' ?> <?= rp($r['jumlah']) ?></td>
          <td><?= e($r['author']) ?></td>
          <td class="text-nowrap">
            <?php if ($can): ?>
            <a href="kas_form.php?id=<?= $r['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i> Ubah</a>
            <form method="post" action="kas_aksi.php" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button></form>
            <?php else: ?><span class="small text-gray-500">Hanya pencatat atau administrator</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
