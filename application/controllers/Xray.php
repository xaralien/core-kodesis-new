<?php if (!defined('BASEPATH')) exit('Hacking Attempt. Keluar dari sistem.');

/**
 * Sistem Capture X-Ray
 *
 * Mengambil gambar dari mesin X-Ray melalui capture card HDMI -> USB/Type-C
 * (contoh: Vention). Capture card terbaca sebagai webcam (UVC) di browser,
 * frame diambil di sisi client lalu dikirim ke sini untuk disimpan.
 */
class Xray extends CI_Controller
{
    const UPLOAD_DIR = 'uploads/xray/';
    const MAX_SIZE   = 15728640; // 15 MB

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('session'));
        $this->load->helper('url');
        $this->load->model('m_login');
        $this->load->model('M_xray', 'xray');
        $this->load->database();
    }

    private function _cek_login($ajax = false)
    {
        if ($this->session->userdata('isLogin') == FALSE) {
            if ($ajax) {
                $this->_json(array('status' => false, 'message' => 'Sesi habis, silakan login ulang.'), 401);
            }
            redirect('login/login_form');
        }
    }

    private function _json($data, $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data))
            ->_display();
        exit;
    }

    public function index()
    {
        $this->_cek_login();
        $data['pengguna'] = $this->m_login->dataPengguna($this->session->userdata('username'));
        $this->load->view('xray_capture', $data);
    }

    /**
     * POST multipart: image (file jpg/png) + metadata
     */
    public function save()
    {
        $this->_cek_login(true);

        if ($this->input->method() !== 'post') {
            $this->_json(array('status' => false, 'message' => 'Method tidak diizinkan'), 405);
        }

        $no_ref = trim((string) $this->input->post('no_ref', true));
        if ($no_ref === '') {
            $this->_json(array('status' => false, 'message' => 'No. SMU / referensi wajib diisi'), 422);
        }

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->_json(array('status' => false, 'message' => 'Gambar tidak diterima'), 422);
        }

        $tmp  = $_FILES['image']['tmp_name'];
        $size = (int) $_FILES['image']['size'];
        if ($size <= 0 || $size > self::MAX_SIZE) {
            $this->_json(array('status' => false, 'message' => 'Ukuran gambar tidak valid'), 422);
        }

        // Validasi isi file benar-benar gambar
        $info = @getimagesize($tmp);
        $allowed = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
        if ($info === false || !isset($allowed[$info[2]])) {
            $this->_json(array('status' => false, 'message' => 'Format gambar harus JPG/PNG'), 422);
        }
        $ext = $allowed[$info[2]];

        $sub_dir = self::UPLOAD_DIR . date('Y/m/');
        $abs_dir = FCPATH . $sub_dir;
        if (!is_dir($abs_dir) && !mkdir($abs_dir, 0755, true)) {
            $this->_json(array('status' => false, 'message' => 'Folder upload tidak bisa dibuat'), 500);
        }

        $safe_ref  = preg_replace('/[^A-Za-z0-9\-]/', '', $no_ref);
        $file_name = 'XRAY_' . $safe_ref . '_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6) . '.' . $ext;

        if (!move_uploaded_file($tmp, $abs_dir . $file_name)) {
            $this->_json(array('status' => false, 'message' => 'Gagal menyimpan file'), 500);
        }

        $hasil = strtoupper((string) $this->input->post('hasil', true));
        if (!in_array($hasil, array('AMAN', 'PERLU_CEK', 'DITOLAK'), true)) {
            $hasil = 'AMAN';
        }

        $koli  = $this->input->post('jumlah_koli', true);
        $berat = $this->input->post('berat', true);

        $id = $this->xray->insert(array(
            'no_ref'      => $no_ref,
            'nama_agent'  => $this->input->post('nama_agent', true),
            'jumlah_koli' => is_numeric($koli) ? (int) $koli : null,
            'berat'       => is_numeric($berat) ? (float) $berat : null,
            'hasil'       => $hasil,
            'keterangan'  => $this->input->post('keterangan', true),
            'file_path'   => $sub_dir . $file_name,
            'file_size'   => $size,
            'width'       => (int) $info[0],
            'height'      => (int) $info[1],
            'device_name' => substr((string) $this->input->post('device_name', true), 0, 150),
            'nip'         => $this->session->userdata('nip'),
            'petugas'     => $this->session->userdata('nama'),
            'id_cabang'   => $this->session->userdata('kode_cabang'),
            'created_at'  => date('Y-m-d H:i:s'),
        ));

        $this->_json(array(
            'status'  => true,
            'message' => 'Gambar X-Ray tersimpan',
            'data'    => array('id' => $id, 'url' => base_url($sub_dir . $file_name)),
        ));
    }

    public function list_data()
    {
        $this->_cek_login(true);

        $keyword   = trim((string) $this->input->get('q', true));
        $tgl_awal  = $this->_tanggal($this->input->get('dari', true));
        $tgl_akhir = $this->_tanggal($this->input->get('sampai', true));
        $page      = max(1, (int) $this->input->get('page'));
        $limit     = 24;

        $rows = $this->xray->search($keyword, $tgl_awal, $tgl_akhir, $limit, ($page - 1) * $limit);
        foreach ($rows as $r) {
            $r->url = base_url($r->file_path);
        }

        $this->_json(array(
            'status' => true,
            'total'  => $this->xray->count($keyword, $tgl_awal, $tgl_akhir),
            'page'   => $page,
            'limit'  => $limit,
            'data'   => $rows,
        ));
    }

    public function delete($id = 0)
    {
        $this->_cek_login(true);
        if ($this->input->method() !== 'post') {
            $this->_json(array('status' => false, 'message' => 'Method tidak diizinkan'), 405);
        }
        $ok = $this->xray->soft_delete($id);
        $this->_json(array('status' => $ok, 'message' => $ok ? 'Data dihapus' : 'Data tidak ditemukan'));
    }

    public function download($id = 0)
    {
        $this->_cek_login();
        $row = $this->xray->get($id);
        if (!$row || !is_file(FCPATH . $row->file_path)) {
            show_404();
        }
        $this->load->helper('download');
        force_download(basename($row->file_path), file_get_contents(FCPATH . $row->file_path));
    }

    private function _tanggal($v)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : '';
    }
}
