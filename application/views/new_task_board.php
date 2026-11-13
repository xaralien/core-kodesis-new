<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $this->session->userdata('nama_singkat') ?> | New Tello</title>
  <link href="<?php echo base_url(); ?>src/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>src/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>src/vendors/nprogress/nprogress.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>src/build/css/custom.min.css" rel="stylesheet">
  <style>
    :root {
      --nt-blue: #0c66e4;
      --nt-blue-dark: #0055cc;
      --nt-bg: #1d7afc;
      --nt-col: #f1f2f4;
      --nt-text: #172b4d;
      --nt-muted: #626f86;
    }

    .nt-board-wrap {
      background: linear-gradient(135deg, #0c66e4 0%, #1d7afc 60%, #579dff 100%);
      border-radius: 12px;
      padding: 16px;
      min-height: calc(100vh - 140px);
      color: var(--nt-text);
    }

    .nt-topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 16px;
    }

    .nt-title {
      color: #fff;
      font-size: 22px;
      font-weight: 700;
      margin: 0;
    }

    .nt-search {
      border: 0;
      border-radius: 6px;
      padding: 7px 12px;
      min-width: 220px;
      background: rgba(255, 255, 255, .92);
      outline: none;
    }

    .nt-columns {
      display: flex;
      gap: 14px;
      align-items: flex-start;
      overflow-x: auto;
      padding-bottom: 12px;
    }

    .nt-col {
      background: var(--nt-col);
      border-radius: 12px;
      width: 300px;
      min-width: 300px;
      flex: 0 0 300px;
      padding: 10px;
      box-shadow: 0 1px 2px rgba(9, 30, 66, .25);
    }

    .nt-col-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 4px 6px 10px;
      font-weight: 700;
      font-size: 15px;
    }

    .nt-count {
      background: #dcdfe4;
      color: var(--nt-muted);
      border-radius: 10px;
      padding: 0 8px;
      font-size: 12px;
      font-weight: 600;
    }

    .nt-dot {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin-right: 8px;
    }

    .nt-list {
      min-height: 40px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .nt-card {
      background: #fff;
      border-radius: 8px;
      padding: 10px 12px;
      box-shadow: 0 1px 1px rgba(9, 30, 66, .25);
      cursor: grab;
      word-wrap: break-word;
      border-left: 4px solid transparent;
    }

    .nt-card:hover {
      background: #f7f8f9;
    }

    .nt-card .nt-card-title {
      font-weight: 600;
      font-size: 14px;
      margin: 0 0 2px;
    }

    .nt-card .nt-card-desc {
      color: var(--nt-muted);
      font-size: 12.5px;
      margin: 0;
      white-space: pre-wrap;
    }

    .nt-card-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 8px;
      font-size: 12px;
      color: var(--nt-muted);
    }

    .nt-card-actions button {
      border: 0;
      background: transparent;
      color: var(--nt-muted);
      padding: 2px 6px;
      border-radius: 4px;
    }

    .nt-card-actions button:hover {
      background: #dcdfe4;
      color: var(--nt-text);
    }

    .nt-card.sortable-ghost {
      opacity: .35;
      background: #cce0ff;
    }

    .nt-card.sortable-chosen {
      cursor: grabbing;
      box-shadow: 0 8px 16px rgba(9, 30, 66, .3);
    }

    .nt-pr-high {
      border-left-color: #e2483d;
    }

    .nt-pr-med {
      border-left-color: #f5a623;
    }

    .nt-pr-low {
      border-left-color: #4bce97;
    }

    .nt-due-late {
      color: #c9372c;
      font-weight: 600;
    }

    .nt-add-btn {
      display: block;
      width: 100%;
      text-align: left;
      border: 0;
      background: transparent;
      color: var(--nt-muted);
      padding: 8px 6px;
      margin-top: 6px;
      border-radius: 8px;
      font-weight: 600;
    }

    .nt-add-btn:hover {
      background: #dcdfe4;
      color: var(--nt-text);
    }

    .nt-add-form {
      margin-top: 8px;
    }

    .nt-add-form textarea {
      width: 100%;
      border: 0;
      border-radius: 8px;
      padding: 8px 10px;
      box-shadow: 0 1px 1px rgba(9, 30, 66, .25);
      resize: none;
      outline: none;
    }

    .nt-add-form .btn-primary {
      background: var(--nt-blue);
      border-color: var(--nt-blue);
    }

    .nt-add-form .btn-primary:hover {
      background: var(--nt-blue-dark);
    }

    .nt-empty {
      text-align: center;
      color: var(--nt-muted);
      font-size: 12px;
      padding: 8px 0;
    }

    @media (max-width: 767px) {
      .nt-board-wrap {
        padding: 10px;
      }

      .nt-columns {
        flex-direction: column;
        overflow-x: visible;
      }

      .nt-col {
        width: 100%;
        min-width: 0;
        flex: 1 1 auto;
      }

      .nt-search {
        width: 100%;
      }
    }
  </style>
