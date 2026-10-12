<?php
require __DIR__ . '/../config.php';
need_login(); $u = me();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: kegiatan.php'); exit; }
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$s = $db->prepare("SELECT * FROM kegiatan WHERE id=?"); $s->execute([$id]); $k = $s->fetch();
if (!$k) { flash('Kegiatan tidak ditemukan.'); header('Location: kegiatan.php'); exit; }
$own = (int)$k['author_id'] === $u['id'];
if (($_POST['do'] ?? '') === 'approve' && $u['role'] === 'admin') {
    $db->prepare("UPDATE kegiatan SET status='publish' WHERE id=?")->execute([$id]);
    flash('Kegiatan disetujui dan tampil di situs.');
} elseif (($_POST['do'] ?? '') === 'delete' && ($u['role'] === 'admin' || $own)) {
    $db->prepare("DELETE FROM kegiatan WHERE id=?")->execute([$id]);
    if ($k['image']) @unlink(UPLOAD_DIR . $k['image']);
    flash('Kegiatan dihapus.');
} else { http_response_code(403); exit('Tidak diizinkan.'); }
header('Location: kegiatan.php');
