# Antrian BKPSDM

Sistem informasi antrian dan layanan BKPSDM berbasis web yang dibangun dengan Laravel, Inertia, dan Vue. Aplikasi ini mendukung pengambilan antrian publik, buku tamu, monitoring pelayanan, laporan, dan pengelolaan data master dalam satu platform.

## Ringkasan

Proyek ini dirancang untuk membantu proses pelayanan agar lebih tertata, terpantau, dan mudah dioperasikan. Pengguna publik dapat mengambil antrian dari halaman depan, sementara petugas dapat memantau proses layanan melalui dashboard internal.

## Fitur Utama

- Pengambilan antrian untuk pengguna publik
- Buku tamu kiosk untuk pencatatan kunjungan
- Halaman monitor publik untuk menampilkan status antrian
- Dashboard internal untuk petugas
- Monitoring antrian: panggil, panggil ulang, mulai, selesai, dan lewati
- Manajemen data layanan
- Laporan antrian dengan ekspor PDF dan Excel
- Pengaturan dan utilitas sistem
- Autentikasi dan otorisasi berbasis middleware

## Tech Stack

| Layer | Teknologi |
| --- | --- |
| Backend | PHP 8.3, Laravel 13 |
| Frontend | Vue 3, Inertia.js |
| Styling | Tailwind CSS, @tailwindcss/forms |
| Build Tool | Vite |
| Database | MySQL, database-backed queue/session/cache |
| Auth | Laravel Breeze, Sanctum |
| Export | barryvdh/laravel-dompdf |
| Routing Helper | Ziggy |

## Prasyarat

- PHP 8.3 atau lebih baru
- Composer
- Node.js 18+ dan npm
- MySQL
- Web server lokal seperti Laragon, XAMPP, atau Laravel Sail sesuai kebutuhan

## Instalasi

1. Clone repository ini.
2. Jalankan instalasi dependency PHP dan JavaScript:

```bash
composer install
npm install
```

3. Salin file environment:

```bash
cp .env.example .env
```

4. Sesuaikan konfigurasi database di file `.env`.

5. Generate application key:

```bash
php artisan key:generate
```

6. Jalankan migrasi database:

```bash
php artisan migrate
```

7. Build asset frontend:

```bash
npm run build
```

Jika ingin menjalankan instalasi awal yang lebih cepat, proyek ini juga menyediakan:

```bash
composer run setup
```

## Akun Login Default

Setelah menjalankan seeder, tersedia akun bawaan berikut:

- Admin: `admin@bkpsdm.test`
- Operator: `operator@bkpsdm.test`
- Password: `password`

Jika muncul pesan `Kredensial yang dimasukkan tidak cocok dengan data kami.`, biasanya berarti email belum sesuai, password salah, atau data user belum di-seed ke database.

## Auto Login Trusted Server

Aplikasi ini juga mendukung auto-login saat host masuk ke daftar trusted server.

- Default host trusted: `desktop-904qfme`
- Email user trusted default: `admin@bkpsdm.test`

Pengaturannya bisa diubah lewat environment:

```env
TRUSTED_SERVER_HOSTS=desktop-904qfme
TRUSTED_SERVER_USER_EMAIL=admin@bkpsdm.test
```

Kalau ingin mematikan login manual sementara, tambahkan ini ke `.env`:

```env
AUTH_LOGIN_ENABLED=false
```

Balikkan ke `true` saat fitur login mau dipakai lagi.

Catatan: pada Windows PowerShell, perintah salin file environment dapat diganti dengan:

```bash
Copy-Item .env.example .env
```

## Menjalankan Project

### Mode development

Jalankan semua service penting sekaligus:

```bash
composer run dev
```

Perintah ini akan menjalankan server Laravel, queue listener, log viewer, dan Vite dev server.

### Jalankan manual

Jika ingin menjalankan per service:

```bash
php artisan serve
npm run dev
php artisan queue:listen
```

## Struktur Folder

```text
app/
  Http/
    Controllers/
    Middleware/
    Requests/
  Models/
  Providers/
  Support/
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
public/
resources/
  css/
  js/
    Components/
    composables/
    Layouts/
    Pages/
    utils/
  views/
routes/
storage/
```

## Alur Halaman

- `/` dan `/ambil-antrian` untuk halaman antrian publik
- `/buku-tamu` untuk kiosk buku tamu
- `/monitor-publik` untuk tampilan monitor publik
- `/dashboard` untuk dashboard internal
- `/antrian` untuk manajemen antrian
- `/monitoring` untuk operasional antrian
- `/laporan` untuk rekap dan ekspor laporan
- `/layanan` untuk data layanan
- `/pengaturan/update-server` untuk utilitas pembaruan sistem

## Konfigurasi Environment

Contoh konfigurasi utama di `.env`:

```env
APP_NAME="Antrian BKPSDM"
APP_URL=http://localhost/antrian-bkpsdm/public
APP_TIMEZONE=Asia/Makassar
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=antrian_bkpsdm
DB_USERNAME=root
DB_PASSWORD=
```

Pastikan database sudah dibuat sebelum menjalankan migrasi.

## Script Composer

- `composer run dev` menjalankan stack development lengkap
- `composer run test` membersihkan config dan menjalankan test
- `composer setup` menyiapkan instalasi awal proyek

## Testing

Jalankan test suite dengan:

```bash
composer run test
```

## Catatan Deploy

- Pastikan `APP_ENV=production` dan `APP_DEBUG=false` di server produksi
- Jalankan `php artisan migrate --force`
- Build asset frontend dengan `npm run build`
- Pastikan queue worker aktif jika fitur antrian dan proses latar belakang digunakan

## License

Proyek ini mengikuti lisensi MIT, kecuali ada ketentuan lain dari pemilik repository.
