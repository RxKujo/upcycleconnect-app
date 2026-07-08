@extends('layouts.admin')
@section('title', 'Publicités')

@section('content')
<div class="page-header">
    <h1 class="page-title">Publicités</h1>
    <div style="display:flex;gap:12px;">
        <a href="{{ route('admin.publicites.stats') }}" class="btn-secondary">Statistiques CTR</a>
        <a href="{{ route('admin.publicites.rotation') }}" class="btn-secondary">Rotation WRR</a>
    </div>
</div>

@php
    $enAttente = collect($publicites)->where('statut', 'en_attente')->count();
@endphp

@if(session('success'))
    <div class="alert alert-success">
        <span style="font-size:1.4rem;">✓</span> {{ session('success') }}
    </div>
@endif

@if($enAttente > 0)
    <div class="alert" style="background:var(--wheat);border-color:var(--coffee);color:var(--coffee);">
        <span style="font-size:1.4rem;">⚠</span>
        {{ $enAttente }} publicité{{ $enAttente > 1 ? 's' : '' }} en attente de validation
    </div>
@endif

{{-- Filtres --}}
<div style="display:flex;gap:12px;margin-bottom:32px;flex-wrap:wrap;align-items:center;">
    @foreach([''=>'Tous','en_attente'=>'En attente','active'=>'Active','validee'=>'Validée','refusee'=>'Refusée','expiree'=>'Expirée'] as $val => $label)
        <a href="{{ route('admin.publicites.index', $val ? ['statut'=>$val] : []) }}"
           class="font-mono"
           style="padding:8px 18px;border:2px solid var(--coffee);font-size:0.8rem;text-decoration:none;box-shadow:2px 2px 0px rgba(18,3,9,0.15);
                  {{ $statut_filtre === $val ? 'background:var(--coffee);color:var(--cream);' : 'background:var(--cream);color:var(--coffee);' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Aperçu &amp; titre</th>
                <th>Entreprise</th>
                <th>Statut</th>
                <th style="text-align:right;">Vues</th>
                <th style="text-align:right;">Clics</th>
                <th>Période</th>
                <th style="text-align:center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($publicites as $pub)
            @php
                $st = $pub['statut'] ?? '';
                $badgeClass = match($st) {
                    'validee', 'active' => 'badge-valid',
                    'refusee'           => 'badge-refused',
                    'en_attente'        => 'badge-waiting',
                    default             => '',
                };
            @endphp
            <tr>
                <td style="min-width:220px;">
                    <div style="font-weight:600; margin-bottom:6px;">{{ $pub['titre'] }}</div>
                    @if(!empty($pub['visuel_url']))
                        <a href="{{ $pub['visuel_url'] }}" target="_blank" rel="noopener" title="Ouvrir le visuel">
                            <img src="{{ $pub['visuel_url'] }}" alt="Visuel de la publicité" style="max-height:64px; max-width:150px; border:2px solid var(--coffee); display:block;">
                        </a>
                    @else
                        <div style="font-family:'DM Mono',monospace; font-size:0.72rem; color:var(--cherry);">⚠ aucun visuel</div>
                    @endif
                    @if(!empty($pub['url_cible']))
                        <a href="{{ $pub['url_cible'] }}" target="_blank" rel="noopener"
                           style="display:inline-block; margin-top:6px; font-family:'DM Mono',monospace; font-size:0.72rem; color:var(--teal); word-break:break-all;">
                            → {{ \Illuminate\Support\Str::limit($pub['url_cible'], 42) }}
                        </a>
                    @else
                        <div style="font-family:'DM Mono',monospace; font-size:0.7rem; color:#999; margin-top:4px;">(pas de lien de destination)</div>
                    @endif
                </td>
                <td>{{ $pub['nom_entreprise'] ?? '—' }}</td>
                <td>
                    <span class="badge {{ $badgeClass }}">{{ $st }}</span>
                </td>
                <td style="text-align:right;" class="font-mono">{{ number_format($pub['nb_vues'] ?? 0) }}</td>
                <td style="text-align:right;" class="font-mono">{{ number_format($pub['nb_clics'] ?? 0) }}</td>
                <td class="font-mono" style="font-size:0.85rem;">
                    @if($pub['date_debut']){{ \Carbon\Carbon::parse($pub['date_debut'])->format('d/m/Y') }}@endif
                    @if($pub['date_debut'] && $pub['date_fin']) → @endif
                    @if($pub['date_fin']){{ \Carbon\Carbon::parse($pub['date_fin'])->format('d/m/Y') }}@endif
                    @if(!$pub['date_debut'] && !$pub['date_fin'])—@endif
                </td>
                <td>
                    <div class="action-cell" style="justify-content:center; flex-wrap:wrap; gap:8px;">
                        <button type="button" class="btn-secondary btn-sm pub-detail-btn"
                            data-titre="{{ $pub['titre'] }}"
                            data-entreprise="{{ $pub['nom_entreprise'] ?? '' }}"
                            data-statut="{{ $st }}"
                            data-visuel="{{ $pub['visuel_url'] ?? '' }}"
                            data-url="{{ $pub['url_cible'] ?? '' }}"
                            data-debut="{{ $pub['date_debut'] ? \Carbon\Carbon::parse($pub['date_debut'])->format('d/m/Y') : '' }}"
                            data-fin="{{ $pub['date_fin'] ? \Carbon\Carbon::parse($pub['date_fin'])->format('d/m/Y') : '' }}"
                            data-cout="{{ number_format($pub['cout_mensuel'] ?? 100, 2) }}"
                            data-vues="{{ $pub['nb_vues'] ?? 0 }}"
                            data-clics="{{ $pub['nb_clics'] ?? 0 }}"
                            data-motif="{{ $pub['motif_refus'] ?? '' }}">Détail</button>
                        @if($pub['statut'] === 'en_attente')
                            <form method="POST" action="{{ route('admin.publicites.valider', $pub['id_publicite']) }}">
                                @csrf @method('PUT')
                                <button type="submit" class="btn-success btn-sm">Valider</button>
                            </form>
                            <form method="POST" action="{{ route('admin.publicites.refuser', $pub['id_publicite']) }}"
                                  data-confirm="Refuser cette publicité ?">
                                @csrf @method('PUT')
                                <button type="submit" class="btn-danger btn-sm">Refuser</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:48px;text-align:center;color:#999;font-family:'DM Mono',monospace;font-size:0.9rem;">
                        Aucune publicité trouvée.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modale : détail complet d'une publicité --}}
<div id="pub-detail-modal" style="display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:9999; align-items:flex-start; justify-content:center; padding:40px 16px; overflow-y:auto;">
    <div style="background:var(--cream); border:3px solid var(--coffee); box-shadow:8px 8px 0 var(--coffee); max-width:640px; width:100%; padding:32px; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 class="font-bebas" style="font-size:2rem; letter-spacing:0.05em;" id="pd-titre">Détail</h2>
            <button type="button" id="pd-close" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--coffee);font-family:'DM Mono',monospace;">✕</button>
        </div>
        <div id="pd-visuel-wrap" style="margin-bottom:20px; text-align:center; background:#fff; border:3px solid var(--coffee); padding:12px;">
            <img id="pd-visuel" alt="Visuel de la publicité" style="max-width:100%; max-height:320px; display:inline-block;">
        </div>
        <div id="pd-novisuel" style="display:none; margin-bottom:20px; padding:20px; border:2px dashed var(--cherry); text-align:center; font-family:'DM Mono',monospace; font-size:0.85rem; color:var(--cherry);">⚠ Aucun visuel fourni par l'annonceur</div>
        <table style="width:100%; border-collapse:collapse;">
            <tr><td class="pd-k">Entreprise</td><td class="pd-v" id="pd-entreprise"></td></tr>
            <tr><td class="pd-k">Statut</td><td class="pd-v" id="pd-statut"></td></tr>
            <tr><td class="pd-k">Période</td><td class="pd-v" id="pd-periode"></td></tr>
            <tr><td class="pd-k">Coût</td><td class="pd-v" id="pd-cout"></td></tr>
            <tr><td class="pd-k">Audience</td><td class="pd-v" id="pd-stats"></td></tr>
            <tr><td class="pd-k">Destination</td><td class="pd-v" id="pd-url"></td></tr>
            <tr id="pd-motif-row" style="display:none;"><td class="pd-k">Motif du refus</td><td class="pd-v" id="pd-motif" style="color:var(--cherry);"></td></tr>
        </table>
    </div>
</div>
<style>
    #pub-detail-modal .pd-k { font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.04em; color:#888; padding:10px 14px 10px 0; vertical-align:top; width:140px; border-bottom:2px solid rgba(18,3,9,0.08); }
    #pub-detail-modal .pd-v { padding:10px 0; font-size:0.95rem; border-bottom:2px solid rgba(18,3,9,0.08); word-break:break-word; }
</style>
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('pub-detail-modal');
    if (!modal) return;
    var close = function () { modal.style.display = 'none'; };
    document.getElementById('pd-close').addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

    document.querySelectorAll('.pub-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = btn.dataset;
            document.getElementById('pd-titre').textContent = d.titre || 'Détail';
            var img = document.getElementById('pd-visuel');
            var wrap = document.getElementById('pd-visuel-wrap');
            var nov = document.getElementById('pd-novisuel');
            if (d.visuel) { img.src = d.visuel; wrap.style.display = 'block'; nov.style.display = 'none'; }
            else { wrap.style.display = 'none'; nov.style.display = 'block'; }
            document.getElementById('pd-entreprise').textContent = d.entreprise || '—';
            document.getElementById('pd-statut').textContent = d.statut || '';
            document.getElementById('pd-periode').textContent = (d.debut || d.fin)
                ? ((d.debut || '?') + (d.fin ? ' → ' + d.fin : '')) : '—';
            document.getElementById('pd-cout').textContent = d.cout + ' €/mois';
            document.getElementById('pd-stats').textContent = d.vues + ' vue(s) · ' + d.clics + ' clic(s)';
            var urlCell = document.getElementById('pd-url');
            urlCell.textContent = '';
            if (d.url && /^https?:\/\//i.test(d.url)) {
                var a = document.createElement('a');
                a.href = d.url; a.target = '_blank'; a.rel = 'noopener';
                a.textContent = d.url;
                a.style.cssText = 'color:var(--teal); word-break:break-all;';
                urlCell.appendChild(a);
            } else {
                urlCell.textContent = d.url || '(aucun lien de destination)';
            }
            var motifRow = document.getElementById('pd-motif-row');
            if (d.motif) { document.getElementById('pd-motif').textContent = d.motif; motifRow.style.display = ''; }
            else { motifRow.style.display = 'none'; }
            modal.style.display = 'flex';
        });
    });
})();
</script>
@endpush
