<?php
$page_title = 'New Tello';
$this->load->view('new_task/header', array('page_title' => $page_title, 'count_inbox' => $count_inbox, 'count_inbox2' => $count_inbox2));

$active = array();
$closed = array();
foreach ($projects as $p) {
  if ($p->activity == '3') {
    $closed[] = $p;
  } else {
    $active[] = $p;
  }
}

function nt_project_tile($p)
{
  $total = (int) $p->total_card;
  $done = (int) $p->total_closed;
  $pct = $total ? round($done / $total * 100) : 0;
  $search = strtolower($p->name . ' ' . $p->pic_nama);
?>
  <a href="<?= base_url('New_Task/board/' . $p->id) ?>" class="nt-project<?= $p->activity == '3' ? ' is-closed' : '' ?>" data-search="<?= htmlspecialchars($search) ?>">
    <p class="nt-project-name"><?= htmlspecialchars($p->name) ?></p>
    <?php if ($p->pic == get_instance()->session->userdata('nip')) { ?>
      <span class="nt-project-edit" data-href="<?= base_url('New_Task/board/' . $p->id) ?>#edit" title="Edit project"><i class="fa fa-pencil"></i></span>
    <?php } ?>
    <div class="nt-project-meta">
      <i class="fa fa-user"></i> PIC: <?= htmlspecialchars($p->pic_nama ?: $p->pic) ?>
      &middot; <i class="fa fa-calendar"></i> <?= htmlspecialchars($p->date_created) ?>
    </div>
    <div class="nt-progress"><span style="width:<?= $pct ?>%"></span></div>
    <div class="nt-pills">
      <span class="nt-pill"><?= $done ?>/<?= $total ?> card selesai</span>
      <?php if ($p->total_open) { ?><span class="nt-pill open"><?= (int) $p->total_open ?> Open</span><?php } ?>
      <?php if ($p->total_pending) { ?><span class="nt-pill pending"><?= (int) $p->total_pending ?> Pending</span><?php } ?>
      <?php if ($p->total_overdue) { ?><span class="nt-pill late"><?= (int) $p->total_overdue ?> Overdue</span><?php } ?>
    </div>
  </a>
<?php
}
?>

<div class="nt-wrap">
  <div class="nt-topbar">
    <div>
      <h3 class="nt-title"><i class="fa fa-columns"></i> New Tello</h3>
      <div class="nt-subtitle">Pilih project untuk membuka papannya</div>
    </div>
    <div class="nt-tools">
      <input type="search" id="ntProjectSearch" class="nt-search" placeholder="Cari project atau PIC...">
      <?php if ($can_create) { ?>
        <a href="<?= base_url('task/create_task') ?>" class="nt-btn-light"><i class="fa fa-plus"></i> Project baru</a>
      <?php } ?>
    </div>
  </div>

  <?php if (!$projects) { ?>
    <div class="nt-empty-state">Belum ada project. Project tampil di sini jika Anda menjadi PIC atau member.</div>
  <?php } ?>

  <?php if ($active) { ?>
    <div class="nt-section-title">Project aktif (<?= count($active) ?>)</div>
    <div class="nt-project-grid">
      <?php foreach ($active as $p) nt_project_tile($p); ?>
    </div>
  <?php } ?>

  <?php if ($closed) { ?>
    <div class="nt-section-title">Baru ditutup, 7 hari terakhir (<?= count($closed) ?>)</div>
    <div class="nt-project-grid">
      <?php foreach ($closed as $p) nt_project_tile($p); ?>
    </div>
  <?php } ?>
</div>

<?php $this->load->view('new_task/footer'); ?>
<script>
  // pensil di kotak project: buka papan langsung dengan form edit
  $(document).on('click', '.nt-project-edit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    location.href = $(this).attr('data-href');
  });
  $('#ntProjectSearch').on('input', function() {
    var q = $.trim($(this).val()).toLowerCase();
    $('.nt-project').each(function() {
      $(this).toggle(!q || $(this).attr('data-search').indexOf(q) !== -1);
    });
  });
</script>
</body>

</html>
