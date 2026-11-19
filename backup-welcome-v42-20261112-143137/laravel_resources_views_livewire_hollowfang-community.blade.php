<div class="min-w-0 space-y-5 pb-28 text-zinc-100 sm:pb-6" x-data="{confirm:false,kind:'',toast:'',ask(k){this.kind=k;this.confirm=true}}" x-on:hf-community-toast.window="toast=$event.detail.message;setTimeout(()=>toast='',4000)">
    <section class="rounded-3xl border border-violet-400/20 bg-[#161720] p-5 sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[.3em] text-violet-300">HOLLOWFANG • COMMUNITY SYSTEM</p>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-2xl font-extrabold text-white sm:text-3xl">Community Control</h2><p class="mt-2 text-sm text-zinc-400">Welcome · AutoMod · Tickets · Self Roles</p></div><button type="button" wire:click="refreshData" class="min-h-11 rounded-xl border border-white/10 bg-white/5 px-4 text-sm hover:bg-white/10">↻ Sinkronkan</button></div>
    </section>
    @if($errorMessage)<p role="alert" class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200">{{ $errorMessage }}</p>@endif
    @if($errors->any())<p role="alert" class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-200">{{ $errors->first() }}</p>@endif
    @if($successMessage)<p role="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-200">✓ {{ $successMessage }}</p>@endif
    @if(empty($config))
        <div class="rounded-2xl border border-white/10 bg-[#191a21] p-6 text-sm text-zinc-400">API Community belum tersambung. Jalankan ManukAPP dengan port 3213.</div>
    @else
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-4 sm:p-6">
            <div><h3 class="text-lg font-semibold">✦ Welcome & Goodbye</h3><p class="mt-1 text-xs text-zinc-400">Butuh Guild Members Intent aktif di Discord Developer Portal.</p></div>
            <div class="flex items-center justify-between rounded-xl bg-[#111218] p-3"><span class="text-sm">Welcome otomatis</span><input type="checkbox" wire:model="config.welcomeEnabled" class="h-5 w-5 accent-violet-500"></div>
            <label class="block space-y-1 text-xs text-zinc-400">Channel Welcome<select wire:model="config.welcomeChannelId" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"><option value="">Pilih channel</option>@foreach($channels as $id => $name)<option value="{{ $id }}">#{{ $name }}</option>@endforeach</select></label>
            <label class="block space-y-1 text-xs text-zinc-400">Pesan Welcome<textarea wire:model="config.welcomeText" rows="3" maxlength="1000" class="w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"></textarea></label>
            <div class="flex items-center justify-between rounded-xl bg-[#111218] p-3"><span class="text-sm">Goodbye otomatis</span><input type="checkbox" wire:model="config.goodbyeEnabled" class="h-5 w-5 accent-violet-500"></div>
            <label class="block space-y-1 text-xs text-zinc-400">Channel Goodbye<select wire:model="config.goodbyeChannelId" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"><option value="">Pilih channel</option>@foreach($channels as $id => $name)<option value="{{ $id }}">#{{ $name }}</option>@endforeach</select></label>
            <label class="block space-y-1 text-xs text-zinc-400">Pesan Goodbye<textarea wire:model="config.goodbyeText" rows="3" maxlength="1000" class="w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"></textarea></label>
            <p class="text-xs text-zinc-500">Template: <code>{user}</code>, <code>{username}</code>, <code>{server}</code>. Mention tidak mengirim ping massal.</p>
        </section>
        <section class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-4 sm:p-6">
            <div><h3 class="text-lg font-semibold">⛨ Auto Moderation</h3><p class="mt-1 text-xs text-zinc-400">Butuh Guild Messages + Message Content Intents. Default nonaktif.</p></div>
            <div class="flex items-center justify-between rounded-xl bg-[#111218] p-3"><span class="text-sm">Aktifkan AutoMod</span><input type="checkbox" wire:model="config.autoModEnabled" class="h-5 w-5 accent-violet-500"></div>
            <div class="flex items-center justify-between rounded-xl bg-[#111218] p-3"><span class="text-sm">Blokir tautan</span><input type="checkbox" wire:model="config.blockLinks" class="h-5 w-5 accent-violet-500"></div>
            <div class="grid grid-cols-2 gap-3"><label class="block space-y-1 text-xs text-zinc-400">Batas pesan<input wire:model="config.spamLimit" type="number" min="3" max="30" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"></label><label class="block space-y-1 text-xs text-zinc-400">Dalam (detik)<input wire:model="config.spamWindowSeconds" type="number" min="3" max="120" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"></label></div>
            <label class="block space-y-1 text-xs text-zinc-400">Kata terlarang (pisahkan koma)<textarea wire:model="words" rows="4" placeholder="kata1, kata2" class="w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"></textarea></label>
            <p class="rounded-xl border border-amber-500/15 bg-amber-500/5 p-3 text-xs leading-relaxed text-amber-200">AutoMod dapat menghapus pesan secara langsung. Uji di channel khusus sebelum dipakai server publik. Staff dengan Manage Messages dikecualikan.</p>
        </section>
    </div>
    <button type="button" x-on:click="ask('settings')" class="min-h-12 w-full rounded-xl bg-violet-700 px-5 py-3 text-sm font-semibold text-white hover:bg-violet-600">Simpan Pengaturan Welcome & AutoMod</button>
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-4 sm:p-6">
            <h3 class="text-lg font-semibold">▣ Ticket Support</h3><p class="text-xs text-zinc-400">Kirim panel tombol ke channel. Member dapat membuka tiket pribadi. Bot memerlukan Manage Channels.</p>
            <label class="block space-y-1 text-xs text-zinc-400">Channel untuk panel<select wire:model="panelChannelId" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"><option value="">Pilih channel</option>@foreach($channels as $id => $name)<option value="{{ $id }}">#{{ $name }}</option>@endforeach</select></label>
            <label class="block space-y-1 text-xs text-zinc-400">ID kategori ticket (opsional)<input type="text" wire:model="config.ticketCategoryId" placeholder="ID kategori dari Discord" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white" /></label>
            <label class="block space-y-1 text-xs text-zinc-400">Role staff ticket<select wire:model="config.ticketSupportRoleId" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"><option value="">Tanpa role khusus</option>@foreach($roles as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
            <p class="text-xs text-amber-200">Setelah mengubah kategori atau role staff, klik <strong>Simpan Pengaturan</strong> terlebih dahulu. Role staff harus punya akses yang sesuai dan bot memerlukan Manage Channels serta Read Message History.</p>
            <button type="button" x-on:click="ask('ticket')" class="min-h-11 w-full rounded-xl border border-violet-400/30 bg-violet-500/10 p-3 text-sm font-semibold text-violet-100 hover:bg-violet-500/20">Publikasikan Panel Ticket</button>
            <div class="max-h-80 space-y-3 overflow-y-auto" aria-label="Riwayat ticket">
                @forelse($tickets as $ticket)
                    <article wire:key="hf-ticket-{{ $ticket['id'] ?? $loop->index }}" class="rounded-xl border border-white/10 bg-[#111218] p-3 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-semibold text-violet-200">Ticket {{ substr((string)($ticket['id'] ?? ''), 0, 8) }}</span>
                            <span @class(['rounded-full px-2 py-1 font-semibold', 'bg-emerald-500/10 text-emerald-300' => ($ticket['status'] ?? '') === 'open', 'bg-violet-500/10 text-violet-300' => ($ticket['status'] ?? '') === 'claimed', 'bg-zinc-500/15 text-zinc-300' => ($ticket['status'] ?? '') === 'closed'])>{{ strtoupper((string)($ticket['status'] ?? '—')) }}</span>
                        </div>
                        <p class="mt-2 break-all text-zinc-400">Member: {{ $ticket['userId'] ?? '—' }}</p>
                        @if(!empty($ticket['claimedBy']))<p class="mt-1 break-all text-zinc-400">Ditangani: {{ $ticket['claimedBy'] }}</p>@endif
                        @if(!empty($ticket['transcriptFile']))
                            <button type="button" wire:click="downloadTicketTranscript('{{ $ticket['id'] }}')" wire:loading.attr="disabled" class="mt-3 min-h-11 w-full rounded-lg border border-violet-500/30 bg-violet-500/10 px-3 font-semibold text-violet-200 hover:bg-violet-500/20 disabled:opacity-50">↓ Unduh Transcript ({{ $ticket['transcriptCount'] ?? '—' }} pesan)</button>
                        @else
                            <p class="mt-2 text-zinc-500">{{ ($ticket['status'] ?? '') === 'closed' ? 'Ticket lama: tidak memiliki transcript.' : 'Transcript tersedia setelah tiket ditutup.' }}</p>
                        @endif
                    </article>
                @empty
                    <p class="text-xs text-zinc-500">Belum ada ticket.</p>
                @endforelse
            </div>
        </section>
        <section class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-4 sm:p-6">
            <h3 class="text-lg font-semibold">✧ Self Role</h3><p class="text-xs text-zinc-400">Member klik tombol Discord untuk mengambil/melepas satu role yang lu pilih.</p>
            <label class="block space-y-1 text-xs text-zinc-400">Role yang tersedia<select wire:model="panelRoleId" class="min-h-11 w-full rounded-xl border border-white/10 bg-[#111218] p-3 text-sm text-white"><option value="">Pilih role</option>@foreach($roles as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
            <button type="button" x-on:click="ask('role')" class="min-h-11 w-full rounded-xl border border-violet-400/30 bg-violet-500/10 p-3 text-sm font-semibold text-violet-100 hover:bg-violet-500/20">Publikasikan Self Role</button>
            <p class="text-xs text-zinc-500">Menggunakan channel panel yang dipilih di sebelah kiri. Role bot harus berada di atas role yang dibagikan.</p>
        </section>
    </div>
    <section class="rounded-2xl border border-white/10 bg-[#191a21] p-4 sm:p-6"><h3 class="font-semibold">Aktivitas Community</h3><div class="mt-4 max-h-80 space-y-2 overflow-y-auto">@forelse($activity as $item)<div class="rounded-xl bg-[#111218] p-3 text-xs text-zinc-400"><span class="font-semibold text-violet-200">{{ $item['action'] ?? 'Activity' }}</span> · {{ $item['at'] ?? '' }}<p class="mt-1 break-words">{{ $item['detail'] ?? '' }}</p></div>@empty<p class="text-xs text-zinc-500">Belum ada aktivitas.</p>@endforelse</div></section>
    @endif
    <div x-cloak x-show="confirm" class="fixed inset-0 z-[110] flex items-center justify-center bg-black/80 p-4" x-on:keydown.escape.window="confirm=false" x-on:click.self="confirm=false"><div role="alertdialog" aria-modal="true" class="max-h-[85dvh] w-full max-w-md overflow-y-auto rounded-2xl border border-violet-400/20 bg-[#1b1c26] p-5 shadow-2xl"><h3 class="text-lg font-bold">Konfirmasi Perubahan</h3><p class="mt-2 text-sm text-zinc-400">Tindakan ini akan mengubah pengaturan atau mengirim panel ke Discord. Lanjutkan?</p><div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><button type="button" x-on:click="confirm=false" class="min-h-11 rounded-xl border border-white/10 px-5 text-sm">Batal</button><button type="button" x-on:click="const k=kind;confirm=false;if(k==='settings')$wire.saveSettings();else $wire.publishPanel(k)" class="min-h-11 rounded-xl bg-violet-700 px-5 text-sm font-semibold">Ya, lanjutkan</button></div></div></div>
    <div x-cloak x-show="toast !== ''" x-transition role="status" x-text="toast" class="fixed bottom-20 left-4 right-4 z-[120] rounded-xl border border-emerald-400/20 bg-[#182622] p-4 text-sm text-emerald-200 shadow-2xl sm:bottom-5 sm:left-auto sm:right-5"></div>
</div>
