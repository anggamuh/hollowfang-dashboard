<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/10 bg-[#191a21] p-6">
        <div>
            <h3 class="text-xl font-semibold text-white">Announcements &amp; Embeds</h3>
            <p class="mt-2 text-sm text-zinc-400">Susun pengumuman gothic hollow.fang, kirim sebagai bot Manuk asli.</p>
        </div>
        <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1 text-xs font-semibold text-violet-300">Owner Only</span>
    </div>

    @if (session('announcement_success'))
        <p class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-300" role="status">{{ session('announcement_success') }}</p>
    @endif
    @error('send')
        <p class="rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300" role="alert">{{ $message }}</p>
    @enderror

    <div class="grid gap-5 xl:grid-cols-2">
        <form wire:submit="send" class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-6">
            <h4 class="font-semibold text-white">Announcement Composer</h4>
            <p class="text-xs text-zinc-500">Hanya channel dalam allowlist yang bisa dipilih. Mention massal dinonaktifkan.</p>

            <div>
                <label for="hf-target" class="mb-1 block text-xs text-zinc-400">Channel tujuan</label>
                <select id="hf-target" wire:model="channelId" required class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white">
                    <option value="">Pilih channel</option>
                    @foreach ($channels as $id => $name)
                        <option value="{{ $id }}">#{{ $name }}</option>
                    @endforeach
                </select>
                @error('channelId')<span class="text-xs text-red-400">{{ $message }}</span>@enderror
                @if (empty($channels))
                    <p class="mt-1 text-xs text-amber-300">Belum ada channel. Isi HOLLOWFANG_ANNOUNCE_CHANNELS di .env Laravel.</p>
                @endif
            </div>

            <div>
                <label for="hf-content" class="mb-1 block text-xs text-zinc-400">Pesan tambahan (opsional)</label>
                <textarea id="hf-content" wire:model.live.debounce.500ms="content" rows="2" maxlength="1800" placeholder="Pengumuman untuk warga hollow.fang..." class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"></textarea>
                @error('content')<span class="text-xs text-red-400">{{ $message }}</span>@enderror
            </div>

            <div>
                <label for="hf-title" class="mb-1 block text-xs text-zinc-400">Judul</label>
                <input id="hf-title" wire:model.live.debounce.500ms="title" maxlength="256" required placeholder="✦ THE ANNOUNCEMENT" class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white" />
                @error('title')<span class="text-xs text-red-400">{{ $message }}</span>@enderror
            </div>

            <div>
                <label for="hf-description" class="mb-1 block text-xs text-zinc-400">Deskripsi / rules (Markdown Discord)</label>
                <textarea id="hf-description" wire:model.live.debounce.500ms="description" rows="7" maxlength="4000" required placeholder="Isi pengumuman..." class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"></textarea>
                @error('description')<span class="text-xs text-red-400">{{ $message }}</span>@enderror
            </div>

            <div class="space-y-3 rounded-xl border border-white/10 bg-white/[0.02] p-4">
                <div class="flex items-center justify-between gap-2">
                    <h5 class="text-sm font-semibold text-white">Gambar 1</h5>
                    <span class="text-[11px] text-zinc-500">Opsional · HTTPS</span>
                </div>
                <label for="hf-image" class="block text-xs text-zinc-400">URL gambar 1</label>
                <input id="hf-image" type="url" wire:model.live.debounce.500ms="imageUrl" placeholder="https://.../gambar-atas.png" class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white" />
                @error('imageUrl')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                <label for="hf-image-position" class="block text-xs text-zinc-400">Posisi gambar 1</label>
                <select id="hf-image-position" wire:model.live="imagePosition" class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white">
                    <option value="top">Di atas judul</option>
                    <option value="after_title">Setelah judul</option>
                    <option value="bottom">Di bawah deskripsi</option>
                </select>
                @error('imagePosition')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-3 rounded-xl border border-white/10 bg-white/[0.02] p-4">
                <div class="flex items-center justify-between gap-2">
                    <h5 class="text-sm font-semibold text-white">Gambar 2</h5>
                    <span class="text-[11px] text-zinc-500">Opsional · HTTPS</span>
                </div>
                <label for="hf-image-2" class="block text-xs text-zinc-400">URL gambar 2</label>
                <input id="hf-image-2" type="url" wire:model.live.debounce.500ms="imageUrl2" placeholder="https://.../gambar-bawah.png" class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white" />
                @error('imageUrl2')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                <label for="hf-image-position-2" class="block text-xs text-zinc-400">Posisi gambar 2</label>
                <select id="hf-image-position-2" wire:model.live="imagePosition2" class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white">
                    <option value="top">Di atas judul</option>
                    <option value="after_title">Setelah judul</option>
                    <option value="bottom">Di bawah deskripsi</option>
                </select>
                @error('imagePosition2')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            </div>

            <p class="text-xs leading-relaxed text-zinc-500">Kedua gambar ditempatkan terpisah dalam <strong>satu</strong> kartu Discord Components V2. Default: gambar 1 di atas judul, gambar 2 di bawah deskripsi. Bukan galeri berdampingan.</p>

            <div class="flex items-center gap-3">
                <label for="hf-color" class="text-xs text-zinc-400">Aksen (disimpan untuk kompatibilitas)</label>
                <input id="hf-color" type="color" wire:model.live="color" class="h-9 w-12 rounded border-0 bg-transparent" />
                <span class="text-xs text-zinc-500">{{ $color }}</span>
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="send" @disabled(empty($channels)) class="w-full rounded-xl bg-violet-700 px-4 py-3 text-sm font-semibold text-white hover:bg-violet-600 disabled:cursor-not-allowed disabled:opacity-40">Kirim sebagai ManukAPP</button>
        </form>

        <div class="space-y-5">
            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                <h4 class="mb-4 font-semibold text-white">Preview Discord — Satu Kartu</h4>
                @php
                    $previewImages = [
                        ['url' => $imageUrl, 'position' => $imagePosition, 'label' => 'Gambar 1'],
                        ['url' => $imageUrl2, 'position' => $imagePosition2, 'label' => 'Gambar 2'],
                    ];
                    $showPreviewImage = static fn ($item) => filter_var($item['url'], FILTER_VALIDATE_URL)
                        && str_starts_with($item['url'], 'https://');
                @endphp
                <div class="rounded-xl bg-[#111216] p-4">
                    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-200">
                        Manuk <span class="rounded bg-[#5865f2] px-1.5 py-0.5 text-[10px] text-white">APP</span>
                    </div>
                    <div class="space-y-4 rounded-xl border border-white/10 bg-[#1d1e23] p-4">
                        @if (trim($content))
                            <p class="whitespace-pre-wrap break-words text-sm text-zinc-300">{{ $content }}</p>
                        @endif

                        @foreach ($previewImages as $img)
                            @if ($img['position'] === 'top' && $showPreviewImage($img))
                                <img src="{{ $img['url'] }}" alt="{{ $img['label'] }} — atas" class="max-h-80 w-full rounded-lg object-contain" loading="lazy" />
                            @endif
                        @endforeach

                        <h5 class="break-words text-base font-bold text-white">{{ $title ?: '✦ THE ANNOUNCEMENT' }}</h5>

                        @foreach ($previewImages as $img)
                            @if ($img['position'] === 'after_title' && $showPreviewImage($img))
                                <img src="{{ $img['url'] }}" alt="{{ $img['label'] }} — tengah" class="max-h-80 w-full rounded-lg object-contain" loading="lazy" />
                            @endif
                        @endforeach

                        <p class="whitespace-pre-wrap break-words text-sm leading-6 text-zinc-300">{{ $description ?: 'Isi pengumuman akan tampil di sini...' }}</p>

                        @foreach ($previewImages as $img)
                            @if ($img['position'] === 'bottom' && $showPreviewImage($img))
                                <img src="{{ $img['url'] }}" alt="{{ $img['label'] }} — bawah" class="max-h-80 w-full rounded-lg object-contain" loading="lazy" />
                            @endif
                        @endforeach

                        <p class="text-xs text-zinc-500">hollow.fang • ManukAPP</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-zinc-500">Ini perkiraan layout. Discord yang merender Markdown dan jarak final, tapi gambar tetap terpisah atas/bawah dalam satu kartu.</p>
            </section>

            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                <h4 class="font-semibold text-white">Riwayat Pengiriman</h4>
                <div class="mt-4 space-y-3">
                    @forelse ($history as $item)
                        <div class="flex items-center justify-between gap-3 border-b border-white/5 pb-3 text-xs">
                            <div class="min-w-0">
                                <p class="truncate text-zinc-200">{{ $item->title }}</p>
                                <p class="mt-1 text-zinc-500">{{ $channels[$item->channel_id] ?? $item->channel_id }} · {{ $item->created_at }}</p>
                            </div>
                            <span class="{{ $item->successful ? 'text-emerald-400' : 'text-red-400' }}">{{ $item->successful ? 'Terkirim' : 'Gagal' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Belum ada pengumuman.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
