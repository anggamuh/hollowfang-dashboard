<dialog x-on:keydown.escape.prevent.stop="$el.close()" x-ref="search" class="hf-dialog" x-on:click="if ($event.target === $el) $el.close()" aria-labelledby="hf-search-title">
    <div class="hf-dialog-heading"><h2 id="hf-search-title">Ke mana kita?</h2><button type="button" class="hf-icon-button" x-on:click="$refs.search.close()" aria-label="Tutup pencarian"><x-hollowfang.icon name="close" /></button></div>
    <label class="hf-command-input"><x-hollowfang.icon name="search" /><input x-ref="searchInput" x-model="query" type="search" placeholder="Cari menu Hollowfang..." aria-label="Cari menu" maxlength="60"></label>
    <div class="hf-command-results">
        @foreach($menus as $key => $label)
            <button type="button" x-show="{{ \Illuminate\Support\Js::from(mb_strtolower($label)) }}.includes(query.toLowerCase())" x-on:click="$refs.search.close()" wire:click="changeMenu('{{ $key }}')"><x-hollowfang.icon :name="$key" /><span>{{ $label }}</span><x-hollowfang.icon name="arrow" /></button>
        @endforeach
        <p x-show="!{{ \Illuminate\Support\Js::from(array_map('mb_strtolower', array_values($menus))) }}.some(label => label.includes(query.toLowerCase()))" class="hf-empty">Menu nggak ditemukan. Coba kata lain.</p>
    </div><p class="hf-dialog-hint">Tab untuk memilih · Enter untuk membuka · Esc untuk menutup</p>
</dialog>
<dialog x-on:keydown.escape.prevent.stop="$el.close()" x-ref="mobileNav" class="hf-dialog hf-mobile-dialog" aria-labelledby="hf-mobile-title" x-on:click="if ($event.target === $el) $el.close()">
    <div class="hf-dialog-heading"><h2 id="hf-mobile-title">Hollowfang workspace</h2><button type="button" class="hf-icon-button" x-on:click="$refs.mobileNav.close()" aria-label="Tutup menu"><x-hollowfang.icon name="close" /></button></div>
    <nav class="hf-command-results" aria-label="Menu ponsel">@foreach($menus as $key => $label)<button type="button" wire:click="changeMenu('{{ $key }}')" x-on:click="$refs.mobileNav.close()" aria-current="{{ $activeMenu === $key ? 'page' : 'false' }}"><x-hollowfang.icon :name="$key" /><span>{{ $label }}</span></button>@endforeach</nav>
    <a href="{{ route('discord.connect') }}" class="hf-dialog-hint">{{ $discordName }} · Sinkronkan profil Discord</a>
</dialog>
