<?php
// Master: out_pesawat (level 909). Kolom COA Pendapatan hanya untuk level 905.
$kolom = [
    ['label' => 'Nama',   'field' => 'nama'],
    ['label' => 'Prefix', 'field' => 'prefix'],
    ['label' => 'Warna',  'field' => 'warna', 'type' => 'color'],
    ['label' => 'Jaster', 'field' => 'jaster', 'type' => 'flag'],
    ['label' => 'Pembagi Volume', 'field' => 'pembagi'],
];
$fields = [
    ['name' => 'nama',   'label' => 'Nama Pesawat', 'key' => true, 'required' => true, 'placeholder' => 'contoh: CITILINK', 'help' => 'Tidak bisa diubah setelah disimpan, karena nama dipakai di data SMU.'],
    ['name' => 'prefix', 'label' => 'Prefix SMU', 'placeholder' => 'contoh: 888'],
    ['name' => 'warna',  'label' => 'Warna Badge', 'type' => 'color'],
    ['name' => 'jaster', 'label' => 'Jaster', 'type' => 'select', 'options' => ['0' => 'Tidak', '1' => 'Ya'], 'default' => '0'],
    ['name' => 'pembagi', 'label' => 'Pembagi Volume', 'type' => 'number', 'step' => '1',
        'placeholder' => 'kosong = ' . ($pembagi_default ?? 6000),
        'help' => 'Volume = P x L x T (cm) / pembagi. Kosongkan untuk memakai pembagi default (' . ($pembagi_default ?? 6000) . ', setting <b>pembagi_default</b>).'],
];
if (!empty($boleh_coa)) {
    $kolom[]  = ['label' => 'COA Pendapatan', 'field' => 'coa_pendapatan'];
    $fields[] = ['name' => 'coa_pendapatan', 'label' => 'COA Pendapatan', 'placeholder' => 'contoh: 41008', 'help' => 'Akun kredit saat invoice dicetak. Kosong = memakai setting coa_pendapatan_default.'];
}
$kolom[]  = ['label' => 'Status', 'field' => 'hold', 'type' => 'flag', 'ya' => 'Hold', 'tidak' => 'Ready', 'warna_ya' => 'danger', 'warna_tidak' => 'success'];
$fields[] = ['name' => 'hold', 'label' => 'Status', 'type' => 'select', 'options' => ['0' => 'Ready', '1' => 'Hold'], 'default' => '0'];

$cfg = [
    'judul'     => 'Daftar Pesawat',
    'store_url' => 'outgoinghlp/store_pesawat',
    'info'      => 'Maskapai yang di-<b>Hold</b> tidak muncul di pilihan pesawat pada form SMU. '
        . '<b>Pembagi Volume</b> dipakai saat menghitung volume dimensi di Kemasan SMU; kolom kosong berarti memakai pembagi default (' . ($pembagi_default ?? 6000) . ').'
        . (!empty($boleh_coa) ? '' : ' Kolom COA pendapatan hanya dapat diatur oleh bagian keuangan.'),
    'kolom'     => $kolom,
    'fields'    => $fields,
];
include __DIR__ . '/master_warehouse_template.php';
