<?php
require __DIR__ . '/config.php';
$page = 'kegiatan';
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $s = $db->prepare("SELECT k.*,u.name author FROM kegiatan k JOIN users u ON u.id=k.author_id WHERE k.id=? AND k.status='publish' AND (k.berlaku_sampai IS NULL OR k.berlaku_sampai >= ?)");
    $s->execute([$id, today()]); $k = $s->fetch();
    if (!$k) {
        http_response_code(404); $title = 'Tidak ditemukan'; include __DIR__ . '/inc_head.php';
        echo '<main class="wrap pad"><h1>Kegiatan tidak ditemukan atau sudah tidak ditampilkan</h1><p><a href="' . BASE . '/kegiatan.php">Lihat kegiatan lainnya</a></p></main>';
        include __DIR__ . '/inc_foot.php'; exit;
    }
    $others = array_slice(array_values(array_filter(kegiatan_aktif($db, 8), fn($o) => (int)$o['id'] !== $id)), 0, 5);
    [$lbl, $cls] = kegiatan_label($k);
    $title = $k['judul'];
    $url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'];
    include __DIR__ . '/inc_head.php'; ?>
    <main class="wrap pad">
      <p><a href="<?= BASE ?>/kegiatan.php">&larr; Kembali ke kegiatan desa</a></p>
      <div class="art-grid">
        <article class="article">
          <span class="tag <?= $cls ?>"><?= $lbl ?></span>
          <h1><?= e($k['judul']) ?></h1>
          <?php if ($k['image']): ?><img class="full" src="<?= img($k['image']) ?>" alt=""><?php endif; ?>
          <ul class="facts">
            <li><span class="fi"><?= icon('calendar', 20) ?></span><div><small>Tanggal</small><b><?= e(tgl_lengkap($k['tanggal'])) ?></b></div></li>
            <?php if ($k['waktu']): ?><li><span class="fi"><?= icon('clock', 20) ?></span><div><small>Waktu</small><b><?= e($k['waktu']) ?></b></div></li><?php endif; ?>
            <?php if ($k['lokasi']): ?><li><span class="fi"><?= icon('pin', 20) ?></span><div><small>Lokasi</small><b><?= e($k['lokasi']) ?></b></div></li><?php endif; ?>
          </ul>
          <div class="body"><?= paras($k['deskripsi']) ?></div>
          <p class="meta">Diumumkan oleh <?= e($k['author']) ?><?= $k['berlaku_sampai'] ? ' · Informasi ditampilkan hingga ' . e(tgl($k['berlaku_sampai'])) : '' ?></p>
          <div class="share">
            <span>Bagikan:</span>
            <a class="btn small" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($k['judul'] . ' - ' . tgl_lengkap($k['tanggal']) . ' - ' . $url) ?>">WhatsApp</a>
            <button class="btn small ghost-d" type="button" data-copy>Salin tautan</button>
          </div>
        </article>
        <aside class="side">
          <h3>Kegiatan lainnya</h3>
          <?php if (!$others): ?><p class="empty">Belum ada kegiatan lain.</p><?php endif; ?>
          <?php foreach ($others as $o): ?>
            <a class="side-li" href="<?= BASE ?>/kegiatan.php?id=<?= $o['id'] ?>"><small><?= e(tgl_lengkap($o['tanggal'])) ?></small><b><?= e($o['judul']) ?></b></a>
          <?php endforeach; ?>
        </aside>
      </div>
    </main>
<?php } else {
    $rows = kegiatan_aktif($db, 60);
    $title = 'Kegiatan desa';
    include __DIR__ . '/inc_head.php'; ?>
    <section class="pagehead"><div class="wrap">
      <h1>Kegiatan desa</h1>
      <p>Agenda dan kegiatan warga <?= e(SITE_NAME) ?>, <?= e(SITE_ADDRESS) ?>, lengkap dengan tanggal, waktu, dan lokasi.</p>
    </div></section>
    <main class="wrap pad">
      <?php if (!$rows): ?><p class="empty">Belum ada kegiatan yang ditampilkan.</p><?php endif; ?>
      <?php if ($rows): $cnt = ['soon' => 0, 'today' => 0, 'past' => 0]; foreach ($rows as $r) { $cnt[kegiatan_label($r)[1]]++; } ?>
      <div class="chips js-only" role="group" aria-label="Filter kegiatan">
        <button type="button" class="chip on" data-filter="all" aria-pressed="true">Semua (<?= count($rows) ?>)</button>
        <?php foreach (['soon' => 'Akan datang', 'today' => 'Hari ini', 'past' => 'Telah berlangsung'] as $key => $lab): if ($cnt[$key]): ?>
          <button type="button" class="chip" data-filter="<?= $key ?>" aria-pressed="false"><?= $lab ?> (<?= $cnt[$key] ?>)</button>
        <?php endif; endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="grid"><?php foreach ($rows as $k) echo card_keg($k); ?></div>
      <p class="empty" id="chip-empty" hidden>Tidak ada kegiatan pada kategori ini.</p>
    </main>
<?php }
include __DIR__ . '/inc_foot.php';
