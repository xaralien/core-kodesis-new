<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Referensi & setting modul Outgoing.
 *
 * Semua nilai yang sebelumnya ditulis langsung (hardcode) di controller
 * Outgoinghlp (tarif, PPN/PPh, COA, prefix nomor, label kode, dll.)
 * diambil dari tabel di database corebank:
 *   - out_setting      : key-value (tarif, COA tetap, prefix, batas nominal)
 *   - out_ref          : kode -> label per grup (catg_smu, metode_periksa, ...)
 *   - out_pay_methode  : metode pembayaran + COA debit
 *   - out_bank         : COA bank -> nama bank
 *   - out_pesawat.coa_pendapatan : COA pendapatan per maskapai
 *
 * Lihat: application/sql/outgoing_ref_setting.sql
 */
class M_outgoing_ref extends CI_Model
{
	private $cb;
	private $setting     = null;
	private $ref         = [];
	private $pay_methode = null;
	private $bank        = null;
	private $coa_pesawat = [];

	public function __construct()
	{
		parent::__construct();
		$this->cb = $this->load->database('corebank', TRUE);
	}

	/**
	 * Ambil semua baris tabel (SELECT *) tanpa menghentikan halaman bila
	 * tabel / kolom belum ada (misal SQL migrasi belum dijalankan).
	 * Error dicatat ke log CodeIgniter, hasilnya array kosong.
	 */
	private function ambil($table)
	{
		$debug = $this->cb->db_debug;
		$this->cb->db_debug = FALSE;
		$query = $this->cb->get($table);
		$this->cb->db_debug = $debug;

		if (!$query) {
			$err = $this->cb->error();
			log_message('error', 'M_outgoing_ref: gagal membaca ' . $table . ' - ' . ($err['message'] ?? ''));
			return [];
		}
		return $query->result_array();
	}

	/** Urutkan baris berdasarkan kolom (kolom yang tidak ada dianggap 0). */
	private function urutkan(array $rows, $kolom, $kolom2 = null)
	{
		usort($rows, function ($a, $b) use ($kolom, $kolom2) {
			$c = ((float) ($a[$kolom] ?? 0)) <=> ((float) ($b[$kolom] ?? 0));
			if ($c === 0 && $kolom2) {
				$c = strcmp((string) ($a[$kolom2] ?? ''), (string) ($b[$kolom2] ?? ''));
			}
			return $c;
		});
		return $rows;
	}

	// ------------------------------------------------------------------
	//  out_setting
	// ------------------------------------------------------------------

	/** Nilai mentah dari out_setting. $default dipakai bila kunci belum ada. */
	public function setting($kunci, $default = null)
	{
		if ($this->setting === null) {
			$this->setting = array_column($this->ambil('out_setting'), 'nilai', 'kunci');
		}
		return array_key_exists($kunci, $this->setting) ? $this->setting[$kunci] : $default;
	}

	/** Nilai angka dari out_setting (tarif, persen, batas nominal). */
	public function rate($kunci, $default = 0)
	{
		return (float) $this->setting($kunci, $default);
	}

	/** Daftar kode dipisah koma di out_setting -> array. */
	public function setting_list($kunci, $default = '')
	{
		$raw = (string) $this->setting($kunci, $default);
		return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
	}

	// ------------------------------------------------------------------
	//  out_ref
	// ------------------------------------------------------------------

	/**
	 * Semua kode => label pada satu grup (urut sesuai kolom urutan).
	 * $form_only = true : hanya yang tampil_form = 1 (untuk <option> di form input).
	 */
	public function list_ref($grup, $form_only = false)
	{
		if (!isset($this->ref[$grup])) {
			$rows = array_filter($this->ambil_ref(), function ($r) use ($grup) {
				return $r['grup'] === $grup && ($r['hold'] ?? '0') != '1';
			});
			$this->ref[$grup] = $this->urutkan($rows, 'urutan');
		}
		$rows = $this->ref[$grup];
		if ($form_only) {
			// Kolom tampil_form belum ada (migrasi lama) => anggap tampil
			$rows = array_filter($rows, function ($r) {
				return ($r['tampil_form'] ?? '1') == '1';
			});
		}
		return array_column($rows, 'label', 'kode');
	}

	/** Seluruh isi out_ref, dibaca sekali per request. */
	private $ref_all = null;
	private function ambil_ref()
	{
		if ($this->ref_all === null) {
			$this->ref_all = $this->ambil('out_ref');
		}
		return $this->ref_all;
	}

	/** Label untuk satu kode; $default bila kode kosong / tidak terdaftar. */
	public function label($grup, $kode, $default = '-')
	{
		$kode = (string) $kode;
		if ($kode === '') {
			return $default;
		}
		$list = $this->list_ref($grup);
		return $list[$kode] ?? $default;
	}

	// ------------------------------------------------------------------
	//  out_pay_methode
	// ------------------------------------------------------------------

