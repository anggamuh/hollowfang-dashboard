<div class="min-w-0 space-y-5 pb-28 text-zinc-100 sm:space-y-6 sm:pb-6" wire:poll.45s.visible="refreshData"
    x-data="{
        confirmOpen: false, confirmKind: '', confirmId: '', confirmTitle: '', confirmText: '', working: false,
        toast: '', toastType: 'success',
        ask(kind, id = '') {
            this.confirmKind = kind; this.confirmId = id;
            const details = {
                moderate: ['Konfirmasi tindakan moderasi', 'Tindakan ini dapat mengubah akses anggota di Discord secara langsung. Pastikan target, tindakan, dan alasannya sudah benar.'],
                role: ['Konfirmasi perubahan role', 'Perubahan role akan langsung diterapkan pada akun Discord yang dipilih.'],
                cancel: ['Batalkan jadwal event?', 'Pengumuman terjadwal ini akan dibatalkan dan tidak dikirim.']
            };
            [this.confirmTitle, this.confirmText] = details[kind] || ['Konfirmasi', 'Lanjutkan tindakan?'];
            this.confirmOpen = true;
        },
        async execute() {
            if (this.working) return;
            this.working = true;
            try {
                if (this.confirmKind === 'moderate') await $wire.moderate();
                if (this.confirmKind === 'role') await $wire.changeRole();
                if (this.confirmKind === 'cancel') await $wire.cancelEvent(this.confirmId);
                this.confirmOpen = false;
            } finally { this.working = false; }
        }
    }"
    x-on:hf-v3-toast.window="toast=$event.detail.message; toastType=$event.detail.type || 'success'; setTimeout(() => toast='', 4500)"
    x-on:keydown.escape.window="if (!working) confirmOpen=false">

    <div class="relative overflow-hidden rounded-3xl border border-violet-400/15 bg-[#15151e] p-4 sm:p-8">
        <div class="pointer-events-none absolute -right-16 -top-32 h-80 w-80 rounded-full bg-violet-600/10 blur-3xl"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-5">
            <div class="space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-[.32em] text-violet-300">Hollowfang · Control Center</p>
                <h2 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Server Operations <span class="text-violet-400">V3</span></h2>
                <p class="max-w-xl text-sm text-zinc-400">Kelola komunitas, keamanan, role, dan kegiatan server melalui ManukAPP.</p>
            </div>
            <button type="button" wire:click="refreshData" wire:loading.attr="disabled" wire:target="refreshData"
                class="rounded-xl border border-white/10 bg-white/[.04] px-4 py-2.5 text-sm text-zinc-200 transition hover:border-violet-400/40 hover:bg-white/10 disabled:opacity-50">
                <span wire:loading.remove wire:target="refreshData">↻ Sinkronkan data</span><span wire:loading wire:target="refreshData">Memuat...</span>
            </button>
        </div>
    </div>

    <div class="flex gap-2 overflow-x-auto rounded-2xl border border-white/10 bg-[#16171f] p-2" aria-label="Navigasi operasi server">
        @foreach (['overview' => '✦ Overview', 'members' => '♙ Members & Roles', 'moderation' => '⚑ Moderation', 'logs' => '▤ Audit Logs', 'events' => '◷ Events'] as $key => $label)
            <button type="button" wire:click="switchTab('{{ $key }}')"
                @class(['rounded-xl px-4 py-2.5 text-xs font-semibold transition sm:text-sm', 'bg-violet-700 text-white shadow-lg shadow-violet-900/20' => $tab === $key, 'text-zinc-400 hover:bg-white/5 hover:text-white' => $tab !== $key])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($errorMessage)
        <div role="alert" class="flex items-start justify-between gap-4 rounded-2xl border border-red-500/25 bg-red-500/10 p-4 text-sm text-red-200">
            <span><strong>Operasi gagal.</strong> {{ $errorMessage }}</span>
            <button type="button" wire:click="$set('errorMessage','')" aria-label="Tutup pesan" class="text-red-200 hover:text-white">✕</button>
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-200">{{ $errors->first() }}</div>
    @endif
    @if ($successMessage)
        <div role="status" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-200">✓ {{ $successMessage }}</div>
    @endif

    @if ($tab === 'overview')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Anggota Server', data_get($overview, 'guild.memberCount', '—'), 'Jumlah anggota Discord'],
                ['Gateway Ping', data_get($overview, 'bot.pingMs') === null ? '—' : data_get($overview, 'bot.pingMs').' ms', 'Latensi bot'],
                ['Event Terjadwal', data_get($overview, 'events.scheduled', '—'), 'Menunggu pengiriman'],
                ['Audit Tersimpan', data_get($overview, 'auditCount', '—'), 'Maksimum 500 rekaman'],
            ] as [$title, $value, $note])
                <div class="rounded-2xl border border-white/10 bg-[#191a21] p-5 shadow-xl shadow-black/10">
                    <p class="text-xs font-medium text-zinc-400">{{ $title }}</p><p class="mt-3 text-3xl font-bold tracking-tight text-white" style="font-variant-numeric:tabular-nums">{{ $value }}</p><p class="mt-2 text-xs text-zinc-500">{{ $note }}</p>
                </div>
            @endforeach
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                <h3 class="text-base font-bold">Discord Bot Permissions</h3><p class="mt-1 text-xs text-zinc-500">Status izin bot di server. Hierarki role tetap berlaku.</p>
                <div class="mt-5 space-y-3">
                    @foreach (['moderate' => 'Moderate Members', 'kick' => 'Kick Members', 'ban' => 'Ban Members', 'roles' => 'Manage Roles'] as $key => $label)
                        <div class="flex items-center justify-between rounded-xl bg-[#111218] px-4 py-3 text-sm"><span>{{ $label }}</span><span class="text-xs font-semibold {{ data_get($overview, 'permissions.'.$key) ? 'text-emerald-400' : 'text-amber-400' }}">{{ data_get($overview, 'permissions.'.$key) ? '✓ Diizinkan' : '✕ Belum diizinkan' }}</span></div>
                    @endforeach
                </div>
            </section>
            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                <h3 class="text-base font-bold">Operation Summary</h3>
                <div class="mt-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-white/5 pb-3 text-sm"><span class="text-zinc-400">Server</span><span class="font-semibold">{{ data_get($overview, 'guild.name', 'Belum terhubung') }}</span></div>
                    <div class="flex items-center justify-between border-b border-white/5 pb-3 text-sm"><span class="text-zinc-400">Warning tercatat</span><span class="font-semibold text-amber-300">{{ data_get($overview, 'warningCount', '—') }}</span></div>
                    <div class="flex items-center justify-between border-b border-white/5 pb-3 text-sm"><span class="text-zinc-400">Uptime bot</span><span class="font-semibold">{{ is_numeric(data_get($overview, 'bot.uptimeSeconds')) ? floor(data_get($overview, 'bot.uptimeSeconds') / 3600).' jam' : '—' }}</span></div>
                </div>
                <p class="mt-5 rounded-xl bg-violet-500/5 p-3 text-xs leading-relaxed text-zinc-400">Jumlah anggota online tidak ditampilkan sebagai angka palsu. Statistik ini diambil dari API bot.</p>
            </section>
        </div>

    @elseif ($tab === 'members' || $tab === 'moderation')
        <section class="rounded-2xl border border-white/10 bg-[#191a21] p-5 sm:p-6">
            <h3 class="font-semibold text-white">Cari Anggota Discord</h3>
            <p class="mt-1 text-xs text-zinc-500">Pencarian nama dari cache bot; Discord ID bisa dicari langsung meskipun tidak masuk cache.</p>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <input type="search" wire:model="search" wire:keydown.enter="searchMembers" maxlength="100" aria-label="Cari username atau Discord ID" placeholder="Username atau Discord ID" class="min-w-0 flex-1 rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white outline-none focus:border-violet-500/60"/>
                <button type="button" wire:click="searchMembers" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-violet-700 px-5 py-3 text-sm font-semibold hover:bg-violet-600">Cari</button>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-xs text-zinc-400">Jenis anggota
                    <select wire:model.live="memberType" class="mt-1 w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white">
                        <option value="all">Semua anggota</option><option value="human">Manusia</option><option value="bot">Bot</option>
                    </select>
                </label>
                <label class="text-xs text-zinc-400">Filter role
                    <select wire:model.live="memberRole" class="mt-1 w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white">
                        <option value="">Semua role</option>
                        @foreach ($roles as $role)<option value="{{ $role['id'] }}">{{ $role['name'] }}</option>@endforeach
                    </select>
                </label>
            </div>
            <p class="mt-3 text-xs text-zinc-400">{{ $memberMatches }} cocok dari {{ count($members) }} hasil API (maksimal 100). Filter berlaku pada hasil yang dimuat, bukan seluruh server.</p>
            @if ($membersPartial)<p class="mt-3 text-xs text-amber-300">Sebagian member mungkin belum termuat karena Discord GuildMembers intent tidak diaktifkan.</p>@endif
        </section>
            <div class="grid gap-3 md:grid-cols-2">
                @forelse ($visibleMembers as $m)
                    <article wire:key="member-{{ $m['id'] }}" class="flex flex-col items-start justify-between gap-3 rounded-2xl border border-white/10 bg-[#191a21] p-4 sm:flex-row sm:p-5">
                        <div class="min-w-0"><p class="truncate font-semibold">{{ $m['displayName'] ?? $m['username'] ?? 'Unknown' }} @if($m['bot'] ?? false)<span class="ml-1 rounded bg-violet-600/20 px-1.5 py-0.5 text-[10px] text-violet-300">BOT</span>@endif</p><p class="mt-1 break-all text-xs text-zinc-500">{{ $m['id'] }}</p><p class="mt-2 text-xs text-zinc-400">{{ count($m['roleIds'] ?? []) }} role · Bergabung {{ !empty($m['joinedAt']) ? \Carbon\Carbon::parse($m['joinedAt'])->timezone('Asia/Jakarta')->format('d M Y') : '—' }}</p></div>
                        <button type="button" wire:click="selectTarget('{{ $m['id'] }}')" class="min-h-11 w-full shrink-0 rounded-lg border border-violet-500/30 px-3 py-2 text-xs font-semibold text-violet-300 hover:bg-violet-500/10 sm:w-auto">Pilih</button>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-white/10 bg-[#191a21] p-8 text-center text-sm text-zinc-400">Belum ada anggota yang cocok dalam cache.</div>
                @endforelse
            </div>
            <nav aria-label="Halaman anggota" class="flex items-center justify-between gap-3 text-sm">
                <button type="button" wire:click="goToMemberPage({{ $memberPage - 1 }})" @disabled($memberPage <= 1) class="rounded-xl border border-white/10 px-4 py-2 disabled:opacity-30">← Sebelumnya</button>
                <span class="text-xs text-zinc-400">{{ $memberPage }} / {{ $memberPages }}</span>
                <button type="button" wire:click="goToMemberPage({{ $memberPage + 1 }})" @disabled($memberPage >= $memberPages) class="rounded-xl border border-white/10 px-4 py-2 disabled:opacity-30">Berikutnya →</button>
            </nav>
            @if ($targetId)
                <p role="status" class="rounded-xl border border-violet-400/20 bg-violet-500/10 p-3 text-sm text-violet-200">Anggota dipilih: <span class="font-mono">{{ $targetId }}</span></p>
            @endif
        @if ($tab === 'members')
            <form x-on:submit.prevent="ask('role')" class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-6">
                <div><h3 class="font-semibold text-white">Kelola Role Anggota</h3><p class="mt-1 text-xs text-zinc-400">Aksi langsung ke Discord; hanya role yang boleh dikelola bot dan operator.</p></div>
                <label class="block space-y-1 text-xs text-zinc-400">Discord ID anggota<input wire:model="targetId" pattern="[0-9]{15,22}" required placeholder="Masukkan atau pilih ID anggota" class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"></label>
                <div class="grid gap-3 sm:grid-cols-2"><label class="block space-y-1 text-xs text-zinc-400">Role<select wire:model="roleId" required class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"><option value="">Pilih role Discord</option>@foreach ($roles as $r)<option value="{{ $r['id'] }}" @disabled($r['managed'] ?? false)>{{ $r['name'] }} {{ ($r['managed'] ?? false) ? '(managed)' : '' }}</option>@endforeach</select></label><label class="block space-y-1 text-xs text-zinc-400">Tindakan<select wire:model="roleOperation" class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"><option value="add">Tambahkan role</option><option value="remove">Cabut role</option></select></label></div>
                <button type="submit" class="rounded-xl bg-violet-700 px-5 py-3 text-sm font-semibold transition hover:bg-violet-600">Konfirmasi perubahan role</button>
            </form>
        @else
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(260px,.65fr)]">
                <form x-on:submit.prevent="ask('moderate')" class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-6">
                    <h3 class="text-lg font-bold">Moderation Action</h3>
                    <label class="block space-y-1 text-xs text-zinc-400">Target Discord ID<input wire:model="targetId" pattern="[0-9]{15,22}" required placeholder="ID target" class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"></label>
                    <label class="block space-y-1 text-xs text-zinc-400">Jenis tindakan<select wire:model.live="action" class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"><option value="warn">Warning (catatan lokal)</option><option value="timeout">Timeout</option><option value="untimeout">Hapus Timeout</option><option value="kick">Kick Member</option><option value="ban">Ban Member</option></select></label>
                    @if($action === 'timeout')<label class="block space-y-1 text-xs text-zinc-400">Durasi timeout (menit)<input wire:model="minutes" type="number" min="1" max="40320" required class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"></label>@endif
                    <label class="block space-y-1 text-xs text-zinc-400">Alasan tindakan<textarea wire:model="reason" rows="3" minlength="3" maxlength="300" required placeholder="Minimal 3 karakter" class="w-full resize-y rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"></textarea></label>
                    <button type="submit" class="w-full rounded-xl bg-red-700 px-4 py-3 text-sm font-bold transition hover:bg-red-600">Lanjutkan tindakan</button>
                </form>
                <aside class="rounded-2xl border border-amber-500/15 bg-[#191a21] p-6"><h3 class="font-semibold text-amber-300">Keamanan Moderasi</h3><div class="mt-4 space-y-4 text-sm leading-relaxed text-zinc-400"><p>⛨ Pemeriksaan permission dan role hierarchy dilakukan di ManukAPP sebelum aksi Discord.</p><p>⛨ Owner server, diri sendiri, dan bot dilindungi dari aksi tertentu.</p><p>⛨ Warning dicatat di sistem V3. Warning <strong>bukan</strong> timeout dan tidak otomatis mengirim DM.</p><p>⛨ Kick dan ban tidak bisa dibatalkan hanya dengan menutup pop-up.</p></div></aside>
            </div>
        @endif

    @elseif ($tab === 'logs')
        <div class="rounded-2xl border border-violet-400/15 bg-[#191a21] p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-white">Audit Explorer</h3>
                    <p class="mt-1 text-xs text-zinc-400">Cari tindakan, actor, target, atau alasan. Ekspor hanya dari maksimal 100 catatan terbaru yang disediakan API V3.</p>
                </div>
                <button type="button" wire:click="exportAuditCsv" wire:loading.attr="disabled" wire:target="exportAuditCsv"
                    class="rounded-xl border border-violet-400/25 bg-violet-500/15 px-4 py-2.5 text-sm font-semibold text-violet-100 hover:bg-violet-500/25 disabled:opacity-50">
                    <span wire:loading.remove wire:target="exportAuditCsv">↓ Export CSV</span>
                    <span wire:loading wire:target="exportAuditCsv">Menyiapkan CSV...</span>
                </button>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_210px]">
                <label class="block text-xs text-zinc-400">Cari audit
                    <input type="search" wire:model.live.debounce.350ms="auditSearch" maxlength="100" placeholder="Aksi, ID Discord, atau alasan..."
                        class="mt-1 w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white outline-none focus:border-violet-400/60" />
                </label>
                <label class="block text-xs text-zinc-400">Kategori
                    <select wire:model.live="auditCategory" class="mt-1 w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white outline-none focus:border-violet-400/60">
                        <option value="all">Semua tindakan</option>
                        <option value="moderation">Moderation</option>
                        <option value="role">Members & Roles</option>
                        <option value="event">Events</option>
                    </select>
                </label>
            </div>
            <p class="mt-3 text-xs text-zinc-500">Menampilkan {{ count($filteredAuditLogs) }} dari {{ count($logs) }} log yang diterima. Waktu ekspor menggunakan UTC.</p>
            @error('auditSearch')<p role="alert" class="mt-2 text-xs text-red-300">{{ $message }}</p>@enderror
            @error('auditCategory')<p role="alert" class="mt-2 text-xs text-red-300">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-5"><h3 class="mb-4 font-semibold">Audit Tindakan</h3><div class="max-h-[620px] space-y-2 overflow-y-auto pr-1">@forelse($filteredAuditLogs as $l)<div wire:key="audit-{{ $l['id'] }}" class="rounded-xl border border-white/5 bg-[#111218] p-3"><p class="text-sm font-medium text-violet-200">{{ $l['action'] ?? 'Action' }}</p><p class="mt-1 break-words text-xs text-zinc-400">{{ $l['detail'] ?? '' }}</p><p class="mt-2 break-all text-[11px] text-zinc-500">{{ $l['at'] ?? '' }} · Actor {{ $l['actor'] ?? '—' }} · Target {{ $l['target'] ?? '—' }}</p></div>@empty<p class="py-8 text-center text-sm text-zinc-500">Tidak ada audit yang cocok dengan filter saat ini.</p>@endforelse</div></section>
            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-5"><h3 class="mb-4 font-semibold">Riwayat Warning</h3><div class="max-h-[620px] space-y-2 overflow-y-auto pr-1">@forelse($warnings as $w)<div wire:key="warning-{{ $w['id'] }}" class="rounded-xl border border-white/5 bg-[#111218] p-3"><p class="break-words text-sm text-amber-200">{{ $w['reason'] ?? '—' }}</p><p class="mt-2 break-all text-[11px] text-zinc-500">{{ $w['at'] ?? '' }} · Target {{ $w['target'] ?? '—' }}</p></div>@empty<p class="py-8 text-center text-sm text-zinc-500">Belum ada warning.</p>@endforelse</div></section>
        </div>

    @elseif ($tab === 'events')
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <form wire:submit.prevent="createEvent" class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-6"><h3 class="text-lg font-bold">Jadwalkan Pengumuman</h3>
                <label class="block space-y-1 text-xs text-zinc-400">Nama event<input wire:model="eventTitle" maxlength="100" required placeholder="Contoh: Hollowfang Game Night" class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"></label>
                <label class="block space-y-1 text-xs text-zinc-400">Deskripsi<textarea wire:model="eventDescription" rows="4" maxlength="1000" placeholder="Informasi acara" class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"></textarea></label>
                <label class="block space-y-1 text-xs text-zinc-400">Channel tujuan<select wire:model="eventChannelId" required class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"><option value="">Pilih channel</option>@foreach($channels as $id => $name)<option value="{{ $id }}">#{{ $name }}</option>@endforeach</select></label>
                <label class="block space-y-1 text-xs text-zinc-400">Tanggal dan jam (zona waktu aplikasi)<input type="datetime-local" wire:model="eventAt" required class="w-full rounded-xl border border-white/10 bg-[#101116] p-3 text-sm text-white"></label>
                <button type="submit" wire:loading.attr="disabled" wire:target="createEvent" class="w-full rounded-xl bg-violet-700 px-5 py-3 text-sm font-semibold hover:bg-violet-600 disabled:opacity-50">Buat Jadwal</button>
                <p class="text-xs leading-relaxed text-zinc-500">Jadwal ini mengirim pesan Discord pada waktunya, bukan Discord Scheduled Event dengan RSVP. Jika bot mati, pengiriman dicoba saat bot kembali aktif.</p>
            </form>
            <section class="space-y-3 rounded-2xl border border-white/10 bg-[#191a21] p-5"><h3 class="font-semibold">Daftar Jadwal</h3><div class="max-h-[620px] space-y-3 overflow-y-auto">@forelse($events as $e)<div wire:key="event-{{ $e['id'] }}" class="rounded-xl border border-white/10 bg-[#111218] p-4"><div class="flex items-start justify-between gap-2"><p class="break-words font-semibold">{{ $e['title'] ?? 'Event' }}</p><span class="rounded-full bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-violet-300">{{ $e['status'] ?? 'unknown' }}</span></div><p class="mt-2 text-xs text-zinc-400">{{ !empty($e['at']) ? \Carbon\Carbon::parse($e['at'])->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : '—' }}</p>@if(($e['status'] ?? '') === 'scheduled')<button type="button" x-on:click="ask('cancel', @js($e['id']))" class="mt-3 rounded-lg border border-red-500/30 px-3 py-2 text-xs font-semibold text-red-300 hover:bg-red-500/10">Batalkan event</button>@endif</div>@empty<p class="py-8 text-center text-sm text-zinc-500">Belum ada jadwal.</p>@endforelse</div></section>
        </div>
    @endif

    {{-- Mobile-first navigation; preserves existing desktop tabs and Livewire state. --}}
    <nav aria-label="Navigasi Hollowfang V3 pada ponsel"
        class="fixed inset-x-0 bottom-0 z-[90] border-t border-violet-400/20 bg-[#11121b]/95 px-1 pb-[max(env(safe-area-inset-bottom),0.5rem)] pt-2 shadow-2xl shadow-black/60 backdrop-blur-xl sm:hidden">
        <div class="mx-auto grid max-w-lg grid-cols-5 gap-1">
            @foreach ([
                ['overview', '⌂', 'Home'],
                ['members', '♙', 'Member'],
                ['moderation', '⚑', 'Mod'],
                ['logs', '▤', 'Logs'],
                ['events', '◷', 'Event'],
            ] as [$key, $symbol, $label])
                <button type="button" wire:click="switchTab('{{ $key }}')"
                    aria-current="{{ $tab === $key ? 'page' : 'false' }}"
                    @class([
                        'flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-xl px-1 py-1 text-[10px] font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-400',
                        'bg-violet-600/25 text-violet-100' => $tab === $key,
                        'text-zinc-400 active:bg-white/10' => $tab !== $key,
                    ])>
                    <span class="text-xl leading-none" aria-hidden="true">{{ $symbol }}</span>
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </nav>

    {{-- Confirmation dialog: accessible, no CDN, independent of browser confirm() --}}
    <div x-cloak x-show="confirmOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm" role="presentation" x-on:click.self="if(!working) confirmOpen=false">
        <div role="alertdialog" aria-modal="true" aria-labelledby="hf-confirm-title" aria-describedby="hf-confirm-desc" class="max-h-[85dvh] w-full max-w-md overflow-y-auto rounded-2xl border border-violet-500/20 bg-[#1c1d27] p-4 shadow-2xl shadow-black/70 sm:p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 text-2xl text-amber-300">⚠</div>
            <h3 id="hf-confirm-title" x-text="confirmTitle" class="text-lg font-bold text-white"></h3>
            <p id="hf-confirm-desc" x-text="confirmText" class="mt-2 text-sm leading-relaxed text-zinc-400"></p>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" x-on:click="confirmOpen=false" :disabled="working" class="min-h-11 rounded-xl border border-white/10 px-5 py-2.5 text-sm text-zinc-200 hover:bg-white/10">Batal</button><button type="button" x-on:click="execute()" :disabled="working" class="min-h-11 rounded-xl bg-red-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-600 disabled:opacity-50"><span x-show="!working">Ya, lanjutkan</span><span x-show="working">Memproses...</span></button></div>
        </div>
    </div>
    <div x-cloak x-show="toast !== ''" x-transition role="status" class="fixed bottom-24 left-4 right-4 z-[110] max-w-sm rounded-2xl sm:bottom-5 sm:left-auto sm:right-5 border border-emerald-400/20 bg-[#1c2424] p-4 text-sm text-emerald-100 shadow-2xl" x-text="toast"></div>
</div>
