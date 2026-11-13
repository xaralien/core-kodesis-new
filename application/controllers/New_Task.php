<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * New_Task (New Tello)
 * Tampilan Kanban bergaya Trello di atas data Tello yang sudah ada:
 *   - tabel `task`        = project / board
 *   - tabel `task_detail` = card di dalam board
 *   - task_detail.activity: 1 = Open (In Progress), 2 = Pending, 3 = Closed (Done)
 * Aturan akses mengikuti Task.php / task_view.php.
 */
class New_Task extends CI_Controller
{
	// NIP dengan akses penuh, sama seperti di Task.php
	private $soeryo = '2146501';

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library(array('session', 'Api_Whatsapp'));
		$this->load->helper('url');
		date_default_timezone_set('Asia/Jakarta');
	}

	// ===================================================================
	// Halaman
	// ===================================================================

	// Daftar project (board) milik user
	public function index()
	{
		$this->require_login();
		$nip = $this->session->userdata('nip');
		$batas_tanggal = date('Y-m-d', strtotime('-7 days'));
		$today = date('Y-m-d');

		// Filter sama dengan m_task->task_get: project aktif / closed <= 7 hari, user sebagai member atau PIC
		$this->db->select("a.*, u.nama AS pic_nama,
			COUNT(b.id_detail) AS total_card,
			SUM(CASE WHEN b.activity = '1' THEN 1 ELSE 0 END) AS total_open,
			SUM(CASE WHEN b.activity = '2' THEN 1 ELSE 0 END) AS total_pending,
			SUM(CASE WHEN b.activity = '3' THEN 1 ELSE 0 END) AS total_closed,
			SUM(CASE WHEN b.activity = '1' AND b.due_date <= " . $this->db->escape($today) . " THEN 1 ELSE 0 END) AS total_overdue", false);
		$this->db->from('task a');
		$this->db->join('task_detail b', 'b.id_task = a.id', 'left');
		$this->db->join('users u', 'u.nip = a.pic', 'left');
		$this->db->group_start();
		$this->db->where('a.closed_date', NULL);
		$this->db->or_where('a.closed_date >=', $batas_tanggal);
		$this->db->group_end();
		$this->db->group_start();
		$this->db->like('a.member', $nip);
		$this->db->or_like('a.pic', $nip);
		$this->db->group_end();
		$this->db->group_by('a.id');
		$this->db->order_by('a.activity', 'ASC');
		$this->db->order_by('a.date_created', 'DESC');
		$data['projects'] = $this->db->get()->result();

		$data['can_create'] = strpos($this->session->userdata('level'), '601') !== false;
		$this->set_nav_counts($data);
		$this->load->view('new_task/projects', $data);
	}

	// Papan Kanban satu project
	public function board($id = null)
	{
		$this->require_login();
		$task = $this->get_task_or_fail($id, false);
		if (!$task) {
			echo "<script>alert('Unauthorize Privilage!');window.location.href='" . base_url('New_Task') . "';</script>";
			return;
		}

		$this->mark_read('task', 'id', $task->id, $task->read);

		$this->card_query();
		$this->db->where('b.id_task', $task->id);
		$this->db->order_by('b.due_date IS NULL', 'ASC', false);
		$this->db->order_by('b.due_date', 'ASC');
		$this->db->order_by('b.id_detail', 'ASC');
		$rows = $this->db->get()->result();

		$cards = array();
		foreach ($rows as $r) {
			$cards[] = $this->card_payload($r, $task);
		}

		$data['task'] = $task;
		$data['cards'] = $cards;
		$data['members'] = $this->project_members($task);
		$data['is_pic'] = $task->pic == $this->session->userdata('nip');
		$data['can_add'] = $data['is_pic'] && $task->activity == '1';
		$data['candidates'] = $data['is_pic'] ? $this->member_candidates($task) : array();
		$this->set_nav_counts($data);
		$this->load->view('new_task/board', $data);
	}

	// ===================================================================
	// AJAX (JSON)
	// ===================================================================

	// Tambah card cepat dari kolom. Hanya PIC dan selama project masih Open (sama seperti tombol Add Card di task_view)
	public function add_card()
	{
		$this->require_login(true);
		$task = $this->get_task_or_fail($this->input->post('id_task'), true);
		if ($task->pic != $this->session->userdata('nip') || $task->activity != '1') {
			$this->json_error('Hanya PIC yang bisa menambah card pada project yang masih Open.');
		}

		$title = trim((string) $this->input->post('task_name'));
		$activity = $this->valid_activity($this->input->post('activity'));
		if ($title === '') {
			$this->json_error('Judul card wajib diisi.');
		}

		$nip = $this->session->userdata('nip');
		$this->db->insert('task_detail', array(
			'id_task'       => $task->id,
			'task_name'     => $title,
			'responsible'   => $nip,
			'member_detail' => '',
			'description'   => '',
			'start_date'    => date('Y-m-d'),
			'due_date'      => null,
			'activity'      => $activity,
			'attachment'    => null,
			'read'          => $nip,
			'comment'       => '',
		));
		$id_detail = $this->db->insert_id();

		$this->sync_task_status($task->id);
		$this->json_ok(array('card' => $this->card_payload($this->get_card_row($id_detail), $task)));
	}

	// Pindah kolom (drag & drop). Hanya responsible atau PIC, sama seperti form status_activity di task_view
	public function move_card()
	{
		$this->require_login(true);
		$card = $this->get_card_or_fail($this->input->post('id_detail'));
		$task = $this->get_task_or_fail($card->id_task, true);
		if (!$this->can_change_status($card, $task)) {
			$this->json_error('Hanya responsible atau PIC yang bisa mengubah status card ini.');
		}

		$activity = $this->valid_activity($this->input->post('activity'));
		if ($activity != $card->activity) {
			$this->db->where('id_detail', $card->id_detail)->update('task_detail', array('activity' => $activity));
			$this->notify_status($card, $task, $activity);
			$this->sync_task_status($task->id);
		}

		$this->json_ok(array('card' => $this->card_payload($this->get_card_row($card->id_detail), $task)));
	}

	// Edit card dari popup detail. Hanya field yang dikirim yang diubah
	public function update_card()
	{
		$this->require_login(true);
		$card = $this->get_card_or_fail($this->input->post('id_detail'));
		$task = $this->get_task_or_fail($card->id_task, true);
		if (!$this->can_edit($card, $task)) {
			$this->json_error('Anda tidak memiliki akses untuk mengedit card ini.');
		}

		$post = $this->input->post();
		$members = $this->project_members($task);
		$update = array();

		if (array_key_exists('task_name', $post)) {
			$title = trim((string) $post['task_name']);
			if ($title === '') {
				$this->json_error('Judul card wajib diisi.');
			}
			$update['task_name'] = $title;
		}
		if (array_key_exists('description', $post)) {
			$update['description'] = (string) $post['description'];
		}
		if (array_key_exists('start_date', $post)) {
			$update['start_date'] = $this->valid_date($post['start_date']);
		}
		if (array_key_exists('due_date', $post)) {
			$update['due_date'] = $this->valid_date($post['due_date']);
		}
		// Responsible dan member card hanya boleh dipilih dari anggota project
		if (!empty($post['responsible']) && array_key_exists($post['responsible'], $members)) {
			$update['responsible'] = $post['responsible'];
		}
		if (array_key_exists('members_set', $post)) {
			$picked = isset($post['members']) && is_array($post['members']) ? $post['members'] : array();
			$picked = array_values(array_intersect(array_unique($picked), array_keys($members)));
			$update['member_detail'] = implode(';', $picked);
		}

		if ($update) {
			$this->db->where('id_detail', $card->id_detail)->update('task_detail', $update);
			$this->db->where('id', $task->id)->update('task', array('read' => 0));
		}

		// Perubahan status tetap memakai aturan status
		if (array_key_exists('activity', $post)) {
			$activity = $this->valid_activity($post['activity']);
			if ($activity != $card->activity) {
				if (!$this->can_change_status($card, $task)) {
					$this->json_error('Hanya responsible atau PIC yang bisa mengubah status card ini.');
				}
				$this->db->where('id_detail', $card->id_detail)->update('task_detail', array('activity' => $activity));
				$this->notify_status($card, $task, $activity);
				$this->sync_task_status($task->id);
			}
		}

		$this->json_ok(array('card' => $this->card_payload($this->get_card_row($card->id_detail), $task)));
	}

	// Detail card untuk popup: data card, lampiran, dan komentar
	public function card_detail()
	{
		$this->require_login(true);
		$card = $this->get_card_or_fail($this->input->get('id_detail'));
		$task = $this->get_task_or_fail($card->id_task, true);
		$row = $this->get_card_row($card->id_detail);
		$can_edit = $this->can_edit($card, $task);

		$attachments = array();
		foreach (array_filter(explode(';', (string) $card->attachment)) as $f) {
			$attachments[] = $this->file_payload($f, preg_replace('/^\d{9,}_/', '', $f));
		}

		// Komentar hanya untuk yang boleh membuka detail card (sama seperti task_view)
		$comments = array();
		if ($can_edit) {
			$this->mark_read('task_detail', 'id_detail', $card->id_detail, $card->read);
			$this->db->select('c.*, u.nama');
			$this->db->from('task_detail_comment c');
			$this->db->join('users u', 'u.nip = c.member', 'left');
			$this->db->where('c.id_task_detail', $card->id_detail);
			$this->db->order_by('c.date_created', 'DESC');
			foreach ($this->db->get()->result() as $c) {
				$comments[] = $this->comment_payload($c);
			}
		}

		$creator = $this->db->select('nama')->get_where('users', array('nip' => $task->pic))->row();
		$this->json_ok(array(
			'card'        => $this->card_payload($row, $task),
			'attachments' => $attachments,
			'comments'    => $comments,
			'can_comment' => $can_edit && $card->activity == '1',
			'can_view_comments' => $can_edit,
			'creator'     => $creator ? $creator->nama : $task->pic,
		));
	}

	// Tambah komentar (+ lampiran opsional). Logika sama dengan Task::activity_comment
	public function add_comment()
	{
		$this->require_login(true);
		$card = $this->get_card_or_fail($this->input->post('id_detail'));
		$task = $this->get_task_or_fail($card->id_task, true);
		if (!$this->can_edit($card, $task)) {
			$this->json_error('Anda tidak memiliki akses ke card ini.');
		}
		if ($card->activity != '1') {
			$this->json_error('Komentar hanya bisa ditambahkan pada card yang masih Open.');
		}

		$comment = trim((string) $this->input->post('comment'));
		if ($comment === '') {
			$this->json_error('Komentar wajib diisi.');
		}

		$names = $this->upload_files(function ($orig, $i) use ($card) {
			return time() . '_' . $card->id_detail . '-' . $i . '.' . pathinfo($orig, PATHINFO_EXTENSION);
		});

		$insert = array(
			'id_task_detail' => $card->id_detail,
			'comment_member' => $comment,
			'member'         => $this->session->userdata('nip'),
		);
		if ($names) {
			$insert['attachment'] = implode(';', array_column($names, 'file'));
			$insert['attachment_name'] = implode(';', array_column($names, 'name'));
		}
		$this->db->insert('task_detail_comment', $insert);
		$id_comment = $this->db->insert_id();

		$this->db->where('id_detail', $card->id_detail)->update('task_detail', array('read' => '0'));
		$this->db->where('id', $task->id)->update('task', array('read' => '0'));
		$this->notify_comment($card, $task, $comment);

		$this->db->select('c.*, u.nama');
		$this->db->from('task_detail_comment c');
		$this->db->join('users u', 'u.nip = c.member', 'left');
		$this->db->where('c.id_task_detail', $card->id_detail);
		$this->db->order_by('c.date_created', 'DESC');
		$this->db->limit(1);
		$row = $this->db->get()->row();

		$this->json_ok(array(
			'comment' => $this->comment_payload($row),
			'card'    => $this->card_payload($this->get_card_row($card->id_detail), $task),
		));
	}

	// Tambah lampiran langsung ke card (task_detail.attachment), bukan ke komentar
	public function add_attachment()
	{
		$this->require_login(true);
		$card = $this->get_card_or_fail($this->input->post('id_detail'));
		$task = $this->get_task_or_fail($card->id_task, true);
		if (!$this->can_edit($card, $task)) {
			$this->json_error('Anda tidak memiliki akses untuk menambah lampiran card ini.');
		}

		// Penamaan sama dengan save_detail_task: waktu_namaasli tanpa spasi
		$names = $this->upload_files(function ($orig) {
			return str_replace(' ', '', time() . '_' . $orig);
		});
		if (!$names) {
			$this->json_error('Pilih file yang akan dilampirkan.');
		}

		$files = array_values(array_filter(explode(';', (string) $card->attachment)));
		foreach ($names as $n) {
			$files[] = $n['file'];
		}
		$this->db->where('id_detail', $card->id_detail)->update('task_detail', array('attachment' => implode(';', $files)));
		$this->db->where('id', $task->id)->update('task', array('read' => 0));

		$this->json_ok(array('card' => $this->card_payload($this->get_card_row($card->id_detail), $task)));
	}

	// Lepas lampiran dari card. File di server tidak dihapus
	public function remove_attachment()
	{
		$this->require_login(true);
		$card = $this->get_card_or_fail($this->input->post('id_detail'));
		$task = $this->get_task_or_fail($card->id_task, true);
		if (!$this->can_edit($card, $task)) {
			$this->json_error('Anda tidak memiliki akses untuk mengubah lampiran card ini.');
		}

		$file = (string) $this->input->post('file');
		$files = array_values(array_filter(explode(';', (string) $card->attachment)));
		$left = array_values(array_diff($files, array($file)));
		if (count($left) == count($files)) {
			$this->json_error('Lampiran tidak ditemukan pada card ini.');
		}
		$this->db->where('id_detail', $card->id_detail)->update('task_detail', array('attachment' => implode(';', $left)));

		$this->json_ok(array('card' => $this->card_payload($this->get_card_row($card->id_detail), $task)));
	}

	// Edit project: nama, member, status. Hanya PIC (sama seperti tombol edit di Task List)
	public function update_project()
	{
		$this->require_login(true);
		$task = $this->get_task_or_fail($this->input->post('id_task'), true);
		if ($task->pic != $this->session->userdata('nip')) {
			$this->json_error('Hanya PIC yang bisa mengedit project.');
		}

		$name = trim((string) $this->input->post('name'));
		if ($name === '') {
			$this->json_error('Nama project wajib diisi.');
		}

		// Member hanya boleh dari daftar yang sama dengan form Create (m_task->sendto) atau member lama
		$picked = $this->input->post('members');
		$picked = is_array($picked) ? array_unique($picked) : array();
		$picked = array_values(array_intersect($picked, array_keys($this->member_candidates($task))));
		if (!$picked) {
			$this->json_error('Pilih minimal satu member.');
		}

		$this->db->where('id', $task->id)->update('task', array(
			'name'     => $name,
			'member'   => implode(';', $picked) . ';', // format sama dengan save_task / update_task
			'activity' => $this->valid_activity($this->input->post('activity')),
		));

		$this->json_ok();
	}

	// Hapus card. Hanya PIC project
	public function delete_card()
	{
		$this->require_login(true);
		$card = $this->get_card_or_fail($this->input->post('id_detail'));
		$task = $this->get_task_or_fail($card->id_task, true);
		if ($task->pic != $this->session->userdata('nip')) {
			$this->json_error('Hanya PIC project yang bisa menghapus card.');
		}

		$this->db->where('id_task_detail', $card->id_detail)->delete('task_detail_comment');
		$this->db->where('id_detail', $card->id_detail)->delete('task_detail');
		$this->sync_task_status($task->id);

		$this->json_ok(array('id_detail' => $card->id_detail));
	}

	// ===================================================================
	// Helper
	// ===================================================================

	private function require_login($ajax = false)
	{
		$ok = $this->session->userdata('isLogin') != FALSE
			&& strpos((string) $this->session->userdata('level'), '60') !== false;
		if (!$ok) {
			if ($ajax) {
				$this->json_error('Sesi habis, silakan login ulang.', 401);
			}
			redirect('home');
		}
	}

	private function is_super()
	{
		return $this->session->userdata('nip') == $this->soeryo
			|| $this->session->userdata('level_jabatan') > 4;
	}

	// Project bisa dibuka oleh member, PIC, atau level jabatan > 4
	private function get_task_or_fail($id, $ajax)
	{
		$task = $this->db->get_where('task', array('id' => (int) $id))->row();
		$nip = $this->session->userdata('nip');
		$allowed = $task && ($task->pic == $nip
			|| in_array($nip, $this->split_nip($task->member))
			|| $this->is_super());
		if (!$allowed) {
			if ($ajax) {
				$this->json_error('Project tidak ditemukan atau Anda tidak memiliki akses.', 403);
			}
			return null;
		}
		return $task;
	}

	private function get_card_or_fail($id_detail)
	{
		$card = $this->db->get_where('task_detail', array('id_detail' => (int) $id_detail))->row();
		if (!$card) {
			$this->json_error('Card tidak ditemukan.', 404);
		}
		return $card;
	}

	private function get_card_row($id_detail)
	{
		$this->card_query();
		$this->db->where('b.id_detail', $id_detail);
		return $this->db->get()->row();
	}

	// Query dasar card + nama responsible + jumlah komentar
	private function card_query()
	{
		$this->db->select('b.*, a.nama AS responsible_nama,
			(SELECT COUNT(*) FROM task_detail_comment c WHERE c.id_task_detail = b.id_detail) AS comment_count', false);
		$this->db->from('task_detail b');
		$this->db->join('users a', 'a.nip = b.responsible', 'left');
	}

	private function can_change_status($card, $task)
	{
		$nip = $this->session->userdata('nip');
		return $card->responsible == $nip || $task->pic == $nip;
	}

	// Sama dengan syarat tombol Detail/Edit di task_view
	private function can_edit($card, $task)
	{
		$nip = $this->session->userdata('nip');
		return $this->is_super()
			|| $task->pic == $nip
			|| $card->responsible == $nip
			|| in_array($nip, $this->split_nip($card->member_detail));
	}

	private function card_payload($r, $task)
	{
		$attachments = array_values(array_filter(explode(';', (string) $r->attachment)));
		// Gambar pertama di lampiran card dipakai sebagai sampul, seperti Trello
		$cover = null;
		foreach ($attachments as $f) {
			if ($this->is_image($f)) {
				$cover = $this->file_url($f);
				break;
			}
		}
		return array(
			'id'               => (int) $r->id_detail,
			'title'            => $r->task_name,
			'description'      => (string) $r->description,
			'activity'         => (string) $r->activity,
			'start_date'       => $r->start_date,
			'due_date'         => $r->due_date,
			'responsible'      => $r->responsible,
			'responsible_nama' => isset($r->responsible_nama) ? $r->responsible_nama : '',
			'member_nips'      => $this->split_nip($r->member_detail),
			'attachments'      => count($attachments),
			'cover'            => $cover,
			'comments'         => isset($r->comment_count) ? (int) $r->comment_count : 0,
			'date_created'     => isset($r->date_created) ? $r->date_created : null,
			'unread'           => stripos((string) $r->read, (string) $this->session->userdata('nip')) === false,
			'can_edit'         => $this->can_edit($r, $task),
			'can_move'         => $this->can_change_status($r, $task),
			'detail_url'       => base_url('task/task_view/' . $task->id . '/' . $r->id_detail),
		);
	}

	// Calon member project: daftar dari m_task->sendto (sama dengan form Create Tello lama) + member yang sudah ada
	private function member_candidates($task)
	{
		$list = array();
		$level_jabatan = (int) $this->session->userdata('level_jabatan');
		// sendto() hanya menangani level jabatan 1-6
		if ($level_jabatan >= 1 && $level_jabatan <= 6) {
			$this->load->model('m_task');
			foreach ($this->m_task->sendto($level_jabatan, $this->session->userdata('bagian')) as $u) {
				$list[$u->nip] = array(
					'nama'    => $u->nama,
					'jabatan' => isset($u->nama_jabatan) ? (string) $u->nama_jabatan : '',
				);
			}
		}
		$current = $this->split_nip($task->member);
		if ($current) {
			foreach ($this->db->select('nip, nama')->where_in('nip', $current)->get('users')->result() as $u) {
				if (!isset($list[$u->nip])) {
					$list[$u->nip] = array('nama' => $u->nama, 'jabatan' => '');
				}
			}
		}
		return $list;
	}

	// Anggota project (member + PIC) untuk pilihan responsible
	private function project_members($task)
	{
		$nips = $this->split_nip($task->member);
		$nips[] = $task->pic;
		$nips = array_values(array_unique(array_filter($nips)));
		$members = array();
		if ($nips) {
			$users = $this->db->select('nip, nama')->where_in('nip', $nips)->get('users')->result();
			foreach ($users as $u) {
				$members[$u->nip] = $u->nama;
			}
		}
		return $members;
	}

	private function split_nip($str)
	{
		return array_values(array_filter(array_map('trim', explode(';', (string) $str))));
	}

	private function valid_activity($v)
	{
		return in_array((string) $v, array('1', '2', '3'), true) ? (string) $v : '1';
	}

	private function valid_date($v)
	{
		$v = trim((string) $v);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
	}

	// Sama seperti status_activity: project ikut Closed bila semua card Closed
	private function sync_task_status($id_task)
	{
		$row = $this->db->query(
			"SELECT COUNT(id_detail) AS total, SUM(CASE WHEN activity = '3' THEN 1 ELSE 0 END) AS closed FROM task_detail WHERE id_task = ?",
			array($id_task)
		)->row();
		if ($row->total > 0 && $row->total == $row->closed) {
			$this->db->where('id', $id_task)->update('task', array('activity' => '3'));
		}
	}

	// Notifikasi WA ke member project saat status card berubah (pesan sama dengan status_activity)
	private function notify_status($card, $task, $activity)
	{
		$utility = $this->db->get_where('utility', array('Id' => 1))->row_array();
		if (!$utility || $utility['notif_wa'] != 1) {
			return;
		}
		$label = array('1' => 'Open', '2' => 'Pending', '3' => 'Close');
		$nama_session = $this->session->userdata('nama');
		$msg = "There's a new comment\nCard Name : *{$card->task_name}*\nStatus : *{$label[$activity]}*\n\nChange from :  *$nama_session*\n\n" . base_url();
		foreach ($this->split_nip($task->member) as $nip) {
			$user = $this->db->get_where('users', array('nip' => $nip))->row_array();
			if ($user && !empty($user['phone'])) {
				$this->api_whatsapp->wa_notif($msg, $user['phone']);
			}
		}
	}

	// Upload file dari input attach[] ke upload/task_comment (maks. total 5 MB, sama dengan activity_comment).
	// $namer($nama_asli, $index) menentukan nama file di server. Mengembalikan [['file' => ..., 'name' => ...]]
	private function upload_files($namer)
	{
		$files = !empty($_FILES['attach']['name']) ? (array) $_FILES['attach']['name'] : array();
		if (!count(array_filter($files))) {
			return array();
		}
		if (array_sum((array) $_FILES['attach']['size']) > 5 * 1024 * 1024) {
			$this->json_error('Total ukuran file tidak boleh lebih dari 5 MB.');
		}

		// Folder upload/task_comment tidak ikut di repo (git tidak menyimpan folder kosong), jadi buat bila belum ada
		$dir = FCPATH . 'upload/task_comment/';
		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
			$this->json_error('Folder upload/task_comment belum ada dan tidak bisa dibuat. Buat folder itu di server dan pastikan bisa ditulis.');
		}
		if (!is_writable($dir)) {
			$this->json_error('Folder upload/task_comment tidak bisa ditulis. Periksa izin (permission) folder di server.');
		}

		$this->load->library('upload');
		$names = array();
		$uploaded = array();
		foreach ($files as $i => $orig) {
			if ($orig == '') {
				continue;
			}
			$_FILES['file_temp'] = array(
				'name'     => $_FILES['attach']['name'][$i],
				'type'     => $_FILES['attach']['type'][$i],
				'tmp_name' => $_FILES['attach']['tmp_name'][$i],
				'error'    => $_FILES['attach']['error'][$i],
				'size'     => $_FILES['attach']['size'][$i],
			);
			$this->upload->initialize(array(
				'upload_path'   => $dir,
				'allowed_types' => '*',
				'max_size'      => 5120,
				'file_name'     => $namer($orig, $i),
				'encrypt_name'  => false,
			));
			if (!$this->upload->do_upload('file_temp')) {
				// batalkan semua bila satu file gagal
				foreach ($uploaded as $path) {
					if (file_exists($path)) {
						unlink($path);
					}
				}
				$this->json_error('Upload gagal: ' . strip_tags($this->upload->display_errors()));
			}
			$data = $this->upload->data();
			$uploaded[] = $data['full_path'];
			$names[] = array('file' => $data['file_name'], 'name' => $orig);
		}
		return $names;
	}

	private function file_url($file)
	{
		return base_url('upload/task_comment/' . rawurlencode($file));
	}

	private function is_image($file)
	{
		return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'), true);
	}

	private function file_payload($file, $name)
	{
		return array('file' => $file, 'name' => $name, 'url' => $this->file_url($file), 'is_image' => $this->is_image($file));
	}

	private function comment_payload($c)
	{
		$files = array_values(array_filter(explode(';', (string) (isset($c->attachment) ? $c->attachment : ''))));
		$labels = explode(';', (string) (isset($c->attachment_name) ? $c->attachment_name : ''));
		$attachments = array();
		foreach ($files as $i => $f) {
			$attachments[] = $this->file_payload($f, !empty($labels[$i]) ? $labels[$i] : $f);
		}
		return array(
			'nama'         => $c->nama ? $c->nama : $c->member,
			'mine'         => $c->member == $this->session->userdata('nip'),
			'text'         => (string) $c->comment_member,
			'date_created' => $c->date_created,
			'attachments'  => $attachments,
		);
	}

	// Notifikasi WA komentar ke member project (pesan sama dengan activity_comment)
	private function notify_comment($card, $task, $comment)
	{
		$utility = $this->db->get_where('utility', array('Id' => 1))->row_array();
		if (!$utility || $utility['notif_wa'] != 1) {
			return;
		}
		$nama_session = $this->session->userdata('nama');
		$msg = "There's a new comment\nCard Name : *{$card->task_name}*\nComment : *$comment*\n\nComment from :  *$nama_session*\n\n" . base_url();
		foreach ($this->split_nip($task->member) as $nip) {
			$user = $this->db->get_where('users', array('nip' => $nip))->row_array();
			if ($user && !empty($user['phone'])) {
				$this->api_whatsapp->wa_notif($msg, $user['phone']);
			}
		}
	}

	private function mark_read($table, $key, $id, $read)
	{
		$nip = $this->session->userdata('nip');
		if (stripos((string) $read, (string) $nip) === false) {
			$this->db->where($key, $id)->update($table, array('read' => trim($read . ' ' . $nip)));
		}
	}

	private function set_nav_counts(&$data)
	{
		$nip = $this->session->userdata('nip');
		$data['count_inbox'] = $this->db->query(
			"SELECT COUNT(Id) AS jml FROM memo WHERE (nip_kpd LIKE ? OR nip_cc LIKE ?) AND (`read` NOT LIKE ?)",
			array("%$nip%", "%$nip%", "%$nip%")
		)->row()->jml;
		$data['count_inbox2'] = $this->db->query(
			"SELECT COUNT(id) AS jml FROM task WHERE (`member` LIKE ? OR `pic` LIKE ?) AND activity = '1'",
			array("%$nip%", "%$nip%")
		)->row()->jml;
	}

	private function json_ok($payload = array())
	{
		$this->output->set_content_type('application/json')
			->set_output(json_encode(array_merge(array('ok' => true), $payload)))
			->_display();
		exit;
	}

	private function json_error($message, $code = 400)
	{
		$this->output->set_status_header($code)
			->set_content_type('application/json')
			->set_output(json_encode(array('ok' => false, 'message' => $message)))
			->_display();
		exit;
	}
}