	/** Semua metode bayar (termasuk yang di-hold): kode => row. */
	public function list_pay_methode()
	{
		if ($this->pay_methode === null) {
			$this->pay_methode = [];
			foreach ($this->urutkan($this->ambil('out_pay_methode'), 'kode') as $r) {
				// Tabel versi lama belum punya kolom label / tampil_*: isi nilai bawaan
				$nama = (string) ($r['nama'] ?? '');
				if (!isset($r['label']) || $r['label'] === '') {
					$r['label'] = strlen($nama) <= 4 ? $nama : ucfirst(strtolower($nama));
				}
				$r['tampil_ubah']  = $r['tampil_ubah']  ?? '1';
				$r['tampil_bayar'] = $r['tampil_bayar'] ?? '1';
				$r['hold']         = $r['hold']         ?? '0';
				$this->pay_methode[(string) $r['kode']] = (object) $r;
			}
		}
		return $this->pay_methode;
	}

	/**
	 * Pilihan metode bayar untuk <option>: kode => label.
	 * $kolom : null (semua aktif), 'tampil_ubah' atau 'tampil_bayar'.
	 */
	public function pay_options($kolom = null)
	{
		$out = [];
		foreach ($this->list_pay_methode() as $kode => $r) {
			if (($r->hold ?? '0') == '1') continue;
			if ($kolom && isset($r->$kolom) && $r->$kolom != '1') continue;
			$out[$kode] = $r->label;
		}
		return $out;
	}

	/** Satu metode bayar, null bila tidak terdaftar. */
	public function pay_methode($kode)
	{
		$list = $this->list_pay_methode();
		return $list[(string) $kode] ?? null;
	}

	/** Label tampilan metode bayar (Deposit, Cash, ...). */
	public function pay_label($kode, $default = '')
	{
		$pm = $this->pay_methode($kode);
		return $pm ? $pm->label : $default;
	}

	// ------------------------------------------------------------------
	//  out_bank
	// ------------------------------------------------------------------

	/** Semua bank (termasuk yang di-hold): coa => row, urut kolom urutan. */
	public function list_bank()
	{
		if ($this->bank === null) {
			$this->bank = [];
			foreach ($this->urutkan($this->ambil('out_bank'), 'urutan', 'coa') as $r) {
				$this->bank[(string) $r['coa']] = (object) $r;
			}
		}
		return $this->bank;
	}

	/** Satu bank berdasarkan COA, null bila tidak terdaftar. */
	public function bank($coa)
	{
		$list = $this->list_bank();
		return $list[(string) $coa] ?? null;
	}

	/** Pilihan bank aktif untuk <option>: coa => nama. */
	public function bank_options()
	{
		$out = [];
		foreach ($this->list_bank() as $coa => $r) {
			if (($r->hold ?? '0') == '1') continue;
			$out[$coa] = $r->nama;
		}
		return $out;
	}

	// ------------------------------------------------------------------
	//  out_pesawat.coa_pendapatan
	// ------------------------------------------------------------------

	/**
	 * COA pendapatan untuk maskapai.
	 * $tabel   : out_pesawat (outgoing) atau in_pesawat (incoming)
	 * $default : kunci out_setting untuk COA bila maskapai belum punya COA
	 */
	public function coa_pendapatan($pesawat, $tabel = 'out_pesawat', $default = 'coa_pendapatan_default')
	{
		if (!isset($this->coa_pesawat[$tabel])) {
			$this->coa_pesawat[$tabel] = [];
			$rows = $this->ambil($tabel);
			if ($rows && !array_key_exists('coa_pendapatan', $rows[0])) {
				log_message('error', 'M_outgoing_ref: kolom ' . $tabel . '.coa_pendapatan belum ada, semua maskapai memakai ' . $default . '. Jalankan SQL upgrade.');
			}
			foreach ($rows as $r) {
				$this->coa_pesawat[$tabel][strtoupper(trim((string) ($r['nama'] ?? '')))] = $r['coa_pendapatan'] ?? null;
			}
		}
		$coa = $this->coa_pesawat[$tabel][strtoupper(trim((string) $pesawat))] ?? null;
		$fallback = $tabel === 'in_pesawat' ? '41003' : '41002';
		return !empty($coa) ? $coa : $this->setting($default, $fallback);
	}

	// ------------------------------------------------------------------
	//  Helper non-database
	// ------------------------------------------------------------------

	/**
	 * Render <option> dari array kode => label.
	 * $selected : kode yang terpilih (opsional).
	 */
	public function options(array $list, $selected = null)
	{
		$html = '';
		foreach ($list as $kode => $label) {
			$sel   = ($selected !== null && (string) $selected === (string) $kode) ? ' selected' : '';
			$html .= '<option value="' . html_escape($kode) . '"' . $sel . '>' . html_escape($label) . "</option>\n";
		}
		return $html;
	}

	/** 1..12 -> I..XII (aturan baku, tidak perlu disimpan di database). */
	public function romawi($bulan)
	{
		$r = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
		return $r[(int) $bulan] ?? '';
	}
}
