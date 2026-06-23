@extends('layouts.salarie')

@section('title', 'Boîte à idées')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Boîte à idées</h1>
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;opacity:0.5;margin:8px 0 0;">
            Partagez vos idées avec l'équipe — réservé aux salariés
        </p>
    </div>
    <button class="btn-primary" onclick="document.getElementById('modal-add').style.display='flex'">
        + Proposer une idée
    </button>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Idées partagées</div>
        <div class="stat-value">{{ count($idees) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Mes idées</div>
        <div class="stat-value" id="count-mes-idees">—</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Idées votées</div>
        <div class="stat-value" id="count-votes">{{ count(array_filter($idees, fn($i) => !empty($i['a_vote']))) }}</div>
    </div>
</div>

@if(empty($idees))
    <div class="card" style="text-align:center;padding:60px 40px;">
        <p style="font-family:'Bebas Neue',sans-serif;font-size:2rem;opacity:0.3;margin:0;">Aucune idée pour le moment</p>
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;opacity:0.4;margin:12px 0 0;">
            Soyez le premier à proposer quelque chose !
        </p>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;margin-bottom:32px;">
        @foreach($idees as $idee)
        <div class="card" style="padding:28px;margin-bottom:0;transition:transform 0.2s,box-shadow 0.2s;"
             onmouseenter="this.style.transform='translate(-3px,-3px)';this.style.boxShadow='var(--shadow)'"
             onmouseleave="this.style.transform='';this.style.boxShadow='var(--shadow-sm)'">

            {{-- Header --}}
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;margin:0;line-height:1.2;flex:1;">
                    {{ $idee['titre'] ?? '—' }}
                </h3>
                <span class="badge {{ !empty($idee['a_vote']) ? 'badge-valid' : 'badge-waiting' }}" style="margin-left:10px;flex-shrink:0;">
                    {{ ($idee['nb_votes'] ?? 0) }} vote{{ ($idee['nb_votes'] ?? 0) != 1 ? 's' : '' }}
                </span>
            </div>

            <p style="font-size:1rem;line-height:1.6;color:rgba(18,3,9,0.75);margin:0 0 16px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                {{ $idee['contenu'] ?? '' }}
            </p>

            @if(!empty($idee['tags']))
                <div style="margin-bottom:14px;display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach(explode(',', $idee['tags']) as $tag)
                        @if(trim($tag))
                        <span style="background:var(--wheat);border:2px solid var(--coffee);font-family:'DM Mono',monospace;font-size:0.7rem;text-transform:uppercase;padding:2px 8px;letter-spacing:0.05em;">
                            {{ trim($tag) }}
                        </span>
                        @endif
                    @endforeach
                </div>
            @endif

            <div style="font-family:'DM Mono',monospace;font-size:0.75rem;text-transform:uppercase;opacity:0.4;margin-bottom:16px;">
                Par {{ $idee['auteur_prenom'] ?? '' }} {{ $idee['auteur_nom_initiale'] ?? '' }}
                · {{ isset($idee['date_publication']) ? date('d/m/Y', strtotime($idee['date_publication'])) : '' }}
            </div>

            <div style="display:flex;gap:10px;align-items:center;">
                {{-- Vote toggle --}}
                <form action="/salarie/idees/{{ $idee['id_idee'] ?? 0 }}/voter" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="{{ !empty($idee['a_vote']) ? 'btn-success' : 'btn-secondary' }} btn-sm"
                            style="min-width:90px;">
                        {{ !empty($idee['a_vote']) ? '✓ Voté' : '▲ Voter' }}
                    </button>
                </form>

                @if(($idee['id_auteur'] ?? 0) == session('salarie_id'))
                    <button class="btn-secondary btn-sm"
                            onclick="openEditModal({{ $idee['id_idee'] ?? 0 }}, '{{ addslashes($idee['titre'] ?? '') }}', '{{ addslashes($idee['contenu'] ?? '') }}', '{{ addslashes($idee['tags'] ?? '') }}')">
                        Modifier
                    </button>
                    <form action="{{ route('salarie.idees.destroy', $idee['id_idee'] ?? 0) }}" method="POST"
                          onsubmit="return confirm('Supprimer cette idée ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- Modal ajout --}}
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:540px;position:relative;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Proposer une idée</h2>
        <form action="{{ route('salarie.idees.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Titre <span style="color:var(--cherry)">*</span></label>
                <input type="text" name="titre" class="form-input" required maxlength="200" placeholder="Ex: Organiser un atelier zéro déchet">
            </div>
            <div class="form-group">
                <label class="form-label">Description <span style="color:var(--cherry)">*</span></label>
                <textarea name="contenu" class="form-textarea" required placeholder="Décrivez votre idée en détail…"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tags <span style="opacity:0.5;font-size:0.8rem;">(séparés par des virgules)</span></label>
                <input type="text" name="tags" class="form-input" maxlength="300" placeholder="Ex: recyclage, événement, formation">
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary">Proposer</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-add').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal édition --}}
<div id="modal-edit" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:540px;position:relative;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Modifier l'idée</h2>
        <form id="form-edit" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Titre <span style="color:var(--cherry)">*</span></label>
                <input type="text" id="edit-titre" name="titre" class="form-input" required maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">Description <span style="color:var(--cherry)">*</span></label>
                <textarea id="edit-contenu" name="contenu" class="form-textarea" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tags</label>
                <input type="text" id="edit-tags" name="tags" class="form-input" maxlength="300">
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary">Enregistrer</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-edit').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openEditModal(id, titre, contenu, tags) {
    document.getElementById('form-edit').action = '/salarie/idees/' + id;
    document.getElementById('edit-titre').value = titre;
    document.getElementById('edit-contenu').value = contenu;
    document.getElementById('edit-tags').value = tags;
    document.getElementById('modal-edit').style.display = 'flex';
}
// Vote AJAX pour éviter rechargement complet
document.querySelectorAll('form[action*="/voter"]').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = form.querySelector('button');
        const res = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}', 'Accept': 'application/json' },
        });
        if (res.ok) {
            const voted = btn.textContent.trim().startsWith('▲');
            btn.textContent = voted ? '✓ Voté' : '▲ Voter';
            btn.className = voted ? 'btn-success btn-sm' : 'btn-secondary btn-sm';
            btn.style.minWidth = '90px';
            const badge = form.closest('.card')?.querySelector('.badge');
            if (badge) {
                const n = parseInt(badge.textContent) || 0;
                const newN = voted ? n + 1 : Math.max(0, n - 1);
                badge.textContent = newN + ' vote' + (newN !== 1 ? 's' : '');
            }
        }
    });
});
</script>
@endsection
