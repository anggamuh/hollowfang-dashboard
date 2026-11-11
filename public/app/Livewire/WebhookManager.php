<?php

namespace App\Livewire;

use App\Models\HollowfangWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class WebhookManager extends Component
{
    public string $name = '';
    public string $channelName = '';
    public string $webhookUrl = '';
    public string $selectedWebhook = '';
    public string $message = '';
    public string $embedTitle = '';
    public string $embedDescription = '';
    public string $embedColor = '#9b7cf7';

    public function mount(): void
    {
        $this->authorizeOwner();
        $first = HollowfangWebhook::query()->orderBy('name')->first();
        $this->selectedWebhook = $first ? (string) $first->id : '';
    }

    private function authorizeOwner(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->discord_id
            && in_array((string) $user->discord_id, config('hollowfang.owner_discord_ids', []), true),
            403,
            'Hanya Owner Hollowfang yang dapat mengelola webhook.'
        );
    }

    public function addWebhook(): void
    {
        $this->authorizeOwner();
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'channelName' => ['nullable', 'string', 'max:100'],
            'webhookUrl' => ['required', 'string', 'max:600'],
        ]);

        $url = trim($this->webhookUrl);
        // Restrict destination to a canonical Discord HTTPS webhook URL.
        // Prevent posting a secret-bearing request to arbitrary hosts.
        if (!preg_match('~\Ahttps://discord\.com/api(?:/v\d+)?/webhooks/\d{15,22}/[a-zA-Z0-9_-]+\z~D', $url)) {
            throw ValidationException::withMessages([
                'webhookUrl' => 'URL harus berupa Discord webhook resmi: https://discord.com/api/webhooks/ID/TOKEN',
            ]);
        }

        $webhook = HollowfangWebhook::query()->create([
            'name' => trim($this->name),
            'channel_name' => trim($this->channelName) ?: null,
            'webhook_url' => $url,
            'created_by' => auth()->id(),
        ]);

        $this->selectedWebhook = (string) $webhook->id;
        $this->reset('name', 'channelName', 'webhookUrl');
        session()->flash('webhook_success', 'Webhook berhasil disimpan dengan enkripsi.');
    }

    public function removeWebhook(int $id): void
    {
        $this->authorizeOwner();
        HollowfangWebhook::query()->findOrFail($id)->delete();
        if ($this->selectedWebhook === (string) $id) {
            $this->selectedWebhook = (string) (HollowfangWebhook::query()->value('id') ?? '');
        }
        session()->flash('webhook_success', 'Webhook dihapus dari dashboard. Webhook Discord aslinya tidak dihapus.');
    }

    public function sendEmbed(): void
    {
        $this->authorizeOwner();
        $this->validate([
            'selectedWebhook' => ['required', 'integer', 'exists:hollowfang_webhooks,id'],
            'message' => ['nullable', 'string', 'max:1800'],
            'embedTitle' => ['required', 'string', 'max:256'],
            'embedDescription' => ['required', 'string', 'max:4000'],
            'embedColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $key = 'hollowfang:webhook-send:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('send', 'Batas 3 pengiriman per menit. Coba lagi sebentar.');
            return;
        }
        RateLimiter::hit($key, 60);

        $webhook = HollowfangWebhook::query()->findOrFail((int) $this->selectedWebhook);
        $status = null;
        $successful = false;

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withoutRedirecting()
                ->connectTimeout(3)
                ->timeout(10)
                ->post($webhook->webhook_url, [
                    'content' => trim($this->message),
                    'embeds' => [[
                        'title' => trim($this->embedTitle),
                        'description' => trim($this->embedDescription),
                        'color' => hexdec(substr($this->embedColor, 1)),
                    ]],
                    'allowed_mentions' => ['parse' => []],
                ]);

            $status = $response->status();
            $successful = in_array($status, [200, 204], true);
        } catch (Throwable $e) {
            // Don't report request exceptions here: webhook URLs are credentials.
        }

        \Illuminate\Support\Facades\DB::table('hollowfang_webhook_dispatches')->insert([
            'webhook_id' => $webhook->id,
            'user_id' => auth()->id(),
            'title' => mb_substr($this->embedTitle, 0, 256),
            'successful' => $successful,
            'http_status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$successful) {
            $this->addError('send', match ($status) {
                401, 403, 404 => 'Webhook tidak valid, sudah dihapus, atau tidak punya izin.',
                429 => 'Discord membatasi pengiriman. Coba beberapa saat lagi.',
                default => 'Gagal mengirim embed. Cek koneksi atau URL webhook.',
            });
            return;
        }

        $this->reset('message', 'embedTitle', 'embedDescription');
        session()->flash('webhook_success', 'Embed berhasil dikirim ke Discord.');
    }

    public function render()
    {
        return view('livewire.webhook-manager', [
            'webhooks' => HollowfangWebhook::query()->orderBy('name')->get(['id', 'name', 'channel_name']),
            'recentDispatches' => \Illuminate\Support\Facades\DB::table('hollowfang_webhook_dispatches')
                ->leftJoin('hollowfang_webhooks', 'hollowfang_webhook_dispatches.webhook_id', '=', 'hollowfang_webhooks.id')
                ->select('hollowfang_webhook_dispatches.*', 'hollowfang_webhooks.name as webhook_name')
                ->orderByDesc('hollowfang_webhook_dispatches.id')
                ->limit(10)->get(),
        ]);
    }
}
