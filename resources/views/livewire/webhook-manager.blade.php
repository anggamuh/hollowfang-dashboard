<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/10 bg-[#191a21] p-6">
        <div>
            <h3 class="text-xl font-semibold text-white">Webhooks & Embeds</h3>
            <p class="mt-2 text-sm text-zinc-400">Kirim pengumuman Discord dari dashboard Owner. Tidak ada kontrol musik di sini.</p>
        </div>
        <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1 text-xs font-medium text-violet-300">Owner Only</span>
    </div>

    @if (session('webhook_success'))
        <div role="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-300">
            {{ session('webhook_success') }}
        </div>
    @endif
    @error('send')<div role="alert" class="rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">{{ $message }}</div>@enderror

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
            <h4 class="font-semibold text-white">Daftarkan Webhook Discord</h4>
            <p class="mt-2 text-xs leading-relaxed text-zinc-500">Discord: Channel Settings → Integrations → Webhooks → New Webhook → Copy Webhook URL. URL adalah rahasia; jangan bagikan di chat atau screenshot.</p>
            <form wire:submit="addWebhook" class="mt-5 space-y-4">
                <div>
                    <label for="wh-name" class="mb-1 block text-xs text-zinc-400">Nama webhook</label>
                    <input id="wh-name" wire:model="name" maxlength="80" placeholder="Pengumuman Hollowfang" required class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white outline-none focus:border-violet-400/60" />
                    @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="wh-channel" class="mb-1 block text-xs text-zinc-400">Nama channel (opsional, buat penanda)</label>
                    <input id="wh-channel" wire:model="channelName" maxlength="100" placeholder="notice" class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white outline-none focus:border-violet-400/60" />
                    @error('channelName')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="wh-url" class="mb-1 block text-xs text-zinc-400">Webhook URL</label>
                    <input id="wh-url" wire:model="webhookUrl" type="password" autocomplete="off" required placeholder="https://discord.com/api/webhooks/..." class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white outline-none focus:border-violet-400/60" />
                    @error('webhookUrl')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="addWebhook" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-500 disabled:opacity-50">Simpan Webhook</button>
            </form>
            <div class="mt-6 border-t border-white/10 pt-4">
                <h5 class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">Webhook tersimpan ({{ $webhooks->count() }})</h5>
                <div class="space-y-2">
                    @forelse ($webhooks as $webhook)
                        <div wire:key="saved-webhook-{{ $webhook->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-white/5 bg-white/[0.03] p-3">
                            <div class="min-w-0"><p class="truncate text-sm text-zinc-200">{{ $webhook->name }}</p><p class="text-xs text-zinc-500">{{ $webhook->channel_name ? '#'.$webhook->channel_name : 'Channel tidak diberi label' }}</p></div>
                            <button type="button" wire:click="removeWebhook({{ $webhook->id }})" wire:confirm="Hapus webhook ini dari dashboard? Webhook di Discord tidak akan ikut terhapus." class="shrink-0 rounded-lg border border-red-500/20 px-3 py-1.5 text-xs text-red-300 hover:bg-red-500/10">Hapus</button>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Belum ada webhook terdaftar.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
            <h4 class="font-semibold text-white">Embed Composer</h4>
            <p class="mt-2 text-xs text-zinc-500">Susun pesan lalu kirim ke channel webhook pilihan. Mention massal dinonaktifkan.</p>
            <form wire:submit="sendEmbed" class="mt-5 space-y-4">
                <div>
                    <label for="wh-select" class="mb-1 block text-xs text-zinc-400">Tujuan</label>
                    <select id="wh-select" wire:model="selectedWebhook" required class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white outline-none focus:border-violet-400/60">
                        <option value="">Pilih webhook</option>
                        @foreach ($webhooks as $webhook)
                            <option value="{{ $webhook->id }}">{{ $webhook->name }}{{ $webhook->channel_name ? ' · #'.$webhook->channel_name : '' }}</option>
                        @endforeach
                    </select>
                    @error('selectedWebhook')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="wh-message" class="mb-1 block text-xs text-zinc-400">Pesan di luar embed (opsional)</label>
                    <textarea id="wh-message" wire:model.live.debounce.350ms="message" maxlength="1800" rows="2" placeholder="Pengumuman terbaru..." class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white outline-none focus:border-violet-400/60"></textarea>
                    @error('message')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="wh-title" class="mb-1 block text-xs text-zinc-400">Judul embed</label>
                    <input id="wh-title" wire:model.live.debounce.350ms="embedTitle" maxlength="256" required placeholder="Hollowfang Announcement" class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white outline-none focus:border-violet-400/60" />
                    @error('embedTitle')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="wh-description" class="mb-1 block text-xs text-zinc-400">Deskripsi</label>
                    <textarea id="wh-description" wire:model.live.debounce.350ms="embedDescription" maxlength="4000" rows="5" required placeholder="Isi pengumuman..." class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white outline-none focus:border-violet-400/60"></textarea>
                    @error('embedDescription')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-3">
                    <label for="wh-color" class="text-xs text-zinc-400">Warna aksen</label>
                    <input id="wh-color" type="color" wire:model.live="embedColor" class="h-9 w-12 cursor-pointer rounded-lg border border-white/10 bg-transparent" />
                    <span class="text-xs text-zinc-500">{{ $embedColor }}</span>
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="sendEmbed" @disabled($webhooks->isEmpty()) wire:confirm="Yakin kirim pengumuman ini ke Discord?" class="w-full rounded-xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-40">Kirim ke Discord</button>
            </form>
        </section>
    </div>

    <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
        <h4 class="font-semibold text-white">Preview Embed</h4>
        <p class="mt-1 text-xs text-zinc-500">Pratinjau sederhana (tampilan asli di Discord dapat sedikit berbeda).</p>
        <div class="mt-4 max-w-2xl rounded-lg bg-[#24252c] p-4">
            @if (trim($message) !== '')<p class="mb-3 whitespace-pre-wrap break-words text-sm text-zinc-200">{{ $message }}</p>@endif
            <div class="rounded border-l-4 bg-[#191a20] p-4" style="border-left-color: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $embedColor) ? $embedColor : '#9b7cf7' }}">
                <h5 class="break-words text-sm font-semibold text-white">{{ $embedTitle ?: 'Judul Embed' }}</h5>
                <p class="mt-2 whitespace-pre-wrap break-words text-sm text-zinc-300">{{ $embedDescription ?: 'Deskripsi pengumuman akan tampil di sini.' }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
        <h4 class="font-semibold text-white">Riwayat Pengiriman</h4>
        <div class="mt-4 space-y-3">
            @forelse ($recentDispatches as $dispatch)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/5 pb-3 text-sm">
                    <div class="min-w-0"><p class="truncate text-zinc-200">{{ $dispatch->title ?: 'Tanpa judul' }}</p><p class="mt-1 text-xs text-zinc-500">{{ $dispatch->webhook_name ?: 'Webhook dihapus' }} · {{ \Illuminate\Support\Carbon::parse($dispatch->created_at)->format('d M Y H:i') }}</p></div>
                    <span @class(['text-xs', 'text-emerald-400' => $dispatch->successful, 'text-red-400' => !$dispatch->successful])>{{ $dispatch->successful ? 'Terkirim' : 'Gagal' }}{{ $dispatch->http_status ? ' (HTTP '.$dispatch->http_status.')' : '' }}</span>
                </div>
            @empty
                <p class="text-sm text-zinc-500">Belum ada pengiriman.</p>
            @endforelse
        </div>
    </section>
</div>
