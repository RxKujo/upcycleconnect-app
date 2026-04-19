@extends('layouts.admin')
@section('title', 'Utilisateur #' . $utilisateur['id_utilisateur'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Utilisateur #{{ $utilisateur['id_utilisateur'] }}</h1>
    <div class="action-cell">
        <form action="{{ route('admin.utilisateurs.delete', $utilisateur['id_utilisateur']) }}" method="POST"
              onsubmit="return confirm('Supprimer définitivement ce compte ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger btn-sm">Supprimer le compte</button>
        </form>
        <a href="{{ route('admin.utilisateurs.index') }}" class="btn-secondary btn-sm">← Retour</a>
    </div>
</div>

<div class="info-grid" style="margin-bottom: 32px;">

    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;margin:0 0 20px;border-bottom:3px solid var(--coffee);padding-bottom:10px;">Informations</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Nom complet</span>
                <p class="info-value">{{ $utilisateur['prenom'] }} {{ $utilisateur['nom'] }}</p>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <p class="info-value">{{ $utilisateur['email'] }}</p>
            </div>
            <div class="info-item">
                <span class="info-label">Téléphone</span>
                <p class="info-value">{{ $utilisateur['telephone'] ?? '—' }}</p>
            </div>
            <div class="info-item">
                <span class="info-label">Ville</span>
                <p class="info-value">{{ $utilisateur['ville'] ?? '—' }}</p>
            </div>
            <div class="info-item">
                <span class="info-label">Inscription</span>
                <p class="info-value">{{ \Carbon\Carbon::parse($utilisateur['date_creation'])->format('d/m/Y') }}</p>
            </div>
            <div class="info-item">
                <span class="info-label">Statut</span>
                <p class="info-value">
                    @if($utilisateur['est_banni'])
                        <span class="badge badge-refused">Banni</span>
                        @if($utilisateur['date_fin_ban'])
                            <span style="font-size:0.85rem;margin-left:8px;">jusqu'au {{ \Carbon\Carbon::parse($utilisateur['date_fin_ban'])->format('d/m/Y') }}</span>
                        @endif
                    @else
                        <span class="badge badge-valid">Actif</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;margin:0 0 20px;border-bottom:3px solid var(--coffee);padding-bottom:10px;">Rôle</h3>
        <p class="info-value" style="margin-bottom:16px;">Rôle actuel : <span class="badge badge-waiting">{{ $utilisateur['role'] }}</span></p>
        <form action="{{ route('admin.utilisateurs.role', $utilisateur['id_utilisateur']) }}" method="POST">
            @csrf @method('PUT')
            <div style="display:flex;gap:12px;align-items:center;">
                <select name="role" class="form-select" style="flex:1;">
                    @foreach(['particulier','professionnel','salarie','admin'] as $r)
                        <option value="{{ $r }}" {{ $utilisateur['role'] === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary btn-sm" style="white-space:nowrap;">Changer le rôle</button>
            </div>
        </form>
    </div>

    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;margin:0 0 20px;border-bottom:3px solid var(--coffee);padding-bottom:10px;">Bannissement</h3>
        @if($utilisateur['est_banni'])
            <p style="margin-bottom:16px;">Ce compte est banni.</p>
            <form action="{{ route('admin.utilisateurs.unban', $utilisateur['id_utilisateur']) }}" method="POST">
                @csrf
                <button type="submit" class="btn-success">Débannir</button>
            </form>
        @else
            <form action="{{ route('admin.utilisateurs.ban', $utilisateur['id_utilisateur']) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Type de ban</label>
                    <div style="display:flex;gap:12px;margin-bottom:12px;">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="radio" name="ban_type" value="temporaire" checked onchange="toggleBanDate(false)"> Temporaire
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="radio" name="ban_type" value="permanent" onchange="toggleBanDate(true)"> Permanent
                        </label>
                    </div>
                    <input type="hidden" name="permanent" id="permanent-input" value="">
                    <div id="ban-date-group">
                        <label class="form-label">Date de fin</label>
                        <input type="date" name="date_fin_ban" class="form-input" min="{{ now()->addDay()->format('Y-m-d') }}" value="{{ now()->addMonth()->format('Y-m-d') }}">
                    </div>
                </div>
                <button type="submit" class="btn-danger">Bannir</button>
            </form>
        @endif
    </div>

    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;margin:0 0 20px;border-bottom:3px solid var(--coffee);padding-bottom:10px;">Abonnement</h3>
        @if($souscription)
            <div style="margin-bottom:16px;">
                <p class="info-value" style="margin-bottom:4px;"><strong>{{ $souscription['nom'] }}</strong></p>
                <p style="font-size:0.9rem;color:rgba(18,3,9,0.6);">
                    Depuis le {{ \Carbon\Carbon::parse($souscription['date_debut'])->format('d/m/Y') }}
                    @if($souscription['date_fin'])
                        — jusqu'au {{ \Carbon\Carbon::parse($souscription['date_fin'])->format('d/m/Y') }}
                    @else
                        — sans date de fin
                    @endif
                </p>
                @if($souscription['gere_par_admin'])
                    <span class="badge badge-waiting" style="margin-top:8px;">Géré manuellement</span>
                @endif
            </div>
            <form action="{{ route('admin.utilisateurs.abonnement.revoke', $utilisateur['id_utilisateur']) }}" method="POST"
                  onsubmit="return confirm('Révoquer cet abonnement ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger btn-sm">Révoquer</button>
            </form>
        @else
            <p style="margin-bottom:16px;color:rgba(18,3,9,0.6);">Aucun abonnement actif.</p>
        @endif

        @if(count($abonnements) > 0)
        <form action="{{ route('admin.utilisateurs.abonnement.assign', $utilisateur['id_utilisateur']) }}" method="POST" style="margin-top:16px;padding-top:16px;border-top:2px solid rgba(18,3,9,0.1);">
            @csrf
            <label class="form-label">Assigner un abonnement</label>
            <select name="id_abonnement" class="form-select" style="margin-bottom:12px;">
                @foreach($abonnements as $ab)
                    <option value="{{ $ab['id_abonnement'] }}">{{ $ab['nom'] }} — {{ number_format($ab['prix_mensuel'], 2, ',', ' ') }} €/mois ({{ $ab['type_cible'] }})</option>
                @endforeach
            </select>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                <div style="flex:1;">
                    <label class="form-label" style="font-size:0.8rem;">Date de fin (optionnel)</label>
                    <input type="date" name="date_fin" class="form-input">
                </div>
            </div>
            <button type="submit" class="btn-primary btn-sm">Assigner</button>
        </form>
        @endif
    </div>

</div>

<script>
function toggleBanDate(permanent) {
    document.getElementById('ban-date-group').style.display = permanent ? 'none' : 'block';
    document.getElementById('permanent-input').value = permanent ? '1' : '';
}
</script>
@endsection
