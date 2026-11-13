<?php
// Master: out_bank (level 905) - bank tujuan transfer
$cfg = [
    'judul'     => 'Bank Tujuan Transfer',
    'store_url' => 'outgoinghlp/store_bank',
    'info'      => 'COA bank menjadi akun debit saat invoice dibayar dengan metode Transfer. '
        . 'Bank dengan <b>urutan</b> terkecil menjadi pilihan default di form bayar.',
    'kolom'     => [
        ['label' => 'COA',    'field' => 'coa'],
        ['label' => 'Nama Bank', 'field' => 'nama'],
        ['label' => 'Urutan', 'field' => 'urutan'],
        ['label' => 'Status', 'field' => 'hold', 'type' => 'flag', 'ya' => 'Hold', 'tidak' => 'Ready', 'warna_ya' => 'danger', 'warna_tidak' => 'success'],
    ],
    'fields'    => [
        ['name' => 'coa',  'label' => 'COA Bank', 'required' => true, 'placeholder' => 'contoh: 12004', 'help' => 'Harus terdaftar di daftar COA.'],
        ['name' => 'nama', 'label' => 'Nama Bank', 'required' => true, 'placeholder' => 'contoh: BNI MBZ'],
        ['name' => 'urutan', 'label' => 'Urutan', 'type' => 'number', 'step' => '1', 'default' => '0'],
        ['name' => 'hold', 'label' => 'Status', 'type' => 'select', 'options' => ['0' => 'Ready', '1' => 'Hold'], 'default' => '0'],
    ],
];
include __DIR__ . '/master_outgoing_template.php';
