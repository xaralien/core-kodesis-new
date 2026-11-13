-- =====================================================================
--  UPGRADE untuk database yang SUDAH menjalankan SQL referensi versi pertama.
--  Tabel sudah harus bernama all_* (jalankan warehouse_rename_tabel.sql dulu).
--  (sebelumnya: outgoing_ref_setting.sql
--  versi pertama (sebelum ada kolom tampil_form / tampil_ubah /
--  tampil_bayar / urutan bank).
--
--  Jalankan di database CORE BANK. Jika ada ALTER yang error
--  "Duplicate column name", artinya kolom sudah ada: lewati baris itu
--  dan lanjutkan baris berikutnya.
-- =====================================================================

-- 1. Kolom baru
ALTER TABLE all_pay_methode ADD COLUMN label        VARCHAR(50) NOT NULL DEFAULT '' AFTER nama;
ALTER TABLE all_ref         ADD COLUMN tampil_form  CHAR(1) NOT NULL DEFAULT '1' AFTER urutan;
ALTER TABLE all_pay_methode ADD COLUMN tampil_ubah  CHAR(1) NOT NULL DEFAULT '1' AFTER coa_debit;
ALTER TABLE all_pay_methode ADD COLUMN tampil_bayar CHAR(1) NOT NULL DEFAULT '1' AFTER tampil_ubah;
ALTER TABLE all_bank        ADD COLUMN urutan       INT     NOT NULL DEFAULT 0   AFTER nama;

-- 2. Data referensi baru / label yang disesuaikan dengan form
INSERT INTO all_ref (grup, kode, label, urutan, tampil_form) VALUES
('jns_barang', '1', 'Langsung (Direct)',  1, '1'),
('jns_barang', '2', 'Sebagian (Partial)', 2, '1')
ON DUPLICATE KEY UPDATE label = VALUES(label);

UPDATE all_ref SET label = 'Angkut Lanjut (Transhipment)' WHERE grup = 'catg_smu' AND kode = '2';
UPDATE all_ref SET tampil_form = '0' WHERE grup = 'catg_smu' AND kode IN ('4', '5');
UPDATE all_ref SET tampil_form = '0' WHERE grup = 'alasan'   AND kode = '2';

-- 3. Metode bayar: label tampilan, FOC hanya di form ubah, QRIS hanya di form bayar
UPDATE all_pay_methode SET label = 'Deposit'  WHERE kode = '1' AND label = '';
UPDATE all_pay_methode SET label = 'Cash'     WHERE kode = '2' AND label = '';
UPDATE all_pay_methode SET label = 'Transfer' WHERE kode = '3' AND label = '';
UPDATE all_pay_methode SET label = 'Tagihan'  WHERE kode = '4' AND label = '';
UPDATE all_pay_methode SET label = 'FOC'      WHERE kode = '5' AND label = '';
UPDATE all_pay_methode SET label = 'QRIS'     WHERE kode = '6' AND label = '';
UPDATE all_pay_methode SET tampil_bayar = '0' WHERE kode = '5';
UPDATE all_pay_methode SET tampil_ubah  = '0' WHERE kode = '6';

-- 4. Urutan bank (urutan terkecil = default di form bayar)
UPDATE all_bank SET urutan = 1 WHERE coa = '12001';
UPDATE all_bank SET urutan = 2 WHERE coa = '12002';
UPDATE all_bank SET urutan = 3 WHERE coa = '12004';

-- 5. Cek kolom COA pesawat (dari SQL versi pertama). Jika error
--    "Duplicate column name", berarti sudah ada: abaikan.
ALTER TABLE out_pesawat ADD COLUMN coa_pendapatan VARCHAR(20) NULL;
UPDATE out_pesawat SET coa_pendapatan = '41010' WHERE nama = 'BATIK'    AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE out_pesawat SET coa_pendapatan = '41008' WHERE nama = 'CITILINK' AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE out_pesawat SET coa_pendapatan = '41013' WHERE nama = 'FLYJAYA'  AND (coa_pendapatan IS NULL OR coa_pendapatan = '');

-- 6. Pastikan setting yang dipakai sudah ada (tidak menimpa nilai yang sudah diubah)
INSERT INTO all_setting (kunci, nilai, keterangan) VALUES
('cdc_rate',                        '0',            'Cargo Development Charge (x total sewa gudang) jika cdc = 1'),
('coa_list_debit_depo',             '210',          'Daftar invoice: COA debit deposit'),
('coa_list_kredit_depo',            '41002',        'Daftar invoice: COA kredit deposit'),
('coa_list_debit_transfer',         '12002,12001',  'Daftar invoice: COA debit transfer'),
('coa_list_kredit_transfer',        '41002,13010',  'Daftar invoice: COA kredit transfer'),
('coa_list_debit_depo_khusus',      '21040',        'Invoice khusus: COA debit deposit'),
('coa_list_kredit_depo_khusus',     '41001',        'Invoice khusus: COA kredit deposit'),
('coa_list_debit_transfer_khusus',  '12002,12001',  'Invoice khusus: COA debit transfer'),
('coa_list_kredit_transfer_khusus', '41001,13010',  'Invoice khusus: COA kredit transfer'),
('prefix_csd_lama',                 'BDT.CSD.01.',  'Prefix nomor CSD (out_csd)'),
('out_prefix_sj',                   '',             'Outgoing: prefix nomor invoice, contoh HLP.OUT-JASTER-. Kosong = <origin>.OUT-JASTER-')
ON DUPLICATE KEY UPDATE kunci = kunci;
