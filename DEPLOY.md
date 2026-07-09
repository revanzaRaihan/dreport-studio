# Deploy ke Render — Report Studio

## Langkah 1 — Push ke GitHub

Buat repo baru di [github.com/new](https://github.com/new) (private, kosong), lalu jalankan:

```bash
git remote add origin https://github.com/USERNAME/report-studio.git
git branch -M main
git push -u origin main
```

---

## Langkah 2 — Buat Web Service di Render

1. Buka [dashboard.render.com](https://dashboard.render.com) → **New** → **Web Service**
2. Pilih repo GitHub yang baru di-push
3. Render akan otomatis deteksi `Dockerfile` → pilih runtime **Docker**
4. Klik **Create Web Service**

---

## Langkah 3 — Set Environment Variables

Di halaman service Render → tab **Environment**, tambahkan variabel berikut:

| Key | Value |
|-----|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | *(klik Generate Value, atau paste value dari `.env` lokal)* |
| `APP_URL` | `https://nama-service.onrender.com` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | *(dari Supabase → Settings → Database → Session Pooler host)* |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `postgres` |
| `DB_USERNAME` | *(dari Supabase)* |
| `DB_PASSWORD` | *(dari Supabase)* |
| `AI_PROVIDER` | `gemini` |
| `AI_MODEL` | `gemini-2.5-flash` |
| `AI_API_KEY` | *(API key Gemini kamu)* |
| `SESSION_DRIVER` | `cookie` |
| `CACHE_STORE` | `file` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `error` |

---

## Langkah 4 — Deploy

Klik **Save Changes** → Render otomatis trigger build.

Build akan:
1. Build Docker image (PHP 8.3 + Nginx + Supervisor)
2. Install Composer dependencies
3. Saat start: cache config/route/view, jalankan `migrate --force`, lalu start Nginx + PHP-FPM

> ⏱ Build pertama ~5–7 menit (Render free plan cold start ~1 menit setelah idle 15 menit)

---

## Langkah 5 — Seed Admin (sekali saja)

Setelah deploy pertama berhasil, buka Render → **Shell** tab, jalankan:

```bash
php artisan db:seed --force
```

Login dengan: `admin@reportstudio.test` / `password`

---

## Update / Redeploy

Cukup push ke `main`:
```bash
git add -A
git commit -m "update"
git push
```
Render otomatis trigger redeploy.
