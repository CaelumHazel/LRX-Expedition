# LRX Shipment Tracking — Dokumentasi Teknis

Dokumen ini menjelaskan **implementasi kode saat ini**: alur fitur, hubungan backend dan frontend, route, model, request validation, skema database, serta desain CSS. Bagian “Belum tersedia / batasan” membedakan implementasi yang belum sama dengan rencana produk.

## 1. Ringkasan Arsitektur

| Lapisan | Teknologi / lokasi |
|---|---|
| Server | PHP 8.3+, Laravel 13 |
| Database | PostgreSQL, termasuk Supabase; test suite menggunakan SQLite |
| Routing | `routes/web.php`, route web berbasis session dan middleware `auth` |
| Backend | Controller, Form Request, Eloquent Model, service, dan support class di `app/` |
| UI | Blade di `resources/views/` |
| Interaksi browser | Alpine.js diinisialisasi dari `resources/js/app.js` |
| Styling | Tailwind CSS v4 dan CSS kustom di `resources/css/app.css` |
| Asset build | Vite (`npm run build`) |

Pola request standar:

```text
Browser
  -> route (guest/auth)
  -> FormRequest (validasi/otorisasi bila digunakan)
  -> Controller
  -> Service / Eloquent Model
  -> PostgreSQL atau SQLite
  -> Blade response / redirect / JSON
```

`StoreShipmentRequest` dan beberapa Form Request mengembalikan `authorize() === true`; akses halaman tetap dibatasi oleh route `auth`. Pemeriksaan role admin tambahan dilakukan pada operasi yang memang membutuhkannya, misalnya update tracking manual dan pengelolaan tarif.

## 2. Fitur dan Alur Aplikasi

### 2.1 Login dan akses

- `GET /login` menampilkan form username/password.
- `POST /login` memvalidasi kredensial, melakukan regenerate session saat berhasil, dan memiliki throttle `6` percobaan per menit.
- `POST /logout` logout, invalidasi session, dan regenerasi CSRF token.
- Seluruh halaman operasional berada di dalam group middleware `auth`; halaman tracking dengan nomor resi juga belum publik.
- Role yang digunakan adalah `admin` dan `staff`. Admin dapat melakukan update tracking manual dan mengelola tarif. Form penjadwalan tracking membatasi akses ke admin.
- Seeder membaca `ADMIN_USERNAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` dari konfigurasi aplikasi dan membuat/memperbarui akun berdasarkan username. Kredensial aktual harus disimpan di `.env`, bukan di source control atau dokumentasi.

### 2.2 Dashboard

`DashboardController@index` menampilkan ringkasan total shipment, pending, in-transit, delivered, serta jumlah customer, item, warehouse, dan courier. Ringkasan disimpan di cache selama 30 detik; delapan shipment terbaru dimuat untuk daftar dashboard.

### 2.3 Membuat shipment

1. `ShipmentController@create` memuat courier dan warehouse aktif, pilihan cargo, serta katalog kota.
2. Form mengirim nama dan nomor telepon sender/receiver, detail paket, total berat hasil input manual, alamat penerima lengkap, cargo, kota asal/tujuan, dan assignment opsional.
3. `StoreShipmentRequest` memvalidasi data. Untuk setiap baris `items`, harus tersedia `item_id` atau nama paket; kuantitas minimal satu.
4. `ShipmentController@store` menjalankan transaksi database:
   - Mengalokasikan nomor resi dari tabel `tracking_number_sequences` dengan row lock.
   - Menghitung ongkir dan estimasi pengiriman pada server.
   - Mencari atau membuat `Customer` berdasarkan nomor telepon untuk menjaga relasi lama.
   - Menyimpan snapshot nama dan telepon ke `shipments`.
   - Baris dengan `item_id` menjadi `shipment_items`; baris paket teks bebas menjadi `shipment_packages`.
   - Jika ada item katalog, reservasi stok dilakukan dalam transaksi.
   - Membuat log tracking pertama dengan status `pending`.
5. Setelah berhasil, browser diarahkan ke detail shipment.

Format nomor resi adalah `LRXyyyymmddxxxx`, misalnya `LRX202611150001`. Sequence dimulai dari `0001` per tanggal, unik melalui constraint database, dan penguncian baris sequence mencegah alokasi bersamaan yang sama. Maksimum sequence harian adalah `9999`. Saat tabel sequence harian belum ada, generator menginisialisasi angka dari resi lama pada tanggal tersebut.

