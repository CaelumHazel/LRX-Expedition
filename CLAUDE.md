# Shipment App — Project Spec

> File ini dibaca Claude Code sebagai konteks proyek. Kode sudah sebagian ditulis, fokus saat ini: **bug fixing** dan penyempurnaan fitur sesuai spec di bawah.

## 1. Ringkasan Proyek

Aplikasi manajemen pengiriman barang (Shipment/Tracking) untuk membuat resi, melacak posisi barang, dan mengelola stok barang yang sedang atau sudah selesai dikirim.

- **Backend/Framework:** Laravel
- **Database/Auth:** Supabase (PostgreSQL)
- **Tema UI:** Dark, Catppuccin Mocha, minimalis & futuristic

## 2. Cara Kerja dengan Claude (Instruksi)

- Kode sudah ada sebagian. **Baca kode yang ada dulu** sebelum menulis ulang; perbaiki dan lanjutkan, jangan rewrite tanpa alasan.
- Prioritas: (1) bug fixing, (2) melengkapi fitur yang belum ada, (3) polish UI.
- Ikuti konvensi Laravel (Controller, Model, FormRequest, Migration, Blade/komponen) dan konsisten dengan struktur folder yang sudah ada.
- Jelaskan singkat apa yang diubah dan kenapa. Jika ada asumsi, sebutkan.
- Jangan menambah package baru tanpa konfirmasi.

## 3. Fitur

### 3.1 Login
- Halaman login dengan **username + password**.
- Semua halaman lain wajib terautentikasi.
- Role minimal: **admin** (boleh update tracking manual).

### 3.2 Home (Dashboard)
- Menampilkan **list resi** terbaru.
- Klik resi → **detail tracking** (info resi, pengirim/penerima, riwayat posisi & status).

### 3.3 Create Tracking
- **Generate nomor resi** otomatis dengan format `LRXyyyymmddxxxx`
  - `LR` = prefix tetap
  - `yyyymmdd` = tanggal pembuatan
  - `xxxx` = kode urut/random **per hari** (harus unik per hari, reset tiap hari)
  - Contoh: `LRX202611150001`
  - Pastikan aman dari race condition (unique constraint + retry atau sequence per hari).
- **Input detail:**
  - Nama pengirim, nama penerima
  - No HP pengirim, no HP penerima
  - Jenis barang
  - Alamat penerima (**detail address lengkap**, penting)
  - Jenis cargo
  - Harga
- **Generate barcode** dari nomor resi (tampil di detail dan bisa dicetak).

### 3.4 Update Tracker
- Ubah **posisi barang** dan **status** (contoh: `Dalam Pengiriman`, `Sudah Dikirim`).
- Dua cara update:
  1. **Manual oleh admin**
  2. **Otomatis** berdasarkan tanggal & waktu (scheduler/job Laravel)
- Setiap perubahan dicatat sebagai **riwayat tracking** (posisi, status, waktu, siapa/sistem yang mengubah).

### 3.5 Stok Barang
- Halaman list detail barang: mana yang **masih shipment** dan mana yang **sudah selesai**.
- UI harus **rapi dan mudah dipindai** (tabel jelas, badge status berwarna, spasi nyaman) untuk proses penyisiran barang.

### 3.6 Filter & Search
- Search berdasarkan **nomor resi** (dan idealnya nama pengirim/penerima).
- Filter: status, jenis cargo, kota asal/tujuan, kurir, rentang tanggal.

### 3.7 Ongkir & Estimasi (jika memungkinkan)
- Hitung **ongkos kirim otomatis** dan **estimasi waktu tiba** berdasarkan berat, jenis cargo, kota asal, kota tujuan, dan kurir.
- Tarif disimpan di tabel konfigurasi agar mudah diubah.

### 3.8 Update Posisi Real-time
- Posisi barang dapat diperbarui selama proses pengiriman dan langsung terlihat di halaman tracking.

## 4. Data Barang (Form Input)

### Input Item
| Grup | Field |
|------|-------|
| Sender | nama, no HP |
| Receiver | nama, no HP |
| Item | jenis barang, deskripsi |
| Weight | **input manual** (bukan otomatis) |
| Detail Address | alamat lengkap penerima (jalan, no, RT/RW, kelurahan, kecamatan, kode pos, patokan) |

### Selected (dropdown/pilihan)
- Cargo Type
- Origin City
- Destination City
- Courier

## 5. Model Data (Saran Awal)

> Sesuaikan dengan skema yang sudah ada di Supabase/migration. Jangan buat duplikat tabel.

- `users` — username, password, role
- `shipments` — tracking_number (unique), sender_name, sender_phone, receiver_name, receiver_phone, item_type, weight, receiver_address, cargo_type, origin_city, destination_city, courier, price, shipping_cost, estimated_arrival, status, current_position, timestamps
- `tracking_histories` — shipment_id, position, status, updated_by (nullable = otomatis), created_at
- `shipping_rates` — konfigurasi tarif (origin, destination, courier, cargo_type, harga per kg, estimasi hari)

## 6. UI / UX

