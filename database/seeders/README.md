# Data awal menu CiksCoffee

`MenuCatalogSeeder` menyiapkan 6 kategori, 18 produk, 36 bahan baku,
foto hasil imagegen, dan takaran resep per porsi. Nama, harga jual, resep,
serta stok awal merupakan data contoh yang dapat diubah melalui dashboard.
Sistem bahan baku saat ini menyimpan satuan dan stok, belum biaya pembelian
per satuan; harga jual di bawah merupakan nilai awal, bukan perhitungan HPP.

| Kategori | Produk | Base / harga jual | Lite |
| --- | --- | ---: | ---: |
| Coffee | Kopi Susu Gula Aren | Rp24.000 | Rp21.000 |
| Coffee | Iced Americano | Rp18.000 | Rp15.000 |
| Coffee | Iced Caffe Latte | Rp22.000 | Rp19.000 |
| Meal | Nasi Goreng Ayam | Rp28.000 | — |
| Meal | Rice Bowl Ayam Teriyaki | Rp30.000 | — |
| Meal | Mie Goreng Ayam | Rp26.000 | — |
| Milk | Chocolate Milk | Rp20.000 | — |
| Milk | Strawberry Milk | Rp20.000 | — |
| Milk | Vanilla Milk | Rp18.000 | — |
| Snack | French Fries | Rp18.000 | — |
| Snack | Pisang Goreng Cokelat | Rp18.000 | — |
| Snack | Roti Bakar Cokelat Keju | Rp20.000 | — |
| Squash | Lemon Squash | Rp18.000 | — |
| Squash | Strawberry Squash | Rp20.000 | — |
| Squash | Lychee Squash | Rp20.000 | — |
| Tea | Original Iced Tea | Rp10.000 | — |
| Tea | Lemon Tea | Rp15.000 | — |
| Tea | Lychee Tea | Rp18.000 | — |

## Pemakaian

Setelah migrasi database selesai, jalankan seeder menu secara terpisah:

```bash
php artisan db:seed --class=MenuCatalogSeeder
php artisan storage:link
```

`DatabaseSeeder` juga memanggil seeder menu untuk inisialisasi lokal. Pada
produksi gunakan hanya `MenuCatalogSeeder`, sehingga tidak membuat akun demo.
Deployment CI/CD tetap tidak menjalankan seeder pada setiap push.

Pada VPS bersama, buat backup terlebih dahulu dan targetkan container CiksCoffee:

```bash
sudo docker exec cikscoffee-dery-app /usr/local/bin/cikscoffee-backup
sudo docker exec -u www-data -w /var/www/cikscoffee cikscoffee-dery-app \
  php artisan db:seed --class=MenuCatalogSeeder --force
```

## Hubungan dan pengulangan

- Definisi menu ada di `data/cikscoffee-menu.php`.
- Kategori ditautkan melalui `products.category_id`.
- Resep ditautkan melalui `ingredient_product`, lengkap dengan takaran dan
  satuan dari bahan baku. Coffee memiliki resep `base` dan `lite`; menu lain
  menggunakan varian `null`. Lite menggunakan kopi lebih sedikit per gelas.
- Harga mengikuti varian produk. API menghitung harga dari database dan
  mengurangi stok berdasarkan resep varian yang dipesan.
- Foto asli ada di `assets/products/<slug>.png`; seeder menyalin foto yang
  dibutuhkan ke disk publik `products/cikscoffee-menu-v1/<slug>.png`.
  URL gambar mengikuti alur `/storage/...` yang sudah dipakai web dan mobile.
- Pengulangan menambah data yang belum ada, tanpa menggandakan resep,
  mereset stok, mengubah harga/foto/resep/status yang sudah diedit operator,
  atau mengganti kata sandi akun yang sudah ada.
- Nama bahan yang sudah ada wajib menggunakan satuan yang sama. Ketidakcocokan
  satuan menghentikan transaksi seeding; stok tidak dikonversi diam-diam.
- Produk lama dengan nama dan kategori yang sama tetap dipertahankan; seeder
  tidak mengganti pengaturan maupun resep produk tersebut.

Foto merupakan ilustrasi produk hasil AI, bukan foto menu aktual kedai.
Prompt seluruh foto tersedia di `assets/products/PROMPTS.md`.

## Verifikasi

Pengujian seeder memakai SQLite terpisah dan disk publik sementara, tanpa
mengubah database operasional:

```bash
php artisan test --filter=MenuCatalogSeederTest
```

Pengujian mencakup jumlah produk per kategori, ketersediaan foto dan resep,
pengulangan tanpa mereset perubahan, penolakan satuan stok yang berbeda,
serta checkout Lite yang memakai harga database dan mengurangi bahan sesuai
takaran. Untuk seluruh pengujian aplikasi, jalankan `npm run build` sebelum
`php artisan test`.
