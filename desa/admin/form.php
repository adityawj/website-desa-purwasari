<?php
require_once __DIR__ . '/../config.php'; need_login();
$u = me();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0); $p = null;
if ($id) {
    $s = $db->prepare("SELECT * FROM posts WHERE id=?"); $s->execute([$id]); $p = $s->fetch();
    if (!$p) { http_response_code(404); exit('Tulisan tidak ditemukan.'); }
    if ($u['role'] !== 'admin' && (int)$p['author_id'] !== $u['id']) { http_response_code(403); exit('Ini bukan tulisan Anda.'); }
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $type = ($_POST['type'] ?? '') === 'pengumuman' ? 'pengumuman' : 'berita';
    $t = trim($_POST['title'] ?? ''); $b = trim($_POST['body'] ?? '');
    $exp = $type === 'pengumuman' ? trim($_POST['expires_at'] ?? '') : '';
    $expOk = $exp === '' || (preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp) && checkdate((int)substr($exp, 5, 2), (int)substr($exp, 8, 2), (int)substr($exp, 0, 4)));
    if ($t === '' || mb_strlen($t) > 150) $err = 'Judul wajib diisi, maksimal 150 karakter.';
    elseif ($b === '') $err = 'Isi tulisan wajib diisi.';
    elseif (!$expOk) $err = 'Format tanggal berlaku tidak valid.';
    elseif ($exp !== '' && $exp < today() && $exp !== ($p['expires_at'] ?? '')) $err = 'Tanggal berlaku tidak boleh sebelum hari ini.';
    else {
        try {
            $new = upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            $status = $u['role'] === 'admin' ? (($_POST['status'] ?? 'publish') === 'pending' ? 'pending' : 'publish') : 'pending';
            if ($p) {
                $db->prepare("UPDATE posts SET type=?,title=?,body=?,image=?,status=?,expires_at=? WHERE id=?")
                   ->execute([$type, $t, $b, $new ?? $p['image'], $status, $exp ?: null, $id]);
                if ($new && $p['image']) @unlink(UPLOAD_DIR . $p['image']);
            } else {
                $db->prepare("INSERT INTO posts(type,title,body,image,status,author_id,created_at,expires_at) VALUES(?,?,?,?,?,?,?,?)")
                   ->execute([$type, $t, $b, $new, $status, $u['id'], date('Y-m-d H:i:s'), $exp ?: null]);
            }
            flash($u['role'] !== 'admin' ? 'Tersimpan. Menunggu persetujuan administrator.' : 'Tersimpan.');
            header('Location: tulisan.php'); exit;
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    }
}
$v = fn(string $k, string $d = '') => e($_POST[$k] ?? $p[$k] ?? $d);
$title = $p ? 'Ubah tulisan' : 'Tulis baru'; $nav = 'tulis';
require __DIR__ . '/_head.php';
?>
<h1 class="h3 mb-4 text-gray-800"><?= $title ?></h1>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<div class="row"><div class="col-xl-9 col-lg-10">
<div class="card shadow mb-4">
  <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Formulir tulisan</h6></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="form-group">
        <label for="typeSel">Jenis</label>
        <select class="form-control" name="type" id="typeSel">
          <?php foreach (['berita' => 'Berita', 'pengumuman' => 'Pengumuman'] as $k => $l): ?>
            <option value="<?= $k ?>" <?= ($_POST['type'] ?? $p['type'] ?? 'berita') === $k ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label for="title">Judul</label><input class="form-control" id="title" name="title" maxlength="150" required value="<?= $v('title') ?>"></div>
      <div class="form-group"><label for="body">Isi</label><textarea class="form-control" id="body" name="body" rows="10" required><?= $v('body') ?></textarea></div>
      <div class="form-group">
        <label for="image">Foto <small class="text-gray-600">(JPG, PNG, atau WebP, maksimal 2 MB)</small></label>
        <?php if ($p && $p['image']): ?><div class="mb-2"><img src="<?= img($p['image']) ?>" alt="Foto saat ini" class="img-thumbnail" style="max-width:220px"></div><?php endif; ?>
        <input type="file" class="form-control-file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
      </div>

      <div id="expBox" class="card border-left-warning mb-4"><div class="card-body">
        <h6 class="font-weight-bold text-warning">Masa aktif pengumuman</h6>
        <div class="form-group">
          <label for="expDate">Tanggal berlaku / masa aktif</label>
          <input type="date" class="form-control" id="expDate" name="expires_at" value="<?= $v('expires_at') ?>">
          <small class="form-text text-muted">Setelah tanggal ini, pengumuman otomatis disembunyikan dari warga. Kosongkan jika tanpa batas waktu.</small>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" data-days="7">+7 Hari</button>
        <button type="button" class="btn btn-outline-primary btn-sm" data-days="14">+14 Hari</button>
        <button type="button" class="btn btn-outline-primary btn-sm" data-days="30">+30 Hari</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-days="none">Tanpa batas waktu</button>
      </div></div>

      <?php if ($u['role'] === 'admin'): ?>
      <div class="form-group">
        <label for="status">Status</label>
        <select class="form-control" id="status" name="status">
          <option value="publish" <?= ($_POST['status'] ?? $p['status'] ?? 'publish') === 'publish' ? 'selected' : '' ?>>Tayang di situs</option>
          <option value="pending" <?= ($_POST['status'] ?? $p['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Simpan sebagai draf</option>
        </select>
      </div>
      <?php else: ?><div class="alert alert-info">Setelah disimpan, tulisan menunggu persetujuan administrator sebelum tayang.</div><?php endif; ?>

      <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      <a href="tulisan.php" class="btn btn-secondary">Batal</a>
    </form>
  </div>
</div>
</div></div>
<?php
$pageScript = <<<'JS'
<script>
(function () {
  var sel = document.getElementById('typeSel'), box = document.getElementById('expBox'), inp = document.getElementById('expDate');
  function sync() { box.hidden = sel.value !== 'pengumuman'; }
  sel.addEventListener('change', sync); sync();
  var pad = function (n) { return String(n).padStart(2, '0'); };
  box.querySelectorAll('[data-days]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (b.dataset.days === 'none') { inp.value = ''; return; }
      var d = new Date(); d.setDate(d.getDate() + Number(b.dataset.days));
      inp.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    });
  });
})();
</script>
JS;
require __DIR__ . '/_foot.php'; ?>
