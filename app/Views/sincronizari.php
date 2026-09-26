<?php require_once('header.php'); ?>
<script src="/extern/chart.js"></script>

<div class="container-fluid px-3 pt-2">

  <!-- ================= ACOPERIRE API ================= -->
  <div class="d-flex align-items-center mb-2">
    <h6 class="mb-0 me-3"><i class="fa-solid fa-signal me-1"></i> Acoperire citiri API</h6>
    <div class="btn-group btn-group-sm" role="group">
      <a href="?days=7"  class="btn btn-outline-primary <?= $data['days']==7?'active':'' ?>">7 zile</a>
      <a href="?days=30" class="btn btn-outline-primary <?= $data['days']==30?'active':'' ?>">30 zile</a>
    </div>
    <span class="text-muted small ms-3">Universul = <?= (int)$data['universe'] ?> PODuri (consumatori reali: activi + consum recent)</span>
  </div>

  <div class="row">
    <div class="col-lg-7"><canvas id="covChart" height="120"></canvas></div>
    <div class="col-lg-5">
      <div class="d-flex align-items-center mb-1">
        <span class="small text-muted me-2">Necitite pe ziua:</span>
        <strong class="small"><?= esc($data['day'] ?? '—') ?></strong>
      </div>
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light"><tr><th>Distribuitor</th><th class="text-end">Necitite</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($data['missByDist'] as $m): ?>
          <tr>
            <td class="small"><?= esc($m['dist']) ?></td>
            <td class="text-end"><span class="badge bg-danger"><?= (int)$m['necitite'] ?></span></td>
            <td class="text-end"><a href="#" class="small" onclick="showMissing('<?= esc($data['day']) ?>','<?= (int)$m['distributor_id'] ?>');return false;">listă</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$data['missByDist']): ?><tr><td colspan="3" class="text-center text-muted small py-3">Toate PODurile au fost citite în ziua selectată.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <hr class="my-3">

  <!-- ================= JURNAL RULARI ================= -->
  <form method="get" class="d-flex align-items-center gap-2 mb-2">
    <h6 class="mb-0 me-2"><i class="fa-solid fa-list-check me-1"></i> Rulări joburi</h6>
    <select name="job" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
      <option value="">— toate —</option>
      <?php foreach ($data['jobs'] as $j): ?>
        <option value="<?= esc($j['job']) ?>" <?= $data['filterJob']===$j['job']?'selected':'' ?>><?= esc($j['job']) ?></option>
      <?php endforeach; ?>
    </select>
    <a href="<?= site_url('sincronizari') ?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="fa-solid fa-rotate"></i></a>
  </form>

  <table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr><th>Job</th><th>Pornit</th><th>Terminat</th><th class="text-end">Durată</th><th class="text-center">Status</th><th>Ce &amp; cât</th><th></th></tr></thead>
    <tbody>
      <?php if (!$data['runs']): ?><tr><td colspan="7" class="text-center text-muted py-4">Nicio rulare încă.</td></tr><?php endif; ?>
      <?php foreach ($data['runs'] as $r):
        $sec=(int)$r['durata_s']; $dur=$sec>=60?floor($sec/60).'m '.($sec%60).'s':$sec.'s';
        $b=$r['status']==='ok'?'success':($r['status']==='fail'?'danger':'secondary'); ?>
      <tr>
        <td class="fw-semibold"><?= esc($r['job']) ?></td>
        <td class="text-nowrap small"><?= esc($r['started_at']) ?></td>
        <td class="text-nowrap small text-muted"><?= esc($r['finished_at'] ?? '—') ?></td>
        <td class="text-end small"><?= $r['finished_at']?$dur:'<span class="text-warning">în curs…</span>' ?></td>
        <td class="text-center"><span class="badge bg-<?= $b ?>"><?= esc($r['status']) ?></span></td>
        <td class="small"><?= esc($r['summary'] ?? '') ?></td>
        <td class="text-end"><a href="#" class="small" onclick="showLog(<?= (int)$r['id'] ?>);return false;"><i class="fa-solid fa-file-lines"></i></a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="infoModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header py-2"><h6 class="modal-title" id="infoTitle"></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="infoBody"></div></div></div></div>

<script>
const COV = <?= json_encode($data['coverage']) ?>;
const UNI = <?= (int)$data['universe'] ?>;
(function(){
  const labels = COV.map(x=>x.d);
  const citite = COV.map(x=>+x.citite);
  const necit  = COV.map(x=>Math.max(0, UNI-(+x.citite)));
  new Chart(document.getElementById('covChart'), {
    type:'bar',
    data:{labels, datasets:[
      {label:'Citite prin API', data:citite, backgroundColor:'#198754'},
      {label:'Necitite',        data:necit,  backgroundColor:'#dc3545'}
    ]},
    options:{responsive:true, scales:{x:{stacked:true},y:{stacked:true, beginAtZero:true}}, plugins:{legend:{position:'bottom'}}}
  });
})();
function modal(title, html){ document.getElementById('infoTitle').textContent=title; document.getElementById('infoBody').innerHTML=html; new bootstrap.Modal(document.getElementById('infoModal')).show(); }
function showLog(id){ fetch('<?= site_url('sincronizari/detail') ?>?id='+id).then(r=>r.text()).then(t=>modal('Log rulare','<pre class="small mb-0" style="white-space:pre-wrap">'+t.replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]))+'</pre>')); }
function showMissing(day,dist){ fetch('<?= site_url('sincronizari/necitite') ?>?day='+day+'&dist='+dist).then(r=>r.text()).then(t=>modal('PODuri necitite',t)); }
</script>

<?php require_once('footer.php'); ?>
</body></html>
