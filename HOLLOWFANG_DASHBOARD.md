# Hollowfang Control Center — instalasi dan monitoring

Dashboard ini menggunakan **Laravel + Livewire**. Koneksi Discord OAuth dan pengaturan Owner yang sudah ada tetap menjadi sumber izin; menu **Moderation, Members & Roles, Events, Logs, Announcements** memakai modul mereka yang sudah ada.

## Fitur PR ini

- **Overview**: kartu metrik server, status sistem, dan lagu yang benar-benar sedang diputar.
- **Bot Monitoring**: kesehatan API dan antrean lama tetap tersedia.
- **Discord Gateway**: status bot, guild ID, ping, uptime, Lavalink, dan member count (hanya jika disediakan API).
- **Music Monitoring**: judul, artis, sumber, progress lagu, panjang antrean, voice channel ID, status Lavalink; read-only.
- **Settings**: kondisi koneksi API, konfigurasi yang lengkap/belum ada, dan sinkronisasi akun Discord. Token/secrets tidak dicetak di HTML.
- **Navigasi**: UI dark-violet, sidebar lebih rapi, tombol refresh, serta status offline dan pesan diagnostik yang eksplisit.

## Konfigurasi

Tambahkan/cek di `.env` Laravel:

```dotenv
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_REDIRECT_URI=https://DOMAIN-DASHBOARD/auth/discord/callback
HOLLOWFANG_OWNER_DISCORD_IDS=123456789012345678
MANUK_API_BASE_URL=http://127.0.0.1:3210
MANUK_API_TOKEN=
```

Samakan `MANUK_API_TOKEN` antara Laravel dan proses crypt (minimum 32 karakter). Pada crypt, monitoring API tersedia di `GET /api/health` dan membutuhkan `Authorization: Bearer TOKEN`.

**Penting untuk hosting:** API crypt saat ini bind ke **127.0.0.1:3210**, yang hanya dapat dijangkau dari mesin tempat crypt berjalan. Jika Laravel di Hostinger sementara bot di PC/VPS lain, `127.0.0.1` di Laravel menunjuk server Hostinger **bukan** PC bot. Gunakan VPN/private reverse proxy/tunnel HTTPS dengan autentikasi yang benar atau jalankan keduanya dalam satu host. Jangan buka port kontrol bot 3211/3212 atau monitoring 3210 ke internet tanpa pembatasan. PR ini **tidak** membuat tunnel.

Data yang tidak tersedia ditampilkan sebagai `—` atau `Unknown`, bukan angka nol buatan. Jumlah online member tidak diklaim tersedia jika bot tidak mengumpulkan presence.

## Deployment

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

Sebelum merge, jalankan test fokus:

```bash
php artisan test tests/Feature/HollowfangControlCenterTest.php tests/Feature/ManukApiServiceTest.php
php scripts/check-control-center-blade.php
```

## Batasan yang masih ada

Repo memiliki beberapa **test Laravel starter-kit lama** untuk login/password/settings yang belum sesuai dengan file view dan komponen yang tersedia. PR ini tidak mengganti mekanisme OAuth, mereset password, atau mengaktifkan kontrol bot tambahan. CI memakai pengujian Control Center dan Monitoring yang scoped; masalah starter-kit harus dibereskan sebagai pekerjaan terpisah sebelum menyatakan seluruh aplikasi bebas masalah.
