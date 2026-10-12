<?php
require_once __DIR__ . '/../config.php'; need_login();
$u = me();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0); $k = null;
if ($id) {
    $s = $db->prepare("SELECT * FROM kas WHERE id=?"); $s->execute([$id]); $k = $s->fetch();
    if (!$k) { http_response_code(404); exit('Transaksi tidak ditemukan.'); }
    if ($u['role'] !== 'admin' && (int)$k['author_id'] !== $u['id']) { http_response_code(403); exit('Ini bukan transaksi yang Anda catat.'); }
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $jenis = ($_POST['jenis'] ?? '') === 'keluar' ? 'keluar' : 'masuk';
    $tgl = trim($_POST['tanggal'] ?? ''); $kat = trim($_POST['kategori'] ?? '');
    $ket = trim($_POST['keterangan'] ?? ''); $jml = (int)preg_replace('/\D/', '', $_POST['jumlah'] ?? '');
    $tglOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) && checkdate((int)substr($tgl, 5, 2), (int)substr($tgl, 8, 2), (int)substr($tgl, 0, 4));
    if (!$tglOk) $err = 'Tanggal tidak valid.';
    elseif ($tgl > today()) $err = 'Tanggal transaksi tidak boleh di masa depan.';
    elseif (!in_array($kat, KAS_KATEGORI[$jenis], true)) $err = 'Pilih kategori yang tersedia.';
    elseif ($ket === '' || mb_strlen($ket) > 200) $err = 'Keterangan wajib diisi, maksimal 200 karakter.';
    elseif ($jml < 1 || $jml > 100000000000) $err = 'Jumlah harus lebih dari 0.';
    else {
        if ($k) {
            $db->prepare("UPDATE kas SET jenis=?,tanggal=?,kategori=?,keterangan=?,jumlah=? WHERE id=?")->execute([$jenis, $tgl, $kat, $ket, $jml, $id]);
        } else {
            $db->prepare("INSERT INTO kas(jenis,tanggal,kategori,keterangan,jumlah,author_id,created_at) VALUES(?,?,?,?,?,?,?)")
               ->execute([$jenis, $tgl, $kat, $ket, $jml, $u['id'], date('Y-m-d H:i:s')]);
        }
        flash('Transaksi kas tersimpan.');
        header('Location: kas.php'); exit;
    }
}
$jenisVal = $_POST['jenis'] ?? $k['jenis'] ?? ($_GET['jenis'] ?? 'masuk');
if (!in_array($jenisVal, ['masuk', 'keluar'], true)) $jenisVal = 'masuk';
$tglVal = $_POST['tanggal'] ?? $k['tanggal'] ?? today();
$katVal = $_POST['kategori'] ?? $k['kategori'] ?? '';
$ketVal = $_POST['keterangan'] ?? $k['keterangan'] ?? '';
$jmlRaw = $_POST['jumlah'] ?? ($k ? number_format((int)$k['jumlah'], 0, ',', '.') : '');
$title = $k ? 'Ubah transaksi kas' : 'Catat transaksi kas'; $nav = $k ? 'kas' : 'kas_baru';
$pageScript = '<script>var KAT=' . json_encode(KAS_KATEGORI, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';var SEL=' . json_encode($katVal, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>' . <<<'JS'
<script>
(function () {
  var sel = document.getElementById('kategori'), amt = document.getElementById('jumlah');
  function fill() {
    var j = document.querySelector('input[name=jenis]:checked').value, cur = sel.value || SEL;
    sel.innerHTML = '';
    KAT[j].forEach(function (k) { var o = document.createElement('option'); o.value = o.textContent = k; if (k === cur) o.selected = true; sel.appendChild(o); });
  }
  document.querySelectorAll('input[name=jenis]').forEach(function (r) { r.addEventListener('change', function () { SEL = ''; sel.value = ''; fill(); }); });
  fill();
  amt.addEventListener('input', function () { var d = amt.value.replace(/\D/g, ''); amt.value = d ? Number(d).toLocaleString('id-ID') : ''; });
})();
</script>
JS;
require __DIR__ . '/_head.php';
?>
<h1 class="h3 mb-4 text-gray-800"><?= $title ?></h1>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<div class="row"><div class="col-xl-7 col-lg-9">
<div class="card shadow mb-4">
  <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Formulir transaksi</h6></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="form-group">
        <label class="d-block">Jenis transaksi</label>
        <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="j1" name="jenis" value="masuk" class="custom-control-input" <?= $jenisVal === 'masuk' ? 'checked' : '' ?>><label class="custom-control-label text-success font-weight-bold" for="j1">Pemasukan</label></div>
        <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="j2" name="jenis" value="keluar" class="custom-control-input" <?= $jenisVal === 'keluar' ? 'checked' : '' ?>><label class="custom-control-label text-danger font-weight-bold" for="j2">Pengeluaran</label></div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6"><label for="tanggal">Tanggal</label><input type="date" class="form-control" id="tanggal" name="tanggal" max="<?= today() ?>" required value="<?= e($tglVal) ?>"></div>
        <div class="form-group col-md-6"><label for="kategori">Kategori</label><select class="form-control" id="kategori" name="kategori" required></select></div>
      </div>
      <div class="form-group"><label for="jumlah">Jumlah</label>
        <div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
          <input type="text" inputmode="numeric" class="form-control" id="jumlah" name="jumlah" placeholder="contoh: 150.000" required value="<?= e($jmlRaw) ?>"></div></div>
      <div class="form-group"><label for="keterangan">Keterangan / rincian</label><input class="form-control" id="keterangan" name="keterangan" maxlength="200" required placeholder="Contoh: Iuran warga bulan Oktober" value="<?= e($ketVal) ?>"></div>
      <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      <a href="kas.php" class="btn btn-secondary">Batal</a>
    </form>
  </div>
</div>
</div></div>
<?php require __DIR__ . '/_foot.php'; ?>
