@extends('layouts.admin')
@section('title', 'Utilisateur #' . $utilisateur['id_utilisateur'])

@section('content')
<style>
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(18,3,9,0.6); z-index: 1000; justify-content: center; align-items: center; }
    .modal-overlay.active { display: flex; }
    .modal { background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 32px; max-width: 500px; width: 90%; }
    .modal h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; margin-bottom: 16px; }
    .modal-actions { display: flex; gap: 12px; margin-top: 24px; }
    .danger-zone { border-color: var(--cherry); border-width: 3px; }
    .user-avatar { width: 120px; height: 120px; border: var(--border); background: var(--wheat); display: flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: var(--coffee); overflow: hidden; border-radius: 50%; }
    .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
</style>

<div class="page-header">
    <h1 class="page-title">Utilisateur #{{ $utilisateur['id_utilisateur'] }}</h1>
    <a href="{{ route('admin.utilisateurs.index') }}" class="btn-secondary btn-sm">Retour</a>
</div>

<div class="info-grid">
    <!-- Informations Generales -->
    <div class="card" style="cursor: default; transform: none; grid-column: 1 / -1;">
        <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; margin-bottom: 20px; border-bottom: 3px solid var(--coffee); padding-bottom: 12px;">Informations Generales</h3>
        <div style="display: flex; gap: 32px; align-items: flex-start; flex-wrap: wrap;">
            <div class="user-avatar">
                @if(isset($utilisateur['photo_profil_url']) && $utilisateur['photo_profil_url'])
                    <img src="/uploads/{{ $utilisateur['photo_profil_url'] }}" alt="Avatar">
                @else
                    {{ strtoupper(substr($utilisateur['prenom'] ?? '', 0, 1)) }}{{ strtoupper(substr($utilisateur['nom'] ?? '', 0, 1)) }}
                @endif
            </div>
            <div style="flex: 1;" class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nom</span>
                    <p class="info-value">{{ $utilisateur['nom'] }}</p>
                </div>
                <div class="info-item">
                    <span class="info-label">Prenom</span>
                    <p class="info-value">{{ $utilisateur['prenom'] }}</p>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <p class="info-value">{{ $utilisateur['email'] }}</p>
                </div>
                <div class="info-item">
                    <span class="info-label">Telephone</span>
                    <p class="info-value">{{ $utilisateur['telephone'] ?? '---' }}</p>
                </div>
                <div class="info-item">
                    <span class="info-label">Ville</span>
                    <p class="info-value">{{ $utilisateur['ville'] ?? '---' }}</p>
                </div>
                <div class="info-item">
                    <span class="info-label">Adresse</span>
                    <p class="info-value">{{ $utilisateur['adresse_complete'] ?? '---' }}</p>
                </div>
                <div class="info-item">
                    <span class="info-label">Role</span>
                    <p class="info-value"><span class="badge badge-waiting">{{ ucfirst($utilisateur['role']) }}</span></p>
                </div>
                <div class="info-item">
                    <span class="info-label">Inscription</span>
                    <p class="info-value">{{ isset($utilisateur['date_creation']) ? \Carbon\Carbon::parse($utilisateur['date_creation'])->format('d/m/Y H:i') : '---' }}</p>
                </div>
                <div class="info-item">
                    <span class="info-label">Statut</span>
                    <p class="info-value">
                        @if($utilisateur['est_banni'])
                            <span class="badge badge-refused">Banni</span>
                        @else
                            <span class="badge badge-valid">Actif</span>
                        @endif
                    </p>
                </div>
                <div class="info-item">
                    <span class="info-label">Upcycling Score</span>
                    <p class="info-value-large">{{ $utilisateur['upcycling_score'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestion du Role -->
    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin-bottom: 16px; border-bottom: 3px solid var(--coffee); padding-bottom: 10px;">Gestion du Role</h3>
        <form id="role-form" onsubmit="return changeRole(event)">
            <div class="form-group">
                <select class="form-select" id="role-select" style="font-size: 1rem; padding: 10px;">
                    <option value="particulier" {{ $utilisateur['role'] === 'particulier' ? 'selected' : '' }}>Particulier</option>
                    <option value="professionnel" {{ $utilisateur['role'] === 'professionnel' ? 'selected' : '' }}>Professionnel</option>
                    <option value="salarie" {{ $utilisateur['role'] === 'salarie' ? 'selected' : '' }}>Salarie</option>
                    <option value="admin" {{ $utilisateur['role'] === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-primary btn-sm">Appliquer</button>
        </form>
    </div>

    <!-- Bannissement -->
    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin-bottom: 16px; border-bottom: 3px solid var(--coffee); padding-bottom: 10px;">Bannissement</h3>
        @if($utilisateur['est_banni'])
            <p style="margin-bottom: 12px;">
                @if(isset($utilisateur['date_fin_ban']) && $utilisateur['date_fin_ban'])
                    <span class="badge badge-refused">Banni jusqu'au {{ \Carbon\Carbon::parse($utilisateur['date_fin_ban'])->format('d/m/Y') }}</span>
                @else
                    <span class="badge badge-refused">Banni de maniere permanente</span>
                @endif
            </p>
            <form action="{{ route('admin.utilisateurs.unban', $utilisateur['id_utilisateur']) }}" method="POST"
                  onsubmit="return confirm('Confirmer le debannissement ?')">
                @csrf
                <button type="submit" class="btn-success btn-sm">Debannir</button>
            </form>
        @else
            <p style="margin-bottom: 12px; font-size: 0.95rem;">L'utilisateur est actuellement actif.</p>
            <button class="btn-danger btn-sm" onclick="openBanModal()">Bannir</button>
        @endif
    </div>

    <!-- Abonnement -->
    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin-bottom: 16px; border-bottom: 3px solid var(--coffee); padding-bottom: 10px;">Abonnement</h3>
        @if(isset($subscription) && $subscription)
            <p style="margin-bottom: 8px;"><strong>Plan :</strong> {{ $subscription['nom_abonnement'] }}</p>
            <p style="margin-bottom: 8px;"><strong>Jusqu'au :</strong> {{ \Carbon\Carbon::parse($subscription['date_fin'])->format('d/m/Y') }}</p>
            @if($subscription['gere_par_admin'] ?? false)
                <span class="badge badge-waiting" style="margin-bottom: 12px;">Gere par admin</span>
            @endif
            <br>
            <button class="btn-secondary btn-sm" onclick="openSubModal()" style="margin-top: 8px;">Modifier abonnement</button>
        @else
            <p style="margin-bottom: 12px; color: rgba(18,3,9,0.5);">Aucun abonnement actif</p>
            <button class="btn-success btn-sm" onclick="openSubModal()">Attribuer un abonnement</button>
        @endif
    </div>

    <!-- Donnees -->
    <div class="card" style="cursor: default; transform: none;">
        <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin-bottom: 16px; border-bottom: 3px solid var(--coffee); padding-bottom: 10px;">Donnees</h3>
        <button class="btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;" disabled>Telecharger donnees PDF</button>
        <p style="font-size: 0.8rem; margin-top: 8px; color: rgba(18,3,9,0.5);">Fonctionnalite a venir</p>
    </div>

    <!-- Actions Dangereuses -->
    <div class="card danger-zone" style="cursor: default; transform: none;">
        <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin-bottom: 16px; border-bottom: 3px solid var(--cherry); padding-bottom: 10px; color: var(--cherry);">Actions Dangereuses</h3>
        <button class="btn-danger btn-sm" onclick="openDeleteModal()">Supprimer le compte</button>
        <p style="font-size: 0.8rem; margin-top: 8px; color: var(--cherry);">Cette action est irreversible</p>
    </div>
</div>

<!-- Ban Modal -->
<div class="modal-overlay" id="ban-modal">
    <div class="modal">
        <h3>Bannir l'utilisateur</h3>
        <form action="{{ route('admin.utilisateurs.ban', $utilisateur['id_utilisateur']) }}" method="POST" id="ban-form">
            @csrf
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="ban-permanent" onchange="toggleBanDate()">
                    <span class="form-label" style="margin: 0;">Ban permanent</span>
                </label>
            </div>
            <div class="form-group" id="ban-date-group">
                <label class="form-label">Date de fin (si temporaire)</label>
                <input type="date" class="form-input" name="date_fin_ban" id="ban-date" value="{{ now()->addMonth()->format('Y-m-d') }}">
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-danger">Bannir</button>
                <button type="button" class="btn-secondary" onclick="closeBanModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Subscription Modal -->
<div class="modal-overlay" id="sub-modal">
    <div class="modal">
        <h3>Gerer l'abonnement</h3>
        <form id="sub-form" onsubmit="return assignSubscription(event)">
            <div class="form-group">
                <label class="form-label">Plan</label>
                <select class="form-select" id="sub-plan">
                    @if(isset($abonnements))
                        @foreach($abonnements as $ab)
                            <option value="{{ $ab['id_abonnement'] }}">{{ $ab['nom'] }} ({{ number_format($ab['prix_mensuel'], 2, ',', ' ') }} EUR/mois)</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date de fin</label>
                <input type="date" class="form-input" id="sub-date" value="{{ now()->addYear()->format('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="sub-admin" checked>
                    <span class="form-label" style="margin: 0;">Gere par admin</span>
                </label>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-success">Appliquer</button>
                <button type="button" class="btn-secondary" onclick="closeSubModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="delete-modal">
    <div class="modal">
        <h3 style="color: var(--cherry);">Supprimer le compte</h3>
        <p style="margin-bottom: 16px;">Etes-vous sur ? Cette action est irreversible. Toutes les donnees de l'utilisateur seront supprimees.</p>
        <div class="modal-actions">
            <form action="{{ route('admin.utilisateurs.delete', $utilisateur['id_utilisateur']) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Supprimer definitivement</button>
            </form>
            <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Annuler</button>
        </div>
    </div>
</div>

<script>
const API_BASE = 'http://localhost:8888';
const TOKEN = '{{ session("admin_token") }}';
const USER_ID = {{ $utilisateur['id_utilisateur'] }};

function openBanModal() { document.getElementById('ban-modal').classList.add('active'); }
function closeBanModal() { document.getElementById('ban-modal').classList.remove('active'); }
function openSubModal() { document.getElementById('sub-modal').classList.add('active'); }
function closeSubModal() { document.getElementById('sub-modal').classList.remove('active'); }
function openDeleteModal() { document.getElementById('delete-modal').classList.add('active'); }
function closeDeleteModal() { document.getElementById('delete-modal').classList.remove('active'); }

function toggleBanDate() {
    const permanent = document.getElementById('ban-permanent').checked;
    const dateGroup = document.getElementById('ban-date-group');
    dateGroup.style.display = permanent ? 'none' : 'block';
    if (permanent) {
        document.getElementById('ban-date').value = '';
        document.getElementById('ban-date').removeAttribute('name');
    } else {
        document.getElementById('ban-date').setAttribute('name', 'date_fin_ban');
    }
}

async function changeRole(e) {
    e.preventDefault();
    const role = document.getElementById('role-select').value;
    try {
        const resp = await fetch(API_BASE + '/api/v1/admin/utilisateurs/' + USER_ID + '/role', {
            method: 'PUT',
            headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' },
            body: JSON.stringify({ role: role })
        });
        if (resp.ok) {
            alert('Role mis a jour');
            location.reload();
        } else {
            const d = await resp.json();
            alert('Erreur: ' + (d.erreur || 'inconnue'));
        }
    } catch (err) {
        alert('Erreur de connexion');
    }
    return false;
}

async function assignSubscription(e) {
    e.preventDefault();
    const payload = {
        id_abonnement: parseInt(document.getElementById('sub-plan').value),
        date_fin: document.getElementById('sub-date').value + 'T23:59:59Z',
        gere_par_admin: document.getElementById('sub-admin').checked
    };
    try {
        const resp = await fetch(API_BASE + '/api/v1/admin/utilisateurs/' + USER_ID + '/subscription', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (resp.ok) {
            alert('Abonnement attribue');
            location.reload();
        } else {
            const d = await resp.json();
            alert('Erreur: ' + (d.erreur || 'inconnue'));
        }
    } catch (err) {
        alert('Erreur de connexion');
    }
    return false;
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>
@endsection
