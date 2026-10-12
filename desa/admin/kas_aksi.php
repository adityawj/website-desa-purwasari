<?php
require __DIR__ . '/../config.php';
need_login(); $u = me();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: kas.php'); exit; }
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$s = $db->prepare("SELECT * FROM kas WHERE id=?"); $s->execute([$id]); $k = $s->fetch();
if (!$k) { flash('Transaksi tidak ditemukan.'); header('Location: kas.php'); exit; }
if ($u['role'] !== 'admin' && (int)$k['author_id'] !== $u['id']) { http_response_code(403); exit('Tidak diizinkan.'); }
$db->prepare("DELETE FROM kas WHERE id=?")->execute([$id]);
flash('Transaksi dihapus.');
header('Location: kas.php');
