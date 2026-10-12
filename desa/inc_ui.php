<?php
function card_news(array $n): string {
    $href = BASE . '/konten.php?t=berita&id=' . $n['id'];
    $im = $n['image'] ? '<img src="' . img($n['image']) . '" alt="" loading="lazy">' : '<div class="noimg">' . icon('leaf', 34) . '</div>';
    return '<a class="card" href="' . $href . '">' . $im . '<div class="cb"><small>' . tgl($n['created_at']) . '</small><h3>' . e($n['title']) . '</h3><p>' . e(excerpt($n['body'])) . '</p></div></a>';
}
function card_ann(array $a): string {
    [$d, $m] = tgl_parts($a['created_at']);
    $until = !empty($a['expires_at']) ? '<span class="until">' . e(berlaku($a)) . '</span>' : '';
    return '<a class="ann-card" href="' . BASE . '/konten.php?t=pengumuman&id=' . $a['id'] . '"><div class="cal"><b>' . $d . '</b><span>' . $m . '</span></div>'
         . '<div><h3>' . e($a['title']) . '</h3><p>' . e(excerpt($a['body'], 110)) . '</p>' . $until . '</div></a>';
}

function card_keg(array $k): string {
    [$lbl, $cls] = kegiatan_label($k);
    $im = $k['image'] ? '<img src="' . img($k['image']) . '" alt="" loading="lazy">' : '<div class="noimg">' . icon('calendar', 36) . '</div>';
    $meta = '';
    if (!empty($k['waktu'])) $meta .= '<span>' . icon('clock', 15) . e($k['waktu']) . '</span>';
    if (!empty($k['lokasi'])) $meta .= '<span>' . icon('pin', 15) . e($k['lokasi']) . '</span>';
    return '<a class="card keg" data-status="' . $cls . '" href="' . BASE . '/kegiatan.php?id=' . $k['id'] . '">' . $im . '<div class="cb"><span class="tag ' . $cls . '">' . $lbl . '</span>'
         . '<div class="kdate">' . icon('calendar', 16) . e(tgl_lengkap($k['tanggal'])) . '</div><h3>' . e($k['judul']) . '</h3><p>' . e(excerpt($k['deskripsi'], 110)) . '</p>'
         . ($meta ? '<div class="kmeta">' . $meta . '</div>' : '') . '</div></a>';
}
