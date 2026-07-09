# Report Studio — Laravel 10 + Supabase

Dashboard laporan progres murid untuk guru les privat. Generate otomatis dengan AI (Gemini/Groq), few-shot dari gaya tulisanmu sendiri, tersimpan aman di database Supabase.

---

## Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.4) |
| Database | Supabase (PostgreSQL) |
| Auth | Laravel Breeze (login-only, tanpa registrasi publik) |
| AI | Google Gemini **atau** Groq Cloud (switchable) |
| Frontend | Blade + Vanilla CSS + Vanilla JS (no framework) |

---

## Setup Lokal

### 1. Clone & Install Dependencies

```bash
git clone <repo-url> report-studio
cd report-studio
composer install
npm install && npm run build
```

### 2. Buat file .env

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Isi Kredensial Supabase di `.env`

Buka **Supabase Dashboard** → Project kamu → **Settings → Database → Connection string**. Pilih mode **URI** atau salin individual params-nya:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=db.xxxxxxxxxxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-db-password
```

> **Tip pooler**: Jika pakai Supabase connection pooler (Pgbouncer), gunakan port `6543` dan host `aws-0-xxx.pooler.supabase.com`.

### 4. Jalankan Migrasi + Seeder

```bash
php artisan migrate
php artisan db:seed
```

Seeder akan membuat satu akun admin berdasarkan nilai `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env`.

### 5. Jalankan Dev Server

```bash
php artisan serve
```

Buka `http://localhost:8000` dan login dengan kredensial admin dari seeder.

---

## Konfigurasi AI

### Google Gemini (default)
1. Buka [Google AI Studio](https://aistudio.google.com/)
2. Buat API key
3. Login ke Report Studio → tab **Pengaturan** → pilih provider **Google Gemini**, isi API key, simpan

### Groq Cloud
1. Buka [console.groq.com](https://console.groq.com/)
2. Buat API key
3. Login → **Pengaturan** → pilih provider **Groq Cloud API**, isi API key, pilih model (mis. `llama3-8b-8192`), simpan

> **Keamanan**: API key disimpan di tabel `app_settings` di server, tidak pernah terekspos ke browser/client.

---

## Struktur Direktori

```
app/
├── Actions/Report/BuildAiPrompt.php          # Prompt engineering (few-shot)
├── Http/Controllers/
│   ├── Auth/                                  # Breeze auth (login/logout)
│   ├── Dataset/DatasetController.php          # CRUD contoh laporan
│   ├── History/HistoryController.php          # Riwayat + hapus
│   ├── Report/ReportController.php            # Generate + simpan laporan
│   ├── Settings/SettingsController.php        # Kelola AI config
│   └── Student/StudentController.php          # CRUD murid
├── Http/Requests/                             # Form validation per-fitur
├── Models/                                    # Student, DatasetEntry, Report, AppSetting
├── Providers/AiServiceProvider.php            # Binding interface → impl
└── Services/Ai/
    ├── AiReportGeneratorInterface.php         # Kontrak AI provider
    ├── GeminiReportGenerator.php              # Implementasi Gemini
    └── GroqReportGenerator.php                # Implementasi Groq

resources/views/
├── auth/login.blade.php
├── layouts/app.blade.php
├── report/generate.blade.php
├── students/index.blade.php
├── dataset/index.blade.php
├── history/index.blade.php
└── settings/index.blade.php
```

---

## Deployment ke InfinityFree / Shared Hosting

> ⚠️ **Catatan**: InfinityFree memerlukan PHP ≥ 8.1 dan tidak ada akses SSH/Composer di server. Pendekatan yang disarankan:

1. **Build lokal** dulu: `composer install --no-dev --optimize-autoloader`
2. **Upload seluruh folder** (termasuk `vendor/`) via FTP ke `public_html`
3. **Pindahkan `public/`** ke root domain, dan sesuaikan `index.php` agar menunjuk ke path yang benar
4. **Isi `.env`** di server via File Manager hosting
5. **Jalankan migrasi** dari lokal dengan koneksi ke Supabase (karena Supabase adalah cloud DB, `php artisan migrate` bisa dijalankan dari mana saja selama kredensial benar)

### Alternatif Hosting Gratis yang Lebih Friendly untuk Laravel:
- **Railway** (railway.app) — deploy dari GitHub, support PHP + PostgreSQL addon
- **Render** (render.com) — Docker-based, support custom PHP environment
- **Fly.io** — CLI deploy, support Laravel dengan Dockerfile

---

## Menjalankan Tests

```bash
php artisan test
```

Tests menggunakan SQLite in-memory (tidak perlu koneksi Supabase):

- `ReportStudioTest` — auth redirect, student CRUD, dataset CRUD, generate+simpan laporan, settings
- `AuthenticationTest` — login/logout flow
- `EmailVerificationTest`, `PasswordResetTest`, dll.

---

## Workflow Harian Guru

1. **Login** → masuk ke halaman Buat Laporan
2. **Tambah murid** di tab Murid (sekali saja di awal)
3. **Tambah dataset** — paste 3–10 laporan lama sebagai few-shot examples
4. **Buat laporan** — pilih murid, isi materi & behavior → Generate
5. Edit hasil jika perlu → **Simpan ke riwayat** (meeting count +1 otomatis)
6. Di tab **Riwayat** — copy laporan lama kapan saja

---

*Dibuat dengan Laravel 13 · Supabase · Gemini/Groq API*
