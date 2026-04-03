# TODO - Implement Edit Profile

- [x] Update `profile.php`
  - [x] Tambah alert sukses setelah redirect `updated=1`
  - [x] Tambah tombol Edit per row dengan `urlencode(groupname)`
  - [x] Pastikan flow tambah/hapus existing tetap berjalan

- [x] Buat `edit_profile.php`
  - [x] Validasi parameter GET `name`
  - [x] Load atribut dari `radgroupcheck` & `radgroupreply` dengan prepared statement
  - [x] Tampilkan form dinamis (attribute/op readonly, value editable)
  - [x] Proses submit UPDATE per attribute menggunakan prepared statement (tanpa delete+insert)
  - [x] Validasi profile tidak ditemukan
  - [x] Redirect ke `profile.php?updated=1` setelah sukses

- [x] Verifikasi akhir struktur dan keamanan dasar (SQL injection)
