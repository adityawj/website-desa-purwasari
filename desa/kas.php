<?php
require __DIR__ . '/config.php';
$title = 'Kas Desa'; $page = 'kas';
if (!KAS_PUBLIK) {
    http_response_code(404); include __DIR__ . '/inc_head.php';
    echo '<main class="wrap pad"><h1>Halaman tidak tersedia</h1><p><a href="' . BASE . '/">Kembali ke beranda</a></p></main>';
    include __DIR__ . '/inc_foot.php'; exit;
}
$bulanList = $db->query("SELECT DISTINCT substr(tanggal,1,7) b FROM kas ORDER BY b DESC")->fetchAll(PDO::FETCH_COLUMN);
$ym = $_GET['bulan'] ?? ''; if (!in_array($ym, $bulanList, true)) $ym = '';
$periode = $ym ? bulan_label($ym) : 'Seluruh periode';
$saldo = kas_saldo($db);
$sections = ['masuk' => 'Rincian pemasukan', 'keluar' => 'Rincian pengeluaran'];
$tot = ['masuk' => kas_total($db, 'masuk', $ym ?: null), 'keluar' => kas_total($db, 'keluar', $ym ?: null)];
include __DIR__ . '/inc_head.php';
?>
<section class="pagehead"><div class="wrap">
  <h1>Transparansi kas</h1>
  <p>Laporan pemasukan dan pengeluaran kas <?= e(SITE_NAME) ?>, <?= e(SITE_ADDRESS) ?>.</p>
</div></section>
<main class="wrap pad">
  <form class="filter" method="get">
    <label for="bulan">Periode</label>
    <select name="bulan" id="bulan" onchange="this.form.submit()">
      <option value="">Seluruh periode</option>
      <?php foreach ($bulanList as $b): ?><option value="<?= e($b) ?>" <?= $b === $ym ? 'selected' : '' ?>><?= e(bulan_label($b)) ?></option><?php endforeach; ?>
    </select>
    <noscript><button class="btn small">Terapkan</button></noscript>
    <button type="button" class="btn small ghost-d" onclick="window.print()">Cetak laporan</button>
  </form>

  <div class="stats flat">
    <div class="stat saldo"><span class="ic"><?= icon('wallet') ?></span><div><small>Saldo kas aktif (keseluruhan)</small><b data-count="<?= $saldo ?>" data-rp="1"><?= rp($saldo) ?></b></div></div>
    <div class="stat in"><span class="ic"><?= icon('in') ?></span><div><small>Total pemasukan · <?= e($periode) ?></small><b data-count="<?= $tot['masuk'] ?>" data-rp="1"><?= rp($tot['masuk']) ?></b></div></div>
    <div class="stat out"><span class="ic"><?= icon('out') ?></span><div><small>Total pengeluaran · <?= e($periode) ?></small><b data-count="<?= $tot['keluar'] ?>" data-rp="1"><?= rp($tot['keluar']) ?></b></div></div>
  </div>

  <?php foreach ($sections as $j => $lbl):
      $w = $ym ? "AND substr(k.tanggal,1,7)=?" : ''; $a = $ym ? [$j, $ym] : [$j];
      $s = $db->prepare("SELECT k.kategori, SUM(k.jumlah) s FROM kas k WHERE k.jenis=? $w GROUP BY k.kategori ORDER BY s DESC"); $s->execute($a); $cats = $s->fetchAll();
      $s = $db->prepare("SELECT k.* FROM kas k WHERE k.jenis=? $w ORDER BY k.tanggal DESC,k.id DESC"); $s->execute($a); $rows = $s->fetchAll();
  ?>
  <section class="panel big <?= $j ?>">
    <div class="ph"><h2><?= $lbl ?></h2><span class="amt"><?= rp($tot[$j]) ?></span></div>
    <?php if (!$rows): ?><p class="empty">Belum ada data pada periode ini.</p><?php else: ?>
    <div class="bars">
      <?php foreach ($cats as $c): $pct = $tot[$j] ? round($c['s'] / $tot[$j] * 100) : 0; ?>
      <div class="bar-row"><div class="bar-top"><span><?= e($c['kategori']) ?> <small><?= $pct ?>%</small></span><b><?= rp($c['s']) ?></b></div><div class="bar"><i style="width:<?= $pct ?>%"></i></div></div>
      <?php endforeach; ?>
    </div>
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="r">Jumlah</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td data-label="Tanggal"><?= tgl($r['tanggal']) ?></td><td data-label="Kategori"><?= e($r['kategori']) ?></td><td data-label="Keterangan"><?= e($r['keterangan']) ?></td><td data-label="Jumlah" class="r amt"><?= rp($r['jumlah']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="3">Total <?= strtolower(substr($lbl, 8)) ?></td><td class="r amt"><?= rp($tot[$j]) ?></td></tr></tfoot>
    </table></div>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/inc_foot.php'; ?>
