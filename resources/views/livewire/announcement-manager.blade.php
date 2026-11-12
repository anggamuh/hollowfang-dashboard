    <div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/10 bg-[#191a21] p-6">
        <div>
            <h3 class="text-xl font-semibold text-white">
                Announcements &amp; Embeds
            </h3>
            <p class="mt-2 text-sm text-zinc-400">
                Susun pengumuman gothic hollow.fang, kirim sebagai bot Manuk asli.
            </p>
        </div>

        <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1 text-xs font-semibold text-violet-300">
            Owner Only
        </span>
    </div>

    {{-- NOTIFICATIONS --}}
    @if (session('announcement_success'))
        <p role="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-300">
            {{ session('announcement_success') }}
        </p>
    @endif

    @error('send')
        <p role="alert" class="rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
            {{ $message }}
        </p>
    @enderror

    <div class="grid gap-5 xl:grid-cols-2">

        {{-- COMPOSER --}}
        <form wire:submit="send" class="space-y-4 rounded-2xl border border-white/10 bg-[#191a21] p-6">

            <h4 class="font-semibold text-white">
                Announcement Composer
            </h4>

            {{-- CHANNEL MANAGER & SINGLE ROLE PICKER --}}
            @include('livewire.partials.announcement-directory')

            <p class="text-xs text-zinc-500">
                Hanya channel yang diizinkan Owner dapat menerima pengumuman.
                Mention massal dinonaktifkan.
            </p>

            {{-- CHANNEL --}}
            <div>
                <label for="hf-target" class="mb-1 block text-xs text-zinc-400">
                    Channel tujuan
                </label>

                <select
                    id="hf-target"
                    wire:model="channelId"
                    required
                    class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"
                >
                    <option value="">Pilih channel</option>

                    @foreach ($channels as $id => $name)
                        <option value="{{ $id }}">#{{ $name }}</option>
                    @endforeach
                </select>

                @error('channelId')
                    <span class="text-xs text-red-400">{{ $message }}</span>
                @enderror

                @if (empty($channels))
                    <p class="mt-1 text-xs text-amber-300">
                        Belum ada channel yang diizinkan.
                        Aktifkan melalui Kelola Channel Discord.
                    </p>
                @endif
            </div>

            {{-- CONTENT --}}
            <div>
                <label for="hf-content" class="mb-1 block text-xs text-zinc-400">
                    Pesan tambahan (opsional)
                </label>

                <textarea
                    id="hf-content"
                    wire:model.live.debounce.500ms="content"
                    rows="2"
                    maxlength="1800"
                    placeholder="Pengumuman untuk warga hollow.fang..."
                    class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"
                ></textarea>

                @error('content')
                    <span class="text-xs text-red-400">{{ $message }}</span>
                @enderror
            </div>

            {{-- TITLE --}}
            <div>
                <label for="hf-title" class="mb-1 block text-xs text-zinc-400">
                    Judul
                </label>

                <input
                    id="hf-title"
                    wire:model.live.debounce.500ms="title"
                    maxlength="256"
                    required
                    placeholder="✦ THE ANNOUNCEMENT"
                    class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"
                />

                @error('title')
                    <span class="text-xs text-red-400">{{ $message }}</span>
                @enderror
            </div>

            {{-- DESCRIPTION --}}
            <div>
                <label for="hf-description" class="mb-1 block text-xs text-zinc-400">
                    Deskripsi / rules (Markdown Discord)
                </label>

                <textarea
                    id="hf-description"
                    wire:model.live.debounce.500ms="description"
                    rows="10"
                    maxlength="4000"
                    required
                    placeholder="Isi pengumuman atau gunakan {role:caelum}..."
                    class="w-full rounded-xl border border-white/10 bg-[#101116] px-3 py-2.5 text-sm leading-6 text-white"
                ></textarea>

                <div class="mt-2 flex justify-between gap-3">
                    <p class="text-xs text-zinc-500">
                        Mendukung Markdown dan Auto Role Tag.
                    </p>
                    <span class="text-xs text-zinc-500">
                        {{ mb_strlen($description) }} / 4000
                    </span>
                </div>

                @error('description')
                    <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- AUTO ROLE TAG --}}
            <div class="space-y-4 rounded-xl border border-violet-500/20 bg-violet-500/[0.05] p-4">

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h5 class="text-sm font-semibold text-white">
                            ✦ Auto Role Tag
                        </h5>

                        <p class="mt-1 text-xs leading-relaxed text-zinc-400">
                            Tulis nama role sebagai placeholder.
                            Semua role akan dikonversi otomatis ke mention Discord asli.
                        </p>
                    </div>

                    <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1 text-[11px] text-violet-300">
                        Hollowfang V2.8
                    </span>
                </div>

                <div class="rounded-lg border border-white/5 bg-[#101116] p-3">
                    <p class="mb-2 text-[11px] uppercase tracking-wide text-zinc-500">
                        Contoh penulisan
                    </p>

                    <pre class="overflow-x-auto whitespace-pre-wrap break-words text-xs leading-6 text-violet-300">01. OWNER &amp; FOUNDER
