# AB Portal Hub

Frontend portal IT RS Awal Bros Botania. Root project adalah folder ini. Struktur mengikuti `architecture.md`: PHP native, controller, view, router, konfigurasi menu, dan koneksi PDO yang dibuat hanya ketika diperlukan.

Panduan untuk rekan yang melakukan clone dan menambah halaman: [Design System](design-system.md). Gunakan dokumen tersebut sebagai acuan ukuran, komponen, serta pola penambahan controller, route, dan view.

## Menjalankan dashboard

Jalankan dari root project:

```powershell
npm run dev
```

Buka http://127.0.0.1:8080. PHP 8.3 atau lebih baru dapat menjalankan frontend; target produksi tetap PHP 8.4 sesuai arsitektur. MySQL dan Composer belum diperlukan untuk melihat dashboard.

Perintah `npm run dev` menjalankan server PHP lokal. Hentikan dengan Ctrl+C. Untuk menjalankan tanpa npm, gunakan `php -S 127.0.0.1:8080 -t public public/index.php`.

CSS Tailwind dan font sudah tersedia di `public/assets/`, sehingga portal tidak bergantung pada CDN maupun Node.js saat dijalankan. IBM Plex Sans dan IBM Plex Mono (woff2, subset latin dan latin-ext) disajikan secara lokal beserta lisensinya.

## Mengembangkan tampilan

```powershell
npm ci
npm run build
```

Gunakan `npm run watch` selama mengubah view. Node.js hanya dipakai untuk membangun CSS. Sumber CSS berada di `resources/css/app.css`, hasil build di `public/assets/css/app.css`. `package-lock.json` mengunci dependensi frontend. Panduan CLI: https://tailwindcss.com/docs/installation/tailwind-cli.

## Halaman

Tampilan mengikuti desain statis di `docs/DESIGN/rs-awal-bros-portal/` (file desain tidak diubah). Semua data masih dummy.

| Route | Controller | View |
| --- | --- | --- |
| `/login` | `AuthController::showLogin` | `auth/login.php` (layout `auth`) |
| `/` | `HomeController::index` | `home/index.php` (portal) |
| `/password`, `/users` | `AccountController` | `account/password.php`, `account/users.php` |
| `/form/suhu`, `/rekap/suhu` | `SuhuController` | `suhu/form.php`, `suhu/rekap.php` |
| `/form/cctv`, `/rekap/cctv` | `CctvController` | `cctv/form.php`, `cctv/rekap.php` |
| `/form/cleardesk`, `/rekap/cleardesk` | `ClearDeskController` | `cleardesk/form.php`, `cleardesk/rekap.php` |

Setiap form dikirim (POST) ke route rekap modulnya, lalu diarahkan kembali ke halaman rekap dengan notifikasi. Belum ada data yang disimpan. Filter dan pencarian rekap berjalan di browser (`public/assets/js/app.js`). Tombol Export dan Cetak masih berupa tampilan.

## Lokasi kode

| Lokasi | Fungsi |
| --- | --- |
| `public/index.php` | Front controller dan router untuk server lokal |
| `bootstrap/app.php` | Autoload, `.env`, dan zona waktu |
| `routes/web.php` | Route aplikasi |
| `app/Controllers/` | Controller per halaman; `Controller.php` berisi penjaga `APP_ENV` |
| `app/Views/layouts/main.php` | Layout bersama: navbar (jam WIB), breadcrumb, footer, toast |
| `app/Views/components/` | Partial: navbar, breadcrumb, footer, page-header, card, badge, button, field, table, baris tabel (`rows/`), dialog kirim, toast |
| `app/Helpers/functions.php` | `e()`, `partial()`, `icon()`, `url()`, `asset_v()`, `dummy()` |
| `app/Helpers/icons.php` | Ikon Lucide (ISC) yang dipakai desain |
| `config/portal.php` | Kartu portal per bagian |
| `config/dashboard.php` | Profil akun dan progres harian awal |
| `database/dummy/` | Data dummy modul suhu, CCTV, dan Clear Desk |
| `resources/css/app.css` | Token desain (`@theme`), gaya dasar, dan komponen bersama |
| `public/assets/js/app.js`, `login.js` | Perilaku halaman (jam, menu, toast, form, filter rekap, login) |
| `config/database.php`, `app/Core/Database.php` | Koneksi MySQL (PDO) |

## Batas pengembangan backend

Autentikasi Delight PHP-Auth, middleware, pengelolaan pengguna, database operasional, migrasi, formulir resmi, dan perhitungan progres adalah tahap berikutnya. Tidak ada SQL migrasi asumsi atau autentikasi tiruan yang dipasang. `composer.json` menyiapkan autoload PSR-4; tambahkan dependensi auth dan kunci `composer.lock` ketika implementasi backend dimulai.

Dashboard saat ini dijalankan pada `APP_ENV=local`, yang menjadi default pengembangan. Pada environment selain local, semua halaman mengembalikan HTTP 503 sampai autentikasi diimplementasikan. Penggantian istilah di antarmuka tidak mengubah pembatasan akses ini.

Pada Apache, arahkan document root ke `public/` dan aktifkan `mod_rewrite`. `APP_URL` dapat diatur menjadi origin portal tanpa trailing slash. `.env` di root project dibaca otomatis oleh `bootstrap/app.php`; variabel environment server tetap diutamakan. Struktur saat ini menggunakan path absolut dari origin dan ditujukan untuk root domain atau virtual host, bukan subfolder URL.

## Database

1. Salin `.env.example` menjadi `.env`, lalu isi koneksi MySQL lokal. `.env` tidak boleh di-commit.
2. Aktifkan `extension=pdo_mysql` pada `php.ini`.
3. Buat tabel dengan `php bin/migrate.php` (atau `composer db:migrate`), atau impor `database/schema.sql` melalui phpMyAdmin. Skrip aman dijalankan berulang.
4. Periksa koneksi dan daftar tabel dengan `php bin/db-check.php` (atau `composer db:check`).

Koneksi dibuat melalui `App\Core\Database::connection()` dengan zona waktu sesi `+07:00`.

## Verifikasi

Lihat `docs/verification.md` untuk hasil pemeriksaan dan batas pemeriksaan visual pada sesi implementasi.