</head>

<body class="nav-md">
  <div class="container body">
    <div class="main_container">
      <div class="col-md-3 left_col">
        <div class="left_col scroll-view">
          <div class="navbar nav_title" style="border: 0;">
            <a href="<?php echo base_url(); ?>" class="site_title"><img src="<?php echo base_url(); ?>img/boc_logo.png" alt="..." height="42" width="60"><span> <?= $this->session->userdata('nama_singkat') ?> </span></a>
          </div>

          <div class="clearfix"></div>

          <!-- menu profile quick info -->
          <div class="profile clearfix">
            <div class="profile_pic">
              <img src="<?php echo base_url(); ?>src/images/img.jpg" alt="..." class="img-circle profile_img">
            </div>
            <div class="profile_info">
              <span>Welcome,</span>
              <h2><?php echo $this->session->userdata('nama'); ?></h2>
            </div>
          </div>
          <!-- /menu profile quick info -->

          <br />

          <!-- sidebar menu -->
          <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <?php $this->load->view('side_menu.php'); ?>
          </div>
          <!-- /sidebar menu -->

          <!-- /menu footer buttons -->

          <!-- /menu footer buttons -->
        </div>
      </div>

      <!-- top navigation -->
      <div class="top_nav">
        <div class="nav_menu">
          <nav>
            <div class="nav toggle">
              <a id="menu_toggle"><i class="fa fa-bars"></i></a>
            </div>

            <ul class="nav navbar-nav navbar-right">
              <li class="">
                <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                  <img src="<?php echo base_url(); ?>src/images/img.jpg" alt=""><?php echo $this->session->userdata('nama'); ?>
                  <span class=" fa fa-angle-down"></span>
                </a>
                <ul class="dropdown-menu dropdown-usermenu pull-right">
                  <li><a href="javascript:;"> Profile</a></li>
                  <li>
                    <a href="javascript:;">
                      <span class="badge bg-red pull-right">50%</span>
                      <span>Settings</span>
                    </a>
                  </li>
                  <li><a href="javascript:;">Help</a></li>
                  <li><a href="<?php echo base_url(); ?>login/logout"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                </ul>
              </li>


              <li role="presentation" class="dropdown">
                <a href="<?php echo base_url() . "app/inbox"; ?>" class="dropdown-toggle info-number">
                  <i class="fa fa-envelope-o"></i>
                  <?php if ($count_inbox == 0) { ?>
                    <span class="badge bg-green"><?php echo $count_inbox; ?></span>
                  <?php } else { ?>
                    <span class="badge bg-red"><?php echo $count_inbox; ?></span>
                  <?php } ?>
                </a>
                <!--ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
                      <li>
                        <a>
                          <span class="image"><img src="<?php echo base_url(); ?>src/images/img.jpg" alt="Profile Image" /></span>
                          <span>
                            <span>John Smith</span>
                            <span class="time">3 mins ago</span>
                          </span>
                          <span class="message">
                            Film festivals used to be do-or-die moments for movie makers. They were where...
                          </span>
                        </a>
                      </li>
                      <li>
                        <a>
                          <span class="image"><img src="<?php echo base_url(); ?>src/images/img.jpg" alt="Profile Image" /></span>
                          <span>
                            <span>John Smith</span>
                            <span class="time">3 mins ago</span>
                          </span>
                          <span class="message">
                            Film festivals used to be do-or-die moments for movie makers. They were where...
                          </span>
                        </a>
                      </li>
                      <li>
                        <a>
                          <span class="image"><img src="<?php echo base_url(); ?>src/images/img.jpg" alt="Profile Image" /></span>
                          <span>
                            <span>John Smith</span>
                            <span class="time">3 mins ago</span>
                          </span>
                          <span class="message">
                            Film festivals used to be do-or-die moments for movie makers. They were where...
                          </span>
                        </a>
                      </li>
                      <li>
                        <a>
                          <span class="image"><img src="<?php echo base_url(); ?>src/images/img.jpg" alt="Profile Image" /></span>
                          <span>
                            <span>John Smith</span>
                            <span class="time">3 mins ago</span>
                          </span>
                          <span class="message">
                            Film festivals used to be do-or-die moments for movie makers. They were where...
                          </span>
                        </a>
                      </li>
                      <li>
                        <div class="text-center">
                          <a>
                            <strong>See All Alerts</strong>
                            <i class="fa fa-angle-right"></i>
                          </a>
                        </div>
                      </li>
                    </ul-->
              </li>
              <?php include 'notif_tello.php' ?>
            </ul>
          </nav>
        </div>
      </div>
      <!-- /top navigation -->

      <!-- page content -->
      <div class="right_col" role="main">
        <div class="nt-board-wrap">
          <div class="nt-topbar">
            <h3 class="nt-title"><i class="fa fa-columns"></i> New Tello</h3>
            <input type="search" id="ntSearch" class="nt-search" placeholder="Cari kartu...">
          </div>

          <div class="nt-columns" id="ntColumns"></div>
        </div>
      </div>
      <!-- /page content -->

      <!-- Modal edit kartu -->
      <div class="modal fade" id="ntModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal">&times;</button>
              <h4 class="modal-title">Edit Kartu</h4>
            </div>
            <div class="modal-body">
              <input type="hidden" id="ntEditId">
              <div class="form-group">
                <label for="ntEditTitle">Judul</label>
                <input type="text" id="ntEditTitle" class="form-control" maxlength="200">
              </div>
              <div class="form-group">
                <label for="ntEditDesc">Deskripsi</label>
                <textarea id="ntEditDesc" class="form-control" rows="4"></textarea>
              </div>
              <div class="row">
                <div class="col-xs-12 col-sm-4 form-group">
                  <label for="ntEditStatus">Kolom</label>
                  <select id="ntEditStatus" class="form-control"></select>
                </div>
                <div class="col-xs-12 col-sm-4 form-group">
                  <label for="ntEditPriority">Prioritas</label>
                  <select id="ntEditPriority" class="form-control">
                    <option value="">Tanpa</option>
                    <option value="low">Rendah</option>
                    <option value="med">Sedang</option>
                    <option value="high">Tinggi</option>
                  </select>
                </div>
                <div class="col-xs-12 col-sm-4 form-group">
                  <label for="ntEditDue">Tenggat</label>
                  <input type="date" id="ntEditDue" class="form-control">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger pull-left" id="ntDeleteBtn"><i class="fa fa-trash"></i> Hapus</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
              <button type="button" class="btn btn-primary" id="ntSaveBtn">Simpan</button>
            </div>
          </div>
        </div>
      </div>

      <script src="<?php echo base_url(); ?>src/vendors/jquery/dist/jquery.min.js"></script>
      <script src="<?php echo base_url(); ?>src/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
      <script src="<?php echo base_url(); ?>src/vendors/fastclick/lib/fastclick.js"></script>
      <script src="<?php echo base_url(); ?>src/vendors/nprogress/nprogress.js"></script>
      <script src="<?php echo base_url(); ?>src/build/js/custom.min.js"></script>
      <!-- SortableJS: drag & drop (mendukung mouse dan layar sentuh) -->
      <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>

      <script>
        (function() {
          var STORAGE_KEY = <?= json_encode($storage_key) ?>;
          var COLUMNS = [
            {id: 'todo', title: 'To Do', color: '#579dff'},
            {id: 'progress', title: 'In Progress', color: '#f5a623'},
            {id: 'done', title: 'Done', color: '#4bce97'}
          ];
          var PRIORITY_LABEL = {high: 'Tinggi', med: 'Sedang', low: 'Rendah'};
          var cards = [];
          var query = '';

          // ---------- storage ----------
          function load() {
            try {
              var raw = localStorage.getItem(STORAGE_KEY);
              cards = raw ? JSON.parse(raw) : [];
              if (!Array.isArray(cards)) cards = [];
            } catch (e) {
              cards = [];
            }
          }

          function save() {
            try {
              localStorage.setItem(STORAGE_KEY, JSON.stringify(cards));
            } catch (e) {
              alert('Gagal menyimpan ke local storage browser.');
            }
          }

          function uid() {
            return 'c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
          }

          function esc(s) {
            return $('<div>').text(s == null ? '' : s).html();
          }

          // ---------- render ----------
          function render() {
            var $wrap = $('#ntColumns').empty();
            COLUMNS.forEach(function(col) {
              var colCards = cards.filter(function(c) {
                return c.status === col.id;
              });
              var visible = colCards.filter(function(c) {
                if (!query) return true;
                return (c.title + ' ' + (c.desc || '')).toLowerCase().indexOf(query) !== -1;
              });

              var $col = $('<div class="nt-col">').attr('data-col', col.id);
              $col.append(
                '<div class="nt-col-head"><span><span class="nt-dot" style="background:' + col.color + '"></span>' +
                esc(col.title) + '</span><span class="nt-count">' + colCards.length + '</span></div>'
              );

              var $list = $('<div class="nt-list">').attr('data-col', col.id);
              visible.forEach(function(c) {
                $list.append(cardEl(c));
              });
              $col.append($list);
              if (!visible.length) {
                $col.append('<div class="nt-empty">' + (query ? 'Tidak ada hasil' : 'Belum ada kartu') + '</div>');
              }

              $col.append(
                '<button type="button" class="nt-add-btn" data-col="' + col.id + '"><i class="fa fa-plus"></i> Tambah kartu</button>' +
                '<div class="nt-add-form" style="display:none">' +
                '<textarea rows="2" placeholder="Masukkan judul kartu..."></textarea>' +
                '<div style="margin-top:6px"><button type="button" class="btn btn-primary btn-sm nt-add-save" data-col="' + col.id + '">Tambah</button> ' +
                '<button type="button" class="btn btn-default btn-sm nt-add-cancel">Batal</button></div></div>'
              );
              $wrap.append($col);
            });
            initSortable();
          }

          function cardEl(c) {
            var today = new Date().toISOString().slice(0, 10);
            var late = c.due && c.due < today && c.status !== 'done';
            var $c = $('<div class="nt-card">').attr('data-id', c.id);
            if (c.priority) $c.addClass('nt-pr-' + c.priority);
            var html = '<p class="nt-card-title">' + esc(c.title) + '</p>';
            if (c.desc) html += '<p class="nt-card-desc">' + esc(c.desc) + '</p>';
            html += '<div class="nt-card-foot"><span>';
            if (c.due) html += '<i class="fa fa-clock-o"></i> <span class="' + (late ? 'nt-due-late' : '') + '">' + esc(c.due) + '</span> ';
            if (c.priority) html += '<span>' + PRIORITY_LABEL[c.priority] + '</span>';
            html += '</span><span class="nt-card-actions">' +
              '<button type="button" class="nt-edit" title="Edit"><i class="fa fa-pencil"></i></button>' +
              '<button type="button" class="nt-del" title="Hapus"><i class="fa fa-trash-o"></i></button></span></div>';
            return $c.html(html);
          }

          // ---------- drag & drop ----------
          function initSortable() {
            if (typeof Sortable === 'undefined') return;
            $('.nt-list').each(function() {
              Sortable.create(this, {
                group: 'nt',
                animation: 150,
                delay: 150,
                delayOnTouchOnly: true,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                filter: 'button',
                preventOnFilter: false,
                onEnd: syncFromDom
              });
            });
          }

          // Susun ulang array cards sesuai urutan DOM setelah drag.
          // Kartu yang sedang tersembunyi oleh pencarian dipertahankan di posisinya.
          function syncFromDom() {
            var byId = {};
            cards.forEach(function(c) {
              byId[c.id] = c;
            });
            var next = [];
            $('.nt-list').each(function() {
              var status = $(this).attr('data-col');
              $(this).children('.nt-card').each(function() {
                var c = byId[$(this).attr('data-id')];
                if (c) {
                  c.status = status;
                  next.push(c);
                  delete byId[c.id];
                }
              });
            });
            Object.keys(byId).forEach(function(k) {
              next.push(byId[k]);
            });
            cards = next;
            save();
            render();
          }

          // ---------- actions ----------
          $(document).on('click', '.nt-add-btn', function() {
            $(this).hide().next('.nt-add-form').show().find('textarea').focus();
          });
          $(document).on('click', '.nt-add-cancel', function() {
            var $f = $(this).closest('.nt-add-form');
            $f.hide().find('textarea').val('');
            $f.prev('.nt-add-btn').show();
          });

          function addCard($form, col) {
            var title = $.trim($form.find('textarea').val());
            if (!title) return;
            cards.push({id: uid(), title: title, desc: '', status: col, priority: '', due: '', created: Date.now()});
            save();
            render();
            // buka kembali form di kolom yang sama agar bisa menambah beruntun
            $('.nt-col[data-col="' + col + '"] .nt-add-btn').click();
          }
          $(document).on('click', '.nt-add-save', function() {
            addCard($(this).closest('.nt-add-form'), $(this).attr('data-col'));
          });
          $(document).on('keydown', '.nt-add-form textarea', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              addCard($(this).closest('.nt-add-form'), $(this).closest('.nt-col').attr('data-col'));
            } else if (e.key === 'Escape') {
              $(this).closest('.nt-add-form').find('.nt-add-cancel').click();
            }
          });

          function findCard(id) {
            return cards.filter(function(c) {
              return c.id === id;
            })[0];
          }

          function removeCard(id) {
            if (!confirm('Hapus kartu ini?')) return false;
            cards = cards.filter(function(c) {
              return c.id !== id;
            });
            save();
            render();
            return true;
          }

          $(document).on('click', '.nt-del', function(e) {
            e.stopPropagation();
            removeCard($(this).closest('.nt-card').attr('data-id'));
          });

          function openEdit(id) {
            var c = findCard(id);
            if (!c) return;
            $('#ntEditId').val(c.id);
            $('#ntEditTitle').val(c.title);
            $('#ntEditDesc').val(c.desc || '');
            $('#ntEditPriority').val(c.priority || '');
            $('#ntEditDue').val(c.due || '');
            $('#ntEditStatus').empty();
            COLUMNS.forEach(function(col) {
              $('#ntEditStatus').append($('<option>').val(col.id).text(col.title));
            });
            $('#ntEditStatus').val(c.status);
            $('#ntModal').modal('show');
          }
          $(document).on('click', '.nt-edit', function(e) {
            e.stopPropagation();
            openEdit($(this).closest('.nt-card').attr('data-id'));
          });
          $(document).on('click', '.nt-card', function() {
            openEdit($(this).attr('data-id'));
          });

          $('#ntSaveBtn').on('click', function() {
            var c = findCard($('#ntEditId').val());
            var title = $.trim($('#ntEditTitle').val());
            if (!c || !title) {
              $('#ntEditTitle').focus();
              return;
            }
            var oldStatus = c.status;
            c.title = title;
            c.desc = $.trim($('#ntEditDesc').val());
            c.priority = $('#ntEditPriority').val();
            c.due = $('#ntEditDue').val();
            c.status = $('#ntEditStatus').val();
            if (c.status !== oldStatus) { // pindah kolom lewat form: taruh di paling bawah kolom tujuan
              cards = cards.filter(function(x) {
                return x.id !== c.id;
              });
              cards.push(c);
            }
            save();
            render();
            $('#ntModal').modal('hide');
          });

          $('#ntDeleteBtn').on('click', function() {
            if (removeCard($('#ntEditId').val())) $('#ntModal').modal('hide');
          });

          $('#ntSearch').on('input', function() {
            query = $.trim($(this).val()).toLowerCase();
            render();
          });

          load();
          render();
        })();
      </script>
    </div>
  </div>
</body>

</html>
