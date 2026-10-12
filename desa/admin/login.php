<?php
require __DIR__ . '/../config.php';
if (me()) { header('Location: ' . BASE . '/admin/'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = $db->prepare("SELECT * FROM users WHERE username=?");
    $s->execute([trim($_POST['username'] ?? '')]);
    $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'role' => $u['role'], 'username' => $u['username']];
        header('Location: ' . BASE . '/admin/'); exit;
    }
    sleep(1);
    $err = 'Nama pengguna atau kata sandi salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Masuk - <?= e(SITE_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
<link href="../assets/admin-green.css?v=<?= @filemtime(__DIR__ . '/../assets/admin-green.css') ?: time() ?>" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-xl-10 col-lg-12 col-md-9">
      <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
          <div class="row">
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-gradient-success text-white text-center p-5">
              <div><i class="fas fa-seedling fa-5x mb-4"></i><h2 class="h3"><?= e(SITE_NAME) ?></h2><p class="mb-0">Panel pengelola berita dan pengumuman desa</p></div>
            </div>
            <div class="col-lg-6">
              <div class="p-5">
                <div class="text-center"><h1 class="h4 text-gray-900 mb-4">Selamat datang kembali!</h1></div>
                <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
                <form class="user" method="post">
                  <?= csrf_field() ?>
                  <div class="form-group"><input type="text" name="username" class="form-control form-control-user" placeholder="Nama pengguna" required autofocus autocomplete="username"></div>
                  <div class="form-group"><input type="password" name="password" class="form-control form-control-user" placeholder="Kata sandi" required autocomplete="current-password"></div>
                  <button class="btn btn-primary btn-user btn-block">Masuk</button>
                </form>
                <hr>
                <div class="text-center"><a class="small" href="<?= BASE ?>/">&larr; Kembali ke situs desa</a></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
