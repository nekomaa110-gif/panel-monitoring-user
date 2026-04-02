# TODO - Perbaikan bug pelanggan, login log, dan aktif koneksi

- [x] Analisis kebutuhan bug:
  - Password hasil generate voucher tidak tampil di halaman pelanggan
  - Log menunjukkan login sukses tetapi halaman login panel gagal (beda domain autentikasi)
  - Tombol aktifkan user tidak mengembalikan profile asli user

- [x] Update `voucher.php`:
  - Sinkron generate voucher ke `radcheck` (`Cleartext-Password`)
  - Tetap sinkron assignment `radusergroup` sesuai profile

- [x] Update `actions/disable.php`:
  - Simpan profile lama user sebelum disable (metadata di tabel `voucher.status`)
  - Tetapkan group disabled seperti saat ini

- [x] Update `actions/enable.php`:
  - Restore profile lama user saat enable (jangan hardcode `Radius-Member`)
  - Gunakan fallback aman jika metadata profile tidak ditemukan

- [x] Update `log.php`:
  - Perjelas bahwa log adalah autentikasi RADIUS user, bukan login admin panel

- [x] Validasi cepat sintaks PHP file yang diubah
- [x] Ringkasan perubahan

- [x] Update query users.php: fallback password dari voucher
- [x] Update tampilan kolom password agar tampil '-' jika kosong
- [x] Verifikasi perubahan
