<div wire:poll.15s class="min-h-screen bg-[#0d0e12] text-zinc-100">

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
        $trackProgress = is_numeric($trackDurationMs) && (float) $trackDurationMs > 0
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

    <div class="flex min-h-screen">

        {{-- SIDEBAR --}}
        <aside class="sticky top-0 hidden h-screen w-72 shrink-0 overflow-y-auto border-r border-violet-400/10 bg-[#111019] p-5 lg:block">

            {{-- BRAND --}}
            <div class="mb-8 rounded-2xl border border-violet-400/15 bg-gradient-to-br from-violet-500/10 via-[#1a1228] to-transparent px-4 py-6">
                <p class="mb-2 text-xs tracking-[.35em] text-violet-300">✦ THE HOLLOW</p>

                <h1 class="text-xl font-bold tracking-widest text-white">
                    HOLLOWFANG
                </h1>

                <p class="mt-1 text-xs tracking-wider text-zinc-500">
                    CONTROL CENTER
                </p>

            </div>

            {{-- NAVIGATION --}}
            <nav class="space-y-1">

                @foreach ($menus as $key => $label)

                    <button
                        type="button"
                        wire:click="changeMenu('{{ $key }}')"
                        @class([
                            'w-full rounded-xl px-4 py-3 text-left text-sm transition',
                            'bg-violet-500/15 text-violet-300'
                                => $activeMenu === $key,
                            'text-zinc-400 hover:bg-white/5 hover:text-white'
                                => $activeMenu !== $key,
                        ])
                    >
                        <span class="mr-2 text-violet-400/80">{{ ['overview'=>'✦','monitoring'=>'◉','webhooks'=>'✉','gateway'=>'⌁','moderation'=>'⚑','members'=>'♙','music'=>'♫','events'=>'◷','logs'=>'▤','settings'=>'⚙'][$key] ?? '◇' }}</span>{{ $label }}
                    </button>

                @endforeach

            </nav>

            {{-- BOT STATUS --}}
            <div class="mt-10 rounded-xl border border-white/10 bg-white/5 p-4">

                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">
                    Bot Status
                </p>

                <p @class([
                    'mt-2 flex items-center gap-2 text-sm',
                    'text-emerald-400' => $botConnected,
                    'text-amber-400' => ! $botConnected,
                ])>
                    <span @class([
                        'h-2 w-2 rounded-full',
                        'bg-emerald-400' => $botConnected,
                        'bg-amber-400' => ! $botConnected,
                    ])></span>
                    {{ $botConnected ? 'Online' : 'Not Connected' }}
                </p>
                @if ($botConnected)
                    <p class="mt-2 text-xs text-zinc-500">Ping: {{ $botPing !== null ? $botPing . ' ms' : '—' }}</p>
                @endif

            </div>

            {{-- DISCORD ACCOUNT --}}
            <div class="mt-5 border-t border-white/10 pt-5">

                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    Discord Account
                </p>

                @if ($discordConnected)

                    <div class="rounded-2xl border border-white/10 bg-[#1b1c24] p-4">

                        {{-- PROFILE --}}
                        <div class="flex items-center gap-3">

                            @if ($discordAvatar)

                                <img
                                    src="{{ $discordAvatar }}"
                                    alt="Avatar Discord"
                                    referrerpolicy="no-referrer"
                                    class="h-11 w-11 shrink-0 rounded-full border border-white/10 object-cover"
                                >

                            @else

                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-violet-500/15 text-lg font-bold text-violet-300">
                                    {{ mb_strtoupper(mb_substr($discordName, 0, 1)) }}
                                </div>

                            @endif

                            <div class="min-w-0 flex-1">

                                <p class="truncate text-sm font-semibold text-white"
                                   title="{{ $discordName }}">
                                    {{ $discordName }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-400">
                                    {{ $isOwner ? 'Hollowfang Owner' : 'Connected Account' }}
                                </p>

                            </div>

                        </div>

                        {{-- CONNECTION STATUS --}}
                        <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-3">

                            <span class="flex items-center gap-2 text-xs text-emerald-400">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                Connected
                            </span>

                            @if ($isOwner)
                                <span class="rounded-full bg-violet-500/10 px-2 py-1 text-[10px] font-semibold text-violet-300">
                                    OWNER
                                </span>
                            @endif

                        </div>

                        {{-- DISCORD ID --}}
                        <p class="mt-3 break-all text-[11px] text-zinc-500">
                            ID: {{ $account->discord_id }}
                        </p>

                        {{-- REFRESH ACCOUNT --}}
                        <a
                            href="{{ route('discord.connect') }}"
                            class="mt-4 block rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-center text-xs font-medium text-zinc-300 transition hover:bg-white/10 hover:text-white"
                        >
                            Sinkronkan Profil
                        </a>

                    </div>

                @else

                    <a
                        href="{{ route('discord.connect') }}"
                        class="block rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-medium text-white transition hover:bg-indigo-500"
                    >
                        Hubungkan Discord
                    </a>

                    <p class="mt-2 text-xs leading-relaxed text-zinc-500">
                        Hubungkan akun Discord untuk menggunakan dashboard Hollowfang.
                    </p>

                @endif

            </div>

        </aside>

        {{-- MAIN CONTENT --}}
        <main class="min-w-0 flex-1">

            {{-- HEADER --}}
            <header class="flex items-center justify-between gap-4 border-b border-white/10 bg-[#14151b] px-6 py-5">

                <div class="min-w-0">

                    <p class="text-xs text-zinc-500">
                        Hollowfang / {{ $menus[$activeMenu] }}
                    </p>

                    <h2 class="mt-1 text-xl font-semibold text-white">
                        {{ $menus[$activeMenu] }}
                    </h2>

                </div>

                <div class="flex shrink-0 items-center gap-3">

                    {{-- BOT INDICATOR --}}
                    <div @class([
                        'hidden rounded-full border px-4 py-2 text-xs sm:block',
                        'border-emerald-500/20 bg-emerald-500/5 text-emerald-400' => $botConnected,
                        'border-white/10 text-zinc-400' => ! $botConnected,
                    ])>
                        crypt · {{ $botConnected ? 'Online' : 'Not Connected' }}
                    </div>

                    {{-- DISCORD AVATAR --}}
                    @if ($discordConnected)

                        <div class="flex items-center gap-2">

                            @if ($discordAvatar)

                                <img
                                    src="{{ $discordAvatar }}"
                                    alt="Profil Discord"
                                    referrerpolicy="no-referrer"
                                    class="h-9 w-9 rounded-full border border-white/10 object-cover"
                                >

                            @else

                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-500/15 text-sm font-bold text-violet-300">
                                    {{ mb_strtoupper(mb_substr($discordName, 0, 1)) }}
                                </div>

                            @endif

                            <div class="hidden xl:block">

                                <p class="max-w-36 truncate text-xs font-semibold text-white">
                                    {{ $discordName }}
                                </p>

                                <p class="text-[11px] text-zinc-500">
                                    {{ $isOwner ? 'Owner' : 'Discord Connected' }}
                                </p>

                            </div>

                        </div>

                    @endif

                </div>

            </header>

            {{-- MOBILE NAVIGATION --}}
            <div class="flex gap-2 overflow-x-auto border-b border-white/10 p-3 lg:hidden">

                @foreach ($menus as $key => $label)

                    <button
                        type="button"
                        wire:click="changeMenu('{{ $key }}')"
                        @class([
                            'shrink-0 rounded-lg px-3 py-2 text-xs',
                            'bg-violet-500/20 text-violet-200'
                                => $activeMenu === $key,
                            'bg-white/5 text-zinc-400'
                                => $activeMenu !== $key,
                        ])
                    >
                        <span class="mr-2 text-violet-400/80">{{ ['overview'=>'✦','monitoring'=>'◉','webhooks'=>'✉','gateway'=>'⌁','moderation'=>'⚑','members'=>'♙','music'=>'♫','events'=>'◷','logs'=>'▤','settings'=>'⚙'][$key] ?? '◇' }}</span>{{ $label }}
                    </button>

                @endforeach

            </div>

            {{-- MOBILE DISCORD ACCOUNT --}}
            <div class="border-b border-white/10 p-3 lg:hidden">

                @if ($discordConnected)

                    <div class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-[#1b1c24] p-3">

                        <div class="flex min-w-0 items-center gap-3">

                            @if ($discordAvatar)

                                <img
                                    src="{{ $discordAvatar }}"
                                    alt="Avatar Discord"
                                    class="h-10 w-10 rounded-full object-cover"
                                >

                            @else

                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-500/15 font-semibold text-violet-300">
                                    {{ mb_strtoupper(mb_substr($discordName, 0, 1)) }}
                                </div>

                            @endif

                            <div class="min-w-0">

                                <p class="truncate text-sm font-semibold text-white">
                                    {{ $discordName }}
                                </p>

                                <p class="text-xs text-emerald-400">
                                    Discord Connected
                                </p>

                            </div>

                        </div>

                        <a
                            href="{{ route('discord.connect') }}"
                            class="shrink-0 rounded-lg bg-white/5 px-3 py-2 text-xs text-zinc-300"
                        >
                            Sync
                        </a>

                    </div>

                @else

                    <a
                        href="{{ route('discord.connect') }}"
                        class="block rounded-lg bg-indigo-600 px-4 py-3 text-center text-sm font-medium text-white"
                    >
                        Hubungkan Discord
                    </a>

                @endif

            </div>

            {{-- PAGE CONTENT --}}
            <div class="mx-auto max-w-[1600px] space-y-7 p-4 sm:p-6 lg:p-8">
                <div class="relative overflow-hidden rounded-3xl border border-violet-400/15 bg-gradient-to-r from-[#21152f] via-[#15131f] to-[#11121a] p-5 shadow-xl shadow-black/20 sm:p-7">
                    <div class="pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full bg-violet-600/15 blur-3xl"></div>
                    <div class="relative flex flex-wrap items-center justify-between gap-5">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[.32em] text-violet-300">Hollowfang · Command Sanctuary</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $menus[$activeMenu] }}</h3>
                            <p class="mt-2 max-w-xl text-sm text-zinc-400">Kelola komunitas dan pantau crypt dari satu tempat.</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button type="button" wire:click="refreshNow" wire:loading.attr="disabled" wire:target="refreshNow"
                                class="rounded-xl border border-violet-300/20 bg-violet-500/15 px-4 py-2.5 text-sm font-medium text-violet-100 transition hover:bg-violet-500/25 focus-visible:outline focus-visible:outline-2 focus-visible:outline-violet-400 disabled:opacity-50">
                                <span wire:loading.remove wire:target="refreshNow">↻ Refresh status</span>
                                <span wire:loading wire:target="refreshNow">Mengecek API...</span>
                            </button>
                            <p class="text-[11px] text-zinc-500">{{ $apiChecked ? 'Pemeriksaan: '.\Illuminate\Support\Carbon::parse($apiChecked)->format('H:i:s') : 'Belum diperiksa' }}</p>
                        </div>
                    </div>
                </div>
                @if (! $botConnected)
                    <div role="status" class="flex items-start gap-3 rounded-2xl border border-amber-400/20 bg-amber-400/5 p-4 text-sm text-amber-200">
                        <span class="text-lg">◇</span>
                        <div><p class="font-semibold">Monitoring offline</p><p class="mt-1 text-amber-100/70">{{ $apiMessage }}</p></div>
                    </div>
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

                    <div>

                        <h3 class="text-2xl font-semibold text-white">
                            Welcome to Hollowfang
                        </h3>

                        <p class="mt-2 text-sm text-zinc-500">
                            Pantau aktivitas dan status sistem server Discord.
                        </p>

                    </div>

                    {{-- STATISTICS --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                        @foreach ([
                            ['Total Members', $guildMembers ?? '—', $guildName ?: 'Server belum terdeteksi'],
                            ['Online Members', $onlineMembers ?? '—', 'Belum tersedia dari API'],
                            ['Music Sessions', $musicSessions ?? '—', $musicSessions === null ? 'Belum tersedia dari API' : 'Sesi voice ManukAPP'],
                            ['Gateway Ping', $botPing !== null ? $botPing . ' ms' : '—', $botConnected ? 'Discord gateway' : 'Bot belum terhubung'],
                        ] as [$title, $value, $description])

                            <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6">

                                <p class="text-sm text-zinc-500">
                                    {{ $title }}
                                </p>

                                <h4 class="mt-4 text-3xl font-bold text-white">
                                    {{ $value }}
                                </h4>

                                <p class="mt-2 text-xs text-zinc-600">
                                    {{ $description }}
                                </p>

                            </div>

                        @endforeach

                    </div>

                    {{-- ACTIVITY & HEALTH --}}
                    <div class="grid gap-5 xl:grid-cols-2">

                        {{-- SERVER ACTIVITY --}}
                        <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6">

                            <h4 class="font-semibold text-white">
                                Server Activity
                            </h4>

                            <div class="mt-5 space-y-4">
                                <div class="flex items-center justify-between rounded-xl border border-white/5 bg-[#11111a] p-4">
                                    <div><p class="text-xs uppercase tracking-wide text-zinc-500">Server</p><p class="mt-1 text-sm font-medium text-white">{{ $guildName ?: 'Tidak tersedia' }}</p></div>
                                    <span class="text-xs {{ $gatewayConnected ? 'text-emerald-400' : 'text-zinc-500' }}">{{ $gatewayConnected ? '● Gateway ready' : '○ Offline' }}</span>
                                </div>
                                <div class="rounded-xl border border-white/5 bg-[#11111a] p-4">
                                    <p class="text-xs uppercase tracking-wide text-violet-300">♫ Now Playing</p>
                                    @if ($nowPlaying)
                                        <p class="mt-2 break-words font-semibold text-white">{{ data_get($nowPlaying, 'title', 'Unknown track') }}</p>
                                        <p class="mt-1 text-xs text-zinc-400">{{ data_get($nowPlaying, 'artist', 'Unknown artist') }}</p>
                                        @if ($trackProgress !== null)
                                            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-violet-400" style="width: {{ $trackProgress }}%"></div></div>
                                        @endif
                                    @else
                                        <p class="mt-2 text-sm text-zinc-500">{{ $botConnected ? 'Belum ada lagu yang sedang diputar.' : 'Menunggu koneksi monitoring.' }}</p>
                                    @endif
                                </div>
                                <div class="flex justify-between text-xs text-zinc-500"><span>Antrean musik</span><span>{{ $queueLength ?? '—' }} track</span></div>
                            </div>

                        </div>

                        {{-- SYSTEM HEALTH --}}
                        <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6">

                            <h4 class="font-semibold text-white">
                                System Health
                            </h4>

                            <div class="mt-6 space-y-5">

                                <div class="flex justify-between gap-4 text-sm">
                                    <span>ManukAPP</span>
                                    <span @class(['text-emerald-400' => $botConnected, 'text-amber-400' => ! $botConnected])>
                                        {{ $botConnected ? 'Online' : 'Not Connected' }}
                                    </span>
                                </div>

                                <div class="flex justify-between gap-4 text-sm">
                                    <span>Lavalink</span>
                                    <span @class(['text-emerald-400' => $lavalinkConnected, 'text-zinc-400' => ! $lavalinkConnected])>
                                        {{ $lavalinkConnected ? 'Connected' : $lavalinkStatus }}
                                    </span>
                                </div>

                                <div class="flex justify-between gap-4 text-sm">
                                    <span>Discord Gateway</span>
                                    <span @class(['text-emerald-400' => $botConnected, 'text-amber-400' => ! $botConnected])>
                                        {{ $botConnected ? 'Connected' : 'Unknown' }}
                                    </span>
                                </div>

                                <div class="flex justify-between gap-4 text-sm">
                                    <span>Bot Uptime</span>
                                    <span class="text-zinc-300">{{ $botUptime }}</span>
                                </div>

                            </div>

                        </div>

                    </div>

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
                        <p class="mt-2 text-sm text-zinc-400">Lihat apa yang sedang diputar, antrean, dan status Lavalink. Tanpa perintah remote yang belum didukung bot.</p>
                        <div class="relative mt-5 flex flex-wrap gap-2">
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">{{ $nowPlaying ? ($musicPaused ? 'Ⅱ Paused' : '♫ Playing') : '◇ Idle' }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">Sessions: {{ $musicSessions ?? '—' }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">Queue: {{ $queueLength ?? '—' }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300">Lavalink: {{ $lavalinkConnected ? 'Connected' : $lavalinkStatus }}</span>
                        </div>
                    </div>
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

                {{-- WEBHOOKS & EMBEDS --}}
                @elseif ($activeMenu === 'webhooks')

                   <livewire:announcement-manager />

                {{-- HOLLOWFANG_V3_INTEGRATED --}}
                @elseif (in_array($activeMenu, ['moderation', 'members', 'events', 'logs'], true))
                    <livewire:hollowfang-v3 :initial-tab="match ($activeMenu) { 'members' => 'members', 'logs' => 'logs', 'events' => 'events', default => 'moderation' }" :key="'hollowfang-v3-'.$activeMenu" />

                {{-- OTHER MODULES --}}
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

            </div>

        </main>

    </div>

</div>
