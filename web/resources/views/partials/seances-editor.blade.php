{{-- Éditeur de séances (1 / plusieurs / récurrent + calendrier). Variables : $seances, $animateurs.
     Sérialise vers seances[i][titre|format|lieu|date_debut|date_fin|animateurs[]]. --}}
@php
    $animateursList = $animateurs ?? [];
    $initSeances = old('seances');
    if (!$initSeances) {
        $initSeances = collect($seances ?? [])->map(function ($s) {
            return [
                'titre'      => $s['titre'] ?? '',
                'format'     => $s['format'] ?? 'presentiel',
                'lieu'       => $s['lieu'] ?? '',
                'date_debut' => !empty($s['date_debut']) ? \Carbon\Carbon::parse($s['date_debut'])->format('Y-m-d\TH:i') : '',
                'date_fin'   => !empty($s['date_fin']) ? \Carbon\Carbon::parse($s['date_fin'])->format('Y-m-d\TH:i') : '',
                'animateurs' => collect($s['animateurs'] ?? [])->map(fn ($a) => (int) ($a['id_utilisateur'] ?? $a))->values()->toArray(),
            ];
        })->values()->toArray();
    }
@endphp

{{-- === Styles === --}}
<style>
    .sc { --sc-line: rgba(18,3,9,0.14); }
    .sc-title { font-family:'Bebas Neue',sans-serif; font-size:1.5rem; letter-spacing:0.04em; margin:0 0 4px; color:var(--coffee); }
    .sc-sub { font-family:'DM Mono',monospace; font-size:0.72rem; opacity:0.55; margin:0 0 16px; letter-spacing:0.03em; }

    /* Assistant */
    .sc-modes { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:22px; }
    .sc-mode { flex:1 1 180px; display:flex; gap:10px; align-items:flex-start; border:var(--border); background:white; box-shadow:var(--shadow-sm); padding:12px 14px; cursor:pointer; transition:transform .08s; }
    .sc-mode:hover { transform:translate(-1px,-1px); }
    .sc-mode.active { background:var(--wheat); }
    .sc-mode input { margin-top:3px; }
    .sc-mode-t { font-family:'Outfit',sans-serif; font-weight:700; font-size:0.98rem; color:var(--coffee); }
    .sc-mode-d { font-size:0.78rem; opacity:0.6; line-height:1.35; }

    .sc-field { display:flex; flex-direction:column; gap:5px; }
    .sc-field > label { font-family:'DM Mono',monospace; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; opacity:0.6; }
    .sc-field input, .sc-field select { padding:9px 11px; border:2px solid var(--coffee); background:white; font-family:'Outfit',sans-serif; font-size:0.94rem; width:100%; }
    .sc-time { display:flex; align-items:center; gap:8px; }
    .sc-time select { width:auto; flex:1; }
    .sc-time span { font-family:'DM Mono',monospace; font-size:0.75rem; opacity:0.6; }

    /* Réglages communs */
    .sc-defaults { border:var(--border); background:var(--wheat); box-shadow:var(--shadow-sm); padding:14px 16px; margin-bottom:20px; }
    .sc-defaults-t { font-family:'DM Mono',monospace; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; margin:0 0 12px; display:flex; align-items:center; gap:8px; }
    .sc-defaults-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; align-items:end; }

    /* Récurrence */
    .sc-rule { border:var(--border); background:#fff; box-shadow:var(--shadow-sm); margin-bottom:20px; padding:0; }
    .rc-head { background:var(--coffee); color:var(--cream); padding:11px 18px; font-family:'DM Mono',monospace; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; display:flex; align-items:center; gap:8px; }
    .rc-body { padding:18px; display:flex; flex-direction:column; gap:14px; }
    .rc-line { display:grid; grid-template-columns:150px 1fr; gap:16px; align-items:center; }
    @media(max-width:600px){ .rc-line{ grid-template-columns:1fr; gap:6px; align-items:start; } }
    .rc-label { font-family:'DM Mono',monospace; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; opacity:0.55; }
    .rc-control { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .rc-num { width:60px; padding:9px 6px; border:2px solid var(--coffee); background:#fff; text-align:center; font-family:'Outfit',sans-serif; font-size:0.95rem; color:var(--coffee); }
    .rc-suffix { font-size:0.9rem; opacity:0.75; }
    .rc-end { flex-direction:column; align-items:flex-start; gap:10px; }
    .rc-end-opt { display:flex; align-items:center; gap:8px; font-size:0.92rem; }
    .rc-hint { font-family:'DM Mono',monospace; font-size:0.64rem; opacity:0.5; }
    .sc-days { display:flex; gap:6px; }
    .sc-day { width:36px; height:36px; border:2px solid var(--coffee); background:#fff; font-family:'DM Mono',monospace; font-size:0.8rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:transform .06s; }
    .sc-day:hover { background:var(--wheat); }
    .sc-day.on { background:var(--cherry); color:var(--cream); }
    .rc-foot { border-top:2px solid rgba(18,3,9,0.12); background:var(--cream); padding:14px 18px; display:flex; justify-content:flex-end; }
    .rc-gen { background:var(--forest); color:var(--cream); border:var(--border); box-shadow:var(--shadow-sm); font-family:'Bebas Neue',sans-serif; font-size:1.25rem; letter-spacing:0.06em; padding:11px 28px; cursor:pointer; }
    .rc-gen:hover { transform:translate(-2px,-2px); box-shadow:5px 5px 0 var(--coffee); }

    /* Calendrier + liste */
    .sc-cl { display:grid; grid-template-columns:minmax(280px,340px) 1fr; gap:22px; align-items:start; }
    @media (max-width:760px){ .sc-cl { grid-template-columns:1fr; } }
    .sc-cal { border:var(--border); background:white; box-shadow:var(--shadow-sm); padding:14px; }
    .sc-cal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .sc-cal-title { font-family:'Bebas Neue',sans-serif; font-size:1.2rem; letter-spacing:0.04em; text-transform:capitalize; }
    .sc-cal-nav { background:none; border:2px solid var(--coffee); width:30px; height:30px; cursor:pointer; font-size:1rem; line-height:1; }
    .sc-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:3px; }
    .sc-cal-dow { font-family:'DM Mono',monospace; font-size:0.62rem; text-align:center; opacity:0.5; padding:4px 0; text-transform:uppercase; }
    .sc-cal-cell { position:relative; aspect-ratio:1; border:2px solid var(--sc-line); background:white; cursor:pointer; font-family:'Outfit',sans-serif; font-size:0.85rem; display:flex; align-items:center; justify-content:center; color:var(--coffee); }
    .sc-cal-cell:hover { border-color:var(--coffee); background:var(--cream); }
    .sc-cal-cell.other { opacity:0.3; }
    .sc-cal-cell.past { opacity:0.4; }
    .sc-cal-cell.has { background:var(--cherry); color:var(--cream); border-color:var(--coffee); font-weight:700; }
    .sc-cal-badge { position:absolute; top:-6px; right:-6px; background:var(--forest); color:var(--cream); border:2px solid var(--coffee); border-radius:50%; width:18px; height:18px; font-size:0.62rem; display:flex; align-items:center; justify-content:center; font-family:'DM Mono',monospace; }
    .sc-cal-hint { font-family:'DM Mono',monospace; font-size:0.66rem; opacity:0.55; margin-top:10px; text-align:center; }

    .sc-list-t { font-family:'DM Mono',monospace; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; margin:0 0 10px; }
    .sc-empty { border:2px dashed var(--sc-line); padding:24px; text-align:center; font-size:0.9rem; opacity:0.6; }
    .sc-row { border:var(--border); background:white; box-shadow:var(--shadow-sm); margin-bottom:12px; }
    .sc-row-main { display:flex; align-items:center; gap:12px; padding:12px 14px; flex-wrap:wrap; }
    .sc-row-num { flex:0 0 auto; width:26px; height:26px; background:var(--cherry); color:var(--cream); border:2px solid var(--coffee); display:flex; align-items:center; justify-content:center; font-family:'Bebas Neue',sans-serif; font-size:0.95rem; }
    .sc-row-date { flex:1 1 130px; font-family:'DM Mono',monospace; font-weight:600; font-size:0.86rem; text-transform:capitalize; }
    .sc-row-date input[type=date]{ padding:6px 8px; border:2px solid var(--coffee); font-family:'Outfit',sans-serif; font-size:0.86rem; }
    .sc-row-times { display:flex; align-items:center; gap:6px; }
    .sc-row-times select { padding:6px 8px; border:2px solid var(--coffee); background:white; font-family:'Outfit',sans-serif; font-size:0.86rem; }
    .sc-row-times span { font-size:0.75rem; opacity:0.6; }
    .sc-row-actions { display:flex; gap:6px; margin-left:auto; }
    .sc-icon { border:2px solid var(--coffee); background:white; cursor:pointer; padding:5px 9px; font-size:0.82rem; }
    .sc-icon:hover { background:var(--wheat); }
    .sc-icon.on { background:var(--coffee); color:var(--cream); }
    .sc-icon.del:hover { background:var(--cherry); color:var(--cream); }
    .sc-row-over { display:none; padding:0 14px 14px; border-top:2px solid var(--sc-line); }
    .sc-row.open .sc-row-over { display:grid; grid-template-columns:1fr 1fr; gap:14px; padding-top:14px; }
    @media (max-width:520px){ .sc-row.open .sc-row-over { grid-template-columns:1fr; } }
    .sc-over-full { grid-column:1/-1; }
    .sc-inherit { font-family:'DM Mono',monospace; font-size:0.64rem; opacity:0.5; }
    .sc-row-date .sc-inherit { text-transform:none; }
    .sc-icon.addslot { font-family:'DM Mono',monospace; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; }

    .sc-anims { display:flex; flex-wrap:wrap; gap:7px; }
    .sc-anims label { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border:2px solid var(--coffee); background:var(--cream); cursor:pointer; font-family:'Outfit',sans-serif; font-size:0.85rem; }
    .sc-anims input { width:auto; }
    .autocomplete-wrapper { position:relative; }
    .autocomplete-dropdown { display:none; position:absolute; top:100%; left:0; right:0; z-index:50; background:var(--cream); border:var(--border); border-top:none; max-height:200px; overflow-y:auto; box-shadow:var(--shadow-sm); }
    .autocomplete-item { padding:8px 11px; cursor:pointer; font-family:'DM Mono',monospace; font-size:0.8rem; border-bottom:1px solid var(--sc-line); }
    .autocomplete-item:hover { background:var(--wheat); }
    .sc-btn { display:inline-flex; align-items:center; gap:8px; border:var(--border); box-shadow:var(--shadow-sm); font-family:'Bebas Neue',sans-serif; font-size:1.05rem; letter-spacing:0.05em; padding:8px 16px; cursor:pointer; }
    .sc-btn:hover { transform:translate(-1px,-1px); }
    .sc-btn-green { background:var(--forest); color:var(--cream); }
</style>

{{-- === Markup === --}}
<div class="sc" id="scEditor">
    <p class="sc-title">Dates & séances</p>
    <p class="sc-sub">La capacité et le prix (ci-dessus) valent pour l'ensemble de la formation.</p>

    <div class="sc-modes" id="scModes">
        <label class="sc-mode" data-mode="single">
            <input type="radio" name="sc_mode" value="single">
            <span><span class="sc-mode-t">Une seule séance</span><br><span class="sc-mode-d">Un atelier, une conférence à une date.</span></span>
        </label>
        <label class="sc-mode" data-mode="multi">
            <input type="radio" name="sc_mode" value="multi">
            <span><span class="sc-mode-t">Plusieurs séances</span><br><span class="sc-mode-d">Une formation sur plusieurs jours/horaires.</span></span>
        </label>
        <label class="sc-mode" data-mode="recurring">
            <input type="radio" name="sc_mode" value="recurring">
            <span><span class="sc-mode-t">Récurrent</span><br><span class="sc-mode-d">Se répète (chaque semaine, quinzaine…).</span></span>
        </label>
    </div>

    {{-- Réglages communs --}}
    <div class="sc-defaults" id="scDefaults" style="display:none;">
        <p class="sc-defaults-t">⚙ Réglages communs — repris pour chaque nouvelle séance</p>
        <div class="sc-defaults-grid">
            <div class="sc-field" style="grid-column:1/-1;">
                <label>Créneaux horaires par défaut — ajoutez-en un 2ᵉ pour matin + après-midi (pause déj)</label>
                <div id="defRanges"></div>
                <button type="button" class="sc-icon" id="addRange" style="margin-top:8px; width:max-content;">+ Ajouter un créneau (ex. après-midi)</button>
            </div>
            <div class="sc-field">
                <label>Format</label>
                <select id="defFormat"><option value="presentiel">Présentiel</option><option value="distanciel">Distanciel</option></select>
            </div>
            <div class="sc-field">
                <label>Lieu (vide si distanciel)</label>
                <div class="autocomplete-wrapper"><input type="text" id="defLieu" autocomplete="off" placeholder="Adresse…"><div class="autocomplete-dropdown"></div></div>
            </div>
            <div class="sc-field sc-over-full" id="defAnimsWrap" style="grid-column:1/-1;">
                <label>Animateurs</label>
                <div class="sc-anims" id="defAnims"></div>
            </div>
        </div>
    </div>

    {{-- Générateur de récurrence --}}
    <div class="sc-rule" id="scRule" style="display:none;">
        <div class="rc-head">⟳ Créer des séances qui se répètent</div>
        <div class="rc-body">
            <div class="rc-line">
                <span class="rc-label">1ʳᵉ séance le</span>
                <div class="rc-control"><input type="date" id="recStart" data-ph="Choisir une date"></div>
            </div>
            <div class="rc-line">
                <span class="rc-label">Fréquence</span>
                <div class="rc-control">
                    <span class="rc-suffix">toutes les</span>
                    <input type="number" id="recEvery" min="1" value="1" class="rc-num">
                    <span class="rc-suffix">semaine(s)</span>
                </div>
            </div>
            <div class="rc-line">
                <span class="rc-label">Les jours</span>
                <div class="rc-control">
                    <div class="sc-days" id="recDays">
                        <div class="sc-day" data-d="1">L</div><div class="sc-day" data-d="2">M</div><div class="sc-day" data-d="3">M</div>
                        <div class="sc-day" data-d="4">J</div><div class="sc-day" data-d="5">V</div><div class="sc-day" data-d="6">S</div><div class="sc-day" data-d="0">D</div>
                    </div>
                    <span class="rc-hint">(vide = le jour de la 1ʳᵉ séance)</span>
                </div>
            </div>
            <div class="rc-line">
                <span class="rc-label">On s'arrête</span>
                <div class="rc-control rc-end">
                    <label class="rc-end-opt"><input type="radio" name="recEnd" value="count" checked> après <input type="number" id="recCount" min="1" max="60" value="6" class="rc-num"> dates</label>
                    <label class="rc-end-opt"><input type="radio" name="recEnd" value="until"> le <input type="date" id="recUntil" data-ph="jj/mm/aaaa"></label>
                </div>
            </div>
        </div>
        <div class="rc-foot">
            <button type="button" class="rc-gen" id="recGen">＋ Ajouter ces séances</button>
        </div>
    </div>

    {{-- Calendrier + liste --}}
    <div class="sc-cl" id="scWorkspace" style="display:none;">
        <div class="sc-cal" id="scCal">
            <div class="sc-cal-head">
                <button type="button" class="sc-cal-nav" id="calPrev">‹</button>
                <span class="sc-cal-title" id="calTitle"></span>
                <button type="button" class="sc-cal-nav" id="calNext">›</button>
            </div>
            <div class="sc-cal-grid" id="calGrid"></div>
            <p class="sc-cal-hint">Clique un jour pour y placer les créneaux par défaut.</p>
        </div>
        <div class="sc-list-wrap">
            <p class="sc-list-t" id="listTitle">Séances</p>
            <div id="scList"></div>
        </div>
    </div>

    {{-- Mode 1 séance (inline) --}}
    <div id="scSingle" style="display:none;"></div>

    {{-- Champs postés --}}
    <div id="scHidden"></div>
</div>

{{-- === Script === --}}
<script>
(function () {
    var ANIMATEURS = @json(collect($animateursList)->map(fn($a) => ['id' => (int)$a['id_utilisateur'], 'nom' => trim(($a['prenom'] ?? '').' '.($a['nom'] ?? ''))])->values());
    var INIT = @json($initSeances ?: []);

    var root = document.getElementById('scEditor');
    var state = {
        mode: INIT.length > 1 ? 'multi' : 'single',
        seances: [],           // {id,date,start,end,format,lieu,animateurs:[],custom,titre}
        defaults: { ranges:[{start:'09:00',end:'12:00'}], format:'presentiel', lieu:'', animateurs:[] },
        cal: null,             // {y,m}
        seq: 1,
    };

    function esc(s){ return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function pad(n){ return String(n).padStart(2,'0'); }

    // -- init séances depuis INIT --
    INIT.forEach(function (s) {
        var dd = (s.date_debut||'').slice(0,10);
        var st = (s.date_debut||'').slice(11,16) || '09:00';
        var en = (s.date_fin||'').slice(11,16) || '12:00';
        state.seances.push({
            id: state.seq++, date: dd, start: st, end: en,
            format: s.format||'presentiel', lieu: s.lieu||'',
            animateurs: (s.animateurs||[]).map(Number), custom: true, titre: s.titre||''
        });
    });
    if (!state.seances.length) {
        state.seances.push({ id: state.seq++, date:'', start:'09:00', end:'12:00', format:'presentiel', lieu:'', animateurs:[], custom:true, titre:'' });
    }
    // calendrier positionné sur la 1re séance datée ou le mois courant (via 1re séance / sinon vide)
    var firstDated = state.seances.find(function(s){ return s.date; });
    if (firstDated) { var d0 = new Date(firstDated.date+'T00:00'); state.cal = { y:d0.getFullYear(), m:d0.getMonth() }; }

    // -- options d'heures (06:00 -> 22:00, pas 15 min) --
    function timeOptions(sel){
        var out=''; for(var h=6;h<=22;h++){ for(var mn=0;mn<60;mn+=15){ var v=pad(h)+':'+pad(mn); out+='<option value="'+v+'"'+(v===sel?' selected':'')+'>'+v+'</option>'; } }
        return out;
    }

    function animChecks(name, selected){
        selected=(selected||[]).map(Number);
        if(!ANIMATEURS.length) return '<span class="sc-inherit">Aucun animateur disponible.</span>';
        return ANIMATEURS.map(function(a){
            return '<label><input type="checkbox" data-anim="'+name+'" value="'+a.id+'" '+(selected.indexOf(a.id)!==-1?'checked':'')+'> '+esc(a.nom)+'</label>';
        }).join('');
    }

    // ---- Autocomplétion adresse (BAN) ----
    function attachAutocomplete(input){
        var dd = input.parentNode.querySelector('.autocomplete-dropdown'); if(!dd) return;
        var timer=null;
        function close(){ dd.style.display='none'; dd.innerHTML=''; }
        input.addEventListener('input', function(){
            var v=input.value.trim(); clearTimeout(timer);
            if(v.length<3){ close(); return; }
            timer=setTimeout(function(){
                fetch('https://data.geopf.fr/geocodage/search/?q='+encodeURIComponent(v)+'&limit=5')
                .then(function(r){ if(!r.ok) throw 0; return r.json(); })
                .then(function(d){
                    var f=(d&&d.features)||[]; if(!f.length){ close(); return; }
                    dd.innerHTML=''; f.forEach(function(ft){
                        var it=document.createElement('div'); it.className='autocomplete-item'; it.textContent=ft.properties.label;
                        it.addEventListener('mousedown', function(e){ e.preventDefault(); input.value=ft.properties.label; input.dispatchEvent(new Event('change')); close(); });
                        dd.appendChild(it);
                    });
                    dd.style.display='block';
                }).catch(close);
            },300);
        });
        input.addEventListener('blur', function(){ setTimeout(close,150); });
    }

    // Bascule une séance en « réglages propres » en copiant les valeurs communes courantes.
    function makeCustom(s){
        if(s.custom) return;
        s.format = state.defaults.format;
        s.lieu = state.defaults.lieu;
        s.animateurs = state.defaults.animateurs.slice();
        s.custom = true;
    }

    // ======= SÉRIALISATION (champs postés) =======
    function serialize(){
        var host=document.getElementById('scHidden'); host.innerHTML='';
        var list = state.mode==='single'
            ? state.seances.slice(0,1)
            : state.seances.slice().sort(function(a,b){ return (a.date+a.start).localeCompare(b.date+b.start); });
        list.forEach(function(s,i){
            if(!s.date) return;
            var own = s.custom || state.mode==='single';
            var fmt = own ? s.format : state.defaults.format;
            var lieu = own ? s.lieu : state.defaults.lieu;
            var anims = own ? s.animateurs : state.defaults.animateurs;
            function add(n,v){ var el=document.createElement('input'); el.type='hidden'; el.name='seances['+i+']['+n+']'; el.value=v; host.appendChild(el); }
            add('titre', s.titre||'');
            add('format', fmt);
            add('lieu', lieu||'');
            add('date_debut', s.date+'T'+s.start);
            add('date_fin', s.date+'T'+s.end);
            (anims||[]).forEach(function(id){ var el=document.createElement('input'); el.type='hidden'; el.name='seances['+i+'][animateurs][]'; el.value=id; host.appendChild(el); });
        });
    }

    // ======= CALENDRIER =======
    function renderCal(){
        var grid=document.getElementById('calGrid'); if(!state.cal){ var now=new Date(); state.cal={y:now.getFullYear(),m:now.getMonth()}; }
        var y=state.cal.y, m=state.cal.m;
        document.getElementById('calTitle').textContent = new Date(y,m,1).toLocaleDateString('fr-FR',{month:'long',year:'numeric'});
        var counts={}; state.seances.forEach(function(s){ if(s.date){ counts[s.date]=(counts[s.date]||0)+1; } });
        var first=new Date(y,m,1); var startDow=(first.getDay()+6)%7; // lundi=0
        var daysInMonth=new Date(y,m+1,0).getDate();
        var today=new Date(); today.setHours(0,0,0,0);
        var html=['lun','mar','mer','jeu','ven','sam','dim'].map(function(d){return '<div class="sc-cal-dow">'+d+'</div>';}).join('');
        for(var i=0;i<startDow;i++){ html+='<div class="sc-cal-cell other"></div>'; }
        for(var day=1;day<=daysInMonth;day++){
            var ds=y+'-'+pad(m+1)+'-'+pad(day);
            var cd=new Date(y,m,day);
            var cls='sc-cal-cell'; if(counts[ds]) cls+=' has'; else if(cd<today) cls+=' past';
            var badge=counts[ds]&&counts[ds]>1 ? '<span class="sc-cal-badge">'+counts[ds]+'</span>' : '';
            html+='<div class="'+cls+'" data-date="'+ds+'">'+day+badge+'</div>';
        }
        grid.innerHTML=html;
    }

    // ======= LISTE =======
    function renderList(){
        var host=document.getElementById('scList');
        var list = state.seances.slice().sort(function(a,b){ return (a.date+a.start).localeCompare(b.date+b.start); });
        document.getElementById('listTitle').textContent = 'Séances ('+list.filter(function(s){return s.date;}).length+')';
        if(!list.length){ host.innerHTML='<div class="sc-empty">Clique un jour dans le calendrier pour ajouter une séance.</div>'; return; }
        host.innerHTML = list.map(function(s,i){ return rowHtml(s,i+1,false,true); }).join('');
        wireRows(host);
    }

    // ======= MODE 1 SÉANCE (inline) =======
    function renderSingle(){
        var host=document.getElementById('scSingle');
        var s=state.seances[0];
        host.innerHTML = rowHtml(s,1,true,false);
        wireRows(host);
    }

    function rowHtml(s, num, expanded, removable){
        var open = expanded ? ' open' : '';
        var own = s.custom || expanded;   // en mode 1 séance, toujours ses propres valeurs
        var overFmt = own ? s.format : state.defaults.format;
        var overLieu = own ? s.lieu : state.defaults.lieu;
        var overAnims = own ? s.animateurs : state.defaults.animateurs;
        var dateLabel = s.date ? new Date(s.date+'T00:00').toLocaleDateString('fr-FR',{weekday:'short',day:'numeric',month:'short'}) : '';
        var badge = (!expanded && s.custom) ? '<span class="sc-inherit"> · réglages propres</span>' : (!expanded ? '<span class="sc-inherit"> · réglages communs</span>' : '');
        return '' +
        '<div class="sc-row'+open+'" data-id="'+s.id+'">' +
          '<div class="sc-row-main">' +
            '<span class="sc-row-num">'+num+'</span>' +
            '<span class="sc-row-date">' +
              (expanded
                ? '<input type="date" data-f="date" value="'+esc(s.date)+'">'
                : ((dateLabel || '<span class="sc-inherit">(sans date)</span>')+badge)) +
            '</span>' +
            '<span class="sc-row-times">' +
              '<select data-f="start">'+timeOptions(s.start)+'</select><span>→</span><select data-f="end">'+timeOptions(s.end)+'</select>' +
            '</span>' +
            '<span class="sc-row-actions">' +
              (expanded ? '' : '<button type="button" class="sc-icon addslot" title="Ajouter un créneau le même jour (ex. après-midi)">＋ créneau</button>') +
              (expanded ? '' : '<button type="button" class="sc-icon tog'+(s.custom?' on':'')+'" title="Titre, lieu et animateurs de cette séance">⚙</button>') +
              (removable ? '<button type="button" class="sc-icon del" title="Supprimer">🗑</button>' : '') +
            '</span>' +
          '</div>' +
          '<div class="sc-row-over">' +
            '<div class="sc-field sc-over-full"><label>Titre de la séance (optionnel)</label><input type="text" data-f="titre" maxlength="200" placeholder="ex. Module 1 : les bases" value="'+esc(s.titre)+'"></div>' +
            '<div class="sc-field"><label>Format</label><select data-f="format"><option value="presentiel"'+(overFmt==='presentiel'?' selected':'')+'>Présentiel</option><option value="distanciel"'+(overFmt==='distanciel'?' selected':'')+'>Distanciel</option></select></div>' +
            '<div class="sc-field"><label>Lieu</label><div class="autocomplete-wrapper"><input type="text" data-f="lieu" autocomplete="off" placeholder="Adresse…" value="'+esc(overLieu)+'"><div class="autocomplete-dropdown"></div></div></div>' +
            '<div class="sc-field sc-over-full"><label>Animateurs</label><div class="sc-anims" data-anims-row>'+animChecks('row-'+s.id, overAnims)+'</div></div>' +
            (expanded ? '' : '<div class="sc-over-full"><button type="button" class="sc-icon reset" '+(s.custom?'':'style="display:none;"')+'>↺ Revenir aux réglages communs</button></div>') +
          '</div>' +
        '</div>';
    }

    function findSeance(id){ return state.seances.find(function(s){ return s.id==id; }); }

    function wireRows(host){
        var single = state.mode==='single';
        host.querySelectorAll('.sc-row').forEach(function(row){
            var id=row.getAttribute('data-id'); var s=findSeance(id); if(!s) return;
            var tog=row.querySelector('.sc-icon.tog');
            var reset=row.querySelector('.sc-icon.reset');
            // marque la séance comme personnalisée dès qu'on touche un réglage propre (format/lieu/animateurs)
            function touchCustom(){
                if(!s.custom){ makeCustom(s); if(tog) tog.classList.add('on'); if(reset) reset.style.display=''; }
            }
            row.querySelectorAll('[data-f]').forEach(function(el){
                var f=el.getAttribute('data-f');
                if(el.type==='checkbox') return;
                var evt = (el.tagName==='SELECT'||el.type==='date') ? 'change' : 'input';
                el.addEventListener(evt, function(){
                    if(f==='date'){ s.date=el.value; serialize(); return; }  // champ date = mode 1 séance uniquement
                    if(f==='start'||f==='end'){ s[f]=el.value; serialize(); return; }
                    if(f==='titre'){ s.titre=el.value; serialize(); return; }
                    // format / lieu = réglages propres
                    touchCustom(); s[f]=el.value; serialize();
                });
            });
            row.querySelectorAll('[data-anims-row] input[type=checkbox]').forEach(function(cb){
                cb.addEventListener('change', function(){
                    touchCustom();
                    var v=Number(cb.value);
                    s.animateurs = s.animateurs || [];
                    if(cb.checked){ if(s.animateurs.indexOf(v)===-1) s.animateurs.push(v); }
                    else { s.animateurs = s.animateurs.filter(function(x){return x!==v;}); }
                    serialize();
                });
            });
            var addslot=row.querySelector('.sc-icon.addslot');
            if(addslot){ addslot.addEventListener('click', function(){
                if(!s.date){ alert('Choisis d\'abord une date pour cette séance.'); return; }
                var r = state.defaults.ranges[1] || {start:'13:00',end:'17:00'};
                addSeanceOne(s.date, r.start, r.end);
                renderCal(); renderList(); serialize();
            }); }
            if(tog){ tog.addEventListener('click', function(){ row.classList.toggle('open'); }); }
            if(reset){ reset.addEventListener('click', function(){ s.custom=false; renderList(); serialize(); }); }
            var del=row.querySelector('.sc-icon.del');
            if(del){ del.addEventListener('click', function(){
                if(state.seances.length<=1){ alert('Il faut au moins une séance.'); return; }
                state.seances = state.seances.filter(function(x){ return x.id!=id; });
                renderCal(); renderList(); serialize();
            }); }
            var lieuInput=row.querySelector('[data-f=lieu]'); if(lieuInput) attachAutocomplete(lieuInput);
        });
    }

    // ======= ORCHESTRATION =======
    function applyModeVisibility(){
        var multi = state.mode!=='single';
        document.getElementById('scDefaults').style.display = multi ? '' : 'none';
        document.getElementById('scWorkspace').style.display = multi ? '' : 'none';
        document.getElementById('scRule').style.display = state.mode==='recurring' ? '' : 'none';
        document.getElementById('scSingle').style.display = multi ? 'none' : '';
        root.querySelectorAll('.sc-mode').forEach(function(m){ m.classList.toggle('active', m.getAttribute('data-mode')===state.mode); });
        var radio = root.querySelector('.sc-mode[data-mode="'+state.mode+'"] input'); if(radio) radio.checked=true;
    }

    function refresh(){
        applyModeVisibility();
        if(state.mode==='single'){ renderSingle(); }
        else { renderCal(); renderList(); }
        serialize();
    }

    function addSeanceOne(date, start, end){
        state.seances.push({ id:state.seq++, date:date, start:start, end:end,
            format:state.defaults.format, lieu:state.defaults.lieu, animateurs:state.defaults.animateurs.slice(), custom:false, titre:'' });
    }
    // Ajoute un jour = un créneau par plage horaire par défaut (matin, après-midi…).
    function addDay(date){
        var rgs = state.defaults.ranges.length ? state.defaults.ranges : [{start:'09:00',end:'12:00'}];
        rgs.forEach(function(r){ addSeanceOne(date, r.start, r.end); });
    }

    // -- Réglages communs : champs --
    function renderRanges(){
        var host=document.getElementById('defRanges');
        host.innerHTML = state.defaults.ranges.map(function(r,i){
            return '<div class="sc-time" data-ri="'+i+'" style="margin-bottom:6px;">'+
                   '<select class="rgStart">'+timeOptions(r.start)+'</select><span>→</span><select class="rgEnd">'+timeOptions(r.end)+'</select>'+
                   (state.defaults.ranges.length>1 ? '<button type="button" class="sc-icon rgDel" title="Retirer ce créneau">✕</button>' : '')+
                   '</div>';
        }).join('');
        host.querySelectorAll('.sc-time').forEach(function(row){
            var i=+row.getAttribute('data-ri');
            row.querySelector('.rgStart').addEventListener('change', function(){ state.defaults.ranges[i].start=this.value; });
            row.querySelector('.rgEnd').addEventListener('change', function(){ state.defaults.ranges[i].end=this.value; });
            var del=row.querySelector('.rgDel');
            if(del) del.addEventListener('click', function(){ state.defaults.ranges.splice(i,1); renderRanges(); });
        });
    }

    function initDefaults(){
        renderRanges();
        document.getElementById('addRange').addEventListener('click', function(){
            state.defaults.ranges.push({start:'13:00',end:'17:00'}); renderRanges();
        });
        document.getElementById('defAnims').innerHTML=animChecks('def', state.defaults.animateurs);
        var dF=document.getElementById('defFormat'), dL=document.getElementById('defLieu');
        dF.value=state.defaults.format; dL.value=state.defaults.lieu;
        dF.addEventListener('change', function(){ state.defaults.format=dF.value; renderList(); serialize(); });
        dL.addEventListener('change', function(){ state.defaults.lieu=dL.value; renderList(); serialize(); });
        dL.addEventListener('input', function(){ state.defaults.lieu=dL.value; });
        attachAutocomplete(dL);
        document.getElementById('defAnims').querySelectorAll('input[type=checkbox]').forEach(function(cb){
            cb.addEventListener('change', function(){
                var v=Number(cb.value);
                if(cb.checked){ if(state.defaults.animateurs.indexOf(v)===-1) state.defaults.animateurs.push(v); }
                else { state.defaults.animateurs=state.defaults.animateurs.filter(function(x){return x!==v;}); }
                renderList(); serialize();
            });
        });
    }

    // -- Modes --
    root.querySelectorAll('.sc-mode input').forEach(function(r){
        r.addEventListener('change', function(){
            var nm=r.value;
            if(nm==='single' && state.seances.filter(function(s){return s.date;}).length>1){
                if(!confirm('Passer en « une seule séance » ne gardera que la première. Continuer ?')){ applyModeVisibility(); return; }
                state.seances=[state.seances[0]];
            }
            state.mode=nm; refresh();
        });
    });

    // -- Calendrier nav + clic --
    document.getElementById('calPrev').addEventListener('click', function(){ state.cal.m--; if(state.cal.m<0){state.cal.m=11;state.cal.y--;} renderCal(); });
    document.getElementById('calNext').addEventListener('click', function(){ state.cal.m++; if(state.cal.m>11){state.cal.m=0;state.cal.y++;} renderCal(); });
    document.getElementById('calGrid').addEventListener('click', function(e){
        var cell=e.target.closest('.sc-cal-cell'); if(!cell||!cell.getAttribute('data-date')) return;
        addDay(cell.getAttribute('data-date')); renderCal(); renderList(); serialize();
    });

    // -- Récurrence --
    document.getElementById('recDays').addEventListener('click', function(e){
        var d=e.target.closest('.sc-day'); if(d) d.classList.toggle('on');
    });
    document.getElementById('recGen').addEventListener('click', function(){
        var start=document.getElementById('recStart').value; if(!start){ alert('Indique la date de départ.'); return; }
        var every=parseInt(document.getElementById('recEvery').value,10)||1;
        var days=[].slice.call(document.querySelectorAll('#recDays .sc-day.on')).map(function(d){return parseInt(d.getAttribute('data-d'),10);});
        var base=new Date(start+'T00:00'); if(isNaN(base)){ alert('Date invalide.'); return; }
        if(!days.length) days=[base.getDay()]; // défaut : le jour de la date de départ
        var endMode=(document.querySelector('input[name=recEnd]:checked')||{}).value||'count';
        var count=parseInt(document.getElementById('recCount').value,10)||1;
        var until=document.getElementById('recUntil').value ? new Date(document.getElementById('recUntil').value+'T23:59') : null;
        if(endMode==='until' && !until){ alert('Indique la date de fin.'); return; }
        // La génération REMPLACE les séances existantes (comme Eventbrite).
        var hadDated=state.seances.filter(function(s){return s.date;}).length;
        if(hadDated && !confirm('Générer remplacera les '+hadDated+' séance(s) déjà présente(s). Continuer ?')) return;
        state.seances=[];
        var added=0, safety=0;
        // parcours semaine par semaine (pas = every), on prend les jours cochés dans chaque semaine
        var weekStart=new Date(base); weekStart.setDate(weekStart.getDate()-((base.getDay()+6)%7)); // lundi de la semaine de base
        while(safety<400){
            safety++;
            for(var od=0; od<7; od++){
                var day=new Date(weekStart); day.setDate(weekStart.getDate()+od);
                if(day<base) continue;
                var dow=day.getDay();
                if(days.indexOf(dow)===-1) continue;
                if(endMode==='until' && day>until){ safety=999; break; }
                if(endMode==='count' && added>=count){ safety=999; break; }
                addDay(day.getFullYear()+'-'+pad(day.getMonth()+1)+'-'+pad(day.getDate()));
                added++;
            }
            if(safety>=999) break;
            weekStart.setDate(weekStart.getDate()+7*every);
        }
        if(!state.seances.length){ addSeanceOne('','09:00','12:00'); }  // garde-fou : au moins une ligne
        // recentrer le calendrier sur la 1re séance générée
        var fd=state.seances.find(function(s){return s.date;}); if(fd){ var dd=new Date(fd.date+'T00:00'); state.cal={y:dd.getFullYear(),m:dd.getMonth()}; }
        renderCal(); renderList(); serialize();
    });

    // ---- démarrage ----
    initDefaults();
    refresh();
})();
</script>
