<?php require_once('header.php'); ?>

<div class="container-fluid px-3 pt-2">

  <form method="get" class="d-flex align-items-center gap-2 mb-3">
    <label class="text-muted small">Job:</label>
    <select name="job" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
      <option value="">— toate —</option>
      <?php foreach ($data['jobs'] as $j): ?>
        <option value="<?= esc($j['job']) ?>" <?= $data['filterJob'] === $j['job'] ? 'selected' : '' ?>><?= esc($j['job']) ?></option>
      <?php endforeach; ?>
    </select>
    <span class="text-muted small ms-2"><?= count($data['runs']) ?> rulări (max 300)</span>
    <a href="<?= site_url('sincronizari') ?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="fa-solid fa-rotate"></i> Reîmprospătează</a>
  </form>

  <table class="table table-sm table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>Job</th>
        <th>Pornit</th>
        <th>Terminat</th>
        <th class="text-end">Durată</th>
        <th class="text-center">Status</th>
        <th>Ce &amp; cât a procesat</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$data['runs']): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Nicio rulare înregistrată încă.</td></tr>
      <?php endif; ?>
      <?php foreach ($data['runs'] as $r):
        $sec = (int)$r['durata_s'];
        $dur = $sec >= 60 ? floor($sec/60) . 'm ' . ($sec%60) . 's' : $sec . 's';
        $badge = $r['status'] === 'ok' ? 'success' : ($r['status'] === 'fail' ? 'danger' : 'secondary');
      ?>
      <tr>
        <td><span class="fw-semibold"><?= esc($r['job']) ?></span></td>
        <td class="text-nowrap small"><?= esc($r['started_at']) ?></td>
        <td class="text-nowrap small text-muted"><?= esc($r['finished_at'] ?? '—') ?></td>
        <td class="text-end small text-nowrap"><?= $r['finished_at'] ? $dur : '<span class="text-warning">în curs…</span>' ?></td>
        <td class="text-center"><span class="badge bg-<?= $badge ?>"><?= esc($r['status']) ?></span></td>
        <td class="small"><?= esc($r['summary'] ?? '') ?></td>
        <td class="text-end"><a href="#" class="small" onclick="showLog(<?= (int)$r['id'] ?>);return false;"><i class="fa-solid fa-file-lines"></i> log</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- modal log -->
<div class="modal fade" id="logModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title">Log rulare</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><pre id="logBody" class="small mb-0" style="white-space:pre-wrap"></pre></div>
    </div>
  </div>
</div>

<script>
function showLog(id){
  fetch('<?= site_url('sincronizari/detail') ?>?id='+id)
    .then(r => r.text())
    .then(t => {
      document.getElementById('logBody').textContent = t || '(fără detalii)';
      new bootstrap.Modal(document.getElementById('logModal')).show();
    });
}
</script>

<?php require_once('footer.php'); ?>

</body>
</html>
