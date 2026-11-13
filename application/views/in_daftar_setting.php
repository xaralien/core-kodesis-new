<?php
// Master: setting khusus incoming (kunci in_*), level 915
$cfg = [
    'judul'     => 'Setting Incoming',
    'store_url' => 'incominghlp/store_setting',
    'info'      => 'Halaman ini hanya menampilkan setting khusus incoming (kunci diawali <b>in_</b>). '
        . 'Setting umum yang dipakai bersama outgoing (PPN, minimum sewa gudang, biaya administrasi, COA piutang, '
        . 'batas saldo deposit, metode bayar, bank) diatur dari menu Outgoing oleh level 905.',
    'kolom'     => [
        ['label' => 'Kunci',      'field' => 'kunci'],
        ['label' => 'Nilai',      'field' => 'nilai'],
        ['label' => 'Keterangan', 'field' => 'keterangan'],
    ],
    'fields'    => [
        ['name' => 'kunci', 'label' => 'Kunci', 'key' => true, 'required' => true, 'placeholder' => 'contoh: in_surcharge_dg_rate', 'help' => 'Wajib diawali in_. Tidak bisa diubah setelah disimpan.'],
        ['name' => 'nilai', 'label' => 'Nilai', 'required' => true],
        ['name' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea'],
    ],
];
include __DIR__ . '/master_warehouse_template.php';
