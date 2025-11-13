<?php
session_start();
$config = json_decode(@file_get_contents(__DIR__.'/config.json'), true) ?: [];
$site_name = $config['site_name'] ?? 'Odia-Hindi TTS';
$ad_code = $config['ad_code'] ?? '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($site_name)?> - TTS</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
  <script src="https://unpkg.com/wavesurfer.js"></script>
</head>
<body>
<header class="topbar">
  <div class="logo">
    <div class="logo-anim">🔊</div>
    <div class="site-title"><?=htmlspecialchars($site_name)?></div>
  </div>
  <nav><a href="admin/index.php">Admin</a></nav>
</header>
<main class="container">
  <section class="card">
    <h1>Odia / Hindi Text-to-Speech</h1>
    <div class="controls">
      <label>Language
        <select id="lang">
          <option value="or-IN">Odia</option>
          <option value="hi-IN">Hindi</option>
        </select>
      </label>
      <label>Voice
        <select id="voice">
          <option value="female">Female</option>
          <option value="male">Male</option>
        </select>
      </label>
    </div>
    <textarea id="txt" maxlength="10000" placeholder="Type Odia or Hindi text (max 10,000 chars)"></textarea>
    <div class="meta"><span id="count">0</span>/10000 characters</div>
    <div class="actions">
      <button id="convert" class="btn">Convert to Voice</button>
      <button id="downloadBtn" class="btn btn-secondary" disabled>Download (after ad)</button>
    </div>
    <div id="playerArea"></div>
    <div class="ad-slot"><?= $ad_code ?></div>
  </section>
</main>

<!-- Ad modal -->
<div id="adModal" class="modal">
  <div class="modal-content">
    <h3>Watch this ad to unlock download</h3>
    <div id="adContainer" class="ad-placeholder"></div>
    <div class="timer">Please wait <span id="timer">10</span> seconds</div>
    <button id="skipAd" disabled class="btn">Skip Ad</button>
  </div>
</div>

<script>
const txt = document.getElementById('txt');
const count = document.getElementById('count');
txt.addEventListener('input', ()=> count.textContent = txt.value.length);

document.getElementById('convert').addEventListener('click', async ()=>{
  const text = txt.value.trim();
  if(!text){ alert('Enter some text'); return; }
  if(text.length > 10000){ alert('Text exceeds 10,000 characters'); return; }

  const lang = document.getElementById('lang').value;
  const voice = document.getElementById('voice').value;

  const res = await fetch('tts.php', {
    method:'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ text, lang, voice })
  });
  const j = await res.json();
  if(j.error){ alert(j.error); return; }
  const files = j.files;
  const playerArea = document.getElementById('playerArea');
  playerArea.innerHTML = '';
  let idx = 0;
  const audio = new Audio();
  audio.addEventListener('ended', ()=> {
    idx++;
    if(idx < files.length){ audio.src = files[idx]; audio.play(); }
    else playerArea.innerHTML += '<p class="done">Playback finished.</p>';
  });
  audio.src = files[0];
  audio.play();
  playerArea.innerHTML = '<div id="waveform"></div>';
  const wavesurfer = WaveSurfer.create({container:'#waveform', waveColor:'#8bd3ff', progressColor:'#7a3cff', height:60});
  wavesurfer.load(files[0]);
  window._tts_files = files;
  document.getElementById('downloadBtn').disabled = false;
});

document.getElementById('downloadBtn').addEventListener('click', ()=>{
  const modal = document.getElementById('adModal');
  modal.style.display = 'block';
  fetch('admin/get_ad_html.php').then(r=>r.text()).then(html=> {
    document.getElementById('adContainer').innerHTML = html;
  });
  let t = 10;
  document.getElementById('timer').textContent = t;
  const btn = document.getElementById('skipAd');
  btn.disabled = true;
  const iv = setInterval(()=>{ t--; document.getElementById('timer').textContent = t; if(t<=0){ clearInterval(iv); btn.disabled = false; btn.textContent='Skip Ad'; } },1000);
  btn.onclick = ()=>{
    modal.style.display='none';
    fetch('download.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ files: window._tts_files })})
      .then(r=>r.blob()).then(blob=>{
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'tts_audio.zip'; document.body.appendChild(a); a.click(); a.remove();
      });
  };
});

window.onclick = function(e){ const m = document.getElementById('adModal'); if(e.target==m) m.style.display='none'; };
</script>
</body>
</html>
