<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HasAnnouncementDirectory
{
    public array $directoryChannels = [];

    public array $approvedChannels = [];

    public array $directoryRoles = [];

    public string $selectedRoleId = '';

    // =====================================================
    // API REQUEST
    // =====================================================

    private function directoryRequest(
        string $method,
        string $path,
        array $data = []
    ): ?array {
        $url = rtrim(
            (string) config('hollowfang_announcements.url'),
            '/'
        );

        $token = (string) config('services.manuk.token');

        if (
            $token === '' ||
            ! preg_match(
                '~^http://127\.0\.0\.1:\d{1,5}$~',
                $url
            )
        ) {
            $this->addError(
                'directory',
                'API direktori ManukAPP belum dikonfigurasi.'
            );

            return null;
        }

        try {
            $request = Http::acceptJson()
                ->withToken($token)
                ->withoutRedirecting()
                ->connectTimeout(2)
                ->timeout(8);

            $response = match (strtoupper($method)) {
                'PUT' => $request->put($url.$path, $data),

                'GET' => $request->get($url.$path),

                default => throw new \InvalidArgumentException(
                    'HTTP method tidak didukung.'
                ),
            };

            if (! $response->successful()) {
                $status = $response->status();

                $apiError = $response->json('error');

                $reason = is_string($apiError)
                    ? $apiError
                    : 'Unknown API error';

                Log::warning(
                    'Hollowfang Announcement Directory HTTP Error',
                    [
                        'method' => strtoupper($method),
                        'path' => $path,
                        'status' => $status,
                        'error' => $reason,
                    ]
                );

                $message = match ($status) {
                    401 => 'Token API Laravel dan ManukAPP tidak cocok.',

                    403 => 'ManukAPP menolak akses channel atau server Discord.',

                    404 => 'Endpoint direktori tidak ditemukan.',

                    422 => 'Request ditolak ManukAPP: '.$reason,

                    429 => 'Terlalu banyak permintaan. Coba lagi.',

                    500, 502, 503 => 'API ManukAPP bermasalah (HTTP '.$status.').',

                    default => 'Discord API gagal (HTTP '.$status.'): '.$reason,
                };

                $this->addError('directory', $message);

                return null;
            }

            $result = $response->json();

            if (! is_array($result)) {
                $this->addError(
                    'directory',
                    'Respons API ManukAPP tidak valid.'
                );

                return null;
            }

            return $result;

        } catch (Throwable $e) {
            Log::warning(
                'Hollowfang Announcement Directory Exception',
                [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'exception' => get_class($e),
                ]
            );

            $this->addError(
                'directory',
                'Request direktori gagal: '.class_basename($e)
            );

            return null;
        }
    }

    // =====================================================
    // REFRESH CHANNEL & ROLE DIRECTORY
    // =====================================================

    public function refreshDirectory(): bool
    {
        $this->guardOwner();

        $this->resetErrorBag('directory');

        $data = $this->directoryRequest(
            'GET',
            '/api/announcements/directory'
        );

        if ($data === null) {
            return false;
        }

        $channels = [];
        $approved = [];
        $roles = [];

        foreach (($data['channels'] ?? []) as $channel) {
            if (! is_array($channel)) {
                continue;
            }

            $id = (string) ($channel['id'] ?? '');
            $name = (string) ($channel['name'] ?? '');

            if (! preg_match('/^\d{15,22}$/', $id)) {
                continue;
            }

            $channels[$id] = $name;

            if ($channel['allowed'] ?? false) {
                $approved[$id] = $name;
            }
        }

        foreach (($data['roles'] ?? []) as $role) {
            if (! is_array($role)) {
                continue;
            }

            $id = (string) ($role['id'] ?? '');

            if (! preg_match('/^\d{15,22}$/', $id)) {
                continue;
            }

            $roles[$id] = (string) (
                $role['name'] ?? 'Role'
            );
        }

        $this->directoryChannels = $channels;
        $this->approvedChannels = $approved;
        $this->directoryRoles = $roles;

        if (
            ! isset(
                $this->approvedChannels[$this->channelId]
            )
        ) {
            $this->channelId = (string) (
                array_key_first($this->approvedChannels) ?? ''
            );
        }

        return true;
    }

    // =====================================================
    // ENABLE / DISABLE ANNOUNCEMENT CHANNEL
    // =====================================================

    public function toggleAnnouncementChannel(string $id): void
    {
        $this->guardOwner();

        $this->resetErrorBag('directory');

        if (! $this->refreshDirectory()) {
            return;
        }

        if (! isset($this->directoryChannels[$id])) {
            $this->addError(
                'directory',
                'Channel tidak ditemukan dalam server Discord.'
            );

            return;
        }

        // Discord ID wajib dikirim sebagai string JSON.
        $ids = array_map(
            'strval',
            array_keys($this->approvedChannels)
        );

        $isActive = in_array($id, $ids, true);

        if ($isActive) {
            $ids = array_values(
                array_diff($ids, [$id])
            );
        } else {
            $ids[] = $id;

            $ids = array_values(
                array_unique($ids)
            );
        }

        foreach ($ids as $channelId) {
            if (! preg_match('/^\d{15,22}$/', $channelId)) {
                $this->addError(
                    'directory',
                    'Daftar channel mengandung ID tidak valid.'
                );

                return;
            }
        }

        $result = $this->directoryRequest(
            'PUT',
            '/api/announcements/channels',
            [
                'channel_ids' => $ids,
            ]
        );

        if ($result === null) {
            return;
        }

        if (($result['ok'] ?? false) !== true) {
            $this->addError(
                'directory',
                'ManukAPP tidak mengonfirmasi perubahan channel.'
            );

            return;
        }

        if (! $this->refreshDirectory()) {
            return;
        }

        session()->flash(
            'directory_success',
            $isActive
                ? 'Channel berhasil dinonaktifkan.'
                : 'Channel berhasil diaktifkan.'
        );
    }

    // =====================================================
    // INSERT SINGLE ROLE MENTION
    // =====================================================

    public function insertSelectedRole(): void
    {
        $this->guardOwner();

        if (
            ! preg_match('/^\d{15,22}$/', $this->selectedRoleId) ||
            ! isset($this->directoryRoles[$this->selectedRoleId])
        ) {
            $this->addError(
                'selectedRoleId',
                'Pilih role Discord yang valid.'
            );

            return;
        }

        $this->resetErrorBag('selectedRoleId');

        $mention = '<@&'.$this->selectedRoleId.'>';

        $separator = (
            $this->description === '' ||
            str_ends_with($this->description, "\n")
        ) ? '' : "\n";

        $candidate = $this->description
            .$separator
            .$mention;

        if (mb_strlen($candidate) > 4000) {
            $this->addError(
                'description',
                'Teks melebihi 4000 karakter.'
            );

            return;
        }

        $this->description = $candidate;
    }

    // =====================================================
    // NORMALIZE ROLE NAMES
    // =====================================================

    private function normalizeRoleName(string $name): string
    {
        $name = mb_strtolower(trim($name));

        // Abaikan ornamen gothic pada nama role.
        // Pertahankan huruf, angka, dan spasi.
        $normalized = preg_replace(
            '/[^\p{L}\p{N}]+/u',
            ' ',
            $name
        );

        if ($normalized === null) {
            return '';
        }

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $normalized
            ) ?? ''
        );
    }

    // =====================================================
    // RESOLVE ALL ROLE PLACEHOLDERS
    // =====================================================

    public function resolveAllRoles(): void
    {
        $this->guardOwner();

        $this->resetErrorBag('roles');
        $this->resetErrorBag('description');

        // Selalu sinkronkan role terbaru dari Discord.
        if (! $this->refreshDirectory()) {
            return;
        }

        $description = $this->description;

        // Mendukung:
        // {role:caelum}
        // [ROLE:caelum]
        // [ROLE: caelum]
        // {role: praesidium}
        $pattern = '/\{role:\s*([^{}\r\n]{1,100}?)\s*\}|\[role:\s*([^\[\]\r\n]{1,100}?)\s*\]/iu';

        $matchCount = preg_match_all(
            $pattern,
            $description,
            $matches,
            PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
        );

        if ($matchCount === false) {
            $this->addError(
                'roles',
                'Gagal membaca format placeholder role.'
            );

            return;
        }

        if ($matchCount === 0) {
            $this->addError(
                'roles',
                'Tidak ditemukan placeholder role. Gunakan {role:caelum} atau [ROLE:caelum].'
            );

            return;
        }

        $replacements = [];
        $unresolved = [];

        foreach ($matches as $match) {
            $placeholder = $match[0];

            // Ambil nama role dari format kurung
            // kurawal atau kurung siku.
            $roleName = $match[1] ?? '';

            if ($roleName === '') {
                $roleName = $match[2] ?? '';
            }

            $requested = $this->normalizeRoleName(
                $roleName
            );

            if ($requested === '') {
                $unresolved[] = $placeholder;

                continue;
            }

            $exact = [];
            $suffix = [];

            foreach ($this->directoryRoles as $id => $name) {
                $id = (string) $id;

                if (! preg_match('/^\d{15,22}$/', $id)) {
                    continue;
                }

                $normalized = $this->normalizeRoleName(
                    (string) $name
                );

                // Cocok persis.
                if ($normalized === $requested) {
                    $exact[$id] = $name;

                    continue;
                }

                // Cocok dengan akhiran nama.
                // Berguna untuk role yang memiliki
                // simbol atau prefix dekoratif.
                if (
                    str_ends_with(
                        $normalized,
                        ' '.$requested
                    )
                ) {
                    $suffix[$id] = $name;
                }
            }

            // Cocok persis lebih diprioritaskan.
            $candidates = count($exact) > 0
                ? $exact
                : $suffix;

            // Jangan membuat mention jika:
            // - role tidak ditemukan
            // - ada dua role dengan nama mirip
            if (count($candidates) !== 1) {
                $unresolved[] = $placeholder;

                continue;
            }

            $roleId = (string) array_key_first(
                $candidates
            );

            $replacements[$placeholder] =
                '<@&'.$roleId.'>';
        }

        // Jangan mengganti sebagian jika
        // masih ada role yang tidak cocok.
        if (! empty($unresolved)) {
            $this->addError(
                'roles',
                'Role tidak ditemukan atau ambigu: '.
                implode(
                    ', ',
                    array_unique($unresolved)
                )
            );

            return;
        }

        // Ganti placeholder dengan mention Discord.
        $resolved = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $description
        );

        if (mb_strlen($resolved) > 4000) {
            $this->addError(
                'roles',
                'Hasil mention melebihi 4000 karakter.'
            );

            return;
        }

        // Pastikan tidak ada placeholder role
        // yang belum diproses.
        if (
            preg_match(
                '/\{role:|\[role:/iu',
                $resolved
            )
        ) {
            $this->addError(
                'roles',
                'Masih ada placeholder role yang belum valid.'
            );

            return;
        }

        // Simpan teks hasil konversi ke Livewire.
        $this->description = $resolved;

        session()->flash(
            'roles_resolved',
            $matchCount.
            ' role berhasil dikonversi menjadi mention Discord!'
        );
    }
}