Total berat shipment diinput manual. Berat pada `shipment_items` tetap menyimpan berat katalog item dikali kuantitas untuk kompatibilitas alur inventory lama; nilai itu tidak menggantikan berat manual shipment.

### 2.4 Edit dan hapus shipment

- `ShipmentController@edit` memuat item dan paket shipment beserta pilihan aktif; pilihan lama untuk warehouse tetap disertakan jika warehouse tersebut kini nonaktif.
- `ShipmentController@update` memvalidasi input dengan `UpdateShipmentRequest`, menghitung ulang ongkir/estimasi, memperbarui snapshot kontak, dan mengganti daftar `shipment_packages` jika field paket dikirim.
- Jika warehouse berubah, `current_position` ikut berubah dan baris baru ditambahkan ke `tracking_logs`.
- Penghapusan shipment dilakukan melalui resource route `DELETE /shipments/{shipment}`. Riwayat dan relasi yang menggunakan cascade mengikuti definisi migration.

### 2.5 Tracking manual dan terjadwal

Status yang diterima:

```text
pending
picked_up
in_transit
at_warehouse
out_for_delivery
delivered
cancelled
```

- `POST /shipments/{shipment}/tracking` hanya dapat dipakai admin; controller memeriksa role secara eksplisit.
- Update status, posisi shipment, penyesuaian stok karena transisi status, dan `TrackingLog` dilakukan dalam transaksi.
- Log menyimpan `shipment_id`, `status`, `location`, deskripsi opsional, `tracked_at`, user opsional, dan `source` (`manual` atau `system`).
- `POST /shipments/{shipment}/tracking/schedule` menerima status, lokasi, deskripsi opsional, dan `scheduled_at` yang harus lebih besar dari waktu sekarang. Request ini hanya mengizinkan admin.
- Command `tracking:apply-scheduled-updates` memproses update jatuh tempo satu per satu dalam transaksi dengan row lock.
- Update terjadwal dilewati jika ada log manual yang dibuat sejak jadwal dibuat, atau status shipment sudah `delivered`/`cancelled`. Hasil pemrosesan dicatat sebagai `applied` atau `skipped`.
- Detail resi tersedia pada `/tracking/{trackingNumber}` dan pada detail shipment. URL tersebut tetap memerlukan login. Tidak ada WebSocket/push; perubahan terlihat saat halaman dimuat atau di-refresh.

### 2.6 Barcode

`TrackingBarcode` membangun SVG barcode dari nomor resi yang sesuai pola `LRX` diikuti 12 digit. Barcode ditampilkan pada detail shipment. Aturan `@media print` di CSS menyembunyikan elemen lain agar barcode dapat dicetak.

### 2.7 Inventory dan paket

Ada dua representasi detail barang:

1. **Item katalog** — relasi `shipment_items` ke `items`. Service inventory mengurangi `items.stock` saat shipment dibuat. Perubahan dicatat di `stock_movements`. Pembatalan mengembalikan stok; pembukaan kembali shipment yang sebelumnya dibatalkan memeriksa stok terlebih dahulu.
2. **Paket teks bebas** — relasi `shipment_packages` menyimpan nama, deskripsi, dan kuantitas input. Paket ini tidak terhubung ke katalog item dan tidak mengubah stok.

Halaman `/stock` membedakan shipment aktif dan selesai, serta mendukung pencarian nomor resi dan nama/deskripsi item atau paket. Status `cancelled` dikecualikan dari daftar stock; completed berarti status `delivered`.

### 2.8 Warehouse dan courier

- Warehouse memiliki halaman index, create, show, edit, update, dan delete. Pilihan kota menggunakan katalog kota lokal.
- Detail warehouse menampilkan shipment beserta nama paket/item, penerima, tujuan, courier, status, dan berat.
- Warehouse tidak dapat dihapus jika masih direferensikan shipment.
- Courier yang aktif dapat dipilih ketika membuat atau mengedit shipment.
- Halaman Couriers menampilkan daftar courier dan shipment aktif; detail courier menampilkan shipment dan paket.
- Alur UI yang dirutekan saat ini mendukung index, create, store, dan show courier. Walaupun controller memiliki implementasi edit/update/delete, endpoint tersebut tidak dimasukkan ke resource route.
- Customer dan Inventory memiliki target menu, tetapi route-nya saat ini menampilkan HTTP 404 bertema di dalam layout aplikasi. Controller/view CRUD lama yang ada di repository tidak berarti route CRUD tersebut aktif.

