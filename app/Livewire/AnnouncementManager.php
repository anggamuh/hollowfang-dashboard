<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Throwable;

class AnnouncementManager extends Component
{
    public string $channelId = '';
    public string $content = '';
    public string $title = '';
    public string $description = '';
    public string $color = '#9b7cf7';
    public string $imageUrl = '';
    public string $imagePosition = 'top';
    public string $imageUrl2 = '';
    public string $imagePosition2 = 'bottom';

    private function guardOwner(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->discord_id && in_array(
                (string) $user->discord_id,
                config('hollowfang.owner_discord_ids', []),
                true
            ),
            403,
            'Akses khusus Owner Hollowfang.'
        );
    }

    public function mount(): void
    {
        $this->guardOwner();
        $this->channelId = (string) (array_key_first(config('hollowfang_announcements.channels', [])) ?? '');
    }

    public function send(): void
    {
        $this->guardOwner();
        $this->resetErrorBag();

        $channels = config('hollowfang_announcements.channels', []);
        $this->validate([
            'channelId' => ['required', 'string'],
            'content' => ['nullable', 'string', 'max:1800'],
            'title' => ['required', 'string', 'max:256'],
            'description' => ['required', 'string', 'max:4000'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'imageUrl' => ['nullable', 'url:https', 'max:1800'],
            'imagePosition' => ['required', 'in:top,after_title,bottom'],
            'imageUrl2' => ['nullable', 'url:https', 'max:1800'],
            'imagePosition2' => ['required', 'in:top,after_title,bottom'],
        ]);

        if (! array_key_exists($this->channelId, $channels)) {
            $this->addError('channelId', 'Channel tidak diizinkan.');
            return;
        }

        // Samakan batas teks dengan validasi API ManukAPP.
        $totalTextLength = mb_strlen(trim($this->content))
            + mb_strlen('## ' . trim($this->title))
            + mb_strlen(trim($this->description))
            + mb_strlen('-# hollow.fang • ManukAPP');

        if ($totalTextLength > 4000) {
            $this->addError('description', 'Total judul, pesan, deskripsi, dan footer maksimal 4000 karakter.');
            return;
        }

        $key = 'hollowfang:announcement:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('send', 'Maksimal tiga percobaan per menit.');
            return;
        }
        RateLimiter::hit($key, 60);

        $status = null;
        $messageId = null;
        $success = false;

        try {
            $url = rtrim((string) config('hollowfang_announcements.url'), '/');
            $token = (string) config('services.manuk.token');

            if ($token === '' || ! preg_match('~^http://127\.0\.0\.1:\d{1,5}$~', $url)) {
                throw new \RuntimeException('API announcement belum dikonfigurasi untuk localhost.');
            }

            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutRedirecting()
                ->connectTimeout(2)
                ->timeout(10)
                ->post($url . '/api/announcements', [
                    'channel_id' => $this->channelId,
                    'content' => trim($this->content),
                    'title' => trim($this->title),
                    'description' => trim($this->description),
                    'color' => $this->color,
                    'image_url' => trim($this->imageUrl),
                    'image_position' => $this->imagePosition,
                    'image_url_2' => trim($this->imageUrl2),
                    'image_position_2' => $this->imagePosition2,
                ]);

            $status = $response->status();
            $success = $response->successful() && $response->json('ok') === true;
            if ($success) {
                $messageId = (string) $response->json('message_id');
            }
        } catch (Throwable $e) {
            // Jangan log token/header Authorization atau payload request.
        }

        DB::table('hollowfang_announcements')->insert([
            'user_id' => auth()->id(),
            'channel_id' => $this->channelId,
            'title' => mb_substr(trim($this->title), 0, 256),
            'successful' => $success,
            'http_status' => $status,
            'discord_message_id' => $messageId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $success) {
            $this->addError('send', match ($status) {
                401 => 'Token API ManukAPP tidak cocok.',
                403 => 'Channel ditolak oleh ManukAPP atau bot tidak memiliki izin.',
                422 => 'Isi pesan atau URL gambar ditolak oleh ManukAPP.',
                429 => 'Terlalu banyak permintaan.',
                default => 'Pengiriman gagal. Pastikan API Announcement ManukAPP aktif.',
            });
            return;
        }

        $this->reset('content', 'title', 'description', 'imageUrl', 'imageUrl2');
        session()->flash('announcement_success', 'Pesan berhasil dikirim oleh ManukAPP!');
    }

    public function render()
    {
        $this->guardOwner();

        return view('livewire.announcement-manager', [
            'channels' => config('hollowfang_announcements.channels', []),
            'history' => DB::table('hollowfang_announcements')
                ->orderByDesc('id')->limit(10)->get(),
        ]);
    }
}
