<?php
require_once __DIR__ . '/../config.php'; need_login();
$title ??= 'Panel'; $nav ??= ''; $dt ??= false;
$u = me(); $isAdmin = $u['role'] === 'admin';
$on = fn(string $k) => $nav === $k ? ' active' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?= e($title) ?> - Panel <?= e(SITE_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
<?php if ($dt): ?><link href="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet"><?php endif; ?>
<link href="../assets/admin-green.css?v=<?= @filemtime(__DIR__ . '/../assets/admin-green.css') ?: time() ?>" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

  <!-- Sidebar -->
  <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= BASE ?>/admin/">
      <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-seedling"></i></div>
      <div class="sidebar-brand-text mx-3">Panel Desa</div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item<?= $on('dashboard') ?>">
      <a class="nav-link" href="<?= BASE ?>/admin/"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Konten</div>
    <li class="nav-item<?= $on('tulisan') ?>">
      <a class="nav-link" href="<?= BASE ?>/admin/tulisan.php"><i class="fas fa-fw fa-newspaper"></i><span>Berita &amp; Pengumuman</span></a>
    </li>
    <li class="nav-item<?= $on('kegiatan') ?>">
      <a class="nav-link" href="<?= BASE ?>/admin/kegiatan.php"><i class="fas fa-fw fa-calendar-alt"></i><span>Kegiatan Desa</span></a>
    </li>
    <li class="nav-item<?= $on('tulis') ?>">
      <a class="nav-link" href="<?= BASE ?>/admin/form.php"><i class="fas fa-fw fa-pen"></i><span>Tulis baru</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Keuangan</div>
    <li class="nav-item<?= $on('kas') ?>">
      <a class="nav-link" href="<?= BASE ?>/admin/kas.php"><i class="fas fa-fw fa-wallet"></i><span>Kas Desa</span></a>
    </li>
    <li class="nav-item<?= $on('kas_baru') ?>">
      <a class="nav-link" href="<?= BASE ?>/admin/kas_form.php"><i class="fas fa-fw fa-plus-circle"></i><span>Catat transaksi</span></a>
    </li>
    <?php if ($isAdmin): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Administrasi</div>
    <li class="nav-item<?= $on('users') ?>">
      <a class="nav-link" href="<?= BASE ?>/admin/users.php"><i class="fas fa-fw fa-users"></i><span>Pengguna</span></a>
    </li>
    <?php endif; ?>
    <hr class="sidebar-divider">
    <li class="nav-item">
      <a class="nav-link" href="<?= BASE ?>/" target="_blank" rel="noopener"><i class="fas fa-fw fa-globe"></i><span>Lihat situs</span></a>
    </li>
    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline"><button class="rounded-circle border-0" id="sidebarToggle"></button></div>
  </ul>
  <!-- End Sidebar -->

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">

      <!-- Topbar -->
      <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
        <ul class="navbar-nav ml-auto">
          <div class="topbar-divider d-none d-sm-block"></div>
          <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= e($u['name']) ?> (<?= $isAdmin ? 'Administrator' : 'Ketua RT / Bendahara' ?>)</span>
              <i class="fas fa-user-circle fa-2x text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="<?= BASE ?>/" target="_blank" rel="noopener"><i class="fas fa-globe fa-sm fa-fw mr-2 text-gray-400"></i>Lihat situs</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Keluar</a>
            </div>
          </li>
        </ul>
      </nav>
      <!-- End Topbar -->

      <div class="container-fluid">
<?php show_flash(); ?>