### 2.9 Ongkir dan estimasi

`ShippingFeeEstimator` mencari tarif aktif berdasarkan kota asal, kota tujuan, cargo type, dan courier (tarif khusus courier lebih diprioritaskan; tarif tanpa courier dapat menjadi fallback). Berat dibulatkan ke atas per kilogram:

```text
tarif tersimpan:
  ceil(total_weight_kg) × price_per_kg

fallback dummy:
  ceil(total_weight_kg) × (5000 + distance_km × 5)
```

Jarak dummy dari `IndonesianCityCatalog`:

| Relasi kota | Estimasi |
|---|---:|
| Kota/kabupaten sama | 25 km |
| Provinsi sama | 120 km |
| Kelompok pulau sama | 650 km |
| Kelompok pulau berbeda | 1700 km |

Hari dummy dihitung `max(1, ceil(distance_km / 500))`. Ini bukan rute jalan atau estimasi kurir nyata. Preview form menggunakan endpoint `GET /shipments/estimate`; ongkir tetap dihitung ulang oleh server saat create/update, bukan mempercayai nilai browser. Declared value adalah nilai barang yang diinput manual dan terpisah dari ongkir.

### 2.10 Katalog kota

`database/data/indonesia-cities.json` berisi 514 kabupaten/kota. Data dasar berasal dari dataset api-wilayah-indonesia; pemisahan provinsi Papua yang lebih baru ditambahkan secara lokal. `IndonesianCityCatalog` membaca file, membentuk label tampilan, memvalidasi nama kota, menangani alias data lama untuk Jakarta/Bandung/Depok, dan mengelompokkan provinsi untuk perkiraan jarak.

## 3. Peta Routes

Route operasional selain login berada di dalam group `auth` pada `routes/web.php`.

| Method | Path | Name | Fungsi / akses |
|---|---|---|---|
| GET | `/login` | `login` | Form login, guest |
| POST | `/login` | `login.store` | Login, throttle 6/menit, guest |
| POST | `/logout` | `logout` | Logout terautentikasi |
| GET | `/` | — | Redirect ke dashboard, auth |
| GET | `/dashboard` | `dashboard` | Ringkasan dan shipment terbaru |
| GET | `/shipments` | `shipments.index` | Daftar, pencarian, filter |
| GET | `/shipments/create` | `shipments.create` | Form shipment |
| POST | `/shipments` | `shipments.store` | Simpan shipment |
| GET | `/shipments/estimate` | `shipments.estimate` | Preview JSON ongkir/estimasi |
| GET | `/shipments/{shipment}` | `shipments.show` | Detail dan barcode |
| GET | `/shipments/{shipment}/edit` | `shipments.edit` | Form edit |
| PUT/PATCH | `/shipments/{shipment}` | `shipments.update` | Simpan perubahan |
| DELETE | `/shipments/{shipment}` | `shipments.destroy` | Hapus shipment |
| GET | `/tracking/{trackingNumber}` | `tracking.show` | Detail shipment berdasarkan resi |
| POST | `/shipments/{shipment}/tracking` | `tracking.store` | Update manual; admin saja |
| POST | `/shipments/{shipment}/tracking/schedule` | `tracking.schedule` | Jadwalkan update; admin saja |
| GET | `/stock` | `stock.index` | Daftar stok/shipment aktif/selesai |
| GET/POST/PUT/PATCH/DELETE | `/warehouses...` | `warehouses.*` | CRUD warehouse |
| GET | `/couriers` | `couriers.index` | Daftar courier |
| GET | `/couriers/create` | `couriers.create` | Form Add Courier |
| POST | `/couriers` | `couriers.store` | Simpan courier |
| GET | `/couriers/{courier}` | `couriers.show` | Detail courier |
| GET | `/shipping-rates` | `shipping-rates.index` | Daftar tarif; admin |
| POST | `/shipping-rates` | `shipping-rates.store` | Tambah tarif; admin |
| PUT/PATCH | `/shipping-rates/{shipping_rate}` | `shipping-rates.update` | Edit tarif; admin |
| DELETE | `/shipping-rates/{shipping_rate}` | `shipping-rates.destroy` | Hapus tarif; admin |
| GET | `/customers` | `customers.index` | Placeholder 404 bertema |
| GET | `/items` | `items.index` | Placeholder 404 bertema |
| GET | `/ui-test` | `ui.test` | Halaman uji UI |

