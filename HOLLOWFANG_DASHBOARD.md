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

## Upgrade Operations November 2026

- **Webhooks & Embeds** kini memiliki dua tab: *Bot Announcements* dan *Discord Webhook Manager*. Manager menyimpan URL webhook terenkripsi memakai `APP_KEY` Laravel dan mencatat pengiriman.
- **Music Remote Control** mengirim perintah Pause, Resume, Skip, Shuffle, Autoplay, dan Loop dari owner yang terhubung ke Discord. Perintah benar-benar dikirim ke `POST /api/music/control`; butuh [PR bot ManukAPP #24](https://github.com/anggamuh/spike-discord-bot/pull/24) diterapkan dan proses bot direstart.
- **Moderation / Roles / Events** diberi pembatasan mutasi 5 aksi/menit per jenis; role yang tidak muncul dalam daftar bot atau managed tidak dapat diubah dari dashboard. Bot tetap memvalidasi role hierarchy dan izin Discord.
- **Community Control** memakai `MANUK_V4_API_PORT` yang bisa diubah dan memeriksa pilihan role ketika mengirim panel self-role.
- **Mobile**: tab V3 kini dapat digulir secara horizontal pada layar kecil.
- **Music Monitoring** tidak menampilkan progress 0% saat posisi lagu sebenarnya tidak tersedia.

Tambahkan (atau sesuaikan) konfigurasi bot lokal:

```dotenv
MANUK_V3_API_PORT=3212
MANUK_V4_API_PORT=3213
MANUK_ANNOUNCE_API_URL=http://127.0.0.1:3211
```

API kontrol musik menggunakan `MANUK_API_BASE_URL` yang sama dengan monitoring (default port 3210). Jangan mengekspos port ini publik; gunakan jaringan privat atau HTTPS reverse proxy dengan autentikasi. Fitur kontrol hanya bekerja bila dashboard dapat menjangkau bot dan `MANUK_API_TOKEN` cocok.

Pengujian fokus:

```bash
php artisan test tests/Feature/HollowfangOperationsUpgradeTest.php tests/Feature/HollowfangControlCenterTest.php tests/Feature/ManukApiServiceTest.php
php scripts/check-control-center-blade.php
```

Sebelum deploy jalankan `php artisan migrate --force`, khususnya untuk tabel `hollowfang_webhooks` dan `hollowfang_webhook_dispatches` jika belum dibuat.

## Audit Explorer (November 2026)

Menu **Audit Logs** sekarang memiliki pencarian case-insensitive pada action, actor ID, target ID, keterangan, dan waktu; filter kategori *Moderation*, *Members & Roles*, dan *Events*; serta tombol **Export CSV**.

Ekspor mengunduh maksimal **100 entri terbaru** dari endpoint ManukAPP V3 `GET /api/v3/logs`, bukan seluruh riwayat. Daftar warning tetap terpisah. CSV memakai UTF-8 BOM dan menetralkan awalan formula spreadsheet serta mempertahankan snowflake Discord agar Excel tidak membulatkan ID. Waktu menggunakan UTC. Hanya Owner yang terautentikasi dapat mengekspor; API di-query ulang pada saat ekspor.

Jalankan:
```bash
php artisan test tests/Unit/HollowfangAuditTest.php tests/Feature/HollowfangAuditExportTest.php
php scripts/check-control-center-blade.php
```
