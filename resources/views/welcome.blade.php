<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#08090b">
    <meta name="description" content="Meet Hollowfang, an underground Discord community built around connection, music, and memorable nights.">
    <title>HOLLOWFANG — An Underground Community</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      :root{color-scheme:dark;--bg:#08090b;--ink:#e9e7e1;--soft:#bab7b2;--muted:#8e8c91;--line:rgba(232,227,217,.18);--accent:#c4b5fd}
      *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--ink);font-family:'DM Sans',sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}a{color:inherit;text-decoration:none}button{font:inherit}
      body:before{content:"";pointer-events:none;position:fixed;inset:0;opacity:.095;z-index:10;background-image:url('data:image/svg+xml,%3Csvg viewBox="0 0 180 180" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="n"%3E%3CfeTurbulence type="fractalNoise" baseFrequency=".7" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23n)" opacity=".32"/%3E%3C/svg%3E')}
      .shell{max-width:1380px;margin:auto;padding-inline:clamp(22px,5vw,80px)}
      .nav{height:90px;display:flex;justify-content:space-between;align-items:center;gap:24px;border-bottom:1px solid var(--line);position:relative;z-index:5}.brand{display:inline-flex;align-items:center;gap:12px;letter-spacing:.3em;font-size:14px;font-weight:700}.brand-mark{width:29px;height:29px;display:grid;place-items:center;border:1px solid #8d8988;transform:rotate(45deg)}.brand-mark span{transform:rotate(-45deg);font-family:'Cormorant Garamond',serif;font-size:24px;line-height:1}.links{display:flex;align-items:center;gap:clamp(16px,2.8vw,42px)}.links a:not(.nav-cta){color:#c2c0c0;font-size:11px;letter-spacing:.17em;text-transform:uppercase;transition:color .2s}.links a:hover{color:#fff}.nav-cta{padding:13px 21px;border:1px solid rgba(237,232,225,.45);font-size:10px;letter-spacing:.15em;font-weight:700;text-transform:uppercase;transition:background .2s,color .2s}.nav-cta:hover{background:#ebe6e0;color:#111}.nav-toggle{display:none;background:none;color:var(--ink);border:1px solid var(--line);padding:10px 13px;cursor:pointer}
      .hero{position:relative;min-height:calc(100svh - 90px);display:grid;place-items:center;isolation:isolate;text-align:center;padding-top:50px;padding-bottom:76px;overflow:hidden}.hero:after{content:"";position:absolute;inset:0;pointer-events:none;z-index:-1;background:linear-gradient(180deg,#08090b 1%,rgba(8,9,11,.20) 25%,rgba(8,9,11,.05) 58%,#08090b 100%),linear-gradient(90deg,#08090b 0%,transparent 23%,transparent 77%,#08090b 100%)}.hero-art{position:absolute;inset:-3% -4% 0;z-index:-2;background-image:url('{{ asset('images/hollowfang-gothic-banner.png') }}');background-position:center center;background-size:cover;filter:brightness(.75) contrast(1.07);opacity:.53}.hero-content{max-width:1040px;margin:auto;position:relative;z-index:2;padding:20px}.eyebrow{display:flex;align-items:center;justify-content:center;gap:16px;color:#d4d0ce;font-size:10px;letter-spacing:.42em;text-transform:uppercase;font-weight:700}.eyebrow:before,.eyebrow:after{content:"";height:1px;width:42px;background:rgba(235,228,220,.6)}.hero h1{font-family:'Cormorant Garamond',Georgia,serif;font-size:clamp(65px,13vw,183px);font-weight:500;letter-spacing:-.065em;line-height:.86;margin:36px 0 27px;text-shadow:0 7px 40px rgba(0,0,0,.9)}.hero h1 span{display:block;font-size:clamp(11px,1.35vw,17px);font-family:'DM Sans',sans-serif;letter-spacing:.64em;font-weight:500;margin-bottom:18px;color:#dedbd5}.hero .quote{font-family:'Cormorant Garamond',Georgia,serif;font-size:clamp(26px,3.4vw,45px);font-weight:500;line-height:1.15;margin:24px auto 11px;max-width:760px;text-shadow:0 3px 15px #000}.hero .desc{font-size:12px;color:#d0ccc8;letter-spacing:.07em;line-height:1.9;margin:0 auto 31px;max-width:550px;text-shadow:0 2px 8px #000}.actions{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:13px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 28px;font-size:11px;letter-spacing:.13em;text-transform:uppercase;font-weight:700;transition:transform .2s,background .2s,color .2s,border .2s}.btn:hover{transform:translateY(-2px)}.btn-primary{background:#eee9e3;color:#111;border:1px solid #eee9e3}.btn-primary:hover{background:white}.btn-secondary{border:1px solid rgba(238,233,227,.45);background:rgba(0,0,0,.2);color:#f2eeee}.btn-secondary:hover{border-color:#fff;background:rgba(255,255,255,.08)}.hero-bottom{position:absolute;bottom:27px;left:0;right:0;display:flex;justify-content:center;gap:12px;color:#c1bdb8;font-size:9px;letter-spacing:.3em;text-transform:uppercase}.hero-bottom .line{height:1px;width:24px;background:#8b8683;margin-top:5px}
      .section{padding:110px 0;border-top:1px solid var(--line)}.section-head{text-align:center;margin-bottom:54px}.section-kicker{font-size:10px;letter-spacing:.36em;color:#bdb6b2;text-transform:uppercase}.section-title{font-family:'Cormorant Garamond',Georgia,serif;font-size:clamp(43px,6vw,82px);font-weight:500;line-height:1;margin:20px 0 16px}.section-copy{font-size:13px;line-height:2;color:#a7a2a0;max-width:610px;margin:0 auto}.feature-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));border:1px solid var(--line)}.feature{padding:42px 34px;min-height:238px}.feature+.feature{border-left:1px solid var(--line)}.feature-n{font-size:11px;color:#8e8388;letter-spacing:.2em}.feature h3{font-family:'Cormorant Garamond',serif;font-size:37px;font-weight:500;margin:24px 0 12px}.feature p{font-size:12px;color:#aaa7a8;line-height:1.9;margin:0;max-width:300px}.join{text-align:center;position:relative;padding:110px 25px 120px;overflow:hidden}.join:before{content:"";position:absolute;left:50%;top:50%;width:min(70vw,600px);height:400px;transform:translate(-50%,-50%);background:radial-gradient(ellipse,rgba(105,86,150,.12),transparent 70%);pointer-events:none}.join>*{position:relative}.join h2{font-family:'Cormorant Garamond',serif;font-size:clamp(44px,7vw,94px);line-height:1;margin:18px 0 23px;font-weight:500}.join p{color:#aaa3a3;font-size:13px;margin-bottom:32px}.manifesto{border-top:1px solid var(--line);padding:95px 0 110px}.manifesto-inner{max-width:910px;margin:auto;display:grid;grid-template-columns:170px 1fr;gap:46px;align-items:start}.manifesto-number{font-size:10px;letter-spacing:.27em;color:#a9a5a5;text-transform:uppercase;padding-top:13px}.manifesto h2{font-family:'Cormorant Garamond',serif;font-size:clamp(43px,6vw,80px);font-weight:500;line-height:1.07;margin:0 0 30px}.manifesto p{font-size:13px;color:#aaa6a7;line-height:2.1;margin:0 0 14px}.values{display:grid;grid-template-columns:repeat(3,1fr);border:1px solid var(--line);margin-top:60px}.value{padding:32px 28px}.value+.value{border-left:1px solid var(--line)}.value .symbol{display:block;font-family:'Cormorant Garamond',serif;font-size:37px;color:#dfdcdb;margin-bottom:12px}.value strong{font-weight:600;letter-spacing:.13em;text-transform:uppercase;font-size:11px}.value p{font-size:12px;line-height:1.9;color:#aaa5a3;margin:12px 0 0}.timeline{padding:100px 0;border-top:1px solid var(--line)}.timeline-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:44px}.timeline-item{border-top:1px solid rgba(235,230,226,.4);padding-top:25px}.timeline-item .step{font-size:10px;color:#afaaa8;letter-spacing:.2em}.timeline-item h3{font-family:'Cormorant Garamond',serif;font-weight:500;font-size:34px;margin:15px 0}.timeline-item p{font-size:12px;color:#a6a2a3;line-height:1.9}.footer{border-top:1px solid var(--line);padding:27px 0;color:#999399}.footer-inner{display:flex;justify-content:space-between;align-items:center;gap:16px;font-size:10px;letter-spacing:.13em}.footer .wordmark{font-size:13px;letter-spacing:.26em;color:#e5e2dd;font-weight:700}
      @media(max-width:760px){.manifesto-inner{grid-template-columns:1fr;gap:14px}.values{grid-template-columns:1fr}.value+.value{border-left:0;border-top:1px solid var(--line)}.timeline-grid{grid-template-columns:1fr}.timeline-item{padding-bottom:20px}.nav{height:76px}.nav-toggle{display:block}.links{display:none;position:absolute;top:76px;left:0;right:0;padding:24px;background:#101012;border-bottom:1px solid var(--line);flex-direction:column;align-items:stretch;gap:20px}.links.is-open{display:flex}.nav-cta{text-align:center}.hero{min-height:calc(100svh - 76px);padding:88px 0 75px}.hero-art{background-position:52% center;opacity:.39}.hero h1{letter-spacing:-.048em}.hero h1 span{letter-spacing:.36em}.eyebrow{letter-spacing:.2em}.eyebrow:before,.eyebrow:after{width:19px}.hero .desc{max-width:320px}.feature-grid{grid-template-columns:1fr}.feature+.feature{border-left:0;border-top:1px solid var(--line)}.feature{min-height:auto;padding:34px 30px}.section{padding:80px 0}.footer-inner{flex-direction:column;text-align:center}.brand{font-size:12px}}
      @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{animation:none!important;transition:none!important}}
    </style>
</head>
<body>
    <header class="shell">
      <nav class="nav" aria-label="Main navigation">
        <a href="{{ route('home') }}" class="brand" aria-label="Hollowfang home"><span class="brand-mark" aria-hidden="true"><span>H</span></span>HOLLOWFANG</a>
        <button type="button" class="nav-toggle" id="menu-toggle" aria-expanded="false" aria-controls="main-links">☰ <span class="sr-only">Menu</span></button>
        <div class="links" id="main-links">
          <a href="#home">Home</a>
          <a href="#about">About</a>
          <a href="#community">Community</a>
          <a href="#values">Our Values</a>
          <a class="nav-cta" href="#discover">Discover Hollowfang ↗</a>
        </div>
      </nav>
    </header>
    <main>
      <section id="home" class="hero shell" aria-labelledby="hero-title">
        <div class="hero-art" aria-hidden="true"></div>
        <div class="hero-content">
          <div class="eyebrow">Welcome to the underground</div>
          <h1 id="hero-title"><span>✦ &nbsp; ENTER THE VOID &nbsp; ✦</span>HOLLOWFANG</h1>
          <p class="quote">“Stay quiet, or be heard.<br>Either way, you belong here.”</p>
          <p class="desc">A place for the lost, the restless, and those who refuse to fade.<br>Welcome to your corner of the dark.</p>
          <div class="actions">
            <a class="btn btn-primary" href="#about">Discover Hollowfang <span aria-hidden="true">&nbsp;↘</span></a>
            <a class="btn btn-secondary" href="#community">Our Community ↓</a>
          </div>
        </div>
        <div class="hero-bottom"><span class="line"></span>Scroll to discover<span class="line"></span></div>
      </section>
      <section id="about" class="section shell" aria-labelledby="about-title">
        <div class="section-head"><div class="section-kicker">01 / The beginning</div><h2 class="section-title" id="about-title">Not just another server.</h2><p class="section-copy">Hollowfang is a little corner of the internet for conversations after midnight, music that never stops, and people who feel at home in the unusual.</p></div>
        <div id="community" class="feature-grid">
          <article class="feature"><div class="feature-n">01 — CONNECTION</div><h3>Community</h3><p>Meet your people. Find a place to talk, listen, and simply exist as yourself.</p></article>
          <article class="feature"><div class="feature-n">02 — FREQUENCY</div><h3>Music</h3><p>Share your soundtrack, discover something new, and let the night play on.</p></article>
          <article class="feature"><div class="feature-n">03 — MOMENTS</div><h3>Events</h3><p>Make memories beyond the usual. Good company is the whole point.</p></article>
        </div>
      </section>
      <section id="values" class="manifesto shell" aria-labelledby="values-title">
        <div class="manifesto-inner">
          <div class="manifesto-number">02 / Our identity</div>
          <div>
            <h2 id="values-title">A home for the<br>beautifully unusual.</h2>
            <p>Hollowfang is an online community shaped by late-night conversations, shared playlists, gaming sessions, and the little moments that make strangers feel familiar.</p>
            <p>We believe a good community doesn't need you to perform. Come as you are, find your rhythm, and make the space your own.</p>
          </div>
        </div>
        <div class="values">
          <article class="value"><span class="symbol" aria-hidden="true">✧</span><strong>Belonging</strong><p>Different personalities. One place to feel welcome and be yourself.</p></article>
          <article class="value"><span class="symbol" aria-hidden="true">◌</span><strong>Expression</strong><p>Share a song, a story, or a quiet moment. Every voice has its space.</p></article>
          <article class="value"><span class="symbol" aria-hidden="true">✦</span><strong>Connection</strong><p>The best moments are the ones we create together.</p></article>
        </div>
      </section>
      <section id="discover" class="timeline shell" aria-labelledby="discover-title">
        <div class="section-head"><div class="section-kicker">03 / Life inside Hollowfang</div><h2 class="section-title" id="discover-title">There is room for everyone.</h2><p class="section-copy">However you spend your nights, there's always something worth sharing.</p></div>
        <div class="timeline-grid">
          <article class="timeline-item"><span class="step">01 / TALK</span><h3>Find your circle.</h3><p>Get to know the people behind the usernames. Start a conversation or enjoy the company.</p></article>
          <article class="timeline-item"><span class="step">02 / LISTEN</span><h3>Stay for the music.</h3><p>Build playlists together, trade recommendations, and let a song set the mood.</p></article>
          <article class="timeline-item"><span class="step">03 / EXPERIENCE</span><h3>Make it a memory.</h3><p>Hang out, play together, and turn ordinary evenings into stories worth keeping.</p></article>
        </div>
      </section>
      <section class="join" aria-labelledby="join-title"><div class="section-kicker">Hollowfang / The underground</div><h2 id="join-title">The night is yours.</h2><p>Stay quiet, or be heard. Either way, you belong here.</p>
        <a class="btn btn-primary" href="#home">Back to the beginning ↑</a>
      </section>
    </main>
    <footer class="footer"><div class="shell footer-inner"><div class="wordmark">HOLLOWFANG</div><div>© {{ date('Y') }} HOLLOWFANG. ALL RIGHTS RESERVED.</div><div>STAY QUIET, OR BE HEARD.</div></div></footer>
    <script>
      const toggle=document.getElementById('menu-toggle');
      const links=document.getElementById('main-links');
      toggle?.addEventListener('click',()=>{const open=links.classList.toggle('is-open');toggle.setAttribute('aria-expanded',String(open));});
      links?.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{links.classList.remove('is-open');toggle?.setAttribute('aria-expanded','false');}));
    </script>
</body>
</html>