Di environment `local`, terdapat route diagnostik `/_debug/ping`, `/_debug/db`, `/_debug/db-timing`, dan `/_debug/db-reuse`. Route ini tidak terdaftar di environment lain.

## 4. Controllers dan Alur Backend

| Controller | Tanggung jawab |
|---|---|
| `AuthenticatedSessionController` | Menampilkan login, mengautentikasi username/password, mengelola session login/logout |
| `DashboardController` | Statistik dashboard (cache 30 detik) dan delapan shipment terbaru |
| `ShipmentController` | Index/filter, create/store, detail, edit/update/delete, stock page, dan endpoint estimate |
| `TrackingController` | Menampilkan detail resi, update manual, dan membuat jadwal update |
| `CourierController` | Daftar courier, form/simpan courier, detail assignment; controller juga memiliki edit/update/delete yang tidak dirutekan saat ini |
| `WarehouseController` | CRUD warehouse dan data shipment/item pada halaman operasional |
| `ShippingRateController` | CRUD tarif; operasi dibatasi admin |

Services/support:

| Class | Tanggung jawab |
|---|---|
| `ShipmentInventoryService` | Reservasi stok, pengembalian stok, status transition, pencatatan `stock_movements` |
| `ShippingFeeEstimator` | Tarif aktif, prioritas tarif courier, fallback ongkir, hari estimasi |
| `IndonesianCityCatalog` | Memuat katalog lokal, nama valid, alias legacy, dan perkiraan jarak |
| `TrackingBarcode` | Memvalidasi nomor resi dan merender barcode SVG |
| `ApplyScheduledTrackingUpdates` | Memproses jadwal tracking jatuh tempo tanpa menimpa update manual terbaru |

## 5. Form Requests dan Validasi

Form Request berlokasi di `app/Http/Requests/`; controller memanggil `$request->validated()` untuk menggunakan data yang lolos aturan.

| Request | Validasi utama | Otorisasi khusus |
|---|---|---|
| `Auth/LoginRequest` | Username wajib maks. 255; password wajib; remember opsional boolean | Guest route |
| `Shipment/StoreShipmentRequest` | Sender/receiver bisa memakai ID customer lama atau nama/telepon; telepon dibatasi regex digit/`+() -`; alamat wajib maks. 2000; kota harus dari catalog; berat numeric `0.01..99999999.99`; cargo; warehouse/courier opsional valid; `items` wajib array minimal 1, kuantitas integer min. 1, setiap baris harus berisi item katalog atau nama paket | `authorize()` true; route auth |
| `Shipment/UpdateShipmentRequest` | Data shipment serupa; `packages` opsional, nama/deskripsi/kuantitas divalidasi; paket bernama memerlukan kuantitas | `authorize()` true; route auth |
| `Tracking/StoreTrackingRequest` | Status dari daftar status, lokasi wajib maks. 255, deskripsi opsional maks. 2000 | Role admin diperiksa oleh `TrackingController@store` |
| `Tracking/StoreScheduledTrackingRequest` | Status, lokasi, deskripsi, `scheduled_at` harus setelah now | `authorize()` memeriksa role admin |
| `Courier/StoreCourierRequest` | Nama dan telepon wajib; tipe/nomor kendaraan opsional; aktif boolean | `authorize()` true |
| `Warehouse/StoreWarehouseRequest` | Nama, code unik maks. 20, alamat, kota dari catalog, aktif boolean | `authorize()` true |
| `ShippingRate/SaveShippingRateRequest` | Origin/destination berbeda, cargo, courier opsional valid, harga/kg positif, hari 1..365 | `authorize()` hanya admin |

Request lain untuk edit/update courier, warehouse, customer, dan item tersedia di repository. Keberadaan request/controller lama tidak berarti seluruh resource tersebut dapat diakses melalui `routes/web.php`.

## 6. Models dan Eloquent Relationships

```text
Customer 1 ──< Shipment (sender)
Customer 1 ──< Shipment (receiver)
Courier  1 ──< Shipment
Warehouse 1 ─< Shipment

Shipment 1 ──< ShipmentItem >── 1 Item
Shipment 1 ──< ShipmentPackage
Shipment 1 ──< TrackingLog >── 0..1 User
Shipment 1 ──< ScheduledTrackingUpdate >── 0..1 User (creator)
Shipment 1 ──< StockMovement >── 1 Item
Warehouse 1 ─< StockMovement
Courier 1 ──< ShippingRate
```

