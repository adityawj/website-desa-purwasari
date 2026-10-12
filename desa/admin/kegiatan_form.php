<?php
require_once __DIR__ . '/../config.php'; need_login();
$u = me();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0); $k = null;
if ($id) {
    $s = $db->prepare("SELECT * FROM kegiatan WHERE id=?"); $s->execute([$id]); $k = $s->fetch();
    if (!$k) { http_response_code(404); exit('Kegiatan tidak ditemukan.'); }
    if ($u['role'] !== 'admin' && (int)$k['author_id'] !== $u['id']) { http_response_code(403); exit('Ini bukan kegiatan yang Anda tambahkan.'); }
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $judul = trim($_POST['judul'] ?? ''); $desk = trim($_POST['deskripsi'] ?? '');
    $tgl = trim($_POST['tanggal'] ?? ''); $waktu = trim($_POST['waktu'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? ''); $berlaku = trim($_POST['berlaku_sampai'] ?? '');
    if ($judul === '' || mb_strlen($judul) > 150) $err = 'Nama kegiatan wajib diisi, maksimal 150 karakter.';
    elseif ($desk === '') $err = 'Deskripsi kegiatan wajib diisi.';
    elseif (!valid_date($tgl)) $err = 'Tanggal kegiatan tidak valid.';
    elseif (mb_strlen($waktu) > 60) $err = 'Waktu maksimal 60 karakter.';
    elseif (mb_strlen($lokasi) > 120) $err = 'Lokasi maksimal 120 karakter.';
    elseif ($berlaku !== '' && !valid_date($berlaku)) $err = 'Format tanggal berlaku tidak valid.';
    elseif ($berlaku !== '' && $berlaku < today() && $berlaku !== ($k['berlaku_sampai'] ?? '')) $err = 'Tanggal berlaku tidak boleh sebelum hari ini.';
    else {
        try {
            $new = upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            $status = $u['role'] === 'admin' ? (($_POST['status'] ?? 'publish') === 'pending' ? 'pending' : 'publish') : 'pending';
            if ($k) {
                $db->prepare("UPDATE kegiatan SET judul=?,deskripsi=?,image=?,tanggal=?,waktu=?,lokasi=?,berlaku_sampai=?,status=? WHERE id=?")
                   ->execute([$judul, $desk, $new ?? $k['image'], $tgl, $waktu ?: null, $lokasi ?: null, $berlaku ?: null, $status, $id]);
                if ($new && $k['image']) @unlink(UPLOAD_DIR . $k['image']);
            } else {
                $db->prepare("INSERT INTO kegiatan(judul,deskripsi,image,tanggal,waktu,lokasi,berlaku_sampai,status,author_id,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$judul, $desk, $new, $tgl, $waktu ?: null, $lokasi ?: null, $berlaku ?: null, $status, $u['id'], date('Y-m-d H:i:s')]);
            }
            flash($u['role'] !== 'admin' ? 'Tersimpan. Menunggu persetujuan administrator.' : 'Kegiatan tersimpan.');
            header('Location: kegiatan.php'); exit;
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    }
}
$v = fn(string $f) => e($_POST[$f] ?? $k[$f] ?? '');
$title = $k ? 'Ubah kegiatan' : 'Tambah kegiatan'; $nav = 'kegiatan';
$pageScript = <<<'JS'
<script>
(function () {
  var tg = document.getElementById('tanggal'), bl = document.getElementById('berlaku');
  var pad = function (n) { return String(n).padStart(2, '0'); };
  document.querySelectorAll('[data-add]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (b.dataset.add === 'none') { bl.value = ''; return; }
      var d = tg.value ? new Date(tg.value + 'T00:00:00') : new Date();
      d.setDate(d.getDate() + Number(b.dataset.add));
      bl.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    });
  });
})();
</script>
JS;
require __DIR__ . '/_head.php';
?>
<h1 class="h3 mb-4 text-gray-800"><?= $title ?></h1>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<div class="row"><div class="col-xl-9 col-lg-10">
<div class="card shadow mb-4">
  <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Formulir kegiatan</h6></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="form-group"><label for="judul">Nama kegiatan</label><input class="form-control" id="judul" name="judul" maxlength="150" required value="<?= $v('judul') ?>" placeholder="Contoh: Kerja bakti membersihkan lingkungan"></div>
      <div class="form-group"><label for="deskripsi">Deskripsi</label><textarea class="form-control" id="deskripsi" name="deskripsi" rows="8" required placeholder="Jelaskan kegiatan. Beri baris kosong untuk memisahkan paragraf."><?= $v('deskripsi') ?></textarea></div>
      <div class="form-group">
        <label for="image">Gambar <small class="text-gray-600">(JPG, PNG, atau WebP, maksimal 2 MB)</small></label>
        <?php if ($k && $k['image']): ?><div class="mb-2"><img src="<?= img($k['image']) ?>" alt="Gambar saat ini" class="img-thumbnail" style="max-width:240px"><div class="small text-gray-600">Pilih berkas baru untuk mengganti gambar.</div></div><?php endif; ?>
        <input type="file" class="form-control-file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
      </div>
      <div class="form-row">
        <div class="form-group col-md-4"><label for="tanggal">Tanggal kegiatan</label><input type="date" class="form-control" id="tanggal" name="tanggal" required value="<?= $v('tanggal') ?>"></div>
        <div class="form-group col-md-4"><label for="waktu">Waktu</label><input class="form-control" id="waktu" name="waktu" maxlength="60" value="<?= $v('waktu') ?>" placeholder="07.00 WIB - selesai"></div>
        <div class="form-group col-md-4"><label for="lokasi">Lokasi</label><input class="form-control" id="lokasi" name="lokasi" maxlength="120" value="<?= $v('lokasi') ?>" placeholder="Balai warga"></div>
      </div>

      <div class="card border-left-warning mb-4"><div class="card-body">
        <h6 class="font-weight-bold text-warning">Tanggal berlaku (masa tampil di situs)</h6>
        <div class="form-group">
          <input type="date" class="form-control" id="berlaku" name="berlaku_sampai" value="<?= $v('berlaku_sampai') ?>">
          <small class="form-text text-muted">Setelah tanggal ini, kegiatan otomatis disembunyikan dari warga. Kosongkan jika tanpa batas waktu. Tombol di bawah menghitung dari tanggal kegiatan.</small>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" data-add="0">Sampai hari kegiatan</button>
        <button type="button" class="btn btn-outline-primary btn-sm" data-add="7">+7 hari</button>
        <button type="button" class="btn btn-outline-primary btn-sm" data-add="14">+14 hari</button>
        <button type="button" class="btn btn-outline-primary btn-sm" data-add="30">+30 hari</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-add="none">Tanpa batas waktu</button>
      </div></div>

      <?php if ($u['role'] === 'admin'): ?>
      <div class="form-group"><label for="status">Status</label>
        <select class="form-control" id="status" name="status">
          <option value="publish" <?= ($_POST['status'] ?? $k['status'] ?? 'publish') === 'publish' ? 'selected' : '' ?>>Tampil di situs</option>
          <option value="pending" <?= ($_POST['status'] ?? $k['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Simpan sebagai draf</option>
        </select></div>
      <?php else: ?><div class="alert alert-info">Setelah disimpan, kegiatan menunggu persetujuan administrator sebelum tampil.</div><?php endif; ?>

      <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      <a href="kegiatan.php" class="btn btn-secondary">Batal</a>
    </form>
  </div>
</div>
</div></div>
<?php require __DIR__ . '/_foot.php'; ?>
