{{-- This partial is included inside AnnouncementManager's root div. --}}
<div class="space-y-3 rounded-xl border border-white/10 bg-[#121318] p-4">
    <div class="flex items-center justify-between gap-2">
        <h5 class="text-sm font-semibold text-white">Kelola Channel Discord</h5>
        <button type="button" wire:click="refreshDirectory" class="rounded-lg bg-white/10 px-3 py-1.5 text-xs text-white hover:bg-white/20">Refresh Discord</button>
    </div>
    <p class="text-xs text-zinc-400">Klik Aktifkan untuk mengizinkan pengumuman. Perubahan langsung tersimpan di ManukAPP, tanpa edit .env.</p>
    @error('directory')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
    <div class="max-h-48 space-y-1 overflow-y-auto">
        @forelse($directoryChannels as $id => $name)
            <div wire:key="hf-channel-{{ $id }}" class="flex items-center justify-between gap-3 rounded-lg bg-white/5 px-3 py-2">
                <span class="truncate text-sm text-zinc-200">#{{ $name }}</span>
                <button type="button" wire:click="toggleAnnouncementChannel('{{ $id }}')" class="shrink-0 rounded-md px-2 py-1 text-xs {{ isset($approvedChannels[$id]) ? 'bg-emerald-500/15 text-emerald-300' : 'bg-zinc-700 text-zinc-200' }}">
                    {{ isset($approvedChannels[$id]) ? 'Aktif • klik nonaktifkan' : 'Aktifkan' }}
                </button>
            </div>
        @empty
            <p class="text-xs text-amber-300">Daftar channel belum tersedia. Periksa API Announcement atau klik Refresh.</p>
        @endforelse
    </div>
</div>
<div class="space-y-2 rounded-xl border border-white/10 bg-[#121318] p-4">
    <h5 class="text-sm font-semibold text-white">Insert Role Mention</h5>
    <p class="text-xs text-zinc-400">Pilih role. Mention akan ditambahkan ke akhir deskripsi tanpa ping notifikasi.</p>
    <div class="flex flex-col gap-2 sm:flex-row">
        <select wire:model="selectedRoleId" class="min-w-0 flex-1 rounded-lg border border-white/10 bg-[#101116] px-3 py-2.5 text-sm text-white">
            <option value="">Pilih role Discord...</option>
            @foreach($directoryRoles as $id => $name)
                <option value="{{ $id }}">&#64;{{ $name }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="insertSelectedRole" class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-600">Insert Role</button>
    </div>
</div>
