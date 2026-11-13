<?php
// Master: all_pay_methode (level 905) - metode pembayaran & COA debit jurnal
$cfg = [
    'judul'     => 'Metode Pembayaran',
    'store_url' => 'outgoinghlp/store_pay_methode',
    'info'      => '<b>Nama</b> dipakai di keterangan jurnal (METODE : ...), <b>Label</b> dipakai di tampilan & Excel. '
        . '<b>COA Debit</b> dipakai saat bayar invoice; kosongkan untuk Deposit (COA dari agen deposit) dan Transfer (COA dari bank). '
        . 'Kode 1 (Deposit) dan 3 (Transfer) punya alur khusus di form, jangan diganti artinya.',
    'kolom'     => [
        ['label' => 'Kode',      'field' => 'kode'],
        ['label' => 'Nama',      'field' => 'nama'],
        ['label' => 'Label',     'field' => 'label'],
        ['label' => 'COA Debit', 'field' => 'coa_debit'],
        ['label' => 'Form Ubah Invoice', 'field' => 'tampil_ubah', 'type' => 'flag'],
        ['label' => 'Form Bayar',        'field' => 'tampil_bayar', 'type' => 'flag'],
        ['label' => 'Status', 'field' => 'hold', 'type' => 'flag', 'ya' => 'Hold', 'tidak' => 'Ready', 'warna_ya' => 'danger', 'warna_tidak' => 'success'],
    ],
    'fields'    => [
        ['name' => 'kode',  'label' => 'Kode', 'key' => true, 'required' => true, 'placeholder' => 'contoh: 7', 'help' => 'Angka. Tidak bisa diubah setelah disimpan.'],
        ['name' => 'nama',  'label' => 'Nama (jurnal)', 'required' => true, 'placeholder' => 'contoh: QRIS'],
        ['name' => 'label', 'label' => 'Label (tampilan)', 'required' => true, 'placeholder' => 'contoh: QRIS'],
        ['name' => 'coa_debit', 'label' => 'COA Debit', 'placeholder' => 'contoh: 12001'],
        ['name' => 'tampil_ubah',  'label' => 'Tampil di Form Ubah Invoice', 'type' => 'select', 'options' => ['1' => 'Ya', '0' => 'Tidak'], 'default' => '1'],
        ['name' => 'tampil_bayar', 'label' => 'Tampil di Form Bayar', 'type' => 'select', 'options' => ['1' => 'Ya', '0' => 'Tidak'], 'default' => '1'],
        ['name' => 'hold', 'label' => 'Status', 'type' => 'select', 'options' => ['0' => 'Ready', '1' => 'Hold'], 'default' => '0'],
    ],
];
include __DIR__ . '/master_warehouse_template.php';
