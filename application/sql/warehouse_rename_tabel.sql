-- =====================================================================
--  GANTI NAMA TABEL REFERENSI BERSAMA (outgoing + incoming)
--  out_* -> all_*  (mengikuti konvensi all_agent_deposit, all_topup)
--
--  Jalankan SEKALI di database CORE BANK, SEBELUM memasang kode baru.
--  Data, uid, dan index ikut pindah; tidak ada data yang hilang.
--
--  Jika salah satu tabel lama tidak ada (error "doesn't exist"),
--  jalankan baris RENAME untuk tabel yang ada saja.
-- =====================================================================

RENAME TABLE
  out_setting     TO all_setting,
  out_ref         TO all_ref,
  out_pay_methode TO all_pay_methode,
  out_bank        TO all_bank;

-- Cek hasil
SHOW TABLES LIKE 'all\_%';
