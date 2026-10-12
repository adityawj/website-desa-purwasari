<?php
require_once __DIR__ . '/../config.php'; need_admin();
$me = me(); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'add') {
        $un = trim($_POST['username'] ?? ''); $nm = trim($_POST['name'] ?? ''); $pw = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'rt';
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $un)) $err = 'Nama pengguna 3–30 karakter: huruf, angka, atau garis bawah.';
        elseif ($nm === '') $err = 'Nama lengkap wajib diisi.';
        elseif (strlen($pw) < 8) $err = 'Kata sandi minimal 8 karakter.';
        else try {
            $db->prepare("INSERT INTO users(username,name,password,role) VALUES(?,?,?,?)")->execute([$un, $nm, password_hash($pw, PASSWORD_DEFAULT), $role]);
            flash('Pengguna ditambahkan.'); header('Location: users.php'); exit;
        } catch (PDOException $x) { $err = 'Nama pengguna sudah dipakai.'; }
    } elseif ($do === 'reset') {
        $pw = $_POST['password'] ?? '';
        if (strlen($pw) < 8) $err = 'Kata sandi baru minimal 8 karakter.';
        else { $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($pw, PASSWORD_DEFAULT), (int)$_POST['id']]); flash('Kata sandi diganti.'); header('Location: users.php'); exit; }
    } elseif ($do === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === $me['id']) $err = 'Anda tidak dapat menghapus akun sendiri.';
        else {
            $c = $db->prepare("SELECT COUNT(*) c FROM posts WHERE author_id=?"); $c->execute([$id]);
            if ((int)$c->fetch()['c']) $err = 'Pengguna ini masih punya tulisan. Hapus tulisannya dulu.';
            else { $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]); flash('Pengguna dihapus.'); header('Location: users.php'); exit; }
        }
    }
}
$title = 'Pengguna'; $nav = 'users';
require __DIR__ . '/_head.php';
$users = $db->query("SELECT * FROM users ORDER BY role,username")->fetchAll();
?>
<h1 class="h3 mb-4 text-gray-800">Pengguna</h1>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="card shadow mb-4">
  <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Daftar pengguna</h6></div>
  <div class="card-body"><div class="table-responsive">
    <table class="table table-bordered" width="100%" cellspacing="0">
      <thead><tr><th>Nama</th><th>Pengguna</th><th>Peran</th><th>Ganti kata sandi</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($users as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td><td><?= e($r['username']) ?></td>
          <td><span class="badge badge-<?= $r['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= $r['role'] === 'admin' ? 'Administrator' : 'Ketua RT / Bendahara' ?></span></td>
          <td>
            <form method="post" class="form-inline"><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="id" value="<?= $r['id'] ?>">
              <div class="input-group input-group-sm"><input type="password" name="password" class="form-control" placeholder="Sandi baru (min. 8)" minlength="8" required>
              <div class="input-group-append"><button class="btn btn-primary">Simpan</button></div></div></form>
          </td>
          <td><?php if ((int)$r['id'] !== $me['id']): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button></form>
          <?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</div>

<div class="row"><div class="col-xl-6 col-lg-8">
<div class="card shadow mb-4">
  <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Tambah pengguna</h6></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="do" value="add">
      <div class="form-group"><label>Nama lengkap</label><input class="form-control" name="name" required></div>
      <div class="form-group"><label>Nama pengguna</label><input class="form-control" name="username" required></div>
      <div class="form-group"><label>Kata sandi <small class="text-gray-600">(minimal 8 karakter)</small></label><input type="password" class="form-control" name="password" minlength="8" required></div>
      <div class="form-group"><label>Peran</label><select class="form-control" name="role"><option value="rt">Ketua RT / Bendahara</option><option value="admin">Administrator</option></select></div>
      <button class="btn btn-primary"><i class="fas fa-user-plus"></i> Tambah</button>
    </form>
  </div>
</div>
</div></div>
<?php require __DIR__ . '/_foot.php'; ?>
