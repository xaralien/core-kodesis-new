<?php
$this->load->view('new_task/header', array('page_title' => $task->name, 'count_inbox' => $count_inbox, 'count_inbox2' => $count_inbox2));
$status_label = array('1' => 'Open', '2' => 'Pending', '3' => 'Closed');
?>

<div class="nt-wrap">
  <div class="nt-topbar">
    <div>
      <a href="<?= base_url('New_Task') ?>" class="nt-back"><i class="fa fa-arrow-left"></i> Semua project</a>
      <h3 class="nt-title"><?= htmlspecialchars($task->name) ?></h3>
      <div class="nt-subtitle">
        PIC: <?= htmlspecialchars(isset($members[$task->pic]) ? $members[$task->pic] : $task->pic) ?>
        &middot; Status project: <?= $status_label[$task->activity] ?? $task->activity ?>
        &middot; <?= count($members) ?> anggota
      </div>
    </div>
    <div class="nt-tools">
      <input type="search" id="ntSearch" class="nt-search" placeholder="Cari card atau responsible...">
      <?php if ($is_pic) { ?>
        <button type="button" class="nt-btn-light" id="ntEditProject"><i class="fa fa-pencil"></i> Edit project</button>
      <?php } ?>
      <a href="<?= base_url('task/task_view/' . $task->id) ?>" class="nt-btn-light" title="Buka di tampilan Tello lama"><i class="fa fa-list"></i> Tampilan lama</a>
    </div>
  </div>

  <div class="nt-columns" id="ntColumns"></div>
</div>

<?php if ($is_pic) {
  $current_members = array_filter(array_map('trim', explode(';', (string) $task->member)));
?>
  <!-- Modal edit project (khusus PIC) -->
  <div class="modal fade" id="ntProjModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content nt-proj">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Edit project</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="pjName">Nama project</label>
            <input type="text" id="pjName" class="form-control" maxlength="255" value="<?= htmlspecialchars($task->name) ?>">
          </div>
          <div class="form-group">
            <label for="pjStatus">Status project</label>
            <select id="pjStatus" class="form-control">
              <option value="1" <?= $task->activity == '1' ? 'selected' : '' ?>>Open</option>
              <option value="2" <?= $task->activity == '2' ? 'selected' : '' ?>>Pending</option>
              <option value="3" <?= $task->activity == '3' ? 'selected' : '' ?>>Closed</option>
            </select>
          </div>
          <div class="form-group">
            <label>Member <span class="text-muted" id="pjCount"></span></label>
            <input type="search" id="pjSearch" class="form-control input-sm" placeholder="Cari nama atau jabatan...">
            <div class="nt-proj-list">
              <?php
              // member terpilih ditampilkan paling atas
              uksort($candidates, function ($a, $b) use ($current_members) {
                return (int) in_array($b, $current_members) - (int) in_array($a, $current_members);
              });
              foreach ($candidates as $nip => $c) { ?>
                <label class="nt-proj-opt" data-search="<?= htmlspecialchars(strtolower($c['nama'] . ' ' . $c['jabatan'])) ?>">
                  <input type="checkbox" class="pjMember" value="<?= htmlspecialchars($nip) ?>" <?= in_array($nip, $current_members) ? 'checked' : '' ?>>
                  <?= htmlspecialchars($c['nama']) ?>
                  <?php if ($c['jabatan'] !== '') { ?><span class="text-muted">(<?= htmlspecialchars($c['jabatan']) ?>)</span><?php } ?>
                </label>
              <?php } ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-nt" id="pjSave">Simpan</button>
        </div>
      </div>
    </div>
  </div>
<?php } ?>

