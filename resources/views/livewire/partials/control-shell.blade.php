<div class="hf-workspace">
    <aside class="hf-sidebar">
        <a href="{{ route('control') }}" class="hf-brand"><span class="hf-mark">H<span>F</span></span><span>HOLLOWFANG<small>SERVER WORKSPACE</small></span></a>
        <div class="hf-server"><span class="hf-server-avatar">{{ mb_strtoupper(mb_substr($guildName ?: 'Hollowfang', 0, 1)) }}</span><div><strong>{{ $guildName ?: 'Hollowfang' }}</strong><small>Discord community</small></div><span class="hf-dot {{ $botConnected ? 'is-online' : '' }}"></span></div>
        <nav aria-label="Navigasi utama" class="hf-nav">
            @foreach (['WORKSPACE' => ['overview','members','moderation','community'], 'ENGAGEMENT' => ['music','webhooks','events'], 'SYSTEM' => ['monitoring','gateway','logs','settings']] as $group => $items)
                <p class="hf-nav-label">{{ $group }}</p>
                @foreach ($items as $key)
                    <button type="button" wire:click="changeMenu('{{ $key }}')" aria-current="{{ $activeMenu === $key ? 'page' : 'false' }}" class="hf-nav-item {{ $activeMenu === $key ? 'is-active' : '' }}"><x-hollowfang.icon :name="$key" /><span>{{ $menus[$key] }}</span>@if($activeMenu === $key)<span class="hf-active-dot"></span>@endif</button>
                @endforeach
            @endforeach
        </nav>
        <div class="hf-sidebar-bottom"><span class="hf-dot {{ $botConnected ? 'is-online' : '' }}"></span><span>{{ $botConnected ? 'Bot connected' : 'Bot not connected' }}</span><button type="button" wire:click="changeMenu('settings')" aria-label="Buka pengaturan"><x-hollowfang.icon name="settings" /></button></div>
        <a href="{{ route('discord.connect') }}" class="hf-account" title="Sinkronkan akun Discord">
            @if($discordAvatar)<img src="{{ $discordAvatar }}" alt="" referrerpolicy="no-referrer">@else<span class="hf-avatar">{{ mb_strtoupper(mb_substr($discordName, 0, 1)) }}</span>@endif
            <span><strong>{{ $discordName }}</strong><small>{{ $isOwner ? 'Workspace owner' : 'Connected account' }}</small></span><x-hollowfang.icon name="refresh" />
        </a>
    </aside>
    <div class="hf-main">
        <header class="hf-topbar">
            <button type="button" class="hf-mobile-trigger hf-icon-button" x-on:click="$refs.mobileNav.showModal()" aria-label="Buka menu"><x-hollowfang.icon name="menu" /></button>
            <p class="hf-breadcrumb">Workspace <span>/</span> <strong>{{ $menus[$activeMenu] }}</strong></p>
            <button type="button" class="hf-search-trigger" x-on:click="query = ''; $refs.search.showModal(); $nextTick(() => $refs.searchInput.focus())"><x-hollowfang.icon name="search" /><span>Cari menu...</span><kbd>Ctrl K</kbd></button>
            <span class="hf-top-status"><span class="hf-dot {{ $botConnected ? 'is-online' : '' }}"></span>{{ $botConnected ? 'Connected' : 'Offline' }}</span>
        </header>
        <main id="hf-content" class="hf-content" tabindex="-1">
            <div class="hf-page-heading">
                <div><p class="hf-eyebrow">YOUR COMMUNITY, CONNECTED</p><h1>{{ $activeMenu === 'overview' ? 'Server overview' : $menus[$activeMenu] }}</h1><p>{{ [
                    'overview' => 'Selamat datang kembali. Semua yang perlu lu pantau, di satu tempat.',
                    'members' => 'Temukan anggota dan kelola akses komunitas.',
                    'moderation' => 'Jaga server tetap nyaman dengan tindakan yang terarah.',
                    'community' => 'Buat setiap anggota merasa jadi bagian dari Hollowfang.',
                    'music' => 'Satu ruang untuk musik, antrean, dan kontrol playback.',
                    'events' => 'Rencanakan momen berikutnya bersama komunitas.',
                    'webhooks' => 'Pengumuman yang rapi, untuk channel yang tepat.',
                    'logs' => 'Telusuri aktivitas dan tindakan pengelolaan server.',
                ][$activeMenu] ?? 'Pantau koneksi dan kesiapan sistem Hollowfang.' }}</p></div>
                <div class="hf-refresh"><button type="button" class="hf-button" wire:click="refreshNow" wire:loading.attr="disabled" wire:target="refreshNow"><x-hollowfang.icon name="refresh" /><span wire:loading.remove wire:target="refreshNow">Refresh status</span><span wire:loading wire:target="refreshNow">Mengecek...</span></button><small>{{ $apiChecked ? 'Diperiksa '.\Illuminate\Support\Carbon::parse($apiChecked)->timezone('Asia/Jakarta')->format('H:i:s').' WIB' : 'Belum diperiksa' }}</small></div>
            </div>
