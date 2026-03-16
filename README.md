# panel-monitoring-user

Panel sederhana untuk monitoring user Mikrotik (Hotspot / PPPoE).

Fungsi utama:

- melihat daftar user
- melihat expiration user
- enable / disable user
- extend masa aktif user
- tambah user baru

Panel dibuat untuk kebutuhan monitoring jaringan RT/RW Net atau ISP kecil.

---

## Syarat Server

Server Linux (Ubuntu / Debian / VPS)

Minimal:

- RAM 512MB
- PHP 7.4+
- Nginx / Apache
- Git

Install paket:

sudo apt update
sudo apt install git nginx php php-fpm php-curl -y

---

## Install Panel

Clone repo:

git clone https://github.com/nekomaa110-gif/panel-monitoring-user.git

Masuk folder:

cd panel-monitoring-user

Pindahkan ke web server:

sudo mv panel-monitoring-user /var/www/

Set permission:

sudo chown -R www-data:www-data /var/www/panel-monitoring-user

---

## Konfigurasi Mikrotik

Edit file config.

Biasanya di:

config/mikrotik.php

Contoh:

$ip = "192.168.88.1";
$user = "admin";
$pass = "password";
$port = "8728";

---

## Setting Mikrotik

Aktifkan API:

/ip service enable api

atau cek:

/ip service print

Pastikan server boleh akses router.

---

## Akses Panel

Buka di browser:

http://IP-SERVER/panel-monitoring-user

Login menggunakan user panel.

---

## Cocok Untuk

- RT/RW Net
- Monitoring user hotspot
- Monitoring user PPPoE
- Operator jaringan kecil

---

## Author

Zein - ZeroNet