| Model | Relasi Eloquent yang didefinisikan |
|---|---|
| `User` | Authenticatable; tidak mendefinisikan relasi has-many eksplisit. Password di-cast `hashed`, password/token disembunyikan dari serialisasi. |
| `Customer` | `sentShipments()` melalui `sender_id`; `receivedShipments()` melalui `receiver_id`. |
| `Shipment` | `sender`, `receiver`, `courier`, `warehouse` (`belongsTo`); `items`, `packages`, `trackingLogs`, `scheduledTrackingUpdates`, `stockMovements` (`hasMany`). |
| `ShipmentItem` | `belongsTo` shipment dan item; menyimpan kuantitas dan berat snapshot. |
| `ShipmentPackage` | `belongsTo` shipment; paket teks bebas. |
| `Item` | `hasMany` shipment items dan stock movements. |
| `Courier` | `hasMany` shipments. |
| `Warehouse` | `hasMany` shipments dan stock movements. |
| `TrackingLog` | `belongsTo` shipment dan user (`user_id` nullable untuk log sistem/riwayat yang user-nya dihapus). |
| `ScheduledTrackingUpdate` | `belongsTo` shipment dan creator (`created_by`). |
| `StockMovement` | `belongsTo` item, warehouse, dan shipment (`shipment_id` nullable). |
| `ShippingRate` | `belongsTo` courier; `courier_id` nullable untuk tarif umum. |

Relasi pada tabel `shipments.sender_id` dan `receiver_id` mengarah ke tabel `customers` agar kompatibel dengan skema lama. Nama dan nomor yang terlihat pada resi juga disalin ke kolom snapshot shipment sehingga tampilan shipment mempertahankan data pada saat dibuat.

## 7. Database dan Migrations

Migration adalah sumber skema aktual. Jalankan `php artisan migrate` hanya setelah memastikan environment menunjuk ke database yang benar.

| Tabel | Kolom/fungsi utama | Constraint dan relasi penting |
|---|---|---|
| `users` | Nama, email, password, remember token; migration tambahan menambahkan username dan role | Email dan username unique; role default `staff` |
| `customers` | Nama, telepon, alamat | Direferensikan shipment melalui sender/receiver |
| `couriers` | Nama, telepon, vehicle type/number, active | Direferensikan shipment dan rate |
| `warehouses` | Nama, code, alamat, kota, active | Code unique; direferensikan shipment dan stock movement |
| `items` | Nama, SKU, deskripsi, berat katalog, stok, unit | SKU unique; stok item katalog |
| `shipments` | Resi, sender/receiver IDs dan snapshot, courier/warehouse opsional, kota, cargo, alamat, posisi, berat manual, ongkir, declared value, estimasi tanggal/jarak, status, notes, inventory reservation timestamp | Tracking number unique; sender/receiver `restrictOnDelete`; courier/warehouse `nullOnDelete`; status default `pending` |
| `shipment_items` | Relasi shipment-item, kuantitas, berat | Shipment cascade delete; item restrict delete; pasangan shipment/item unique |
| `shipment_packages` | Nama/deskripsi/kuantitas paket teks bebas | Shipment cascade delete; indeks shipment dan nama |
| `tracking_logs` | Shipment, status, lokasi, deskripsi, waktu tracking, user, source | Shipment cascade delete; user nullable dan null on delete |
| `scheduled_tracking_updates` | Payload status/lokasi, waktu jadwal/proses, result, creator | Shipment cascade delete; creator nullable; indeks proses/jadwal |
| `tracking_number_sequences` | Tanggal sequence dan angka terakhir | `sequence_date` sebagai primary key |
| `stock_movements` | Item, warehouse, shipment opsional, tipe, kuantitas, deskripsi | Item restrict delete; warehouse dan shipment nullable mengikuti migration |
| `shipping_rates` | Route, cargo, courier opsional, harga/kg, estimasi hari, active | Courier nullable; indeks route/cargo/courier |
| `sessions`, `password_reset_tokens`, `jobs`, `cache` | Tabel pendukung Laravel | Dibuat oleh migration framework |

