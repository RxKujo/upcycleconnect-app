{{-- Sélecteur date/heure custom : remplace l'input natif partout (auto-enhance).
     Value ISO conservée. Opt-out par champ : data-no-uc. --}}
{{-- === Styles === --}}
<style>
    .uc-dp-field {
        display:inline-flex; align-items:center; gap:10px; justify-content:space-between;
        border:2px solid var(--coffee, #120309); background:#fff;
        font-family:'Outfit', sans-serif; font-size:0.95rem; color:var(--coffee, #120309);
        padding:10px 12px; cursor:pointer; user-select:none; min-width:150px;
    }
    .uc-dp-field:hover { background:var(--cream, #F5F0E1); }
    .uc-dp-field.uc-open { box-shadow:0 0 0 3px var(--cherry, #A4243B); }
    .uc-dp-field.uc-empty .uc-dp-val { opacity:0.45; text-transform:none; }
    .uc-dp-val { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-transform:capitalize; }
    .uc-dp-ico { flex:0 0 auto; width:16px; height:16px; opacity:0.6; }

    .uc-dp-pop {
        position:absolute; z-index:100000; width:300px;
        background:var(--cream, #F5F0E1); border:3px solid var(--coffee, #120309);
        box-shadow:6px 6px 0 var(--coffee, #120309); padding:14px;
        font-family:'Outfit', sans-serif; color:var(--coffee, #120309);
    }
    .uc-dp-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .uc-dp-title { font-family:'Bebas Neue', sans-serif; font-size:1.2rem; letter-spacing:0.04em; text-transform:capitalize; }
    .uc-dp-nav { background:#fff; border:2px solid var(--coffee, #120309); width:30px; height:30px; cursor:pointer; font-size:1rem; line-height:1; color:var(--coffee,#120309); }
    .uc-dp-nav:hover { background:var(--wheat, #E9D9A6); }
    .uc-dp-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:3px; }
    .uc-dp-dow { font-family:'DM Mono', monospace; font-size:0.6rem; text-align:center; opacity:0.5; padding:3px 0; text-transform:uppercase; }
    .uc-dp-cell {
        aspect-ratio:1; border:2px solid transparent; background:transparent; cursor:pointer;
        font-size:0.85rem; display:flex; align-items:center; justify-content:center; color:var(--coffee,#120309);
    }
    .uc-dp-cell:hover { border-color:var(--coffee, #120309); background:#fff; }
    .uc-dp-cell.uc-empty { visibility:hidden; cursor:default; }
    .uc-dp-cell.uc-today { border-color:var(--coffee, #120309); font-weight:700; }
    .uc-dp-cell.uc-sel { background:var(--cherry, #A4243B); color:var(--cream, #F5F0E1); border-color:var(--coffee, #120309); font-weight:700; }
    .uc-dp-cell.uc-dis { opacity:0.3; cursor:not-allowed; pointer-events:none; }
    .uc-dp-time { display:flex; align-items:center; gap:8px; margin-top:12px; padding-top:12px; border-top:2px solid rgba(18,3,9,0.12); }
    .uc-dp-time label { font-family:'DM Mono', monospace; font-size:0.66rem; text-transform:uppercase; letter-spacing:0.06em; opacity:0.6; }
    .uc-dp-time select { border:2px solid var(--coffee,#120309); background:#fff; padding:6px 8px; font-family:'Outfit',sans-serif; font-size:0.9rem; color:var(--coffee,#120309); }
    .uc-dp-foot { display:flex; justify-content:space-between; margin-top:12px; }
    .uc-dp-link { background:none; border:none; cursor:pointer; font-family:'DM Mono', monospace; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--cherry, #A4243B); padding:4px; }
    .uc-dp-link:hover { text-decoration:underline; }
</style>

{{-- === Script : auto-enhance === --}}
<script>
(function () {
    if (window.__ucDatePicker) return; window.__ucDatePicker = true;

    var MONTHS = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    var DOW = ['lun','mar','mer','jeu','ven','sam','dim'];
    var CAL_SVG = '<svg class="uc-dp-ico" viewBox="0 0 16 16" fill="currentColor"><path d="M11 1a1 1 0 0 1 1 1h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h1a1 1 0 0 1 2 0h4a1 1 0 0 1 1-1zM3 5v8h10V5H3z"/></svg>';
    function pad(n){ return String(n).padStart(2,'0'); }

    function parseVal(v, isDT){
        if(!v) return null;
        var m = isDT ? v.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/) : v.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if(!m) return null;
        return { y:+m[1], mo:+m[2]-1, d:+m[3], hh:isDT?+m[4]:9, mm:isDT?+m[5]:0 };
    }
    function toVal(o, isDT){
        var d = o.y+'-'+pad(o.mo+1)+'-'+pad(o.d);
        return isDT ? d+'T'+pad(o.hh)+':'+pad(o.mm) : d;
    }
    function fmtDisplay(o, isDT){
        var base = new Date(o.y,o.mo,o.d).toLocaleDateString('fr-FR',{weekday:'short',day:'numeric',month:'short',year:'numeric'});
        return isDT ? base+' · '+pad(o.hh)+'h'+pad(o.mm) : base;
    }

    var current = null; // { input, field, pop, isDT, view:{y,m}, sel, min, max }

    function closePop(){
        if(!current) return;
        if(current.pop && current.pop.parentNode) current.pop.parentNode.removeChild(current.pop);
        current.field.classList.remove('uc-open');
        document.removeEventListener('mousedown', onDocDown, true);
        document.removeEventListener('keydown', onKey, true);
        window.removeEventListener('resize', reposition);
        window.removeEventListener('scroll', reposition, true);
        current = null;
    }
    function onDocDown(e){ if(current && !current.pop.contains(e.target) && e.target!==current.field && !current.field.contains(e.target)) closePop(); }
    function onKey(e){ if(e.key==='Escape') closePop(); }
    function reposition(){
        if(!current) return;
        var r = current.field.getBoundingClientRect();
        var pop = current.pop;
        var top = r.bottom + window.scrollY + 4;
        var left = r.left + window.scrollX;
        var maxLeft = window.scrollX + document.documentElement.clientWidth - pop.offsetWidth - 8;
        if(left > maxLeft) left = Math.max(window.scrollX + 8, maxLeft);
        pop.style.top = top+'px'; pop.style.left = left+'px';
    }

    function dayDisabled(o){
        var t = new Date(o.y,o.mo,o.d).getTime();
        if(current.min){ var mn=current.min; if(t < new Date(mn.y,mn.mo,mn.d).getTime()) return true; }
        if(current.max){ var mx=current.max; if(t > new Date(mx.y,mx.mo,mx.d).getTime()) return true; }
        return false;
    }

    function renderCal(){
        var pop = current.pop, v = current.view;
        var today = new Date(); today.setHours(0,0,0,0);
        var first = new Date(v.y, v.m, 1); var startDow = (first.getDay()+6)%7;
        var dim = new Date(v.y, v.m+1, 0).getDate();
        var grid = pop.querySelector('.uc-dp-grid');
        var html = DOW.map(function(d){ return '<div class="uc-dp-dow">'+d+'</div>'; }).join('');
        for(var i=0;i<startDow;i++) html += '<div class="uc-dp-cell uc-empty"></div>';
        for(var day=1; day<=dim; day++){
            var o = { y:v.y, mo:v.m, d:day, hh:(current.sel?current.sel.hh:9), mm:(current.sel?current.sel.mm:0) };
            var cls = 'uc-dp-cell';
            var cd = new Date(v.y,v.m,day);
            if(current.sel && current.sel.y===v.y && current.sel.mo===v.m && current.sel.d===day) cls+=' uc-sel';
            else if(cd.getTime()===today.getTime()) cls+=' uc-today';
            if(dayDisabled(o)) cls+=' uc-dis';
            html += '<div class="'+cls+'" data-day="'+day+'">'+day+'</div>';
        }
        grid.innerHTML = html;
        pop.querySelector('.uc-dp-title').textContent = MONTHS[v.m]+' '+v.y;
    }

    function commit(){
        if(!current.sel){ current.input.value=''; }
        else { current.input.value = toVal(current.sel, current.isDT); }
        current.input.dispatchEvent(new Event('input', {bubbles:true}));
        current.input.dispatchEvent(new Event('change', {bubbles:true}));
        updateField(current.input, current.field, current.isDT);
    }

    function openPop(input, field, isDT){
        if(current && current.input===input){ closePop(); return; }
        closePop();
        var sel = parseVal(input.value, isDT);
        var view = sel ? { y:sel.y, m:sel.mo } : (function(){ var n=new Date(); return { y:n.getFullYear(), m:n.getMonth() }; })();
        var pop = document.createElement('div'); pop.className='uc-dp-pop';
        var timeHtml = '';
        if(isDT){
            var hOpts='', mOpts='';
            for(var h=0;h<24;h++) hOpts+='<option value="'+h+'">'+pad(h)+'</option>';
            for(var mi=0;mi<60;mi+=5) mOpts+='<option value="'+mi+'">'+pad(mi)+'</option>';
            timeHtml = '<div class="uc-dp-time"><label>Heure</label><select class="uc-dp-h">'+hOpts+'</select><span>:</span><select class="uc-dp-m">'+mOpts+'</select></div>';
        }
        pop.innerHTML =
            '<div class="uc-dp-head">'+
              '<button type="button" class="uc-dp-nav uc-dp-prev">‹</button>'+
              '<span class="uc-dp-title"></span>'+
              '<button type="button" class="uc-dp-nav uc-dp-next">›</button>'+
            '</div>'+
            '<div class="uc-dp-grid"></div>'+
            timeHtml+
            '<div class="uc-dp-foot"><button type="button" class="uc-dp-link uc-dp-clear">Effacer</button><button type="button" class="uc-dp-link uc-dp-today">Aujourd\'hui</button></div>';
        document.body.appendChild(pop);

        current = { input:input, field:field, pop:pop, isDT:isDT, view:view, sel:sel,
            min:parseVal(input.getAttribute('min'), false), max:parseVal(input.getAttribute('max'), false) };
        field.classList.add('uc-open');

        if(isDT){
            var hSel=pop.querySelector('.uc-dp-h'), mSel=pop.querySelector('.uc-dp-m');
            hSel.value = String(sel?sel.hh:9); mSel.value = String(sel?sel.mm:0);
            function applyTime(){ if(!current.sel){ current.sel={ y:current.view.y, mo:current.view.m, d:(new Date()).getDate() }; } current.sel.hh=+hSel.value; current.sel.mm=+mSel.value; commit(); }
            hSel.addEventListener('change', applyTime); mSel.addEventListener('change', applyTime);
        }
        pop.querySelector('.uc-dp-prev').addEventListener('click', function(){ current.view.m--; if(current.view.m<0){current.view.m=11;current.view.y--;} renderCal(); });
        pop.querySelector('.uc-dp-next').addEventListener('click', function(){ current.view.m++; if(current.view.m>11){current.view.m=0;current.view.y++;} renderCal(); });
        pop.querySelector('.uc-dp-grid').addEventListener('click', function(e){
            var c=e.target.closest('.uc-dp-cell'); if(!c||!c.getAttribute('data-day')||c.classList.contains('uc-dis')) return;
            var day=+c.getAttribute('data-day');
            var hh = isDT ? (current.sel?current.sel.hh:+pop.querySelector('.uc-dp-h').value) : 9;
            var mm = isDT ? (current.sel?current.sel.mm:+pop.querySelector('.uc-dp-m').value) : 0;
            current.sel = { y:current.view.y, mo:current.view.m, d:day, hh:hh, mm:mm };
            renderCal(); commit();
            if(!isDT) closePop();
        });
        pop.querySelector('.uc-dp-clear').addEventListener('click', function(){ current.sel=null; renderCal(); commit(); closePop(); });
        pop.querySelector('.uc-dp-today').addEventListener('click', function(){
            var n=new Date(); var o={ y:n.getFullYear(), mo:n.getMonth(), d:n.getDate(), hh:(current.sel?current.sel.hh:9), mm:(current.sel?current.sel.mm:0) };
            if(dayDisabled(o)) return;
            current.sel=o; current.view={y:o.y,m:o.mo}; renderCal(); commit(); if(!isDT) closePop();
        });

        renderCal(); reposition();
        document.addEventListener('mousedown', onDocDown, true);
        document.addEventListener('keydown', onKey, true);
        window.addEventListener('resize', reposition);
        window.addEventListener('scroll', reposition, true);
    }

    function updateField(input, field, isDT){
        var o = parseVal(input.value, isDT);
        var val = field.querySelector('.uc-dp-val');
        if(o){ val.textContent = fmtDisplay(o, isDT); field.classList.remove('uc-empty'); }
        else { val.textContent = input.getAttribute('data-ph') || 'Choisir une date'; field.classList.add('uc-empty'); }
    }

    function enhance(input){
        if(input.dataset.ucDp || input.hasAttribute('data-no-uc')) return;
        input.dataset.ucDp='1';
        var isDT = input.type==='datetime-local';
        var field = document.createElement('div');
        field.className = 'uc-dp-field' + (input.className ? ' '+input.className : '');
        field.classList.add('uc-dp-field');
        if(input.style.width) field.style.width = input.style.width;
        if(input.style.minWidth) field.style.minWidth = input.style.minWidth;
        if(input.hasAttribute('required')) field.setAttribute('data-req','1');
        field.innerHTML = '<span class="uc-dp-val"></span>'+CAL_SVG;
        // Masquer l'input natif. Si `required`, le garder focusable (1px transparent) pour
        // ne pas bloquer la validation native du formulaire (un display:none required n'est pas focusable).
        if(input.hasAttribute('required')){
            input.style.cssText += ';width:1px;height:1px;opacity:0;padding:0;border:0;margin:0;pointer-events:none;';
            input.tabIndex = -1;
        } else {
            input.style.display='none';
        }
        input.parentNode.insertBefore(field, input.nextSibling);
        updateField(input, field, isDT);
        field.addEventListener('click', function(){ openPop(input, field, isDT); });
        // si la value change par programme (ex. reset formulaire, JS externe), rafraîchir l'affichage
        input.addEventListener('change', function(){ if(!current || current.input!==input) updateField(input, field, isDT); });
    }

    function scan(root){
        (root||document).querySelectorAll('input[type=date],input[type=datetime-local]').forEach(enhance);
    }

    function boot(){ scan(); }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();

    // Champs ajoutés dynamiquement (ex. éditeur de séances)
    new MutationObserver(function(muts){
        muts.forEach(function(m){
            m.addedNodes.forEach(function(n){
                if(n.nodeType!==1) return;
                if(n.matches && n.matches('input[type=date],input[type=datetime-local]')) enhance(n);
                if(n.querySelectorAll) scan(n);
            });
        });
    }).observe(document.documentElement, { childList:true, subtree:true });
})();
</script>
