      </div><!-- /.container-fluid -->
    </div><!-- /#content -->

    <footer class="sticky-footer bg-white">
      <div class="container my-auto"><div class="copyright text-center my-auto"><span>Hak cipta &copy; <?= e(SITE_NAME) ?> <?= date('Y') ?></span></div></div>
    </footer>
  </div><!-- /#content-wrapper -->
</div><!-- /#wrapper -->

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutLabel" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="logoutLabel">Siap untuk keluar?</h5>
      <button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
    </div>
    <div class="modal-body">Pilih "Keluar" di bawah jika Anda ingin mengakhiri sesi.</div>
    <div class="modal-footer">
      <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
      <form method="post" action="<?= BASE ?>/admin/logout.php" class="d-inline"><?= csrf_field() ?><button class="btn btn-primary">Keluar</button></form>
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery.easing@1.4.1/jquery.easing.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
<?php if ($dt): ?>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.13.6/js/dataTables.bootstrap4.min.js"></script>
<?php endif; ?>
<?= $pageScript ?? '' ?>
</body>
</html>