Tabel riwayat aktual bernama **`tracking_logs`**, bukan `tracking_histories`. Penambahan snapshot sender/receiver dan paket bebas berada pada migration `2026_11_15_000005_add_party_snapshots_and_shipment_packages.php`. Jangan membuat tabel duplikat bila menerapkan perubahan skema di Supabase.

### Penghapusan dan integritas referensial

- Menghapus shipment dapat menghapus data turunannya yang menggunakan `cascadeOnDelete` seperti shipment package, shipment item, tracking log, dan jadwal tracking.
- Customer yang masih dipakai sender/receiver dibatasi dari penghapusan.
- Courier dan warehouse menjadi nullable pada shipment saat record master dihapus menurut FK, tetapi controller mencegah penghapusan jika masih memiliki shipment.
- Item yang dipakai shipment dibatasi dari penghapusan.
- Movement mempertahankan riwayat; shipment/warehouse dapat menjadi null sesuai foreign key.

## 8. Frontend, View, dan Data Binding

### 8.1 Layout bersama

| File | Fungsi |
|---|---|
| `resources/views/layouts/app.blade.php` | HTML dasar, Vite assets, background, sidebar/topbar, area konten, alert success/error, footer |
| `resources/views/layouts/partials/sidebar.blade.php` | Navigasi dashboard, shipment, stock, warehouse, inventory, customer, courier, dan rate admin |
| `resources/views/layouts/partials/topbar.blade.php` | Breadcrumb, page title, pencarian shipment, profil dan logout |
| `resources/views/errors/404.blade.php` | 404 route umum dan halaman Inventory/Customers under development dengan layout normal |

Sidebar desktop dapat collapse; nilainya disimpan pada `localStorage` dengan key `sidebarCollapsed`. Pada layar kecil, sidebar memakai overlay dan menu mobile.

### 8.2 Halaman utama

| View | Data/interaksi |
|---|---|
| `dashboard/index.blade.php` | Statistik dashboard dan shipment terbaru dari `DashboardController` |
| `shipments/index.blade.php` | Daftar shipment, filter status/cargo/kota/courier/tanggal, search, paginasi |
| `shipments/create.blade.php` | Input shipment, city combobox, kalkulasi ongkir asynchronous, baris paket dinamis |
| `shipments/edit.blade.php` | Edit data shipment, paket, assignment dan preview ongkir |
| `shipments/show.blade.php` | Detail shipment, barcode printable, data kontak/paket, riwayat dan form admin untuk tracking |
| `shipments/stock.blade.php` | Shipment aktif/completed dengan pencarian paket/item |
| `warehouses/*` | List/form/detail warehouse dan shipment yang tersimpan di warehouse |
| `couriers/*` | Daftar courier, tambah courier, detail shipment/paket yang ditugaskan |
| `shipping-rates/index.blade.php` | CRUD tabel tarif yang dibatasi admin |
| `auth/login.blade.php` | Form login username/password |

### 8.3 City combobox

`resources/js/app.js` mendaftarkan Alpine data component `cityCombobox`. View Create Shipment mengirim city catalog ke Alpine:

- Ketik untuk mencari nama kota/kabupaten atau provinsi; pencarian juga mencocokkan nama tanpa awalan `KOTA`/`KABUPATEN`.
- Daftar hasil dibatasi 20 pilihan dan bisa dipilih dengan klik atau tombol `ArrowUp`, `ArrowDown`, `Enter`.
- `Escape` dan klik luar menutup dropdown.
- Pilihan valid disimpan sebagai nilai tersembunyi `origin_city`/`destination_city`; teks pencarian tidak dikirim sebagai nama kota.
- Event `city-changed` menyinkronkan pilihan ke state form agar preview ongkir diperbarui.
- Card Route & Cargo Details memakai `overflow-visible` dan z-index agar dropdown destination tidak terpotong oleh batas card.

### 8.4 Preview ongkir

View Create/Edit memantau perubahan kota, cargo, courier, dan berat. Setelah debounce 250 ms, browser mengirim request ke `shipments.estimate`, membaca JSON `shipping_cost`, `distance_km`, `estimated_days`, dan `source`, lalu menampilkan hasil/error di form. Submit tetap divalidasi dan dihitung kembali di server.

## 9. CSS dan Design System

Sumber tema berada di `resources/css/app.css`; import Tailwind v4 menggunakan CSS-first configuration:

```css
@import 'tailwindcss';

@theme {
    --font-sans: 'JetBrains Mono', ui-monospace, ...;
    --color-mocha-base: #11111B;
    /* tokens lainnya */
}
```

