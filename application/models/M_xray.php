<?php if (!defined('BASEPATH')) exit('Hacking Attempt. Keluar dari sistem.');

class M_xray extends CI_Model
{
    private $table = 'xray_capture';

    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function get($id)
    {
        return $this->db->get_where($this->table, array('id' => (int) $id, 'is_deleted' => 0))->row();
    }

    public function search($keyword = '', $tgl_awal = '', $tgl_akhir = '', $limit = 50, $offset = 0)
    {
        $this->_filter($keyword, $tgl_awal, $tgl_akhir);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get($this->table, (int) $limit, (int) $offset)->result();
    }

    public function count($keyword = '', $tgl_awal = '', $tgl_akhir = '')
    {
        $this->_filter($keyword, $tgl_awal, $tgl_akhir);
        return $this->db->count_all_results($this->table);
    }

    public function soft_delete($id)
    {
        $this->db->where('id', (int) $id)->update($this->table, array('is_deleted' => 1));
        return $this->db->affected_rows() > 0;
    }

    private function _filter($keyword, $tgl_awal, $tgl_akhir)
    {
        $this->db->where('is_deleted', 0);
        if ($keyword !== '') {
            $this->db->group_start()
                ->like('no_ref', $keyword)
                ->or_like('nama_agent', $keyword)
                ->or_like('petugas', $keyword)
                ->or_like('keterangan', $keyword)
                ->group_end();
        }
        if ($tgl_awal !== '') {
            $this->db->where('created_at >=', $tgl_awal . ' 00:00:00');
        }
        if ($tgl_akhir !== '') {
            $this->db->where('created_at <=', $tgl_akhir . ' 23:59:59');
        }
    }
}