<!-- Popup detail card (gaya Trello) -->
<div class="modal fade nt-cd" id="ntModal" tabindex="-1" role="dialog">
  <div class="modal-dialog nt-cd-dialog" role="document">
    <div class="modal-content">
      <div class="nt-cd-head">
        <select id="cdStatus" class="nt-cd-status" title="Status card">
          <option value="1">Open</option>
          <option value="2">Pending</option>
          <option value="3">Closed</option>
        </select>
        <div class="nt-cd-head-tools">
          <a href="#" id="cdOldLink" class="nt-cd-icon" title="Buka di tampilan Tello lama" target="_blank"><i class="fa fa-external-link"></i></a>
          <?php if ($is_pic) { ?>
            <button type="button" id="cdDelete" class="nt-cd-icon" title="Hapus card"><i class="fa fa-trash-o"></i></button>
          <?php } ?>
          <button type="button" class="nt-cd-icon" data-dismiss="modal" title="Tutup"><i class="fa fa-times"></i></button>
        </div>
      </div>

      <div class="nt-cd-body">
        <!-- kiri: isi card -->
        <div class="nt-cd-main">
          <div class="nt-cd-title-row">
            <button type="button" id="cdDone" class="nt-cd-check" title="Tandai selesai"><i class="fa fa-check"></i></button>
            <h2 id="cdTitle" class="nt-cd-title"></h2>
            <input type="text" id="cdTitleInput" class="nt-cd-title-input" maxlength="255" style="display:none">
          </div>

          <div class="nt-cd-actions" id="cdActions">
            <button type="button" class="nt-cd-btn" data-pop="cdPopDates"><i class="fa fa-clock-o"></i> Tanggal</button>
            <button type="button" class="nt-cd-btn" data-pop="cdPopMembers"><i class="fa fa-user-plus"></i> Anggota</button>
            <button type="button" class="nt-cd-btn" id="cdAttachBtn" title="Lampirkan file ke card ini (maks. 5 MB)"><i class="fa fa-paperclip"></i> Lampiran</button>
            <input type="file" id="cdCardFile" multiple style="display:none">
          </div>

          <!-- popover tanggal -->
          <div class="nt-cd-pop" id="cdPopDates" style="display:none">
            <div class="nt-cd-pop-title">Tanggal</div>
            <label>Mulai</label>
            <input type="date" id="cdStart" class="form-control input-sm">
            <label>Tenggat</label>
            <input type="date" id="cdDue" class="form-control input-sm">
            <div class="nt-cd-pop-foot">
              <button type="button" class="btn btn-nt btn-sm" id="cdDatesSave">Simpan</button>
              <button type="button" class="btn btn-default btn-sm" id="cdDatesClear">Hapus tenggat</button>
            </div>
          </div>

          <!-- popover anggota -->
          <div class="nt-cd-pop" id="cdPopMembers" style="display:none">
            <div class="nt-cd-pop-title">Anggota card</div>
            <label>Responsible</label>
            <select id="cdResponsible" class="form-control input-sm">
              <?php foreach ($members as $nip => $nama) { ?>
                <option value="<?= htmlspecialchars($nip) ?>"><?= htmlspecialchars($nama) ?></option>
              <?php } ?>
            </select>
            <label>Member</label>
            <div class="nt-cd-member-list">
              <?php foreach ($members as $nip => $nama) { ?>
                <label class="nt-cd-member-opt"><input type="checkbox" class="cdMemberChk" value="<?= htmlspecialchars($nip) ?>"> <?= htmlspecialchars($nama) ?></label>
              <?php } ?>
            </div>
            <div class="nt-cd-pop-foot">
              <button type="button" class="btn btn-nt btn-sm" id="cdMembersSave">Simpan</button>
            </div>
          </div>

          <div class="nt-cd-meta" id="cdMeta"></div>

          <div class="nt-cd-section">
            <div class="nt-cd-section-head"><i class="fa fa-align-left"></i> Deskripsi</div>
            <div id="cdDescView" class="nt-cd-desc"></div>
            <div id="cdDescEdit" style="display:none">
              <textarea id="cdDescInput" class="form-control" rows="5" placeholder="Tambahkan deskripsi yang lebih detail..."></textarea>
              <div class="nt-cd-pop-foot">
                <button type="button" class="btn btn-nt btn-sm" id="cdDescSave">Simpan</button>
                <button type="button" class="btn btn-default btn-sm" id="cdDescCancel">Batal</button>
              </div>
            </div>
          </div>

          <div class="nt-cd-section" id="cdAttachSection" style="display:none">
            <div class="nt-cd-section-head"><i class="fa fa-paperclip"></i> Lampiran card</div>
            <div id="cdAttachList"></div>
            <div id="cdAttachBusy" class="nt-cd-note" style="display:none"><i class="fa fa-spinner fa-spin"></i> Mengunggah lampiran...</div>
          </div>
        </div>

        <!-- kanan: komentar & aktivitas -->
        <div class="nt-cd-side">
          <div class="nt-cd-section-head"><i class="fa fa-comments-o"></i> Komentar dan aktivitas</div>
          <form id="cdCommentForm" class="nt-cd-comment-form" enctype="multipart/form-data">
            <textarea name="comment" id="cdCommentInput" rows="1" placeholder="Tulis komentar..."></textarea>
            <div class="nt-cd-comment-tools" style="display:none">
              <label class="nt-cd-file" title="File ini ikut komentar (maks. 5 MB)">
                <i class="fa fa-paperclip"></i> <span id="cdFileLabel">Lampirkan ke komentar</span>
                <input type="file" name="attach[]" id="cdFile" multiple>
              </label>
              <button type="submit" class="btn btn-nt btn-sm" id="cdCommentSave">Simpan</button>
            </div>
          </form>
          <div id="cdCommentNote" class="nt-cd-note" style="display:none"></div>
          <div id="cdComments" class="nt-cd-comments"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Lightbox gambar -->