Body memakai `font-sans`, warna dasar berasal dari `--color-mocha-base`, dan HTML mengaktifkan `color-scheme: dark`.

### 9.1 Token warna

| Token | Nilai | Kegunaan |
|---|---|---|
| `mocha-base` | `#11111B` | Background halaman |
| `mocha-mantle` | `#181825` | Sidebar dan surface gelap |
| `mocha-surface` | `#1E1E2E` | Card |
| `mocha-overlay` | `#313244` | Surface/input/hover |
| `mocha-border` | `#45475A` | Border dan scrollbar |
| `mocha-text` | `#CDD6F4` | Teks utama |
| `mocha-subtext` | `#A6ADC8` | Teks sekunder |
| `mocha-muted` | `#6C7086` | Teks muted |
| `lrx-purple` | `#CBA6F7` | Aksen brand |
| `lrx-cyan` | `#89DCEB` | Aksen interaktif/fokus |
| `lrx-green` | `#A6E3A1` | Sukses/selesai |
| `lrx-yellow` | `#F9E2AF` | Proses/peringatan |
| `lrx-red` | `#F38BA8` | Error/batal |

Gunakan class token seperti `bg-mocha-base`, `text-mocha-text`, `border-mocha-border`, dan `text-lrx-cyan`; hindari memperkenalkan warna hex baru pada Blade bila token yang tersedia sudah sesuai.

### 9.2 Utility CSS dan ornamen

| Selector | Implementasi |
|---|---|
| `.lrx-background` | Fixed full-screen background; radial glow ungu/cyan dan base mocha |
| `.lrx-background::after` | Grid tipis dari dua linear-gradient, 38px, dengan mask fade |
| `.lrx-nav-active` | Teks cyan, gradient transparan, border cyan tipis, garis inset kiri |
| `.lrx-button-primary` | Gradient cyan→purple, teks base, hover brightness dan translateY halus |
| `.lrx-surface` | Gradient panel overlay → surface → mantle |
| `[x-cloak]` | Menyembunyikan Alpine component sebelum inisialisasi |
| `::-webkit-scrollbar*` | Scrollbar ukuran 7px dengan track mantle dan thumb border/muted |
| `@media print` | Mencetak `#shipment-barcode` saja dan menyembunyikan elemen lain |

Utility umum Tailwind yang dominan digunakan adalah grid/flex responsif, `rounded-xl`/`rounded-2xl`, border overlay, spacing berbasis `gap`, warna token, dan transition singkat. Status operasional memakai accent green/yellow/red/muted sesuai konteks view.

## 10. Seeder, Build, Test, dan Scheduler

### Database seed

`DatabaseSeeder`:

- Membuat/memperbarui admin dari konfigurasi env bila ketiga nilai admin tersedia.
- Memakai `firstOrCreate` untuk customer contoh, courier, warehouse, item katalog, dan tarif contoh; kode master dipakai sebagai lookup agar seeding ulang tidak memasukkan duplikat yang sama.
- Jangan menaruh credential nyata di seeder atau source control.

### Perintah kerja

```sh
# Setup
composer install
npm install
php artisan key:generate

# Migrasi dan seed (pastikan database target benar)
php artisan migrate
php artisan db:seed

# Development
php artisan serve
npm run dev

# Production frontend
npm run build

# Tests
php artisan test

# Scheduler lokal
php artisan schedule:work
```

Di production, jalankan `php artisan schedule:run` setiap menit melalui scheduler sistem. Test menggunakan SQLite sesuai konfigurasi testing dan tidak memverifikasi koneksi maupun melakukan migration terhadap Supabase.

## 11. Belum tersedia / batasan yang disengaja

- Customer dan Inventory CRUD tidak aktif di routes; keduanya menampilkan themed 404/under-development.
- Courier route belum mengaktifkan edit/update/delete meskipun beberapa method/controller/request lama tersedia.
- Perkiraan jarak dan ongkir dummy bukan data geografis atau tarif riil.
- Tracking tidak memiliki channel WebSocket/push dan halaman tracking saat ini dilindungi login.
- Katalog kota adalah file lokal; perubahan wilayah perlu diperbarui dan diuji di katalog tersebut.
- Tarif disimpan per route/cargo/courier, tetapi tidak ada integrasi eksternal dengan API kurir atau peta/routing.

