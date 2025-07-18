# CMS Manajemen Media & Videotron

<p align="center"\>
Aplikasi web untuk mengelola konten, jadwal, dan pemantauan jaringan videotron (digital signage).


<p align="center">
    <a href="https://laravel.com" target="_blank">
    <img src="https://img.shields.io/badge/Laravel-v12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Logo"></a>
    <img src="https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge\&logo=vue.js" alt="Vue.js 3"\>
    <img src="https://img.shields.io/badge/Inertia.js-8A2BE2?style=for-the-badge" alt="Inertia.js"\>
    <img src="https://img.shields.io/badge/Tailwind\_CSS-38B2AC?style=for-the-badge\&logo=tailwind-css" alt="Tailwind CSS"\>
</p\>

## 📄 Tentang Proyek

Aplikasi ini adalah **Content Management System (CMS)** yang dirancang khusus untuk mengelola konten pada jaringan videotron atau layar *digital signage*. Sistem ini memungkinkan administrator untuk mengunggah aset media, membuat jadwal pemutaran yang kompleks, mengelola perangkat videotron dari jarak jauh, dan memantau status pemutaran secara *real-time*.

Dibangun dengan **Laravel** untuk backend yang tangguh dan **Vue.js** dengan **Inertia.js** untuk antarmuka pengguna yang cepat dan reaktif seperti aplikasi *single-page*.

-----

## ✨ Fitur Utama

  - **Manajemen Videotron**: CRUD untuk perangkat videotron, termasuk informasi nama, ID perangkat, lokasi, resolusi, dan status (Aktif, Nonaktif, Perawatan).
  - **Pustaka Media**: Mengunggah, mengelola, dan menyetujui aset media seperti video (MP4) dan gambar (JPG, PNG).
  - **Manajemen Playlist**: Membuat dan mengelola playlist musik latar (MP3) yang dapat dihubungkan ke videotron.
  - **Pembuat Jadwal (Schedule Builder)**: Antarmuka visual berbasis kalender untuk membuat dan mengedit jadwal pemutaran harian.
      - Mendukung *drag-and-drop* untuk mengatur urutan item.
      - Fitur "Salin Jadwal" untuk menduplikasi jadwal ke tanggal lain.
      - Validasi otomatis untuk mencegah jadwal tumpang tindih (*overlapping*).
  - **Manajemen Pengguna & Peran**: Sistem hak akses berbasis peran (Admin, Content Manager, Client) menggunakan `spatie/laravel-permission`.
  - **Pemantauan & Laporan**: Memantau status konektivitas perangkat dan menghasilkan laporan pemutaran konten (*playlog*).

-----

## 💻 Tumpukan Teknologi (Tech Stack)

  - **Backend**: Laravel 11
  - **Frontend**: Vue.js 3, Inertia.js, Tailwind CSS
  - **Database**: MySQL / MariaDB
  - **Server**: Nginx / Apache
  - **Fitur Utama Laravel**: Eloquent ORM, Sanctum, Spatie Laravel Permission, Carbon.

-----

## 🚀 Instalasi & Pengaturan Lokal

Berikut adalah langkah-langkah untuk menjalankan proyek ini di lingkungan pengembangan lokal.

1.  **Clone repository ini:**

    ```bash
    git clone [URL_REPOSITORY_ANDA]
    cd [NAMA_FOLDER_PROYEK]
    ```

2.  **Install dependensi PHP (Composer):**

    ```bash
    composer install
    ```

3.  **Install dependensi JavaScript (NPM):**

    ```bash
    npm install
    ```

4.  **Buat file `.env`:**
    Salin dari file contoh.

    ```bash
    cp .env.example .env
    ```

5.  **Generate kunci aplikasi:**

    ```bash
    php artisan key:generate
    ```

6.  **Konfigurasi file `.env`:**
    Buka file `.env` dan atur koneksi database Anda (DB\_DATABASE, DB\_USERNAME, DB\_PASSWORD) dan variabel lain yang diperlukan seperti `APP_URL`.

7.  **Jalankan migrasi database:**
    Perintah ini akan membuat semua tabel yang dibutuhkan di database Anda.

    ```bash
    php artisan migrate
    ```

8.  **(Opsional) Jalankan Seeder:**
    Jika ada, seeder akan mengisi database dengan data awal (misalnya, peran dan pengguna admin).

    ```bash
    php artisan db:seed
    ```

9.  **Buat Symbolic Link untuk Storage:**
    Ini penting agar file yang diunggah dapat diakses secara publik.

    ```bash
    php artisan storage:link
    ```

10. **Jalankan server pengembangan:**
    Perintah ini akan menjalankan server Vite (untuk frontend) dan server PHP (untuk backend) secara bersamaan.

    ```bash
    npm run dev
    ```

11. **Buka aplikasi:**
    Akses aplikasi melalui `http://127.0.0.1:8000` atau URL yang ditampilkan di terminal Anda.

-----

## 🔧 Penggunaan

  - **Admin Panel**: Akses panel admin melalui `/admin/login`. Gunakan akun yang dibuat oleh seeder atau yang Anda daftarkan dengan peran yang sesuai.
  - **Player Endpoint**: Perangkat videotron (player) akan berinteraksi dengan endpoint API yang tersedia untuk otentikasi, mengambil jadwal, dan mengirimkan log pemutaran.

-----

## 🤝 Berkontribusi

Terima kasih telah mempertimbangkan untuk berkontribusi\! Saat ini, proyek ini dikelola secara internal. Namun, saran dan laporan bug sangat kami hargai.

-----

## 📜 Lisensi

Proyek ini menggunakan lisensi [MIT license](https://opensource.org/licenses/MIT).
