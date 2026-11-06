# Link Audit

Link Audit memeriksa status HTTP dan redirect dari sebuah halaman publik beserta tautan yang ditemukan di dalamnya. Pengguna dapat melihat hasil berhasil, HTTP bermasalah, kegagalan koneksi, redirect, dan tujuan yang diblokir, lalu memfilter serta mengekspor laporan CSV.

Riwayat audit tersimpan per akun. Pemindaian dibatasi pada halaman awal dan tautannya, dengan batas jumlah URL, jumlah permintaan, ukuran respons, dan waktu. Setiap tujuan redirect diperiksa ulang; alamat lokal, privat, dan reserved tidak dihubungi. Sistem tidak menjalankan JavaScript atau membaca halaman yang memerlukan login.

