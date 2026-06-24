@extends('layouts.salarie')

@section('title', 'Mots bannis')

@section('styles')
<style>
.mb-add { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); padding: 24px 26px; margin-bottom: 28px; }
.mb-add h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin: 0 0 16px; letter-spacing: 0.03em; }
.mb-form { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.mb-form .grp { flex: 1; min-width: 220px; }
.mb-hint { font-family: 'DM Mono', monospace; font-size: 0.7rem; opacity: 0.5; margin: 14px 0 0; }

.filter-search { width: 100%; box-sizing: border-box; border: 3px solid var(--coffee); background: white; font-family: 'DM Mono', monospace; font-size: 0.9rem; padding: 11px 14px; outline: none; box-shadow: 3px 3px 0 rgba(18,3,9,0.1); margin-bottom: 24px; }
.filter-search:focus { border-color: var(--forest); }

.chips { display: flex; flex-wrap: wrap; gap: 12px; }
.chip { display: inline-flex; align-items: center; gap: 10px; background: white; border: 3px solid var(--coffee); box-shadow: 3px 3px 0 rgba(18,3,9,0.18); padding: 8px 8px 8px 16px; transition: transform 0.12s, box-shadow 0.12s; }
.chip:hover { transform: translate(-2px,-2px); box-shadow: 5px 5px 0 rgba(18,3,9,0.22); }
.chip .word { font-family: 'DM Mono', monospace; font-weight: 700; text-transform: lowercase; font-size: 0.95rem; }
.chip .date { font-family: 'DM Mono', monospace; font-size: 0.62rem; opacity: 0.45; }
.chip .del { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; background: var(--cream); color: var(--cherry); border: 2px solid var(--cherry); font-size: 0.9rem; padding: 0; transition: all 0.12s; }
.chip .del:hover { background: var(--cherry); color: var(--cream); }

.empty-box { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); text-align: center; padding: 50px 40px; }
.empty-box .big { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; opacity: 0.3; margin: 0; }
.empty-box .sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; opacity: 0.4; margin: 12px 0 0; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title">Mots bannis</h1>
    <span class="font-mono" style="font-size:0.85rem; opacity:0.6;">{{ count($mots) }} mot(s) filtré(s)</span>
</div>

<div class="mb-add">
    <h3>Bannir un mot ou une expression</h3>
    <form action="{{ route('salarie.forum.mots-bannis.add') }}" method="POST" class="mb-form">
        @csrf
        <div class="grp">
            <label class="form-label" for="mot">Mot ou expression</label>
            <input type="text" name="mot" id="mot" class="form-input" required maxlength="100" placeholder="ex : insulte, terme inapproprié…">
        </div>
        <button type="submit" class="btn-primary">Bannir</button>
    </form>
    <p class="mb-hint">Les messages du forum contenant ces termes seront automatiquement bloqués à la publication.</p>
</div>

@if(empty($mots))
    <div class="empty-box">
        <p class="big">Aucun mot banni</p>
        <p class="sub">Ajoute un premier terme à filtrer ci-dessus.</p>
    </div>
@else
    <input type="text" class="filter-search" id="mb-search" placeholder="Filtrer la liste…">
    <div class="chips" id="mb-chips">
        @foreach($mots as $m)
        <div class="chip" data-search="{{ strtolower($m['mot']) }}">
            <div>
                <div class="word">{{ $m['mot'] }}</div>
                <div class="date">depuis le {{ \Carbon\Carbon::parse($m['date_ajout'])->format('d/m/Y') }}</div>
            </div>
            <form action="{{ route('salarie.forum.mots-bannis.delete', $m['id_mot']) }}" method="POST" style="margin:0;" data-confirm="Retirer « {{ $m['mot'] }} » de la liste ?">
                @csrf @method('DELETE')
                <button type="submit" class="del" title="Retirer">✕</button>
            </form>
        </div>
        @endforeach
    </div>
    <div class="empty-box" id="mb-empty" style="display:none; margin-top:24px;">
        <p class="sub">Aucun mot ne correspond.</p>
    </div>
@endif
@endsection

@section('scripts')
<script>
(function () {
    const search = document.getElementById('mb-search');
    const wrap = document.getElementById('mb-chips');
    if (!search || !wrap) return;
    const chips = Array.from(wrap.querySelectorAll('.chip'));
    const empty = document.getElementById('mb-empty');

    search.addEventListener('input', () => {
        const q = search.value.toLowerCase().trim();
        let visible = 0;
        chips.forEach(c => {
            const show = !q || (c.dataset.search || '').includes(q);
            c.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (empty) empty.style.display = visible === 0 ? '' : 'none';
    });
})();
</script>
@endsection
