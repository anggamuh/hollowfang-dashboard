<div wire:poll.15s.visible class="hf-app min-h-screen text-zinc-100" x-data="{ query: '' }" x-on:keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); query = ''; $refs.search.showModal(); $nextTick(() => $refs.searchInput.focus()); }">
    <a href="#hf-content" class="hf-skip">Lewati navigasi</a>

    @php
        $account = auth()->user();

        $discordConnected = !empty($account?->discord_id);

        $discordName = $account?->discord_username
            ?: $account?->name
            ?: 'Discord User';

        $discordAvatar = $account?->discord_avatar;

        // Fallback saat API bot belum tersambung atau baru dimulai.
        $botHealth = $botHealth ?? [];
        $apiStatus = data_get($botHealth, 'status', 'unconfigured');
        $apiChecked = data_get($botHealth, 'checked_at');
        $apiMessage = match ($apiStatus) {
            'online' => 'Sinkronisasi monitoring berhasil.',
            'unconfigured' => 'Isi MANUK_API_BASE_URL dan MANUK_API_TOKEN pada .env dashboard.',
            'unauthorized' => 'Token API ditolak bot. Cocokkan MANUK_API_TOKEN di kedua aplikasi.',
            'invalid_response' => 'Respons API tidak sesuai kontrak monitoring crypt.',
            'http_error' => 'Endpoint /api/health mengembalikan HTTP error.',
            'bot_offline' => 'API merespons tetapi Discord bot belum ready.',
            default => 'API tidak terjangkau dari server Laravel. Periksa alamat host, port, dan jaringan.',
        };
        $botConnected = data_get($botHealth, 'connected') === true;
        $botName = data_get($botHealth, 'bot.name', 'crypt');
        $botPing = $botConnected ? data_get($botHealth, 'bot.ping_ms') : null;
        $uptimeSeconds = $botConnected ? data_get($botHealth, 'bot.uptime_seconds') : null;
        $botUptime = is_numeric($uptimeSeconds)
            ? ((int) floor($uptimeSeconds / 86400) > 0
                ? floor($uptimeSeconds / 86400) . 'd '
                : '')
              . floor(($uptimeSeconds % 86400) / 3600) . 'h '
              . floor(($uptimeSeconds % 3600) / 60) . 'm'
            : '—';

        $guildName = $botConnected ? data_get($botHealth, 'discord.guild_name') : null;
        $gatewayConnected = $botConnected && data_get($botHealth, 'discord.connected') === true;
        $guildId = $botConnected ? data_get($botHealth, 'discord.guild_id') : null;
        $voiceChannelId = $botConnected ? data_get($botHealth, 'music.voice_channel_id') : null;
        $guildMembers = $botConnected ? data_get($botHealth, 'discord.total_members') : null;
        $onlineMembers = $botConnected ? data_get($botHealth, 'discord.online_members') : null;
        $musicSessions = $botConnected ? data_get($botHealth, 'music.active_sessions') : null;
        $nowPlaying = $botConnected ? data_get($botHealth, 'music.now_playing') : null;
        $queueLength = $botConnected ? data_get($botHealth, 'music.queue_length') : null;
        $queuePreview = $botConnected ? (data_get($botHealth, 'music.queue') ?? []) : [];
        $queuePreview = is_array($queuePreview) ? array_slice($queuePreview, 0, 12) : [];
        $musicPaused = data_get($botHealth, 'music.paused') === true;
        $trackDurationMs = data_get($nowPlaying, 'duration_ms');
        $trackPositionMs = data_get($nowPlaying, 'position_ms');
        $trackProgress = is_numeric($trackPositionMs) && is_numeric($trackDurationMs) && (float) $trackDurationMs > 0
            ? max(0, min(100, round(100 * (float) $trackPositionMs / (float) $trackDurationMs)))
            : null;

        $lavalinkStatus = $botConnected
            ? data_get($botHealth, 'lavalink.status', 'Unknown')
            : 'Unknown';
        $lavalinkConnected = $botConnected && data_get($botHealth, 'lavalink.connected') === true;

        $isOwner = $discordConnected
            && in_array(
                (string) $account->discord_id,
                config('hollowfang.owner_discord_ids', []),
                true
            );
    @endphp

    @include('livewire.partials.control-shell')
                @if (! $botConnected)
                    <div role="status" class="flex items-start gap-3 rounded-2xl border border-amber-400/20 bg-amber-400/5 p-4 text-sm text-amber-200">
                        <span class="text-lg">◇</span>
                        <div><p class="font-semibold">Monitoring offline</p><p class="mt-1 text-amber-100/70">{{ $apiMessage }}</p></div>
                    </div>
                @endif

                {{-- RECENT BOT STATUS TRANSITIONS --}}
                @if ($activeMenu === 'monitoring')
                    <section aria-labelledby="bot-status-history-heading" class="rounded-2xl border border-violet-400/15 bg-[#191a21] p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 id="bot-status-history-heading" class="text-sm font-semibold text-white">Riwayat Koneksi Bot</h3>
                                <p class="mt-1 text-xs text-zinc-500">Perubahan status terakhir yang diamati dashboard, bukan catatan uptime penuh.</p>
                            </div>
                            <span class="text-[11px] text-zinc-500">Maksimal 20 perubahan · disimpan 7 hari</span>
                        </div>
                        <ol class="mt-4 space-y-3">
                            @forelse (($statusHistory ?? []) as $entry)
                                @php
                                    $entryStatus = $entry['status'];
                                    $entryLabel = match ($entryStatus) {
                                        'online' => 'Bot online',
                                        'bot_offline' => 'Discord bot offline',
                                        'unreachable' => 'API tidak terjangkau',
                                        'unauthorized' => 'Token API ditolak',
                                        'http_error' => 'API mengembalikan error',
                                        'invalid_response' => 'Respons API tidak valid',
                                        default => 'Monitoring belum dikonfigurasi',
                                    };
                                @endphp
                                <li class="flex items-center justify-between gap-3 rounded-xl border border-white/5 bg-white/[0.025] px-3 py-2 text-xs">
                                    <span class="flex items-center gap-2 {{ $entryStatus === 'online' ? 'text-emerald-300' : 'text-amber-300' }}">
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $entryStatus === 'online' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                        {{ $entryLabel }}
                                    </span>
                                    <time class="shrink-0 text-zinc-500" datetime="{{ $entry['at'] }}">{{ \Illuminate\Support\Carbon::parse($entry['at'])->format('d M Y, H:i:s') }}</time>
                                </li>
                            @empty
                                <li class="text-xs text-zinc-500">Belum ada perubahan status yang tercatat.</li>
                            @endforelse
                        </ol>
                    </section>
                @endif

                {{-- SUCCESS NOTIFICATION --}}
                @if (session('success'))

                    <div
                        role="status"
                        class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-300"
                    >
                        {{ session('success') }}
                    </div>

                @endif

                {{-- DISCORD ERROR --}}
                @if ($errors->has('discord'))

                    <div
                        role="alert"
                        class="rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300"
                    >
                        {{ $errors->first('discord') }}
                    </div>

                @endif

                {{-- OVERVIEW --}}
                @if ($activeMenu === 'overview')
                    @include('livewire.partials.control-overview')

                {{-- BOT MONITORING --}}
                @elseif ($activeMenu === 'monitoring')

                    <div class="space-y-5">
                        <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-xl font-semibold text-white">Bot Monitoring</h3>
                                    <p class="mt-2 text-sm text-zinc-400">Monitoring otomatis setiap 15 detik.</p>
                                </div>
                                <span @class([
                                    'rounded-full border px-3 py-1 text-xs font-semibold',
                                    'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' => $botConnected,
                                    'border-amber-500/20 bg-amber-500/10 text-amber-400' => ! $botConnected,
                                ])>{{ $botConnected ? 'API Connected' : 'API Not Connected' }}</span>
                            </div>
                            @if (! $botConnected)
                                <p class="mt-5 rounded-xl border border-amber-500/20 bg-amber-500/5 p-4 text-sm text-amber-300">
                                    Tidak bisa membaca ManukAPP. Pastikan bot berjalan dan token API Laravel sudah sesuai.
                                </p>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ([
                                ['Gateway Ping', $botPing !== null ? $botPing . ' ms' : '—'],
                                ['Uptime', $botUptime],
                                ['Total Members', $guildMembers ?? '—'],
                                ['Music Sessions', $musicSessions ?? '—'],
                            ] as [$title, $value])
                                <div class="rounded-2xl border border-white/10 bg-[#191a21] p-5">
                                    <p class="text-xs text-zinc-500">{{ $title }}</p>
                                    <p class="mt-3 text-2xl font-bold text-white">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                            <h4 class="font-semibold text-white">Koneksi & Server Discord</h4>
                            <dl class="mt-5 space-y-4 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-zinc-500">Bot</dt>
                                    <dd class="text-right text-zinc-200">{{ $botConnected ? $botName : 'Offline' }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-zinc-500">Guild</dt>
                                    <dd class="text-right text-zinc-200">{{ $guildName ?: '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-zinc-500">Discord Gateway</dt>
                                    <dd class="text-right text-zinc-200">{{ $botConnected ? 'Connected' : 'Unknown' }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-zinc-500">Lavalink</dt>
                                    <dd class="text-right text-zinc-200">{{ $lavalinkConnected ? 'Connected' : $lavalinkStatus }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-zinc-500">Online Members</dt>
                                    <dd class="text-right text-zinc-200">{{ $onlineMembers ?? 'Belum tersedia' }}</dd>
                                </div>
                            </dl>
                            <p class="mt-5 border-t border-white/10 pt-4 text-xs text-zinc-500">
                                Jumlah member online belum tersedia karena API tidak mengumpulkan data presence Discord.
                            </p>
                        </div>

                        {{-- LIVE MUSIC: monitoring only; no remote playback controls --}}
                        <div class="grid gap-5 xl:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h4 class="font-semibold text-white">Now Playing</h4>
                                    <span class="rounded-full bg-white/5 px-3 py-1 text-xs text-zinc-400">
                                        {{ $nowPlaying ? ($musicPaused ? 'Paused' : 'Playing') : 'Idle' }}
                                    </span>
                                </div>
                                @if ($nowPlaying)
                                    <div class="mt-5 flex gap-4">
                                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-violet-500/10 text-2xl text-violet-300">♫</div>
                                        <div class="min-w-0 flex-1">
                                            <p class="break-words text-base font-semibold text-white">{{ data_get($nowPlaying, 'title', 'Unknown track') }}</p>
                                            <p class="mt-1 break-words text-sm text-zinc-400">{{ data_get($nowPlaying, 'artist', 'Unknown artist') }}</p>
                                            <p class="mt-2 text-xs text-zinc-500">Source: {{ data_get($nowPlaying, 'source', 'Unknown') }}</p>
                                        </div>
                                    </div>
                                    @if ($trackProgress !== null)
                                        <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-white/10">
                                            <div class="h-full rounded-full bg-violet-400" style="width: {{ $trackProgress }}%"></div>
                                        </div>
                                        <p class="mt-2 text-xs text-zinc-500">{{ gmdate('i:s', (int) floor($trackPositionMs / 1000)) }} / {{ gmdate('i:s', (int) floor($trackDurationMs / 1000)) }}</p>
                                    @endif
                                @else
                                    <p class="mt-5 rounded-xl border border-dashed border-white/10 p-6 text-center text-sm text-zinc-500">
                                        {{ $botConnected ? 'Belum ada lagu yang diputar pada guild monitoring.' : 'Menunggu koneksi ManukAPP.' }}
                                    </p>
                                @endif
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-semibold text-white">Music Queue</h4>
                                    <span class="text-xs text-zinc-400">{{ $queueLength ?? '—' }} lagu</span>
                                </div>
                                @forelse ($queuePreview as $index => $track)
                                    <div class="mt-4 flex gap-3 border-b border-white/5 pb-3 text-sm">
                                        <span class="w-5 shrink-0 text-zinc-500">{{ $index + 1 }}</span>
                                        <div class="min-w-0">
                                            <p class="break-words text-zinc-200">{{ data_get($track, 'title', 'Unknown') }}</p>
                                            <p class="mt-1 break-words text-xs text-zinc-500">{{ data_get($track, 'artist', 'Unknown') }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="mt-5 rounded-xl border border-dashed border-white/10 p-6 text-center text-sm text-zinc-500">Antrean kosong.</p>
                                @endforelse
                                @if (is_numeric($queueLength) && $queueLength > count($queuePreview))
                                    <p class="mt-3 text-xs text-zinc-500">Menampilkan {{ count($queuePreview) }} dari {{ $queueLength }} lagu.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                {{-- DISCORD GATEWAY: real read-only data from /api/health --}}
                @elseif ($activeMenu === 'gateway')
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            ['Gateway', $gatewayConnected ? 'CONNECTED' : 'UNKNOWN', $gatewayConnected ? 'Koneksi Discord aktif' : 'Status tidak tersedia'],
                            ['Ping', $botPing !== null ? $botPing.' ms' : '—', 'Latensi WebSocket'],
                            ['Guild', $guildName ?: '—', $guildId ?: 'ID tidak tersedia'],
                            ['Members', $guildMembers ?? '—', 'Member count dari Discord'],
                        ] as [$title, $value, $description])
                            <article class="rounded-2xl border border-white/10 bg-[#191a23] p-5">
                                <p class="text-xs uppercase tracking-wider text-zinc-500">{{ $title }}</p>
                                <p class="mt-3 break-words text-xl font-bold text-white">{{ $value }}</p>
                                <p class="mt-2 break-all text-xs text-zinc-500">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                    <div class="grid gap-5 xl:grid-cols-2">
                        <section class="rounded-2xl border border-violet-400/15 bg-[#191822] p-6">
                            <p class="text-xs font-bold uppercase tracking-[.25em] text-violet-300">Gateway Connection</p>
                            <h3 class="mt-2 text-xl font-semibold text-white">Status Discord</h3>
                            <dl class="mt-5 space-y-4 text-sm">
                                <div class="flex justify-between gap-3 border-b border-white/5 pb-3"><dt class="text-zinc-500">Bot Account</dt><dd class="text-right text-white">{{ $botConnected ? $botName : 'Tidak tersedia' }}</dd></div>
                                <div class="flex justify-between gap-3 border-b border-white/5 pb-3"><dt class="text-zinc-500">Guild ID</dt><dd class="break-all text-right font-mono text-xs text-zinc-300">{{ $guildId ?: '—' }}</dd></div>
                                <div class="flex justify-between gap-3 border-b border-white/5 pb-3"><dt class="text-zinc-500">Uptime</dt><dd class="text-right text-white">{{ $botUptime }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-zinc-500">Member Online</dt><dd class="text-right text-zinc-300">{{ $onlineMembers ?? 'Belum tersedia dari monitoring API' }}</dd></div>
                            </dl>
                        </section>
                        <section class="rounded-2xl border border-white/10 bg-[#191a23] p-6">
                            <p class="text-xs font-bold uppercase tracking-[.25em] text-violet-300">Connection Diagnostics</p>
                            <h3 class="mt-2 text-xl font-semibold text-white">Kesehatan Integrasi</h3>
                            <div class="mt-5 space-y-3">
                                @foreach ([
                                    ['Monitoring API', $botConnected ? 'Connected' : strtoupper(str_replace('_', ' ', $apiStatus))],
                                    ['Discord Gateway', $gatewayConnected ? 'Connected' : 'Unknown'],
                                    ['Lavalink', $lavalinkConnected ? 'Connected' : $lavalinkStatus],
                                ] as [$name, $state])
                                    <div class="flex justify-between gap-4 rounded-xl border border-white/5 bg-[#11121b] px-4 py-3 text-sm">
                                        <span class="text-zinc-400">{{ $name }}</span><span class="text-right text-zinc-100">{{ $state }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-5 text-xs leading-relaxed text-zinc-500">Monitoring hanya membaca status, tidak memiliki izin untuk mengubah gateway, voice, maupun konfigurasi bot.</p>
                        </section>
                    </div>

                {{-- MUSIC MONITORING: no remote playback actions --}}
                @elseif ($activeMenu === 'music')
                    <div class="relative overflow-hidden rounded-3xl border border-violet-400/15 bg-gradient-to-br from-[#22162f] to-[#15151e] p-6 sm:p-8">
                        <div class="pointer-events-none absolute -right-16 -top-28 h-72 w-72 rounded-full bg-violet-600/15 blur-3xl"></div>
                        <p class="text-xs font-bold uppercase tracking-[.32em] text-violet-300">♫ Crypt · Music Sanctuary</p>
                        <h3 class="mt-3 text-2xl font-semibold text-white">Live Music Monitoring</h3>
                        <p class="mt-2 text-sm text-zinc-400">Lihat lagu, antrean, dan status Lavalink serta kontrol playback melalui API bot yang diperbarui.</p>
                        <div class="relative mt-5 flex flex-wrap gap-2">
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">{{ $nowPlaying ? ($musicPaused ? 'Ⅱ Paused' : '♫ Playing') : '◇ Idle' }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">Sessions: {{ $musicSessions ?? '—' }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">Queue: {{ $queueLength ?? '—' }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">Lavalink: {{ $lavalinkConnected ? 'Connected' : $lavalinkStatus }}</span>
                        </div>
                    </div>
                    <section class="rounded-3xl border border-violet-400/15 bg-[#191a22] p-5 sm:p-6" aria-labelledby="hf-music-controls">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h4 id="hf-music-controls" class="text-lg font-semibold text-white">Music Remote Control</h4>
                                <p class="mt-1 text-xs text-zinc-400">Kontrol pemutaran untuk Owner/Admin Discord. Membutuhkan endpoint /api/music/control di bot ManukAPP versi terbaru.</p>
                            </div>
                            <span class="rounded-full border border-violet-400/15 bg-violet-500/10 px-3 py-1 text-xs text-violet-200">Owner only · 10 aksi/menit</span>
                        </div>
                        @if ($musicFeedback)
                            <p role="status" class="mt-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-sm text-emerald-200">{{ $musicFeedback }}</p>
                        @endif
                        @if ($musicError)
                            <p role="alert" class="mt-4 rounded-xl border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-200">{{ $musicError }}</p>
                        @endif
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ([
                                ['pause', 'Ⅱ Pause'], ['resume', '▶ Resume'], ['skip', '⇥ Skip'],
                                ['shuffle', '⤨ Shuffle'], ['autoplay_on', 'Autoplay ON'], ['autoplay_off', 'Autoplay OFF'],
                                ['loop_off', 'Loop OFF'], ['loop_song', 'Loop Song'], ['loop_queue', 'Loop Queue'],
                            ] as [$action, $label])
                                <button type="button" wire:click="controlMusic('{{ $action }}')"
                                    @if ($action === 'skip') wire:confirm="Lewati lagu yang sedang diputar?" @endif
                                    wire:loading.attr="disabled" wire:target="controlMusic"
                                    @disabled(! $botConnected)
                                    class="rounded-xl border border-violet-400/20 bg-violet-500/10 px-4 py-2.5 text-sm font-medium text-violet-100 transition hover:bg-violet-500/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-violet-400 disabled:cursor-not-allowed disabled:opacity-40">{{ $label }}</button>
                            @endforeach
                        </div>
                    </section>
                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                        <section class="min-w-0 rounded-3xl border border-white/10 bg-[#191a22] p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3"><h4 class="text-lg font-semibold text-white">Now Playing</h4><span class="text-xs text-zinc-500">Realtime snapshot · 15s</span></div>
                            @if ($nowPlaying)
                                <div class="mt-6 flex items-start gap-5">
                                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl border border-violet-400/20 bg-violet-500/10 text-5xl text-violet-300">♫</div>
                                    <div class="min-w-0 flex-1">
                                        <p class="break-words text-xl font-semibold text-white">{{ data_get($nowPlaying, 'title', 'Unknown track') }}</p>
                                        <p class="mt-2 break-words text-sm text-zinc-400">{{ data_get($nowPlaying, 'artist', 'Unknown artist') }}</p>
                                        <p class="mt-3 text-xs text-violet-300">{{ data_get($nowPlaying, 'source', 'Unknown') }} · {{ $musicPaused ? 'Paused' : 'Playing' }}</p>
                                    </div>
                                </div>
                                @if ($trackProgress !== null)
                                    <div class="mt-8 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-gradient-to-r from-violet-700 to-fuchsia-400" style="width: {{ $trackProgress }}%"></div></div>
                                    <div class="mt-2 flex justify-between text-xs text-zinc-500"><span>{{ gmdate('i:s', (int) floor($trackPositionMs / 1000)) }}</span><span>{{ gmdate('i:s', (int) floor($trackDurationMs / 1000)) }}</span></div>
                                @endif
                            @else
                                <div class="mt-6 flex min-h-48 flex-col items-center justify-center rounded-2xl border border-dashed border-violet-400/15 bg-white/[.02] p-7 text-center">
                                    <span class="text-4xl text-violet-400/50">♫</span>
                                    <p class="mt-4 text-sm font-medium text-zinc-300">{{ $botConnected ? 'Tidak ada lagu aktif' : 'Monitoring belum terkoneksi' }}</p>
                                    <p class="mt-2 text-xs text-zinc-500">Gunakan command musik di Discord untuk memulai sesi.</p>
                                </div>
                            @endif
                            <div class="mt-6 flex justify-between gap-3 border-t border-white/10 pt-4 text-xs text-zinc-500"><span>Voice channel ID</span><span class="break-all text-right font-mono">{{ $voiceChannelId ?: '—' }}</span></div>
                        </section>
                        <section class="min-w-0 rounded-3xl border border-white/10 bg-[#191a22] p-6">
                            <div class="flex justify-between gap-3"><h4 class="text-lg font-semibold text-white">Up Next</h4><span class="text-xs text-zinc-500">{{ $queueLength ?? '—' }} track</span></div>
                            <div class="mt-5 space-y-2">
                                @forelse ($queuePreview as $index => $track)
                                    <div class="flex items-center gap-3 rounded-xl border border-white/5 bg-[#12131b] p-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-400/10 text-sm font-semibold text-violet-300">{{ $index + 1 }}</div>
                                        <div class="min-w-0"><p class="break-words text-sm font-medium text-zinc-200">{{ data_get($track, 'title', 'Unknown') }}</p><p class="mt-1 break-words text-xs text-zinc-500">{{ data_get($track, 'artist', 'Unknown artist') }}</p></div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center text-sm text-zinc-500">Belum ada lagu di antrean.</div>
                                @endforelse
                                @if (is_numeric($queueLength) && $queueLength > count($queuePreview))
                                    <p class="pt-2 text-xs text-zinc-500">Menampilkan {{ count($queuePreview) }} dari {{ $queueLength }} lagu.</p>
                                @endif
                            </div>
                        </section>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Panduan kontrol musik Discord">
                        @foreach ([
                            ['/play', 'Putar lagu atau masukkan ke antrean'],
                            ['/pause /resume', 'Jeda dan lanjutkan playback'],
                            ['/skip /voteskip', 'Lewati lagu sesuai aturan DJ/voting'],
                            ['/autoplay /loop', 'Atur rekomendasi dan pengulangan'],
                            ['/queue /shuffle', 'Lihat dan acak antrean'],
                            ['/remove /move', 'Kelola posisi lagu'],
                            ['/247', 'Kelola mode musik 24/7'],
                            ['/musicsettings', 'Pengaturan musik per server'],
                        ] as [$command, $description])
                            <article class="rounded-2xl border border-white/10 bg-[#191a22] p-4">
                                <p class="font-mono text-sm font-semibold text-violet-200">{{ $command }}</p>
                                <p class="mt-2 text-xs text-zinc-400">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                    <p class="text-xs text-zinc-500">Slash command Discord tetap tersedia. Tombol remote memerlukan bot ManukAPP yang sudah menjalankan endpoint kontrol baru.</p>

                {{-- WEBHOOKS & EMBEDS --}}
                @elseif ($activeMenu === 'webhooks')
                    <div class="space-y-5">
                        <div class="rounded-2xl border border-white/10 bg-[#191a21] p-4 sm:p-5">
                            <h3 class="font-semibold text-white">Announcements & Discord Webhooks</h3>
                            <p class="mt-1 text-xs text-zinc-400">Kirim pengumuman lewat bot atau gunakan webhook Discord tersimpan. Keduanya memakai autentikasi Owner.</p>
                            <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Pilih jenis pengiriman">
                                <button type="button" wire:click="changeWebhookTab('announcements')" aria-pressed="{{ $webhookTab === 'announcements' ? 'true' : 'false' }}"
                                    @class(['rounded-xl px-4 py-2.5 text-sm font-medium transition', 'bg-violet-700 text-white' => $webhookTab === 'announcements', 'border border-white/10 bg-white/5 text-zinc-300 hover:bg-white/10' => $webhookTab !== 'announcements'])>Bot Announcements</button>
                                <button type="button" wire:click="changeWebhookTab('webhooks')" aria-pressed="{{ $webhookTab === 'webhooks' ? 'true' : 'false' }}"
                                    @class(['rounded-xl px-4 py-2.5 text-sm font-medium transition', 'bg-violet-700 text-white' => $webhookTab === 'webhooks', 'border border-white/10 bg-white/5 text-zinc-300 hover:bg-white/10' => $webhookTab !== 'webhooks'])>Discord Webhook Manager</button>
                            </div>
                        </div>
                        @if ($webhookTab === 'announcements')
                            <livewire:announcement-manager :key="'announcements-module'" />
                        @else
                            <livewire:webhook-manager :key="'webhooks-module'" />
                        @endif
                    </div>

                {{-- HOLLOWFANG_V3_INTEGRATED --}}
                @elseif (in_array($activeMenu, ['moderation', 'members', 'events', 'logs'], true))
                    <livewire:hollowfang-v3 :initial-tab="match ($activeMenu) { 'members' => 'members', 'logs' => 'logs', 'events' => 'events', default => 'moderation' }" :key="'hollowfang-v3-'.$activeMenu" />

                {{-- HOLLOWFANG_V4_COMMUNITY --}}
                @elseif ($activeMenu === 'community')
                    <livewire:hollowfang-community :key="'hf-community'" />

                {{-- OTHER MODULES --}}
                @elseif ($activeMenu === 'settings')
                    <section class="rounded-3xl border border-violet-400/20 bg-[#191a21] p-6 sm:p-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-violet-300">System settings</p>
                        <h3 class="mt-2 text-xl font-semibold">Kesiapan koneksi Hollowfang</h3>
                        <p class="mt-2 text-sm text-zinc-400">Konfigurasi dibaca dari server. Ubah melalui .env lalu bersihkan cache konfigurasi Laravel.</p>
                        <div class="mt-6 divide-y divide-white/10">
                            @foreach ([
                                ['MANUK_API_BASE_URL', trim((string) config('services.manuk.base_url')) !== '', 'Alamat monitoring dan kontrol musik'],
                                ['MANUK_API_TOKEN', strlen(trim((string) config('services.manuk.token'))) >= 32, 'Token minimal 32 karakter; nilainya tidak ditampilkan'],
                                ['DISCORD_CLIENT_ID', filled(config('services.discord.client_id')), 'Identitas aplikasi OAuth Discord'],
                                ['DISCORD_CLIENT_SECRET', filled(config('services.discord.client_secret')), 'Secret OAuth; nilainya tidak ditampilkan'],
                                ['DISCORD_REDIRECT_URI', filled(config('services.discord.redirect')), 'Callback OAuth dashboard'],
                                ['HOLLOWFANG_OWNER_DISCORD_IDS', count(config('hollowfang.owner_discord_ids', [])) > 0, 'Daftar akun pengelola dashboard'],
                            ] as [$setting, $configured, $hint])
                                <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                                    <div class="min-w-0"><p class="break-all font-mono text-xs text-zinc-200">{{ $setting }}</p><p class="mt-1 text-xs text-zinc-500">{{ $hint }}</p></div>
                                    <span class="rounded-full px-3 py-1 text-xs {{ $configured ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300' }}">{{ $configured ? 'Terisi' : 'Perlu dilengkapi' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5 rounded-2xl border border-white/10 bg-black/10 p-4 text-sm text-zinc-300">
                            <p class="font-semibold">Hasil pemeriksaan API</p><p class="mt-2 text-zinc-400">{{ $apiMessage }}</p>
                            <p class="mt-2 text-xs text-zinc-500">Status terisi tidak menjamin koneksi berhasil. Monitoring diperiksa melalui endpoint bot; konfigurasi OAuth belum diuji di sini.</p>
                        </div>
                        <a href="{{ route('discord.connect') }}" class="mt-5 inline-block rounded-xl border border-violet-400/30 px-4 py-3 text-sm text-violet-200 hover:bg-violet-500/10">Sinkronkan akun Discord</a>
                    </section>

                @else

                    <div class="rounded-2xl border border-white/10 bg-[#191a21] p-10 text-center">

                        <h3 class="text-xl font-semibold text-white">
                            {{ $menus[$activeMenu] }}
                        </h3>

                        <p class="mt-3 text-sm text-zinc-500">
                            Modul ini akan dikembangkan pada tahap berikutnya.
                        </p>

                        <div class="mt-5 inline-flex rounded-full bg-white/5 px-4 py-2 text-xs text-zinc-400">
                            Coming Soon
                        </div>

                    </div>

                @endif

        </main>
    </div>
</div>
@include('livewire.partials.control-dialogs')
</div>
