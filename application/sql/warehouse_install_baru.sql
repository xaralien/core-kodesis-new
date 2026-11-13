-- =====================================================================
--  INSTALASI BARU: referensi & setting warehouse (Outgoing + Incoming)
--  Untuk database corebank yang BELUM punya tabel all_setting, all_ref,
--  all_pay_methode, all_bank.
--
--  Syarat: tabel out_pesawat dan in_pesawat sudah ada (kolom
--  coa_pendapatan akan ditambahkan, out_pesawat juga kolom pembagi).
--
--  !! SESUAIKAN DULU nilai yang khusus warehouse BDT sebelum dijalankan
--     di warehouse lain (atau ubah nanti lewat menu Setting / Bank /
--     Referensi Kode):
--       - origin & prefix (kosong = ambil dari tabel utility),
--         branch_code_csd 'CORP_03', prefix_csd 'BDT.CSD.', prefix_csd_lama,
--         prefix_do 'BDT.', kop_gdg_khusus 'LJAWH', in_prefix_sj 'HPLI-'
--       - semua nomor COA (11505, 41002, 41003, 41008-41014, 21040, 13010, ...)
--       - daftar bank (12001 BCA EKS, 12002 BNI BDT, 12004 BNI MBZ)
--       - daftar warehouse DO (bdl, ardhya, jas)
--       - agent_deposit_khusus '4' (uid di all_agent_deposit)
--
--  Aman dijalankan ulang, KECUALI baris ALTER TABLE (coa_pendapatan, pembagi):
--  jika error "Duplicate column name", kolom sudah ada, lanjutkan saja
--  baris sesudahnya.
-- =====================================================================

-- #####################################################################
--  BAGIAN 1 - SETTING & REFERENSI BERSAMA + OUTGOING
-- #####################################################################

