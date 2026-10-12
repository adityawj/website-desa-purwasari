(function () {
  'use strict';
  var d = document;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function $(s, c) { return (c || d).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); }

  d.addEventListener('DOMContentLoaded', function () {
    /* ---- menu mobile ---- */
    var burger = $('.burger'), nav = $('.nav');
    function closeNav() { if (nav) { nav.classList.remove('open'); if (burger) burger.setAttribute('aria-expanded', 'false'); } }
    if (burger && nav) {
      burger.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = nav.classList.toggle('open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      $$('a', nav).forEach(function (a) { a.addEventListener('click', closeNav); });
      d.addEventListener('click', function (e) { if (!nav.contains(e.target)) closeNav(); });
      d.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeNav(); });
    }

    /* ---- header, progress bar, tombol ke atas ---- */
    var hdr = $('.hdr'), bar = $('#progress'), totop = $('.totop'), ticking = false;
    function onScroll() {
      var y = window.pageYOffset || d.documentElement.scrollTop;
      var h = d.documentElement.scrollHeight - window.innerHeight;
      if (hdr) hdr.classList.toggle('scrolled', y > 8);
      if (bar) bar.style.transform = 'scaleX(' + (h > 0 ? Math.min(1, y / h) : 0) + ')';
      if (totop) totop.classList.toggle('show', y > 500);
      ticking = false;
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    window.addEventListener('resize', onScroll);
    onScroll();
    if (totop) totop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });

    /* ---- animasi muncul saat digulir ---- */
    if (!reduce && 'IntersectionObserver' in window) {
      var sel = '.sec-head,.card,.ann-card,.feat,.stat,.panel,.about,.side,.search,.chips,.facts li,.pager';
      var groups = new Map();
      var els = $$(sel).filter(function (el) { return el.getBoundingClientRect().top > window.innerHeight * 0.92; });
      els.forEach(function (el) {
        var i = groups.get(el.parentNode) || 0; groups.set(el.parentNode, i + 1);
        el.style.transitionDelay = (Math.min(i, 5) * 70) + 'ms';
        el.classList.add('reveal');
      });
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) return;
          var el = en.target; io.unobserve(el); el.classList.add('in');
          var wait = 1000 + (parseInt(el.style.transitionDelay, 10) || 0);
          setTimeout(function () { el.classList.remove('reveal', 'in'); el.style.transitionDelay = ''; }, wait);
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
      els.forEach(function (el) { io.observe(el); });
    }

    /* ---- hitung naik pada angka ringkasan ---- */
    function fmt(n, rp) {
      var s = Math.abs(Math.round(n)).toLocaleString('id-ID');
      return (rp ? 'Rp ' : '') + (n < 0 ? '\u2212' : '') + s;
    }
    if (!reduce && 'IntersectionObserver' in window) {
      $$('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target) || target === 0) return;
        var rp = el.hasAttribute('data-rp'), started = false;
        var io2 = new IntersectionObserver(function (en) {
          if (!en[0].isIntersecting || started) return;
          started = true; io2.disconnect();
          var t0 = null, dur = 1200;
          (function step(ts) {
            if (t0 === null) t0 = ts;
            var p = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt(target * e, rp);
            if (p < 1) requestAnimationFrame(step); else el.textContent = fmt(target, rp);
          })(performance.now());
        }, { threshold: 0.4 });
        io2.observe(el);
      });
    }

    /* ---- gambar muncul halus saat selesai dimuat ---- */
    $$('img[loading="lazy"]').forEach(function (img) {
      if (img.complete && img.naturalWidth) return;
      img.classList.add('fade');
      function ok() { img.classList.add('loaded'); }
      img.addEventListener('load', ok); img.addEventListener('error', ok);
    });

    /* ---- filter kegiatan ---- */
    var chips = $$('.chip');
    if (chips.length) {
      var items = $$('[data-status]'), empty = $('#chip-empty');
      chips.forEach(function (c) {
        c.addEventListener('click', function () {
          chips.forEach(function (x) { x.classList.toggle('on', x === c); x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
          var f = c.getAttribute('data-filter'), n = 0;
          items.forEach(function (it) { var show = f === 'all' || it.getAttribute('data-status') === f; it.hidden = !show; if (show) n++; });
          if (empty) empty.hidden = n > 0;
        });
      });
    }

    /* ---- salin tautan ---- */
    $$('[data-copy]').forEach(function (el) {
      el.addEventListener('click', function () {
        if (navigator.clipboard) navigator.clipboard.writeText(location.href).then(function () { el.textContent = 'Tautan disalin'; });
      });
    });
  });
})();
