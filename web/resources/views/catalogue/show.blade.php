@extends('layouts.particulier')

@section('title', $evenement['titre'])

@section('styles')
<style>
    .event-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 40px;
        align-items: start;
    }
    .sticky-box {
        position: sticky;
        top: 100px;
    }
    .info-row {
        margin-bottom: 20px;
    }
    .info-label {
        font-family: 'DM Mono', monospace;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: var(--teal);
        display: block;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 1rem;
        font-weight: 600;
    }
    .places-bar {
        height: 8px;
        background: #e0e0e0;
        border: 2px solid var(--coffee);
        margin-top: 6px;
    }
    .places-bar-fill {
        height: 100%;
        background: var(--cherry);
    }
    .price-display {
        font-size: 2.8rem;
        font-family: 'Bebas Neue', sans-serif;
        line-height: 1;
        margin-bottom: 20px;
    }
    @media (max-width: 880px) {
        .event-grid { grid-template-columns: 1fr; }
        .sticky-box { position: static; }
    }
</style>
@endsection

@section('content')

{{-- En-tête --}}
<div class="page-header" style="margin-bottom: 32px;">
    <div>
        <x-btn variant="secondary" size="sm" href="{{ route('evenements.index') }}" style="margin-bottom: 12px;">
            ← Retour au catalogue
        </x-btn>
        <h1 class="page-title">{{ $evenement['titre'] }}</h1>
    </div>
    @php
        $type = $evenement['type_evenement'];
        $badgeColor = ['formation' => 'cherry', 'atelier' => 'teal', 'conference' => 'coffee'][$type] ?? 'secondary';
    @endphp
    <x-badge :variant="$badgeColor" style="font-size: 0.95rem; padding: 6px 14px;">
        {{ ucfirst($type) }}
    </x-badge>
</div>

{{-- Corps --}}
<div class="event-grid" x-data="eventPage({{ $evenement['id_evenement'] }})">

    {{-- Colonne Gauche : Description --}}
    <div>
        <x-card>
            <h3 class="font-bebas" style="font-size: 1.6rem; border-bottom: 3px solid var(--coffee); padding-bottom: 10px; margin-bottom: 20px;">
                Description
            </h3>
            <div style="font-size: 1.05rem; line-height: 1.75; white-space: pre-line;">
                {{ $evenement['description'] }}
            </div>
        </x-card>

        <x-card title="Modalités" style="margin-top: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <span class="info-label">Format</span>
                    <span class="info-value">{{ ucfirst($evenement['format']) }}</span>
                </div>
                <div>
                    <span class="info-label">Lieu</span>
                    <span class="info-value">{{ $evenement['lieu'] ?? 'En ligne' }}</span>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Colonne Droite : Inscription --}}
    <div class="sticky-box">
        <x-card>
            {{-- Prix --}}
            <div class="price-display">
                {{ $evenement['prix'] > 0 ? number_format($evenement['prix'], 2) . ' €' : 'Gratuit' }}
            </div>

            {{-- Dates --}}
            <div class="info-row">
                <span class="info-label">Date de l'événement</span>
                <span class="info-value">
                    {{ \Carbon\Carbon::parse($evenement['date_debut'])->format('d/m/Y') }}
                    de {{ \Carbon\Carbon::parse($evenement['date_debut'])->format('H:i') }}
                    à {{ \Carbon\Carbon::parse($evenement['date_fin'])->format('H:i') }}
                </span>
            </div>

            {{-- Places --}}
            <div class="info-row">
                <span class="info-label">Places disponibles</span>
                <span class="info-value">
                    {{ $evenement['nb_places_dispo'] }} / {{ $evenement['nb_places_total'] }}
                </span>
                <div class="places-bar">
                    @php
                        $pct = $evenement['nb_places_total'] > 0
                            ? (1 - $evenement['nb_places_dispo'] / $evenement['nb_places_total']) * 100
                            : 0;
                    @endphp
                    <div class="places-bar-fill" style="width: {{ $pct }}%;"></div>
                </div>
            </div>

            {{-- Bouton d'action --}}
            @if($evenement['nb_places_dispo'] > 0)

                {{-- Pas encore inscrit --}}
                <div x-show="!subscribed">
                    <button
                        type="button"
                        class="btn-primary"
                        style="width: 100%;"
                        x-on:click="subscribe()"
                        x-bind:disabled="loading"
                        x-bind:style="loading ? 'opacity:0.7;cursor:not-allowed;width:100%' : 'width:100%'">
                        <span x-show="!loading">M'inscrire</span>
                        <span x-show="loading" style="display:none;">Traitement...</span>
                    </button>
                </div>

                {{-- Déjà inscrit --}}
                <div x-show="subscribed" style="display:none;">
                    <div style="background: #d4edda; border: 3px solid #28a745; padding: 12px 16px; font-weight: 700; text-align: center; margin-bottom: 12px;">
                        ✓ Vous êtes inscrit !
                    </div>
                    <x-btn variant="secondary" style="width: 100%;" @click="downloadTicket()">
                        ↓ Télécharger mon billet PDF
                    </x-btn>
                </div>

            @else
                <x-btn variant="secondary" style="width: 100%; opacity: 0.5;" disabled>
                    Événement complet
                </x-btn>
            @endif
        </x-card>
    </div>
</div>

<script>
function eventPage(evenementId) {
    return {
        loading: false,
        subscribed: false,

        async init() {
            // Vérifie si déjà inscrit au chargement (seulement si connecté)
            const token = getToken();
            if (!token) return;

            try {
                const res = await fetch(`${API_BASE}/api/v1/evenements/${evenementId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.subscribed = !!data.is_registered;
                }
            } catch (e) {
                // Silencieux si l'API ne répond pas
            }
        },

        async subscribe() {
            const token = getToken();
            if (!token) {
                showAlert('Vous devez être connecté pour vous inscrire.', 'error');
                setTimeout(() => window.location.href = '/login', 1500);
                return;
            }

            this.loading = true;
            try {
                const res = await fetch(`${API_BASE}/api/v1/evenements/${evenementId}/inscrire`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const data = await res.json();

                if (res.ok) {
                    this.subscribed = true;
                    showAlert('Inscription confirmée ! Consultez vos emails.', 'success');
                } else {
                    showAlert(data.erreur || 'Erreur lors de l\'inscription', 'error');
                }
            } catch (e) {
                showAlert('Impossible de contacter le serveur.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async downloadTicket() {
            const token = getToken();
            if (!token) return;

            showAlert('Génération du billet...', 'success');
            try {
                const res = await fetch(`${API_BASE}/api/v1/evenements/${evenementId}/ticket`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                if (!res.ok) {
                    showAlert('Impossible de récupérer le billet.', 'error');
                    return;
                }

                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `billet-${evenementId}.pdf`;
                a.click();
                URL.revokeObjectURL(url);
            } catch (e) {
                showAlert('Erreur lors du téléchargement.', 'error');
            }
        }
    }
}
</script>

@endsection
