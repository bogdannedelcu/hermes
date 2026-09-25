<?php

require_once(__DIR__.'/../header.php');
require_once(__DIR__.'/../dialogs.php');

?>

<!-- CONTENT -->

<?php require_once(__DIR__.'/estimat-vs-realizat-card.php'); ?>

<?php echo $data['output']; ?>

<!-- Algorithm Info Modal -->
<div class="modal fade" id="algoInfoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="algoInfoTitle">Descriere Algoritm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="algoInfoBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Inchide</button>
      </div>
    </div>
  </div>
</div>

<?php require_once(__DIR__.'/../footer.php') ?>

<script src="/telerik/js/jszip.min.js"></script>
</body>
</html>
