<?php
// Master: all_setting (level 905) - tarif, pajak, COA tetap, prefix nomor, batas nominal
$cfg = [
    'judul'     => 'Setting Outgoing',
    'store_url' => 'outgoinghlp/store_setting',
    'info'      => 'Nilai di sini dipakai langsung oleh perhitungan invoice dan jurnal. '
        . 'Tarif ditulis desimal (contoh PPN 11% = <b>0.11</b>). '
        . 'Kunci <b>coa_*</b> harus nomor COA yang terdaftar; kunci <b>coa_list_*</b> berisi beberapa kode dipisah koma.',
    'kolom'     => [
        ['label' => 'Kunci',      'field' => 'kunci'],
        ['label' => 'Nilai',      'field' => 'nilai'],
        ['label' => 'Keterangan', 'field' => 'keterangan'],
    ],
    'fields'    => [
        ['name' => 'kunci', 'label' => 'Kunci', 'key' => true, 'required' => true, 'placeholder' => 'contoh: ppn_rate', 'help' => 'Huruf kecil, angka dan garis bawah. Tidak bisa diubah setelah disimpan.'],
        ['name' => 'nilai', 'label' => 'Nilai', 'required' => true, 'placeholder' => 'contoh: 0.11'],
        ['name' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea'],
    ],
];
include __DIR__ . '/master_warehouse_template.php';
