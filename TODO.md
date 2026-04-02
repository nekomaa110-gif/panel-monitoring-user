# TODO - Sinkron paket voucher dengan profil DB

- [x] Analisis kebutuhan:
  - Sesuaikan paket "5 jam" dan "mingguan"
  - Ubah nama option di voucher.php agar sinkron dengan nama profil di DB
- [x] Update `voucher.php`:
  - Ambil daftar profil dari DB (radgroupcheck/radgroupreply)
  - Buat mapping normalisasi untuk pencocokan:
    - "5jam" -> "5 jam"
    - "7hari" -> "mingguan"
  - Ganti option hardcoded dengan option dinamis dari profil DB
  - Pastikan proses generate tetap bisa assign `radusergroup` sesuai profil terpilih
- [x] Validasi cepat sintaks PHP file yang diubah
- [x] Ringkasan perubahan