1. **Gaya:** minimalis dan futuristic, dengan sedikit ornamen (glow halus, border tipis, gradient lembut, ikon). Jangan terlihat polos, tapi UX tetap nyaman dan tidak ramai berlebihan.
2. **Warna:** **Catppuccin Mocha**, dark theme dulu, dengan token kustom (`mocha-*` dan `lrx-*`) di Tailwind v4. Lihat palet di bawah.
3. **Layout:** **sidebar menu di kiri**, bisa **hide/unhide (collapse)** agar konten bisa full width. Simpan state collapse (localStorage).
4. Kode desain awal sudah ada; boleh di-tweak agar lebih hidup, selama tetap konsisten dan nyaman dipakai.

### Palet (Tailwind v4 `@theme`, di `resources/css/app.css`)

Sumber kebenaran palet ada di `resources/css/app.css`. **Gunakan token/class ini, jangan hardcode hex baru.** Contoh: `bg-mocha-base`, `text-mocha-text`, `border-mocha-border`, `text-lrx-cyan`.

| Token | Hex | Peran |
|-------|-----|-------|
| `--color-mocha-base` | `#11111B` | Background halaman (paling gelap) |
| `--color-mocha-mantle` | `#181825` | Sidebar, panel, track scrollbar |
| `--color-mocha-surface` | `#1E1E2E` | Card / surface |
| `--color-mocha-overlay` | `#313244` | Input, elemen terangkat, hover |
| `--color-mocha-border` | `#45475A` | Border, thumb scrollbar |
| `--color-mocha-text` | `#CDD6F4` | Teks utama |
| `--color-mocha-subtext` | `#A6ADC8` | Teks sekunder |
| `--color-mocha-muted` | `#6C7086` | Teks redup / placeholder |
| `--color-lrx-purple` | `#CBA6F7` | Aksen utama (brand) |
| `--color-lrx-cyan` | `#89DCEB` | Aksen sekunder, item aktif |
| `--color-lrx-green` | `#A6E3A1` | Sukses / selesai |
| `--color-lrx-yellow` | `#F9E2AF` | Warning / dalam pengiriman |
| `--color-lrx-red` | `#F38BA8` | Error / bermasalah |

Gradient brand: `#89DCEB → #CBA6F7` (cyan ke purple).

### Kelas Utility Kustom (sudah ada di `app.css`)

| Class | Fungsi |
|-------|--------|
| `.lrx-background` | Background fixed futuristic: radial glow purple/cyan + grid halus (pakai `-z-1`, `pointer-events: none`) |
| `.lrx-nav-active` | Item sidebar aktif: teks cyan, gradient tipis, border cyan transparan, garis inset kiri |
| `.lrx-button-primary` | Tombol utama gradient cyan→purple, teks `#11111B`, hover naik 1px + brightness |
| `.lrx-surface` | Card dengan gradient halus (overlay → surface → mantle) |
| `[x-cloak]` | Sembunyikan elemen Alpine sebelum init (dipakai untuk sidebar/menu) |

Scrollbar kustom: lebar 7px, track `#181825`, thumb `#45475A` (hover `#6C7086`).

### Pemetaan Status → Warna
- Dibuat / Pending → `mocha-muted` (abu)
- Dalam Pengiriman → `lrx-yellow`
- Sudah Dikirim → `lrx-green`
- Bermasalah / Batal → `lrx-red`
- Aksen interaktif (link, item aktif, fokus) → `lrx-cyan`; brand/highlight → `lrx-purple`

### Catatan Konsistensi CSS (perlu dibereskan saat bug fixing)
- Font tidak konsisten: `@theme` memakai **Instrument Sans**, tetapi `body` memakai **Inter**. Pilih satu (disarankan lewat `--font-sans`) dan hapus yang lain.
- Blok `@theme` lama yang dikomentari (hanya font) boleh dihapus.
- `body` background di-hardcode `#11111B`; sebaiknya pakai token `--color-mocha-base`.

### Pedoman UX
- Kontras teks cukup, ukuran font nyaman dibaca.
- Tabel stok: header sticky, baris zebra/hover, badge status jelas.
- Form: label jelas, validasi inline, error mudah dilihat.
- Responsif (minimal tablet) dan sidebar collapse bekerja mulus.
- Animasi halus dan singkat (transition 150–250ms), tanpa efek berlebihan.

## 7. Checklist Bug Fixing

Saat diminta memperbaiki bug, periksa hal berikut:

- [ ] Login/logout & proteksi route (middleware auth)
- [ ] Generate nomor resi: format benar, unik, tidak bentrok saat dibuat bersamaan
- [ ] Validasi form create (semua field wajib, format no HP, weight numerik)
- [ ] Barcode ter-generate dan terbaca
- [ ] Update status/posisi tersimpan + tercatat di riwayat
- [ ] Update otomatis (scheduler) berjalan dan tidak menimpa update admin secara salah
- [ ] Filter & search mengembalikan hasil yang tepat
- [ ] Hitung ongkir & estimasi sesuai tarif
- [ ] Koneksi & query Supabase (kredensial dari `.env`, tidak hardcode)
- [ ] Sidebar hide/unhide dan tema dark konsisten di semua halaman

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
