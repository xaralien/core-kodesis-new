<?php
// Master: all_ref (level 909) - label untuk kode (catg_smu, metode pemeriksaan, warehouse, dll.)
$cfg = [
    'judul'     => 'Referensi Kode Outgoing',
    'store_url' => 'outgoinghlp/store_ref',
    'info'      => 'Label di sini tampil di tabel, form pilihan dan cetakan. '
        . '<b>Tampil di Form = Tidak</b> artinya kode hanya dipakai sebagai label (tidak bisa dipilih di form input). '
        . '<b>Hold</b> menyembunyikan kode sepenuhnya. Grup yang dipakai aplikasi: '
        . 'catg_smu, jns_barang, jenis_billing, status_keamanan, metode_periksa, metode_opsional, alasan, warehouse.',
    'kolom'     => [
        ['label' => 'Grup',   'field' => 'grup'],
        ['label' => 'Kode',   'field' => 'kode'],
        ['label' => 'Label',  'field' => 'label'],
        ['label' => 'Urutan', 'field' => 'urutan'],
        ['label' => 'Tampil di Form', 'field' => 'tampil_form', 'type' => 'flag'],
        ['label' => 'Status', 'field' => 'hold', 'type' => 'flag', 'ya' => 'Hold', 'tidak' => 'Ready', 'warna_ya' => 'danger', 'warna_tidak' => 'success'],
    ],
    'fields'    => [
        ['name' => 'grup',  'label' => 'Grup', 'key' => true, 'required' => true, 'placeholder' => 'contoh: catg_smu'],
        ['name' => 'kode',  'label' => 'Kode', 'key' => true, 'required' => true, 'placeholder' => 'contoh: 1', 'help' => 'Grup & Kode tidak bisa diubah setelah disimpan, karena sudah tersimpan di data transaksi.'],
        ['name' => 'label', 'label' => 'Label', 'required' => true],
        ['name' => 'urutan', 'label' => 'Urutan', 'type' => 'number', 'step' => '1', 'default' => '0'],
        ['name' => 'tampil_form', 'label' => 'Tampil di Form', 'type' => 'select', 'options' => ['1' => 'Ya', '0' => 'Tidak'], 'default' => '1'],
        ['name' => 'hold', 'label' => 'Status', 'type' => 'select', 'options' => ['0' => 'Ready', '1' => 'Hold'], 'default' => '0'],
    ],
];
include __DIR__ . '/master_warehouse_template.php';
