<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc_ui.php';
$title = $title ?? 'Beranda'; $page = $page ?? '';
$on = fn(string $k) => $page === $k ? ' class="on"' : '';
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> — <?= e(SITE_NAME) ?></title>
<meta name="description" content="<?= e(SITE_NAME) ?>, <?= e(SITE_ADDRESS) ?>. <?= e(SITE_TAGLINE) ?>.">
<meta name="theme-color" content="#123D2A">
<script>document.documentElement.className+=" js";</script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap">
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/assets/style.css') ?: time() ?>">
</head><body>
<div class="progress" id="progress" aria-hidden="true"></div>
<?php if (!empty($ticker)): ?>
<div class="ticker" role="region" aria-label="Pengumuman terbaru"><div class="wrap">
  <span class="tk-label"><?= icon('bell', 15) ?> Pengumuman</span>
  <div class="tk-track"><div class="tk-move">
    <?php for ($i = 0; $i < 6; $i++): foreach ($ticker as $t): ?><a href="<?= BASE ?>/konten.php?t=pengumuman&id=<?= (int)$t['id'] ?>"<?= $i ? ' tabindex="-1" aria-hidden="true"' : '' ?>><?= e($t['t']) ?></a><?php endforeach; endfor; ?>
  </div></div>
</div></div>
<?php endif; ?>
<header class="hdr"><div class="wrap">
  <a class="brand" href="<?= BASE ?>/"><span class="logo"><?= icon('leaf', 22) ?></span><span><b><?= e(SITE_NAME) ?></b><small><?= e(SITE_ADDRESS) ?></small></span></a>
  <button class="burger" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="nav"><i></i><i></i><i></i></button>
  <nav class="nav" id="nav" aria-label="Menu utama">
    <a<?= $on('home') ?> href="<?= BASE ?>/">Beranda</a>
    <a<?= $on('berita') ?> href="<?= BASE ?>/konten.php?t=berita">Berita</a>
    <a<?= $on('pengumuman') ?> href="<?= BASE ?>/konten.php?t=pengumuman">Pengumuman</a>
    <a<?= $on('kegiatan') ?> href="<?= BASE ?>/kegiatan.php">Kegiatan</a>
    <?php if (KAS_PUBLIK): ?><a<?= $on('kas') ?> href="<?= BASE ?>/kas.php">Kas Desa</a><?php endif; ?>
    <a class="cta" href="<?= BASE ?>/admin/">Masuk pengurus</a>
  </nav>
</div></header>
