-- =====================================================================
--  PREFIX NOMOR INVOICE OUTGOING: kode_invoice -> out_prefix_sj
--
--  Sebelumnya : nomor invoice = <origin> + kode_invoice + nomor
--               (HLP + .OUT-JASTER- + 000123)
--  Sekarang   : nomor invoice = out_prefix_sj + nomor
--               (HLP.OUT-JASTER- + 000123)
--
--  Untuk database yang SUDAH menjalankan SQL warehouse sebelumnya.
--  Jalankan di database CORE BANK.
-- =====================================================================

-- 1. Buat out_prefix_sj dari nilai lama (origin + kode_invoice).
--    Jika origin di all_setting masih kosong (masih dari tabel utility),
--    nilai dibiarkan kosong: aplikasi memakai <origin dari utility>.OUT-JASTER-
INSERT INTO all_setting (kunci, nilai, keterangan)
SELECT 'out_prefix_sj',
       IF(TRIM(COALESCE(o.nilai, '')) = '', '', CONCAT(TRIM(o.nilai), COALESCE(k.nilai, '.OUT-JASTER-'))),
       'Outgoing: prefix nomor invoice, contoh HLP.OUT-JASTER- (nomor = prefix + nomor urut). Kosong = <origin>.OUT-JASTER-'
FROM (SELECT 1) x
LEFT JOIN all_setting o ON o.kunci = 'origin'
LEFT JOIN all_setting k ON k.kunci = 'kode_invoice'
ON DUPLICATE KEY UPDATE kunci = all_setting.kunci;

-- 2. kode_invoice tidak dipakai lagi
DELETE FROM all_setting WHERE kunci = 'kode_invoice';

-- 3. Isi / cek nilainya. Jika masih kosong, isi sesuai warehouse ini, contoh:
--    UPDATE all_setting SET nilai = 'HLP.OUT-JASTER-' WHERE kunci = 'out_prefix_sj';
SELECT kunci, nilai FROM all_setting WHERE kunci IN ('origin', 'out_prefix_sj', 'kode_invoice');