↳ {role:caelum}

02. ADMINISTRATOR
↳ {role:praesidium}

03. MODERATOR
↳ {role:aethernum}</pre>
                </div>

                <button
                    type="button"
                    wire:click="resolveAllRoles"
                    wire:loading.attr="disabled"
                    wire:target="resolveAllRoles"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-violet-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-600 disabled:cursor-wait disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="resolveAllRoles">
                        ✦ Resolve All Roles
                    </span>

                    <span wire:loading wire:target="resolveAllRoles">
                        Menyinkronkan role Discord...
                    </span>
                </button>

                @error('roles')
                    <p role="alert" class="rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-xs text-red-300">
                        {{ $message }}
                    </p>
                @enderror

                @if (session('roles_resolved'))
                    <p role="status" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-xs text-emerald-300">
                        {{ session('roles_resolved') }}
                    </p>
                @endif

                <p class="text-xs leading-relaxed text-zinc-500">
                    Role diambil dari Discord secara langsung.
                    Role yang tidak ditemukan atau memiliki nama ambigu tidak akan diganti.
                    Mention tidak mengirim ping notifikasi.
                </p>
            </div>

            {{-- IMAGE 1 --}}
            <div class="space-y-3 rounded-xl border border-white/10 bg-white/[0.02] p-4">
                <div class="flex items-center justify-between gap-2">
                    <h5 class="text-sm font-semibold text-white">Gambar 1</h5>
                    <span class="text-[11px] text-zinc-500">
                        Opsional · HTTPS
                    </span>
                </div>

                <label for="hf-image" class="block text-xs text-zinc-400">
                    URL gambar 1
                </label>

                <input
                    id="hf-image"
                    type="url"
                    wire:model.live.debounce.500ms="imageUrl"
                    placeholder="https://.../gambar-atas.gif"
                    class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"
                />

                @error('imageUrl')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror

                <label for="hf-image-position" class="block text-xs text-zinc-400">
                    Posisi gambar 1
                </label>

                <select
                    id="hf-image-position"
                    wire:model.live="imagePosition"
                    class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"
                >
                    <option value="top">Di atas judul</option>
                    <option value="after_title">Setelah judul</option>
                    <option value="bottom">Di bawah deskripsi</option>
                </select>

                @error('imagePosition')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- IMAGE 2 --}}
            <div class="space-y-3 rounded-xl border border-white/10 bg-white/[0.02] p-4">
                <div class="flex items-center justify-between gap-2">
                    <h5 class="text-sm font-semibold text-white">Gambar 2</h5>
                    <span class="text-[11px] text-zinc-500">
                        Opsional · HTTPS
                    </span>
                </div>

                <label for="hf-image-2" class="block text-xs text-zinc-400">
                    URL gambar 2
                </label>

                <input
                    id="hf-image-2"
                    type="url"
                    wire:model.live.debounce.500ms="imageUrl2"
                    placeholder="https://.../gambar-bawah.gif"
                    class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"
                />

                @error('imageUrl2')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror

                <label for="hf-image-position-2" class="block text-xs text-zinc-400">
                    Posisi gambar 2
                </label>

                <select
                    id="hf-image-position-2"
                    wire:model.live="imagePosition2"
                    class="w-full rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white"
                >
                    <option value="top">Di atas judul</option>
                    <option value="after_title">Setelah judul</option>
                    <option value="bottom">Di bawah deskripsi</option>
                </select>

                @error('imagePosition2')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-xs leading-relaxed text-zinc-500">
                Kedua gambar ditempatkan terpisah dalam satu kartu Discord Components V2.
                Bukan galeri berdampingan.
            </p>

            {{-- COLOR --}}
            <div class="flex items-center gap-3">
                <label for="hf-color" class="text-xs text-zinc-400">
                    Aksen (kompatibilitas)
                </label>

                <input
                    id="hf-color"
                    type="color"
                    wire:model.live="color"
                    class="h-9 w-12 rounded border-0 bg-transparent"
                />

                <span class="text-xs text-zinc-500">{{ $color }}</span>
            </div>

            {{-- SUBMIT --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="send"
                @disabled(empty($channels))
                class="w-full rounded-xl bg-violet-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-600 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Kirim sebagai ManukAPP
            </button>
        </form>

        {{-- RIGHT CONTENT --}}
        <div class="space-y-5">

            {{-- DISCORD PREVIEW --}}
            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                <h4 class="mb-4 font-semibold text-white">
                    Preview Discord — Satu Kartu
                </h4>

                @php
                    $previewImages = [
                        [
                            'url' => $imageUrl,
                            'position' => $imagePosition,
                            'label' => 'Gambar 1',
                        ],
                        [
                            'url' => $imageUrl2,
                            'position' => $imagePosition2,
                            'label' => 'Gambar 2',
                        ],
                    ];

                    $showPreviewImage = static fn ($item) =>
                        filter_var($item['url'], FILTER_VALIDATE_URL)
                        && str_starts_with($item['url'], 'https://');
                @endphp

                <div class="rounded-xl bg-[#111216] p-4">

                    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-200">
                        Manuk
                        <span class="rounded bg-[#5865f2] px-1.5 py-0.5 text-[10px] text-white">
                            APP
                        </span>
                    </div>

                    <div class="space-y-4 rounded-xl border border-white/10 bg-[#1d1e23] p-4">

                        @if (trim($content))
                            <p class="whitespace-pre-wrap break-words text-sm text-zinc-300">
                                {{ $content }}
                            </p>
                        @endif

                        {{-- TOP IMAGES --}}
                        @foreach ($previewImages as $img)
                            @if ($img['position'] === 'top' && $showPreviewImage($img))
                                <img
                                    src="{{ $img['url'] }}"
                                    alt="{{ $img['label'] }} — atas"
                                    class="max-h-80 w-full rounded-lg object-contain"
                                    loading="lazy"
                                />
                            @endif
                        @endforeach

                        {{-- TITLE --}}
                        <h5 class="break-words text-base font-bold text-white">
                            {{ $title ?: '✦ THE ANNOUNCEMENT' }}
                        </h5>

                        {{-- MIDDLE IMAGES --}}
                        @foreach ($previewImages as $img)
                            @if ($img['position'] === 'after_title' && $showPreviewImage($img))
                                <img
                                    src="{{ $img['url'] }}"
                                    alt="{{ $img['label'] }} — tengah"
                                    class="max-h-80 w-full rounded-lg object-contain"
                                    loading="lazy"
                                />
                            @endif
                        @endforeach

                        {{-- DESCRIPTION --}}
                        <p class="whitespace-pre-wrap break-words text-sm leading-6 text-zinc-300">
                            {{ $description ?: 'Isi pengumuman akan tampil di sini...' }}
                        </p>

                        {{-- BOTTOM IMAGES --}}
                        @foreach ($previewImages as $img)
                            @if ($img['position'] === 'bottom' && $showPreviewImage($img))
                                <img
                                    src="{{ $img['url'] }}"
                                    alt="{{ $img['label'] }} — bawah"
                                    class="max-h-80 w-full rounded-lg object-contain"
                                    loading="lazy"
                                />
                            @endif
                        @endforeach

                        {{-- FOOTER --}}
                        <p class="text-xs text-zinc-500">
                            hollow.fang • ManukAPP
                        </p>
                    </div>
                </div>

                <p class="mt-2 text-xs text-zinc-500">
                    Preview mendekati susunan Discord.
                    Markdown, mention role, dan jarak final mengikuti Discord.
                </p>
            </section>

            {{-- HISTORY --}}
            <section class="rounded-2xl border border-white/10 bg-[#191a21] p-6">
                <h4 class="font-semibold text-white">
                    Riwayat Pengiriman
                </h4>

                <div class="mt-4 space-y-3">

                    @forelse ($history as $item)
                        <div class="flex items-center justify-between gap-3 border-b border-white/5 pb-3 text-xs">

                            <div class="min-w-0">
                                <p class="truncate text-zinc-200">
                                    {{ $item->title }}
                                </p>

                                <p class="mt-1 text-zinc-500">
                                    {{ $channels[$item->channel_id] ?? $item->channel_id }}
                                    · {{ $item->created_at }}
                                </p>
                            </div>

                            <span class="{{ $item->successful ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $item->successful ? 'Terkirim' : 'Gagal' }}
                            </span>
                        </div>

                    @empty
                        <p class="text-sm text-zinc-500">
                            Belum ada pengumuman.
                        </p>
                    @endforelse

                </div>
            </section>
        </div>
    </div>
</div>
