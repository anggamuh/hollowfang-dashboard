<section class="hf-hero">
    <div class="hf-hero-copy"><p class="hf-eyebrow">THE HOLLOW / COMMUNITY HQ</p><h2>A place to belong.<br><span>A space to build.</span></h2><p>{{ $guildName ?: 'Hollowfang' }} dimulai dari orang-orangnya. Kelola komunitas, bangun obrolan, dan bikin setiap momen berarti.</p><button type="button" wire:click="changeMenu('community')" class="hf-button hf-button-primary">Kelola komunitas <x-hollowfang.icon name="arrow" /></button></div>
    <div class="hf-orbit" aria-hidden="true"><div class="hf-orbit-ring"></div><div class="hf-orbit-ring hf-orbit-inner"></div><div class="hf-orbit-core">H<span>F</span></div><span class="hf-orbit-label">HOLLOWFANG / EST. COMMUNITY</span></div>
</section>
<section class="hf-stats" aria-label="Statistik server">
    @foreach ([
        ['members', 'Total Members', $guildMembers ?? '—', $guildName ?: 'Server belum terdeteksi'],
        ['community', 'Online Members', $onlineMembers ?? '—', $onlineMembers === null ? 'Presence belum tersedia' : 'Anggota sedang online'],
        ['music', 'Music Sessions', $musicSessions ?? '—', 'Sesi musik dari monitoring bot'],
        ['monitoring', 'Gateway Ping', $botPing !== null ? $botPing.' ms' : '—', $botConnected ? 'Latensi koneksi Discord' : 'Menunggu koneksi bot'],
    ] as [$icon, $label, $value, $note])
        <article class="hf-stat"><div><span>{{ $label }}</span><x-hollowfang.icon :name="$icon" /></div><strong>{{ $value }}</strong><small>{{ $note }}</small></article>
    @endforeach
</section>
<div class="hf-overview-grid">
    <section class="hf-panel"><div class="hf-panel-title"><div><p class="hf-eyebrow">JUMP RIGHT IN</p><h3>Quick actions</h3></div><x-hollowfang.icon name="arrow" /></div><div class="hf-actions">
        @foreach ([['members','Kelola anggota','Cari member dan atur role.'],['moderation','Moderasi server','Jaga ruang tetap nyaman.'],['webhooks','Buat pengumuman','Sampaikan kabar terbaru.'],['events','Jadwalkan event','Waktunya kumpul bareng.']] as [$destination,$title,$description])
            <button type="button" wire:click="changeMenu('{{ $destination }}')" class="hf-action"><span class="hf-action-icon"><x-hollowfang.icon :name="$destination" /></span><span><strong>{{ $title }}</strong><small>{{ $description }}</small></span><x-hollowfang.icon name="arrow" /></button>
        @endforeach
    </div></section>
    <section class="hf-panel hf-music-panel"><div class="hf-panel-title"><div><p class="hf-eyebrow">THE SERVER SOUNDTRACK</p><h3>Now playing</h3></div><span class="hf-badge">{{ $nowPlaying ? ($musicPaused ? 'Paused' : 'Playing') : 'Idle' }}</span></div><div class="hf-track"><div class="hf-record" aria-hidden="true"><x-hollowfang.icon name="music" /></div><div><strong>{{ $nowPlaying ? data_get($nowPlaying, 'title', 'Unknown track') : 'Ruang untuk lagu berikutnya' }}</strong><p>{{ $nowPlaying ? data_get($nowPlaying, 'artist', 'Unknown artist') : ($botConnected ? 'Mulai musik lewat Discord.' : 'Menunggu koneksi monitoring.') }}</p></div></div>
        @if($trackProgress !== null)<div class="hf-progress" role="progressbar" aria-label="Progress lagu" aria-valuenow="{{ $trackProgress }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $trackProgress }}%"></span></div>@endif
        <div class="hf-panel-footer"><span>{{ $queueLength ?? '—' }} lagu di antrean</span><button type="button" wire:click="changeMenu('music')">Buka music <x-hollowfang.icon name="arrow" /></button></div>
    </section>
</div>
<section class="hf-panel"><div class="hf-panel-title"><div><p class="hf-eyebrow">CONNECTED SYSTEMS</p><h3>System health</h3></div><button type="button" wire:click="changeMenu('gateway')" class="hf-text-button">Lihat diagnostik <x-hollowfang.icon name="arrow" /></button></div><div class="hf-health-grid">
    @foreach ([['ManukAPP',$botConnected,$botConnected ? 'Online' : 'Not Connected'],['Discord Gateway',$gatewayConnected,$gatewayConnected ? 'Connected' : 'Unknown'],['Lavalink',$lavalinkConnected,$lavalinkConnected ? 'Connected' : $lavalinkStatus]] as [$name,$connected,$state])
        <div class="hf-health-item"><span class="hf-dot {{ $connected ? 'is-online' : '' }}"></span><span><strong>{{ $name }}</strong><small>{{ $state }}</small></span></div>
    @endforeach
    <div class="hf-health-item"><x-hollowfang.icon name="monitoring" /><span><strong>Bot uptime</strong><small>{{ $botUptime }}</small></span></div>
</div></section>