-- ---------------------------------------------------------------------
-- A. Setting key-value
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS all_setting (
  uid        INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kunci      VARCHAR(50)  NOT NULL UNIQUE,
  nilai      VARCHAR(255) NOT NULL,
  keterangan VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO all_setting (kunci, nilai, keterangan) VALUES
-- Tarif & pajak
('ppn_rate',                 '0.11',      'PPN invoice outgoing (11%)'),
('pph23_rate',               '0.02',      'PPh 23 (2%)'),
('min_sewa_gudang',          '25000',     'Minimum sewa gudang per invoice / SMU'),
('biaya_adm_1',              '20000',     'Biaya administrasi jika adm = 1'),
('biaya_adm_0',              '3000',      'Biaya administrasi jika adm != 1'),
('cdc_rate',                 '0',         'Cargo Development Charge (x total sewa gudang) jika cdc = 1'),
('limit_saldo_deposit',      '5000000',   'Batas peringatan saldo deposit agen'),
-- COA jurnal
('coa_piutang',              '11505',     'Pendapatan yang akan diterima (debit saat cetak, kredit saat bayar)'),
('coa_pph23',                '99002',     'Akun PPh 23'),
('coa_pendapatan_default',   '41002',     'Pendapatan jika maskapai belum punya coa_pendapatan'),
('coa_pendapatan_khusus',    '41012',     'Pendapatan invoice khusus'),
('coa_deposit_khusus',       '21040',     'Debit pembayaran invoice khusus (deposit)'),
('agent_deposit_khusus',     '4',         'UID all_agent_deposit untuk invoice khusus'),
-- Daftar COA dropdown (pisahkan dengan koma, dicari LIKE prefix)
('coa_list_debit_depo',      '210',       'Daftar invoice: COA debit deposit'),
('coa_list_kredit_depo',     '41002',     'Daftar invoice: COA kredit deposit'),
('coa_list_debit_transfer',  '12002,12001', 'Daftar invoice: COA debit transfer'),
('coa_list_kredit_transfer', '41002,13010', 'Daftar invoice: COA kredit transfer'),
('coa_list_debit_depo_khusus',      '21040',       'Invoice khusus: COA debit deposit'),
('coa_list_kredit_depo_khusus',     '41001',       'Invoice khusus: COA kredit deposit'),
('coa_list_debit_transfer_khusus',  '12002,12001', 'Invoice khusus: COA debit transfer'),
('coa_list_kredit_transfer_khusus', '41001,13010', 'Invoice khusus: COA kredit transfer'),
-- Penomoran
('origin',                   '',          'Kode origin warehouse, contoh HLP. Kosong = ambil dari tabel utility'),
('prefix',                   '',          'Prefix nomor BTB, contoh HLPO. Kosong = ambil dari tabel utility'),
('branch_code_csd',          'CORP_03',   'Branch code CSD actual (ra_csd)'),
('prefix_csd',               'BDT.CSD.',  'Prefix nomor CSD actual'),
('prefix_csd_lama',          'BDT.CSD.01.', 'Prefix nomor CSD (out_csd)'),
('prefix_do',                'BDT.',      'Prefix nomor DO / CH'),
('out_prefix_sj',            '',          'Outgoing: prefix nomor invoice, contoh HLP.OUT-JASTER- (nomor = prefix + nomor urut). Kosong = <origin>.OUT-JASTER-'),
('kop_gdg_khusus',           'LJAWH',     'Kop nomor invoice khusus'),
('tgl_template_khusus',      '20261001000000', 'Mulai tanggal ini invoice khusus pakai template print_invoice_khusus'),
-- Pembagi volume
('pembagi_default',          '6000',      'Pembagi volume bawaan jika maskapai belum punya pembagi (P x L x T / pembagi)')
ON DUPLICATE KEY UPDATE kunci = kunci;

-- ---------------------------------------------------------------------
-- B. Referensi kode -> label
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS all_ref (
  uid    INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  grup   VARCHAR(40)  NOT NULL,
  kode   VARCHAR(20)  NOT NULL,
  label  VARCHAR(100) NOT NULL,
  urutan INT          NOT NULL DEFAULT 0,
  tampil_form CHAR(1) NOT NULL DEFAULT '1',  -- 1 = muncul sebagai pilihan di form input
  hold   CHAR(1)      NOT NULL DEFAULT '0',
  UNIQUE KEY uk_all_ref_grup_kode (grup, kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO all_ref (grup, kode, label, urutan, tampil_form) VALUES
-- Kategori SMU (out_list.catg_smu). 4 & 5 hanya label, tidak muncul di form kemasan
('catg_smu', '1', 'Langsung (Direct)',             1, '1'),
('catg_smu', '2', 'Angkut Lanjut (Transhipment)',  2, '1'),
('catg_smu', '3', 'Terminal Change (w/o Invoice)', 3, '1'),
('catg_smu', '4', 'Via RA LJA',                    4, '0'),
('catg_smu', '5', 'Langsung (AAP)',                5, '0'),
-- Jenis barang (out_list.jns_barang)
('jns_barang', '1', 'Langsung (Direct)',  1, '1'),
('jns_barang', '2', 'Sebagian (Partial)', 2, '1'),
-- Jenis billing (out_bill_catg.jenis_billing)
('jenis_billing', '0', 'UMUM',    1, '1'),
('jenis_billing', '1', 'TRANSIT', 2, '1'),
-- CSD: status keamanan (1 = SPX, 2 = SCO)
('status_keamanan', '1', 'Passenger Aircraft (SPX)',  1, '1'),
('status_keamanan', '2', 'Cargo Aircraft Only (SCO)', 2, '1'),
-- CSD: metode pemeriksaan utama
('metode_periksa', '1', 'XRAY LINE 1', 1, '1'),
('metode_periksa', '2', 'XRAY LINE 2', 2, '1'),
('metode_periksa', '3', 'ETD',         3, '1'),
-- CSD: metode pemeriksaan opsional
('metode_opsional', '1', 'PERIKSA FISIK', 1, '1'),
('metode_opsional', '2', 'ETD',           2, '1'),
-- CSD: alasan diterbitkan (form CSD Actual hanya menawarkan CLEAR)
('alasan', '1', 'CLEAR',  1, '1'),
('alasan', '2', 'REJECT', 2, '0'),
-- Warehouse DO (out_ch.wh_name)
('warehouse', 'bdl',    'BANGUN DESA TEKNOLOGI', 1, '1'),
('warehouse', 'ardhya', 'ARDHYA BUMI PERSADA',   2, '1'),
('warehouse', 'jas',    'JASA ANGKASA SEMESTA',  3, '1')
ON DUPLICATE KEY UPDATE label = VALUES(label), urutan = VALUES(urutan), tampil_form = VALUES(tampil_form);

-- ---------------------------------------------------------------------
-- C. Metode pembayaran
--    nama  : dipakai di keterangan jurnal ("METODE : DEPOSIT")
--    label : dipakai di tampilan & Excel
--    coa_debit NULL = diambil dari bank (transfer) / agen deposit
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS all_pay_methode (
  uid       INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kode      VARCHAR(5)  NOT NULL UNIQUE,
  nama      VARCHAR(50) NOT NULL,
  label     VARCHAR(50) NOT NULL,
  coa_debit VARCHAR(20) NULL,
  tampil_ubah  CHAR(1) NOT NULL DEFAULT '1',  -- muncul di form Ubah/Cetak Invoice
  tampil_bayar CHAR(1) NOT NULL DEFAULT '1',  -- muncul di form Bayar Invoice
  hold      CHAR(1)     NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO all_pay_methode (kode, nama, label, coa_debit, tampil_ubah, tampil_bayar) VALUES
('1', 'DEPOSIT',  'Deposit',  NULL,    '1', '1'),
('2', 'CASH',     'Cash',     NULL,    '1', '1'),
('3', 'TRANSFER', 'Transfer', NULL,    '1', '1'),
('4', 'TAGIHAN',  'Tagihan',  '13010', '1', '1'),
('5', 'FOC',      'FOC',      NULL,    '1', '0'),
('6', 'QRIS',     'QRIS',     '12001', '0', '1')
ON DUPLICATE KEY UPDATE kode = kode;

-- ---------------------------------------------------------------------
-- D. Bank tujuan transfer
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS all_bank (
  uid    INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  coa    VARCHAR(20) NOT NULL UNIQUE,
  nama   VARCHAR(50) NOT NULL,
  urutan INT         NOT NULL DEFAULT 0,   -- urutan teratas = pilihan default di form
  hold   CHAR(1)     NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO all_bank (coa, nama, urutan) VALUES
('12001', 'BCA EKS', 1),
('12002', 'BNI BDT', 2),
('12004', 'BNI MBZ', 3)
ON DUPLICATE KEY UPDATE coa = coa;

-- ---------------------------------------------------------------------
-- E. COA pendapatan per maskapai
--    (jalankan ALTER sekali saja; abaikan error jika kolom sudah ada)
-- ---------------------------------------------------------------------
ALTER TABLE out_pesawat ADD COLUMN coa_pendapatan VARCHAR(20) NULL;

-- Pembagi volume per maskapai (NULL = pakai setting pembagi_default)
ALTER TABLE out_pesawat ADD COLUMN pembagi INT NULL;
UPDATE out_pesawat SET pembagi = 5000 WHERE UPPER(TRIM(nama)) = 'BATIK' AND pembagi IS NULL;

UPDATE out_pesawat SET coa_pendapatan = '41010' WHERE UPPER(TRIM(nama)) = 'BATIK'    AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE out_pesawat SET coa_pendapatan = '41008' WHERE UPPER(TRIM(nama)) = 'CITILINK' AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE out_pesawat SET coa_pendapatan = '41013' WHERE UPPER(TRIM(nama)) = 'FLYJAYA'  AND (coa_pendapatan IS NULL OR coa_pendapatan = '');

-- #####################################################################
--  BAGIAN 2 - KHUSUS INCOMING
-- #####################################################################

-- ---------------------------------------------------------------------
-- 1. Setting khusus incoming (kunci in_*). Tidak menimpa nilai yang
--    sudah diubah.
--    Setting umum dipakai bersama outgoing: ppn_rate, min_sewa_gudang,
--    biaya_adm_1 (20.000), biaya_adm_0 (3.000), cdc_rate, coa_piutang,
--    limit_saldo_deposit.
-- ---------------------------------------------------------------------
INSERT INTO all_setting (kunci, nilai, keterangan) VALUES
('in_surcharge_dg_rate',        '600',         'Incoming: surcharge Dangerous Goods per kg chargeable'),
('in_coa_pendapatan_default',   '41003',       'Incoming: COA pendapatan jika maskapai belum punya coa_pendapatan'),
('in_kode_invoice',             '.IN.',        'Incoming: sisipan nomor invoice: <origin><in_kode_invoice><bulan tahun><nomor>'),
('in_prefix_sj',                'HPLI-',       'Incoming: prefix nomor surat jalan di cetakan invoice'),
('in_coa_list_debit_depo',      '210',         'Incoming daftar invoice: COA debit deposit'),
('in_coa_list_kredit_depo',     '41003',       'Incoming daftar invoice: COA kredit deposit'),
('in_coa_list_debit_transfer',  '12002,12001', 'Incoming daftar invoice: COA debit transfer'),
('in_coa_list_kredit_transfer', '41003',       'Incoming daftar invoice: COA kredit transfer')
ON DUPLICATE KEY UPDATE kunci = kunci;

-- ---------------------------------------------------------------------
-- 2. COA pendapatan per maskapai incoming.
--    Jika ALTER error "Duplicate column name", kolom sudah ada: lewati.
--    Nama dicocokkan tanpa membedakan huruf besar/kecil.
-- ---------------------------------------------------------------------
ALTER TABLE in_pesawat ADD COLUMN coa_pendapatan VARCHAR(20) NULL;

UPDATE in_pesawat SET coa_pendapatan = '41011' WHERE UPPER(TRIM(nama)) = 'BATIK'    AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE in_pesawat SET coa_pendapatan = '41009' WHERE UPPER(TRIM(nama)) = 'CITILINK' AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE in_pesawat SET coa_pendapatan = '41014' WHERE UPPER(TRIM(nama)) = 'FLYJAYA'  AND (coa_pendapatan IS NULL OR coa_pendapatan = '');

-- ---------------------------------------------------------------------
-- 3. Cek: maskapai yang belum punya COA akan memakai 41003
-- ---------------------------------------------------------------------
SELECT uid, nama, coa_pendapatan FROM in_pesawat ORDER BY nama;
