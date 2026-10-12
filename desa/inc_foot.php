<footer class="ftr">
  <div class="wrap fgrid">
    <div><h3><?= e(SITE_NAME) ?></h3><p>Portal informasi warga <?= e(SITE_ADDRESS) ?>: berita, pengumuman, dan laporan kas yang dapat dibaca siapa saja.</p></div>
    <div><h4>Menu</h4>
      <a href="<?= BASE ?>/">Beranda</a><a href="<?= BASE ?>/konten.php?t=berita">Berita</a><a href="<?= BASE ?>/konten.php?t=pengumuman">Pengumuman</a><a href="<?= BASE ?>/kegiatan.php">Kegiatan</a>
      <?php if (KAS_PUBLIK): ?><a href="<?= BASE ?>/kas.php">Kas Desa</a><?php endif; ?>
    </div>
    <div><h4>Alamat</h4><p><?= e(SITE_NAME) ?><br><?= e(SITE_ADDRESS) ?></p></div>
  </div>
  <div class="copy wrap">© <?= date('Y') ?> <?= e(SITE_NAME) ?>. Seluruh informasi resmi desa.</div>
</footer>
<button class="totop" type="button" aria-label="Kembali ke atas"><?= icon('out', 22) ?></button>
<script src="assets/app.js?v=<?= @filemtime(__DIR__ . '/assets/app.js') ?: time() ?>"></script>
</body></html>
