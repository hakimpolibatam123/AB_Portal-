
## Dokumen acuan (baca sebelum mengerjakan fitur)
- docs/prd-product.md: kebutuhan produk dan aturan bisnis

Jika ada konflik antar dokumen, PRD produk menang untuk aturan bisnis,
PRD teknis menang untuk cara implementasi. Tanyakan jika masih ragu.

## Aturan utama
- PHP native (tanpa framework), MySQL lewat PDO prepared statements saja
- Hanya satu admin: tanpa peran, tanpa manajemen user, tanpa halaman Users
- Semua teks UI berbahasa Indonesia
- Ikuti struktur folder yang sudah ada; tambah folder baru seminimal mungkin
- Kerjakan satu milestone per sesi sesuai roadmap; jangan melompat
- Jangan mengubah file di luar cakupan tugas

## Referensi UI
Desain halaman ada di `docs/DESIGN/rs-awal-bros-portal/` (HTML statis, 1 file = 1 halaman).
Lihat `docs/DESIGN/rs-awal-bros-portal/CLAUDE.md` untuk peta halaman dan alurnya.
Saat mengimplementasi tampilan, ikuti markup, class, dan token warna (`--rs-*`) dari file tersebut.