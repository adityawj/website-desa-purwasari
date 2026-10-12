<?php
require __DIR__ . '/config.php';
$t = ($_GET['t'] ?? 'berita') === 'pengumuman' ? 'pengumuman' : 'berita';
$label = $t === 'berita' ? 'Berita' : 'Pengumuman';
$id = (int)($_GET['id'] ?? 0);
$page = $t;

if ($id) {
    $s = $db->prepare("SELECT p.*,u.name author FROM posts p JOIN users u ON u.id=p.author_id WHERE p.id=? AND p.type=? AND p.status='publish' AND (p.expires_at IS NULL OR p.expires_at >= ?)");
    $s->execute([$id, $t, today()]); $post = $s->fetch();
    if (!$post) {
        http_response_code(404); $title = 'Tidak ditemukan'; include __DIR__ . '/inc_head.php';
        echo '<main class="wrap pad"><h1>Konten tidak ditemukan atau sudah tidak berlaku</h1><p><a href="' . BASE . '/">Kembali ke beranda</a></p></main>';
        include __DIR__ . '/inc_foot.php'; exit;
    }
    $s = $db->prepare("SELECT id,title,created_at FROM posts WHERE type=? AND status='publish' AND id<>? AND (expires_at IS NULL OR expires_at >= ?) ORDER BY created_at DESC,id DESC LIMIT 5");
    $s->execute([$t, $id, today()]); $others = $s->fetchAll();
    $title = $post['title'];
    $url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'];
    include __DIR__ . '/inc_head.php'; ?>
    <main class="wrap pad">
      <p><a href="<?= BASE ?>/konten.php?t=<?= $t ?>">&larr; Kembali ke <?= strtolower($label) ?></a></p>
      <div class="art-grid">
        <article class="article">
          <h1><?= e($post['title']) ?></h1>
          <p class="meta"><?= tgl($post['created_at']) ?> · oleh <?= e($post['author']) ?></p>
          <?php if ($post['expires_at']): ?><p><span class="until"><?= e(berlaku($post)) ?></span></p><?php endif; ?>
          <?php if ($post['image']): ?><img class="full" src="<?= img($post['image']) ?>" alt=""><?php endif; ?>
          <div class="body"><?= paras($post['body']) ?></div>
          <div class="share">
            <span>Bagikan:</span>
            <a class="btn small" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($post['title'] . ' - ' . $url) ?>">WhatsApp</a>
            <button class="btn small ghost-d" type="button" data-copy>Salin tautan</button>
          </div>
        </article>
        <aside class="side">
          <h3><?= $label ?> lainnya</h3>
          <?php if (!$others): ?><p class="empty">Belum ada yang lain.</p><?php endif; ?>
          <?php foreach ($others as $o): ?>
            <a class="side-li" href="<?= BASE ?>/konten.php?t=<?= $t ?>&id=<?= $o['id'] ?>"><small><?= tgl($o['created_at']) ?></small><b><?= e($o['title']) ?></b></a>
          <?php endforeach; ?>
        </aside>
      </div>
    </main>
<?php } else {
    $q = trim($_GET['q'] ?? ''); $per = 9; $pg = max(1, (int)($_GET['p'] ?? 1));
    $w = "type=? AND status='publish' AND (expires_at IS NULL OR expires_at >= ?)"; $a = [$t, today()];
    if ($q !== '') { $w .= " AND (title LIKE ? OR body LIKE ?)"; $a[] = "%$q%"; $a[] = "%$q%"; }
    $c = $db->prepare("SELECT COUNT(*) c FROM posts WHERE $w"); $c->execute($a); $total = (int)$c->fetch()['c'];
    $pages = max(1, (int)ceil($total / $per)); $pg = min($pg, $pages);
    $s = $db->prepare("SELECT id,title,body,image,created_at,expires_at FROM posts WHERE $w ORDER BY created_at DESC,id DESC LIMIT $per OFFSET " . (($pg - 1) * $per));
    $s->execute($a); $rows = $s->fetchAll();
    $title = $label;
    $link = fn(int $n) => BASE . '/konten.php?t=' . $t . ($q !== '' ? '&q=' . rawurlencode($q) : '') . '&p=' . $n;
    include __DIR__ . '/inc_head.php'; ?>
    <section class="pagehead"><div class="wrap">
      <h1><?= $label ?></h1>
      <p><?= $t === 'berita' ? 'Kabar dan kegiatan di lingkungan ' . e(SITE_NAME) . '.' : 'Pengumuman yang masih berlaku untuk warga.' ?></p>
    </div></section>
    <main class="wrap pad">
      <form class="search" method="get" role="search">
        <input type="hidden" name="t" value="<?= $t ?>">
        <?= icon('search', 18) ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari <?= strtolower($label) ?>…" aria-label="Cari">
        <button class="btn small">Cari</button>
      </form>
      <?php if (!$rows): ?><p class="empty"><?= $q !== '' ? 'Tidak ada hasil untuk "' . e($q) . '".' : 'Belum ada ' . strtolower($label) . '.' ?></p><?php endif; ?>
      <?php if ($t === 'berita'): ?>
        <div class="grid"><?php foreach ($rows as $n) echo card_news($n); ?></div>
      <?php else: ?>
        <div class="grid2"><?php foreach ($rows as $n) echo card_ann($n); ?></div>
      <?php endif; ?>
      <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="Halaman">
        <?php if ($pg > 1): ?><a href="<?= $link($pg - 1) ?>">&larr; Sebelumnya</a><?php endif; ?>
        <span>Halaman <?= $pg ?> dari <?= $pages ?></span>
        <?php if ($pg < $pages): ?><a href="<?= $link($pg + 1) ?>">Berikutnya &rarr;</a><?php endif; ?>
      </nav>
      <?php endif; ?>
    </main>
<?php }
include __DIR__ . '/inc_foot.php';