<div class="nt-lb" id="ntLightbox" style="display:none">
  <div class="nt-lb-bar">
    <span class="nt-lb-name" id="lbName"></span>
    <span class="nt-lb-tools">
      <span id="lbCount"></span>
      <a href="#" id="lbOpen" target="_blank" title="Buka di tab baru"><i class="fa fa-external-link"></i></a>
      <a href="#" id="lbDownload" download title="Unduh"><i class="fa fa-download"></i></a>
      <button type="button" id="lbClose" title="Tutup (Esc)"><i class="fa fa-times"></i></button>
    </span>
  </div>
  <button type="button" class="nt-lb-nav prev" id="lbPrev" title="Sebelumnya"><i class="fa fa-chevron-left"></i></button>
  <img id="lbImg" alt="">
  <button type="button" class="nt-lb-nav next" id="lbNext" title="Berikutnya"><i class="fa fa-chevron-right"></i></button>
</div>

<div class="nt-toast" id="ntToast"></div>

<?php $this->load->view('new_task/footer'); ?>
<!-- SortableJS: drag & drop untuk mouse dan layar sentuh -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
  (function() {
    var BASE = <?= json_encode(base_url('New_Task/')) ?>;
    var ID_TASK = <?= (int) $task->id ?>;
    var CAN_ADD = <?= $can_add ? 'true' : 'false' ?>;
    var COLUMNS = [
      {activity: '1', title: 'Open / In Progress', color: 'var(--nt-open)'},
      {activity: '2', title: 'Pending', color: 'var(--nt-pending)'},
      {activity: '3', title: 'Closed / Done', color: 'var(--nt-closed)'}
    ];
    var cards = <?= json_encode($cards) ?>;
    var query = '';

    function esc(s) {
      return $('<div>').text(s == null ? '' : s).html();
    }

    function today() {
      var d = new Date();
      return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    }

    function initials(name) {
      return (name || '?').split(/\s+/).slice(0, 2).map(function(w) {
        return w.charAt(0).toUpperCase();
      }).join('');
    }

    function toast(msg) {
      $('#ntToast').text(msg).stop(true, true).fadeIn(150).delay(2500).fadeOut(300);
    }

    function findCard(id) {
      id = parseInt(id, 10);
      for (var i = 0; i < cards.length; i++) {
        if (cards[i].id === id) return cards[i];
      }
      return null;
    }

    function replaceCard(card) {
      for (var i = 0; i < cards.length; i++) {
        if (cards[i].id === card.id) {
          cards[i] = card;
          return;
        }
      }
      cards.push(card);
    }

    function post(action, data) {
      return $.ajax({
        url: BASE + action,
        type: 'POST',
        data: data,
        dataType: 'json'
      }).fail(function(xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan, coba lagi.';
        toast(msg);
      });
    }

    // ---------- render ----------
    function render() {
      var $wrap = $('#ntColumns').empty();
      COLUMNS.forEach(function(col) {
        var colCards = cards.filter(function(c) {
          return c.activity === col.activity;
        });
        var visible = colCards.filter(function(c) {
          if (!query) return true;
          return (c.title + ' ' + c.description + ' ' + c.responsible_nama).toLowerCase().indexOf(query) !== -1;
        });

        var $col = $('<div class="nt-col">');
        $col.append('<div class="nt-col-head"><span><span class="nt-dot" style="background:' + col.color + '"></span>' +
          esc(col.title) + '</span><span class="nt-count">' + colCards.length + '</span></div>');

        var $list = $('<div class="nt-list">').attr('data-activity', col.activity);
        visible.forEach(function(c) {
          $list.append(cardEl(c));
        });
        $col.append($list);
        if (!visible.length) {
          $col.append('<div class="nt-empty">' + (query ? 'Tidak ada hasil' : 'Belum ada card') + '</div>');
        }

        if (CAN_ADD) {
          $col.append(
            '<button type="button" class="nt-add-btn"><i class="fa fa-plus"></i> Tambah card</button>' +
            '<div class="nt-add-form" style="display:none" data-activity="' + col.activity + '">' +
            '<textarea rows="2" placeholder="Masukkan nama card..."></textarea>' +
            '<div style="margin-top:6px"><button type="button" class="btn btn-nt btn-sm nt-add-save">Tambah</button> ' +
            '<button type="button" class="btn btn-default btn-sm nt-add-cancel">Batal</button></div></div>'
          );
        }
        $wrap.append($col);
      });
      initSortable();
    }

    function cardEl(c) {
      var $c = $('<div class="nt-card">').attr('data-id', c.id);
      if (c.can_move) $c.addClass('can-move');
      if (c.unread) $c.addClass('is-unread');

      var html = '';
      if (c.cover) {
        html += '<div class="nt-card-cover"><img src="' + esc(c.cover) + '" alt="" loading="lazy" onerror="this.parentNode.parentNode.removeChild(this.parentNode)"></div>';
      }
      var done = c.activity === '3';
      html += '<div class="nt-card-row">';
      if (c.can_move) {
        html += '<button type="button" class="nt-card-check' + (done ? ' is-done' : '') + '" title="' +
          (done ? 'Tandai belum selesai' : 'Tandai selesai') + '"><i class="fa fa-check"></i></button>';
      } else if (done) {
        html += '<span class="nt-card-check is-done is-static" title="Selesai"><i class="fa fa-check"></i></span>';
      }
      html += '<p class="nt-card-title">' + esc(c.title) + '</p></div>';
      if (c.can_edit) {
        html += '<button type="button" class="nt-card-edit" title="Edit judul"><i class="fa fa-pencil"></i></button>';
      }
      if (c.description) html += '<p class="nt-card-desc">' + esc(c.description) + '</p>';
      html += '<div class="nt-card-foot">';
      if (c.due_date) {
        var cls = c.activity === '3' ? 'done' : (c.due_date <= today() ? 'late' : '');
        html += '<span class="nt-badge-due ' + cls + '"><i class="fa fa-clock-o"></i> ' + esc(c.due_date) + '</span>';
      }
      if (c.member_nips.length) html += '<span title="Member card"><i class="fa fa-users"></i> ' + c.member_nips.length + '</span>';
      if (c.comments) html += '<span title="Komentar"><i class="fa fa-comment-o"></i> ' + c.comments + '</span>';
      if (c.attachments) html += '<span title="Lampiran"><i class="fa fa-paperclip"></i> ' + c.attachments + '</span>';
      html += '<span class="nt-avatar" title="' + esc(c.responsible_nama) + '">' + esc(initials(c.responsible_nama)) + '</span>';
      html += '</div>';
      return $c.html(html);
    }

    // ---------- drag & drop ----------
    function initSortable() {
      if (typeof Sortable === 'undefined') return;
      $('.nt-list').each(function() {
        Sortable.create(this, {
          group: 'nt',
          sort: false, // urutan dalam kolom mengikuti tenggat, tidak disimpan
          draggable: '.nt-card.can-move',
          animation: 150,
          delay: 150,
          delayOnTouchOnly: true,
          ghostClass: 'sortable-ghost',
          chosenClass: 'sortable-chosen',
          filter: '.nt-card-check, .nt-card-edit, .nt-quick',
          preventOnFilter: false,
          onEnd: function(evt) {
            if (evt.from === evt.to) return;
            var card = findCard($(evt.item).attr('data-id'));
            var target = $(evt.to).attr('data-activity');
            if (!card || card.activity === target) return;
            $(evt.item).addClass('is-saving');
            post('move_card', {id_detail: card.id, activity: target})
              .done(function(res) {
                replaceCard(res.card);
              })
              .always(render);
          }
        });
      });
    }

    // ---------- tambah card ----------
    $(document).on('click', '.nt-add-btn', function() {
      $(this).hide().next('.nt-add-form').show().find('textarea').focus();
    });
    $(document).on('click', '.nt-add-cancel', function() {
      var $f = $(this).closest('.nt-add-form');
      $f.hide().find('textarea').val('');
      $f.prev('.nt-add-btn').show();
    });

    function addCard($form) {
      var title = $.trim($form.find('textarea').val());
      var activity = $form.attr('data-activity');
      if (!title) return;
      $form.find('button, textarea').prop('disabled', true);
      post('add_card', {id_task: ID_TASK, task_name: title, activity: activity})
        .done(function(res) {
          cards.push(res.card);
          render();
          $('.nt-add-form[data-activity="' + activity + '"]').prev('.nt-add-btn').click();
        })
        .fail(function() {
          $form.find('button, textarea').prop('disabled', false);
        });
    }
    $(document).on('click', '.nt-add-save', function() {
      addCard($(this).closest('.nt-add-form'));
    });
    $(document).on('keydown', '.nt-add-form textarea', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        addCard($(this).closest('.nt-add-form'));
      } else if (e.key === 'Escape') {
        $(this).closest('.nt-add-form').find('.nt-add-cancel').click();
      }
    });

    // ---------- tombol di kartu: tandai selesai & edit cepat ----------
    $(document).on('click', '.nt-card-check', function(e) {
      e.stopPropagation();
      if ($(this).hasClass('is-static')) return;
      var c = findCard($(this).closest('.nt-card').attr('data-id'));
      if (!c) return;
      var target = c.activity === '3' ? '1' : '3';
      $(this).closest('.nt-card').addClass('is-saving');
      post('move_card', {id_detail: c.id, activity: target})
        .done(function(res) {
          replaceCard(res.card);
          toast(target === '3' ? 'Card ditandai selesai' : 'Card dibuka kembali');
        })
        .always(render);
    });

    $(document).on('click', '.nt-card-edit', function(e) {
      e.stopPropagation();
      var $card = $(this).closest('.nt-card');
      var c = findCard($card.attr('data-id'));
      if (!c || $card.find('.nt-quick').length) return;
      $card.addClass('is-quick');
      $card.find('.nt-card-row').hide().after(
        '<div class="nt-quick"><textarea rows="2" maxlength="255"></textarea>' +
        '<div class="nt-quick-foot"><button type="button" class="btn btn-nt btn-sm nt-quick-save">Simpan</button> ' +
        '<button type="button" class="btn btn-default btn-sm nt-quick-cancel">Batal</button></div></div>'
      );
      $card.find('.nt-quick textarea').val(c.title).focus().select();
    });

    function quickSave($card) {
      var c = findCard($card.attr('data-id'));
      var v = $.trim($card.find('.nt-quick textarea').val());
      if (!c || !v || v === c.title) return render();
      $card.addClass('is-saving');
      post('update_card', {id_detail: c.id, task_name: v})
        .done(function(res) {
          replaceCard(res.card);
        })
        .always(render);
    }
    $(document).on('click', '.nt-quick', function(e) {
      e.stopPropagation(); // jangan buka popup saat sedang edit cepat
    });
    $(document).on('click', '.nt-quick-save', function() {
      quickSave($(this).closest('.nt-card'));
    });
    $(document).on('click', '.nt-quick-cancel', function() {
      render();
    });
    $(document).on('keydown', '.nt-quick textarea', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        quickSave($(this).closest('.nt-card'));
      } else if (e.key === 'Escape') {
        render();
      }
    });

    // ---------- popup detail card ----------
    var current = null; // card yang sedang dibuka

    function fmtDate(d) {
      if (!d) return '';
      var m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      var p = String(d).slice(0, 10).split('-');
      return p.length === 3 ? parseInt(p[2], 10) + ' ' + m[parseInt(p[1], 10) - 1] + ' ' + p[0] : d;
    }

    function fmtTime(d) {
      if (!d) return '';
      var t = String(d).slice(11, 16);
      return fmtDate(d) + (t ? ', ' + t : '');
    }

    function avatar(name, cls) {
      return '<span class="nt-avatar ' + (cls || '') + '" title="' + esc(name) + '">' + esc(initials(name)) + '</span>';
    }

    function fillCard(c) {
      current = c;
      var editable = c.can_edit;
      $('#ntModal').toggleClass('is-readonly', !editable);
      $('#cdTitle').text(c.title).toggleClass('is-done', c.activity === '3');
      $('#cdTitleInput').hide();
      $('#cdTitle').show();
      $('#cdDone').toggleClass('is-done', c.activity === '3').prop('disabled', !c.can_move);
      $('#cdStatus').val(c.activity).prop('disabled', !c.can_move).attr('data-activity', c.activity);
      $('#cdActions').toggle(editable);
      $('#cdOldLink').attr('href', c.detail_url);

      // meta: responsible, member, tanggal
      var names = <?= json_encode($members) ?>;
      var meta = '<div class="nt-cd-meta-item"><div class="nt-cd-meta-label">Responsible</div>' +
        '<div class="nt-cd-meta-val">' + avatar(c.responsible_nama) + ' ' + esc(c.responsible_nama || c.responsible) + '</div></div>';
      if (c.member_nips.length) {
        meta += '<div class="nt-cd-meta-item"><div class="nt-cd-meta-label">Member</div><div class="nt-cd-meta-val">' +
          c.member_nips.map(function(n) {
            return avatar(names[n] || n);
          }).join('') + '</div></div>';
      }
      if (c.start_date || c.due_date) {
        var cls = c.activity === '3' ? 'done' : (c.due_date && c.due_date <= today() ? 'late' : '');
        meta += '<div class="nt-cd-meta-item"><div class="nt-cd-meta-label">Tanggal</div><div class="nt-cd-meta-val">' +
          '<span class="nt-cd-date ' + cls + '">' + (c.start_date ? esc(fmtDate(c.start_date)) + ' - ' : '') +
          (c.due_date ? esc(fmtDate(c.due_date)) : 'tanpa tenggat') +
          (cls === 'late' ? ' <b>Terlambat</b>' : (cls === 'done' ? ' <b>Selesai</b>' : '')) + '</span></div></div>';
      }
      $('#cdMeta').html(meta);

      // deskripsi
      $('#cdDescEdit').hide();
      $('#cdDescView').show().toggleClass('is-empty', !c.description)
        .text(c.description || (editable ? 'Tambahkan deskripsi yang lebih detail...' : 'Tidak ada deskripsi.'));

      // popover
      $('.nt-cd-pop').hide();
      $('#cdStart').val(c.start_date || '');
      $('#cdDue').val(c.due_date || '');
      var $resp = $('#cdResponsible');
      if (!$resp.find('option[value="' + c.responsible + '"]').length) {
        $resp.append($('<option>').val(c.responsible).text(c.responsible_nama || c.responsible));
      }
      $resp.val(c.responsible);
      $('.cdMemberChk').each(function() {
        this.checked = c.member_nips.indexOf(this.value) !== -1;
      });
    }

    // gambar tampil sebagai thumbnail (klik untuk diperbesar), file lain sebagai link
    function renderFiles(list, removable) {
      var thumbs = '', files = '';
      list.forEach(function(f) {
        var rm = removable ? '<button type="button" class="nt-file-remove" data-file="' + esc(f.file) + '" title="Lepas dari card">&times;</button>' : '';
        if (f.is_image) {
          thumbs += '<a class="nt-thumb" href="' + esc(f.url) + '" data-name="' + esc(f.name) + '" title="' + esc(f.name) + '">' +
            '<img src="' + esc(f.url) + '" alt="' + esc(f.name) + '" loading="lazy" onerror="this.parentNode.className+=\' is-broken\'">' +
            '<span class="nt-thumb-name">' + esc(f.name) + '</span>' + rm + '</a>';
        } else {
          files += '<div class="nt-file-row"><a class="nt-file" href="' + esc(f.url) + '" target="_blank" download><i class="fa fa-file-o"></i> ' + esc(f.name) + '</a>' + rm + '</div>';
        }
      });
      return (thumbs ? '<div class="nt-thumbs">' + thumbs + '</div>' : '') + files;
    }

    function renderComments(list, d) {
      var html = '';
      list.forEach(function(cm) {
        html += '<div class="nt-cd-cm">' + avatar(cm.nama) + '<div class="nt-cd-cm-body">' +
          '<div><b>' + esc(cm.nama) + '</b> <span class="nt-cd-time">' + esc(fmtTime(cm.date_created)) + '</span></div>' +
          '<div class="nt-cd-cm-text">' + esc(cm.text) + '</div>';
        if (cm.attachments.length) {
          html += '<div class="nt-cd-files">' + renderFiles(cm.attachments) + '</div>';
        }
        html += '</div></div>';
      });
      // aktivitas pembuatan card
      html += '<div class="nt-cd-cm is-activity">' + avatar(d.creator) + '<div class="nt-cd-cm-body">' +
        '<div><b>' + esc(d.creator) + '</b> menambahkan card ini ke project</div>' +
        '<span class="nt-cd-time">' + esc(fmtTime(d.card.date_created)) + '</span></div></div>';
      $('#cdComments').html(html);
    }

    function openCard(id) {
      var c = findCard(id);
      if (!c) return;
      fillCard(c);
      $('#cdComments').html('<div class="nt-cd-note">Memuat komentar...</div>');
      $('#cdAttachSection, #cdAttachBusy').hide();
      resetCommentForm();
      $('#cdCommentForm, #cdCommentNote').hide();
      $('#ntModal').modal('show');

      $.getJSON(BASE + 'card_detail', {id_detail: c.id}).done(function(d) {
        if (!current || current.id !== d.card.id) return;
        d.card.unread = false;
        replaceCard(d.card);
        fillCard(d.card);
        render();

        $('#cdAttachSection').toggle(d.attachments.length > 0);
        $('#cdAttachList').html(renderFiles(d.attachments, d.card.can_edit));

        $('#cdCommentForm').toggle(d.can_comment);
        if (!d.can_view_comments) {
          $('#cdCommentNote').show().text('Anda tidak memiliki akses untuk melihat komentar card ini.');
        } else if (!d.can_comment) {
          $('#cdCommentNote').show().text('Komentar hanya bisa ditambahkan saat card berstatus Open.');
        }
        renderComments(d.comments, d);
      }).fail(function(xhr) {
        $('#cdComments').html('<div class="nt-cd-note">' + esc((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat detail card.') + '</div>');
      });
    }

    // simpan sebagian field card
    function saveCard(fields) {
      if (!current) return $.Deferred().reject().promise();
      fields.id_detail = current.id;
      return post('update_card', fields).done(function(res) {
        replaceCard(res.card);
        fillCard(res.card);
        render();
      });
    }

    $(document).on('click', '.nt-card', function() {
      openCard($(this).attr('data-id'));
    });

    // judul: klik untuk edit
    $('#cdTitle').on('click', function() {
      if (!current || !current.can_edit) return;
      $(this).hide();
      $('#cdTitleInput').val(current.title).show().focus().select();
    });
    $('#cdTitleInput').on('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        $(this).blur();
      } else if (e.key === 'Escape') {
        $(this).val(current.title).blur();
      }
    }).on('blur', function() {
      var v = $.trim($(this).val());
      if (current && v && v !== current.title) {
        saveCard({task_name: v});
      } else if (current) {
        fillCard(current);
      }
    });

    // status & tombol selesai
    $('#cdStatus').on('change', function() {
      var v = $(this).val();
      saveCard({activity: v}).fail(function() {
        $('#cdStatus').val(current.activity);
      }).done(function() {
        openCard(current.id); // muat ulang: izin komentar bergantung pada status
      });
    });
    $('#cdDone').on('click', function() {
      if (!current || !current.can_move) return;
      $('#cdStatus').val(current.activity === '3' ? '1' : '3').trigger('change');
    });

    // popover tanggal & anggota
    $(document).on('click', '.nt-cd-btn[data-pop]', function() {
      var id = $(this).attr('data-pop');
      var visible = $('#' + id).is(':visible');
      $('.nt-cd-pop').hide();
      if (!visible) $('#' + id).show();
    });
    $('#cdDatesSave').on('click', function() {
      saveCard({start_date: $('#cdStart').val(), due_date: $('#cdDue').val()});
    });
    $('#cdDatesClear').on('click', function() {
      saveCard({due_date: ''});
    });
    $('#cdMembersSave').on('click', function() {
      var picked = $('.cdMemberChk:checked').map(function() {
        return this.value;
      }).get();
      saveCard({responsible: $('#cdResponsible').val(), members_set: 1, members: picked});
    });

    // deskripsi
    $('#cdDescView').on('click', function() {
      if (!current || !current.can_edit) return;
      $(this).hide();
      $('#cdDescEdit').show();
      $('#cdDescInput').val(current.description).focus();
    });
    $('#cdDescCancel').on('click', function() {
      fillCard(current);
    });
    $('#cdDescSave').on('click', function() {
      saveCard({description: $('#cdDescInput').val()});
    });

    // komentar
    function resetCommentForm() {
      $('#cdCommentInput').val('').attr('rows', 1);
      $('#cdFile').val('');
      $('#cdFileLabel').text('Lampirkan ke komentar');
      $('.nt-cd-comment-tools').hide();
    }
    $('#cdCommentInput').on('focus', function() {
      $(this).attr('rows', 3);
      $('.nt-cd-comment-tools').show();
    });
    $('#cdFile').on('change', function() {
      var n = this.files.length;
      $('#cdFileLabel').text(n ? n + ' file dipilih' : 'Lampirkan ke komentar');
    });
    // lampiran card (kiri): langsung tersimpan ke card, terpisah dari lampiran komentar
    $('#cdAttachBtn').on('click', function() {
      $('#cdCardFile').val('').click();
    });
    $('#cdCardFile').on('change', function() {
      if (!current || !this.files.length) return;
      var fd = new FormData();
      for (var i = 0; i < this.files.length; i++) fd.append('attach[]', this.files[i]);
      fd.append('id_detail', current.id);
      var id = current.id;
      $('#cdAttachSection, #cdAttachBusy').show();
      $('#cdAttachBtn').prop('disabled', true);
      $.ajax({
          url: BASE + 'add_attachment',
          type: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          dataType: 'json'
        })
        .done(function(res) {
          replaceCard(res.card);
          render();
          toast('Lampiran ditambahkan ke card');
          if (current && current.id === id) openCard(id);
        })
        .fail(function(xhr) {
          toast((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal mengunggah lampiran.');
          $('#cdAttachBusy').hide();
        })
        .always(function() {
          $('#cdAttachBtn').prop('disabled', false);
        });
    });
    $(document).on('click', '.nt-file-remove', function(e) {
      e.preventDefault();
      e.stopPropagation(); // jangan buka lightbox
      if (!current || !confirm('Lepas lampiran ini dari card?')) return;
      var id = current.id;
      post('remove_attachment', {id_detail: id, file: $(this).attr('data-file')}).done(function(res) {
        replaceCard(res.card);
        render();
        if (current && current.id === id) openCard(id);
      });
    });
    $('#cdCommentForm').on('submit', function(e) {
      e.preventDefault();
      if (!current || !$.trim($('#cdCommentInput').val())) {
        $('#cdCommentInput').focus();
        return;
      }
      var fd = new FormData(this);
      fd.append('id_detail', current.id);
      var $btn = $('#cdCommentSave').prop('disabled', true).text('Menyimpan...');
      $.ajax({
          url: BASE + 'add_comment',
          type: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          dataType: 'json'
        })
        .done(function() {
          resetCommentForm();
          openCard(current.id);
        })
        .fail(function(xhr) {
          toast((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal mengirim komentar.');
        })
        .always(function() {
          $btn.prop('disabled', false).text('Simpan');
        });
    });

    // hapus card
    $('#cdDelete').on('click', function() {
      var c = current;
      if (!c || !confirm('Hapus card "' + c.title + '" beserta komentarnya? Tindakan ini tidak bisa dibatalkan.')) return;
      post('delete_card', {id_detail: c.id}).done(function() {
        cards = cards.filter(function(x) {
          return x.id !== c.id;
        });
        render();
        $('#ntModal').modal('hide');
        toast('Card dihapus');
      });
    });

    // ---------- lightbox ----------
    var lbItems = [], lbIndex = 0;

    function lbShow(i) {
      lbIndex = (i + lbItems.length) % lbItems.length;
      var it = lbItems[lbIndex];
      $('#lbImg').attr('src', it.url).attr('alt', it.name);
      $('#lbName').text(it.name);
      $('#lbOpen, #lbDownload').attr('href', it.url);
      $('#lbCount').text(lbItems.length > 1 ? (lbIndex + 1) + ' / ' + lbItems.length : '');
      $('#lbPrev, #lbNext').toggle(lbItems.length > 1);
    }

    function lbClose() {
      $('#ntLightbox').fadeOut(120);
      $('#lbImg').attr('src', '');
    }

    $(document).on('click', '#ntModal .nt-thumb', function(e) {
      if ($(this).hasClass('is-broken')) return; // file hilang: biarkan link biasa
      e.preventDefault();
      var $all = $('#ntModal .nt-thumb').not('.is-broken');
      lbItems = $all.map(function() {
        return {url: $(this).attr('href'), name: $(this).attr('data-name')};
      }).get();
      lbShow($all.index(this));
      $('#ntLightbox').fadeIn(120);
    });
    $('#lbPrev').on('click', function(e) {
      e.stopPropagation();
      lbShow(lbIndex - 1);
    });
    $('#lbNext').on('click', function(e) {
      e.stopPropagation();
      lbShow(lbIndex + 1);
    });
    $('#lbClose').on('click', lbClose);
    $('#ntLightbox').on('click', function(e) {
      if (e.target === this) lbClose(); // klik area gelap untuk menutup
    });
    $('#ntModal').on('hide.bs.modal', lbClose);
    // tangkap tombol keyboard lebih dulu agar Esc tidak ikut menutup popup card
    document.addEventListener('keydown', function(e) {
      if (!$('#ntLightbox').is(':visible')) return;
      if (e.key === 'Escape') lbClose();
      else if (e.key === 'ArrowLeft') lbShow(lbIndex - 1);
      else if (e.key === 'ArrowRight') lbShow(lbIndex + 1);
      else return;
      e.stopPropagation();
      e.preventDefault();
    }, true);

    // ---------- edit project (PIC) ----------
    function pjCount() {
      $('#pjCount').text('(' + $('.pjMember:checked').length + ' dipilih)');
    }
    $('#ntEditProject').on('click', function() {
      pjCount();
      $('#ntProjModal').modal('show');
    });
    if (location.hash === '#edit' && $('#ntEditProject').length) {
      $('#ntEditProject').click();
    }
    $(document).on('change', '.pjMember', pjCount);
    $('#pjSearch').on('input', function() {
      var q = $.trim($(this).val()).toLowerCase();
      $('.nt-proj-opt').each(function() {
        $(this).toggle(!q || $(this).attr('data-search').indexOf(q) !== -1);
      });
    });
    $('#pjSave').on('click', function() {
      var name = $.trim($('#pjName').val());
      var members = $('.pjMember:checked').map(function() {
        return this.value;
      }).get();
      if (!name) return $('#pjName').focus();
      if (!members.length) return toast('Pilih minimal satu member.');
      var $btn = $(this).prop('disabled', true);
      post('update_project', {id_task: ID_TASK, name: name, activity: $('#pjStatus').val(), members: members})
        .done(function() {
          // muat ulang: daftar anggota dipakai di banyak tempat (responsible, member card, judul)
          location.replace(location.pathname);
        })
        .fail(function() {
          $btn.prop('disabled', false);
        });
    });

    $('#ntSearch').on('input', function() {
      query = $.trim($(this).val()).toLowerCase();
      render();
    });

    render();
  })();
</script>
</body>

</html>
