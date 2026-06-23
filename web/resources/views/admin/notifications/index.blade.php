@extends('layouts.admin')

@section('title', 'Supervision Notifications')

@section('content')
<div class="page-header">
    <h1 class="page-title">Supervision Notifications</h1>
    <button class="btn-primary" onclick="document.getElementById('modal-groupe').style.display='flex'">
        + Envoi groupé
    </button>
</div>

{{-- Stats rapides --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Envois totaux</div>
        <div class="stat-value">{{ count($log) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Push envoyés</div>
        <div class="stat-value">{{ count(array_filter($log, fn($l) => str_contains($l['type_envoi'] ?? '', 'push'))) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Emails envoyés</div>
        <div class="stat-value">{{ count(array_filter($log, fn($l) => str_contains($l['type_envoi'] ?? '', 'email'))) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Erreurs</div>
        <div class="stat-value" style="color:var(--cherry)">{{ count(array_filter($log, fn($l) => ($l['statut'] ?? '') === 'erreur')) }}</div>
    </div>
</div>

{{-- Filtres --}}
<form method="GET" style="display:flex;gap:16px;margin-bottom:32px;flex-wrap:wrap;align-items:flex-end;">
    <div>
        <label class="form-label" style="font-size:0.8rem;">Type</label>
        <select name="type" class="form-select" style="width:180px;">
            <option value="">Tous</option>
            <option value="push" {{ request('type') === 'push' ? 'selected' : '' }}>Push</option>
            <option value="email" {{ request('type') === 'email' ? 'selected' : '' }}>Email</option>
            <option value="groupe_push" {{ request('type') === 'groupe_push' ? 'selected' : '' }}>Groupe Push</option>
            <option value="groupe_email" {{ request('type') === 'groupe_email' ? 'selected' : '' }}>Groupe Email</option>
        </select>
    </div>
    <div>
        <label class="form-label" style="font-size:0.8rem;">Depuis</label>
        <input type="date" name="date_debut" class="form-input" style="width:160px;" value="{{ request('date_debut') }}">
    </div>
    <div>
        <label class="form-label" style="font-size:0.8rem;">Jusqu'au</label>
        <input type="date" name="date_fin" class="form-input" style="width:160px;" value="{{ request('date_fin') }}">
    </div>
    <button type="submit" class="btn-primary" style="padding:14px 24px;">Filtrer</button>
    <a href="{{ route('admin.notifications.index') }}" class="btn-secondary" style="padding:14px 24px;">Réinitialiser</a>
</form>

{{-- Log des envois --}}
<div class="card">
    <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 24px;">Journal des envois</h2>
    @if(empty($log))
        <p style="opacity:0.4;font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.9rem;">Aucun envoi enregistré.</p>
    @else
        <div class="table-container" style="box-shadow:none;border:none;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Titre</th>
                        <th>Segment / Dest.</th>
                        <th>Nb dest.</th>
                        <th>Statut</th>
                        <th>Détail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($log as $entry)
                    <tr>
                        <td style="white-space:nowrap;font-size:0.9rem;">
                            {{ isset($entry['date_envoi']) ? date('d/m/Y H:i', strtotime($entry['date_envoi'])) : '—' }}
                        </td>
                        <td>
                            @php $type = $entry['type_envoi'] ?? ''; @endphp
                            <span class="badge {{ str_contains($type, 'push') ? 'badge-info' : 'badge-waiting' }}">
                                {{ $type ?: '—' }}
                            </span>
                        </td>
                        <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $entry['titre'] ?? '—' }}
                        </td>
                        <td>{{ $entry['segment'] ?? ($entry['id_destinataire'] ?? '—') }}</td>
                        <td style="text-align:center;">{{ $entry['nb_destinataires'] ?? '1' }}</td>
                        <td>
                            @if(($entry['statut'] ?? '') === 'envoye')
                                <span class="badge badge-valid">Envoyé</span>
                            @elseif(($entry['statut'] ?? '') === 'erreur')
                                <span class="badge badge-refused">Erreur</span>
                            @else
                                <span class="badge badge-waiting">{{ $entry['statut'] ?? '—' }}</span>
                            @endif
                        </td>
                        <td style="font-size:0.85rem;color:var(--cherry);max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $entry['erreur_detail'] ?? '' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Sites pour envoi groupé --}}
<div class="card">
    <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 24px;">Sites UpcycleConnect</h2>
    @if(empty($sites))
        <p style="opacity:0.4;font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.9rem;">Aucun site trouvé.</p>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
            @foreach($sites as $site)
            <div style="border:var(--border);padding:20px;box-shadow:var(--shadow-sm);">
                <div style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;">{{ $site['nom_site'] ?? 'Site #' . ($site['id_site_uc'] ?? '?') }}</div>
                <div style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;margin-top:6px;">
                    {{ $site['nb_utilisateurs'] ?? 0 }} utilisateur(s)
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Modal envoi groupé --}}
<div id="modal-groupe" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Envoi groupé</h2>
        <form action="{{ route('admin.notifications.groupe') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Type d'envoi <span style="color:var(--cherry)">*</span></label>
                <select name="type_envoi" class="form-select" required>
                    <option value="groupe_push">Push — groupe</option>
                    <option value="groupe_email">Email — groupe</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Site (facultatif)</label>
                <select name="id_site" class="form-select">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site['id_site_uc'] ?? '' }}">{{ $site['nom_site'] ?? 'Site #' . ($site['id_site_uc'] ?? '') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Segment (rôle)</label>
                <select name="segment" class="form-select">
                    <option value="">Tous les rôles</option>
                    <option value="particulier">Particuliers</option>
                    <option value="salarie">Salariés</option>
                    <option value="professionnel">Professionnels</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Titre</label>
                <input type="text" name="titre" class="form-input" maxlength="255" placeholder="Titre de la notification">
            </div>
            <div class="form-group">
                <label class="form-label">Message <span style="color:var(--cherry)">*</span></label>
                <textarea name="contenu" class="form-textarea" required style="min-height:100px;" placeholder="Contenu de la notification…"></textarea>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary">Envoyer</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-groupe').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection
