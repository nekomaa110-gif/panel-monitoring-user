- [x] Refactor fungsi `indoDate()` di `log.php` agar pakai format `date("d M Y H:i:s", strtotime($waktu))`
- [x] Tambahkan fallback `"-"` jika waktu NULL/kosong/invalid
- [x] Validasi konsistensi output format waktu di semua row

- [ ] Tambah fitur show/hide password di `users.php` seperti `voucher.php`
  - [x] Ubah cell password jadi elemen `.password-cell` dengan `data-masked` dan `data-plain`
  - [x] Tambah switch `#toggle-password` di area atas tabel pelanggan
  - [x] Tambah JavaScript toggle untuk menampilkan password asli / bintang
  - [ ] Verifikasi struktur halaman tetap rapi
