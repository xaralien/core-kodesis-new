<?php
// Master: in_pesawat (level 919). Kolom COA Pendapatan hanya untuk level 915.
$kolom = [
    ['label' => 'Nama',   'field' => 'nama'],
    ['label' => 'Prefix', 'field' => 'prefix'],
    ['label' => 'Warna',  'field' => 'warna', 'type' => 'color'],
];
$fields = [
    ['name' => 'nama',   'label' => 'Nama Pesawat', 'key' => true, 'required' => true, 'placeholder' => 'contoh: CITILINK', 'help' => 'Tidak bisa diubah setelah disimpan, karena nama dipakai di data SMU.'],
    ['name' => 'prefix', 'label' => 'Prefix SMU', 'placeholder' => 'contoh: 888'],
    ['name' => 'warna',  'label' => 'Warna Badge', 'type' => 'color'],
];
if (!empty($boleh_coa)) {
    $kolom[]  = ['label' => 'COA Pendapatan', 'field' => 'coa_pendapatan'];
    $fields[] = ['name' => 'coa_pendapatan', 'label' => 'COA Pendapatan', 'placeholder' => 'contoh: 41009', 'help' => 'Akun kredit saat invoice incoming dicetak. Kosong = memakai setting in_coa_pendapatan_default.'];
}

$cfg = [
    'judul'     => 'Pesawat Incoming',
    'store_url' => 'incominghlp/store_pesawat',
    'info'      => !empty($boleh_coa) ? '' : 'Kolom COA pendapatan hanya dapat diatur oleh bagian keuangan.',
    'kolom'     => $kolom,
    'fields'    => $fields,
];
include __DIR__ . '/master_outgoing_template.php';
