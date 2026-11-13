<div id="page">
  <?php include APPPATH . '/views/mobile/v_nav.php' ?>
  <div class="page-content">
    <div class="card card-style">
      <div class="content mb-0">
        <?php if (!$this->uri->segment(4)) { ?>
          <h3>Create Task</h3>
          <form action="<?= base_url('task/save_detail_task/' . $this->uri->segment(3)) ?>" method="post" enctype="multipart/form-data" id="form-create-card">
            <div class="has-borders no-icon mb-2">
              <label for="project_name" class="form-label">Task Name <em>(required)</em></label>
              <input type="text" class="form-control" name="card_name" id="card_name" value="<?= set_value('card_name') ?>">
              <?= form_error('card_name') ?>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="responsible" class="form-label">Task Responsible <em>(required)</em></label>
              <select class="form-select" name="responsible" id="responsible">
                <option value="" disabled selected>Select Responsible</option>
                <?php foreach ($ss as $data) {
                  if ($data->nip != '') {
                ?>
                    <option value="<?= $data->nip ?>" <?= set_select('responsible', $data->nip) ?>><?= $data->nama ?> (<?php echo $data->nama_jabatan; ?>)</option>
                <?php }
                } ?>
              </select>
              <?= form_error('responsible') ?>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="member_task" class="form-label">Task Member</label>
              <select class="form-control js-example-basic-multiple" name="member_task[]" id="member_task" multiple>
                <?php foreach ($ss as $data) { ?>
                  <option value="<?= $data->nip ?>" <?= set_select('member_task[]', $data->nip) ?>><?= $data->nama ?> (<?php echo $data->nama_jabatan; ?>)</option>
                <?php } ?>
              </select>
              <?= form_error('member_task[]') ?>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="description" class="form-label">Description</label>
              <textarea name="description" id="description" class="form-control"><?= set_value('description') ?></textarea>
            </div>
            <div class="has-borders no-icon mb-2">
              <div class="row">
                <div class="col-6">
                  <label for="start_date" class="form-label">Start <em>(required)</em></label>
                  <input type="date" name="start_date" id="start_date" class="form-control" value="<?= set_value('start_date') ?>">
                  <?= form_error('start_date') ?>
                </div>
                <div class="col-6">
                  <label for="end_date" class="form-label">End <em>(required)</em></label>
                  <input type="date" name="end_date" id="end_date" class="form-control" value="<?= set_value('end_date') ?>">
                  <?= form_error('end_date') ?>
                </div>
              </div>
            </div>
            <div class="has-boders no-icon mb-2">
              <label for="attachment">Attachment</label>
              <input type="file" name="attachment[]" id="attachment" class="form-control" multiple>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="activity" class="form-label">Task Activity <em>(required)</em></label>
              <select name="activity" id="activity" class="form-select">
                <option value="1" <?= set_select('activity', '1') ?>>Open</option>
                <option value="2" <?= set_select('activity', '2') ?>>Pending</option>
                <option value="3" <?= set_select('activity', '3') ?>>Close</option>
              </select>
              <?= form_error('activity') ?>
            </div>
            <div class="my-3">
              <a href="<?= base_url('task/task') ?>" class="btn btn-warning">Back</a>
              <button type="reset" class="btn btn-primary">Reset</button>
              <button type="submit" class="btn btn-success" id="btn-submit-card">Create Task</button>
            </div>
          </form>
        <?php } else { ?>
          <h3>Update Task</h3>
          <form action="<?= base_url('task/update_detail_task') ?>" method="post" enctype="multipart/form-data" id="form-create-card">
            <input type="hidden" value="<?= $this->uri->segment(3) ?>" name="id_task">
            <input type="hidden" value="<?= $this->uri->segment(4) ?>" name="id_card">
            <div class="has-borders no-icon mb-2">
              <label for="project_name" class="form-label">Task Name <em>(required)</em></label>
              <input type="text" class="form-control" name="card_name" id="card_name" value="<?= $row_edit['task_name'] ?>">
              <?= form_error('card_name') ?>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="responsible" class="form-label">Task Responsible <em>(required)</em></label>
              <select class="form-select" name="responsible" id="responsible">
                <option value="" disabled selected>Select Responsible</option>
                <?php foreach ($ss as $data) {
                  if ($data->nip != '') {
                ?>
                    <option value="<?= $data->nip ?>" <?= $data->nip == $row_edit['responsible'] ? 'selected' : '' ?>><?= $data->nama ?> (<?php echo $data->nama_jabatan; ?>)</option>
                <?php }
                } ?>
              </select>
              <?= form_error('responsible') ?>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="member_task" class="form-label">Task Member <em>(required)</em></label>
              <select class="form-control js-example-basic-multiple" name="member_task[]" id="member_task" multiple>
                <?php
                foreach ($ss as $data) :
                  if (strpos($row_edit['member_detail'], $data->nip) !== false) {
                ?>
                    <option selected value="<?php echo $data->nip; ?>"><?php echo $data->nama; ?>
                      (<?php echo $data->nama_jabatan; ?>)</option>
                  <?php
                  } else { ?>
                    <option value="<?php echo $data->nip; ?>"><?php echo $data->nama; ?>
                      (<?php echo $data->nama_jabatan; ?>)</option>

                <?php }
                endforeach; ?>
              </select>
              <?= form_error('member_task[]') ?>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="description" class="form-label">Description</label>
              <textarea name="description" id="description" class="form-control"><?= $row_edit['description'] ?></textarea>
            </div>
            <div class="has-borders no-icon mb-2">
              <div class="row">
                <div class="col-6">
                  <label for="start_date" class="form-label">Start <em>(required)</em></label>
                  <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $row_edit['start_date'] ?>">
                  <?= form_error('start_date') ?>
                </div>
                <div class="col-6">
                  <label for="end_date" class="form-label">End <em>(required)</em></label>
                  <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $row_edit['due_date'] ?>">
                  <?= form_error('end_date') ?>
                </div>
              </div>
            </div>
            <div class="has-boders no-icon mb-2">
              <label for="attachment">Attachment</label>
              <input type="file" name="attachment[]" id="attachment" class="form-control" multiple>
              <p><?= $row_edit['attachment'] == null ? 'File tidak ada' : $row_edit['attachment'] ?></p>
            </div>
            <div class="has-borders no-icon mb-2">
              <label for="activity" class="form-label">Task Activity <em>(required)</em></label>
              <select name="activity" id="activity" class="form-select">
                <option value="1" <?= $row_edit['activity'] == '1' ? 'selected' : '' ?>>Open</option>
                <option value="2" <?= $row_edit['activity'] == '2' ? 'selected' : '' ?>>Pending</option>
                <option value="3" <?= $row_edit['activity'] == '3' ? 'selected' : '' ?>>Close</option>
              </select>
              <?= form_error('activity') ?>
            </div>
            <div class="my-3">
              <a href="<?= base_url('task/task_view/' . $this->uri->segment(3)) ?>" class="btn btn-warning">Back</a>
              <button type="reset" class="btn btn-primary">Reset</button>
              <button type="submit" class="btn btn-success" id="btn-submit-card">Update Task</button>
            </div>
          </form>
        <?php } ?>
      </div>
    </div>
  </div>
</div>