<?php
require __DIR__ . '/../config.php';
need_login(); $u = me();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: tulisan.php'); exit; }
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$s = $db->prepare("SELECT * FROM posts WHERE id=?"); $s->execute([$id]); $p = $s->fetch();
if (!$p) { flash('Tulisan tidak ditemukan.'); header('Location: tulisan.php'); exit; }
$own = (int)$p['author_id'] === $u['id'];
if (($_POST['do'] ?? '') === 'approve' && $u['role'] === 'admin') {
    $db->prepare("UPDATE posts SET status='publish' WHERE id=?")->execute([$id]);
    flash('Tulisan disetujui dan tayang di situs.');
} elseif (($_POST['do'] ?? '') === 'delete' && ($u['role'] === 'admin' || $own)) {
    $db->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
    if ($p['image']) @unlink(UPLOAD_DIR . $p['image']);
    flash('Tulisan dihapus.');
} else { http_response_code(403); exit('Tidak diizinkan.'); }
header('Location: tulisan.php');
