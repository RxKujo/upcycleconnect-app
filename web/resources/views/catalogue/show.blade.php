@extends('layouts.public')

@section('title', $evenement['titre'] ?? 'Événement')
@section('meta_description', Illuminate\Support\Str::limit($evenement['description'] ?? '', 160))
@section('og_title', $evenement['titre'] ?? 'Événement')
@section('og_description', Illuminate\Support\Str::limit($evenement['description'] ?? '', 160))
@section('og_type', 'event')

@php
    $typeLabels = [
        'atelier' => 'Atelier',
        'formation' => 'Formation',
        'conference' => 'Conférence',
    ];
    $formatLabels = [
        'presentiel' => 'Présentiel',
        'distanciel' => 'Distanciel',
    ];
    $date = \Carbon\Carbon::parse($evenement['date_debut']);
    $dateFin = \Carbon\Carbon::parse($evenement['date_fin']);
    $pct = ($evenement['nb_places_total'] ?? 0) > 0
        ? round((1 - ($evenement['nb_places_dispo'] ?? 0) / $evenement['nb_places_total']) * 100)
        : 0;
@endphp

@section('content')
<div class="page-container">
    <a href="{{ route('evenements.index') }}" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
        Retour au catalogue
    </a>

    <div class="event-show-grid" x-data="eventPage({{ $evenement['id_evenement'] }})">

        {{-- Colonne gauche : Description + modalités --}}
        <div>
            {{-- Header --}}
            <div class="event-head">
                <div class="event-head-tags">
                    <span class="event-tag event-tag-{{ $evenement['type_evenement'] }}">{{ $typeLabels[$evenement['type_evenement']] ?? '' }}</span>
                    <span class="event-tag-format">{{ $formatLabels[$evenement['format']] ?? '' }}</span>
                </div>
                <h1 class="event-title">{{ $evenement['titre'] }}</h1>
            </div>

            {{-- Description --}}
            <div class="event-card-block">
                <h2 class="event-block-title">Description</h2>
                <div class="event-description">{{ $evenement['description'] }}</div>
            </div>

            {{-- Modalités --}}
            <div class="event-card-block">
                <h2 class="event-block-title">Modalités</h2>
                <div class="event-modalites-grid">
                    <div class="event-modalite">
                        <span class="event-modalite-label">Format</span>
                        <span class="event-modalite-value">{{ $formatLabels[$evenement['format']] ?? '' }}</span>
                    </div>
                    <div class="event-modalite">
                        <span class="event-modalite-label">Lieu</span>
                        <span class="event-modalite-value">{{ $evenement['lieu'] ?? 'En ligne' }}</span>
                    </div>
                    <div class="event-modalite">
                        <span class="event-modalite-label">Date</span>
                        <span class="event-modalite-value">{{ $date->locale('fr')->isoFormat('dddd D MMMM Y') }}</span>
                    </div>
                    <div class="event-modalite">
                        <span class="event-modalite-label">Horaire</span>
                        <span class="event-modalite-value">{{ $date->format('H\hi') }} — {{ $dateFin->format('H\hi') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne droite : Inscription --}}
        <aside class="event-sidebar">
            {{-- Date block --}}
            <div class="event-sidebar-date">
                <span class="event-sidebar-day">{{ $date->format('d') }}</span>
                <span class="event-sidebar-month">{{ strtoupper($date->locale('fr')->isoFormat('MMMM Y')) }}</span>
            </div>

            {{-- Prix --}}
            <div class="event-sidebar-price-block">
                @if(($evenement['prix'] ?? 0) > 0)
                <span class="event-sidebar-price">{{ number_format($evenement['prix'], 2) }}&euro;</span>
                <span class="event-sidebar-price-sub">par participant</span>
                @else
                <span class="event-sidebar-free">Gratuit</span>
                @endif
            </div>

            {{-- Places --}}
            <div class="event-sidebar-places">
                <div class="event-places-header">
                    <span class="event-places-label">Places restantes</span>
                    <span class="event-places-value">{{ $evenement['nb_places_dispo'] }} / {{ $evenement['nb_places_total'] }}</span>
                </div>
                <div class="event-places-bar">
                    <div class="event-places-bar-fill" style="width: {{ $pct }}%;"></div>
                </div>
            </div>

            {{-- Actions --}}
            @if(($evenement['nb_places_dispo'] ?? 0) > 0)
                <div x-show="!subscribed">
                    <button type="button" class="btn btn-primary btn-lg btn-block" x-on:click="subscribe()" x-bind:disabled="loading">
                        <span x-show="!loading">M'inscrire</span>
                        <span x-show="loading" style="display:none;">Traitement…</span>
                    </button>
                </div>
                <div x-show="subscribed" style="display:none; flex-direction:column; gap:12px;">
                    <div class="event-success-banner">&#10003; Vous êtes inscrit</div>
                    <button type="button" class="btn btn-secondary btn-block" x-on:click="downloadTicket()">
                        Télécharger mon billet PDF
                    </button>
                </div>
            @else
                <button type="button" class="btn btn-secondary btn-block" disabled>Événement complet</button>
            @endif

            {{-- Info connexion --}}
            <p class="event-sidebar-note">
                Vous devez être connecté pour vous inscrire. L'inscription est confirmée par email.
            </p>
        </aside>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>

<script>
const API_BASE = 'http://localhost:8888';

function getToken() {
    return localStorage.getItem('auth_token');
}

function showAlert(msg, type) {
    alert(msg);
}

function eventPage(evenementId) {
    return {
        loading: false,
        subscribed: false,

        async init() {
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
            } catch (e) {}
        },

        async subscribe() {
            const token = getToken();
            if (!token) {
                window.location.href = '/login?intent=evenement&event=' + evenementId;
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
                } else {
                    showAlert(data.erreur || 'Erreur lors de l\'inscription');
                }
            } catch (e) {
                showAlert('Impossible de contacter le serveur.');
            } finally {
                this.loading = false;
            }
        },

        async downloadTicket() {
            const token = getToken();
            if (!token) return;
            try {
                const res = await fetch(`${API_BASE}/api/v1/evenements/${evenementId}/ticket`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                if (!res.ok) { showAlert('Impossible de récupérer le billet.'); return; }
                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `billet-${evenementId}.pdf`;
                a.click();
                URL.revokeObjectURL(url);
            } catch (e) { showAlert('Erreur lors du téléchargement.'); }
        }
    }
}
</script>
@endsection

@section('styles')
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--coffee);
    margin-bottom: 32px;
    opacity: 0.6;
    transition: opacity 0.15s;
}
.back-link:hover { opacity: 1; }

.event-show-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 40px;
    align-items: start;
}
@media (max-width: 900px) {
    .event-show-grid { grid-template-columns: 1fr; }
}

.event-head { margin-bottom: 32px; }
.event-head-tags {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.event-tag {
    font-family: 'DM Mono', monospace;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 5px 14px;
    border: 2px solid var(--coffee);
}
.event-tag-atelier { background: var(--teal); color: var(--cream); }
.event-tag-formation { background: var(--forest); color: var(--cream); }
.event-tag-conference { background: var(--wheat); color: var(--coffee); }
.event-tag-format {
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    color: var(--coffee);
    opacity: 0.6;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.event-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(2rem, 4vw, 3rem);
    letter-spacing: 0.03em;
    line-height: 1;
    color: var(--coffee);
}

.event-card-block {
    background: var(--cream);
    border: var(--border);
    box-shadow: 4px 4px 0 var(--coffee);
    padding: 28px 32px;
    margin-bottom: 24px;
}
.event-block-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.4rem;
    letter-spacing: 0.04em;
    color: var(--coffee);
    padding-bottom: 14px;
    margin-bottom: 18px;
    border-bottom: 2px solid var(--coffee);
}
.event-description {
    font-size: 1rem;
    line-height: 1.7;
    white-space: pre-line;
}
.event-modalites-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 600px) { .event-modalites-grid { grid-template-columns: 1fr; } }
.event-modalite { display: flex; flex-direction: column; gap: 4px; }
.event-modalite-label {
    font-family: 'DM Mono', monospace;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--coffee);
    opacity: 0.5;
}
.event-modalite-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--coffee);
}

/* Sidebar */
.event-sidebar {
    background: var(--cream);
    border: var(--border);
    box-shadow: 5px 5px 0 var(--coffee);
    padding: 28px;
    position: sticky;
    top: calc(var(--nav-height) + 24px);
    display: flex;
    flex-direction: column;
    gap: 20px;
}
@media (max-width: 900px) {
    .event-sidebar { position: static; }
}
.event-sidebar-date {
    background: var(--cherry);
    color: var(--cream);
    border: 2px solid var(--coffee);
    padding: 16px;
    text-align: center;
}
.event-sidebar-day {
    display: block;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 3rem;
    line-height: 1;
    letter-spacing: 0.02em;
}
.event-sidebar-month {
    display: block;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    margin-top: 6px;
}
.event-sidebar-price-block {
    text-align: center;
    padding: 14px 0;
    border-top: 2px solid rgba(18,3,9,0.1);
    border-bottom: 2px solid rgba(18,3,9,0.1);
}
.event-sidebar-price {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 3rem;
    color: var(--cherry);
    line-height: 1;
    letter-spacing: 0.02em;
}
.event-sidebar-price-sub {
    display: block;
    font-family: 'DM Mono', monospace;
    font-size: 0.72rem;
    color: var(--coffee);
    opacity: 0.5;
    margin-top: 6px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.event-sidebar-free {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.5rem;
    color: var(--forest);
    letter-spacing: 0.02em;
}

.event-sidebar-places { display: flex; flex-direction: column; gap: 8px; }
.event-places-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.event-places-label {
    font-family: 'DM Mono', monospace;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--coffee);
    opacity: 0.6;
}
.event-places-value {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.1rem;
    color: var(--coffee);
}
.event-places-bar {
    height: 8px;
    background: var(--wheat);
    border: 2px solid var(--coffee);
}
.event-places-bar-fill {
    height: 100%;
    background: var(--cherry);
    transition: width 0.3s;
}

.event-success-banner {
    background: var(--forest);
    color: var(--cream);
    border: 3px solid var(--coffee);
    box-shadow: 3px 3px 0 var(--coffee);
    padding: 14px;
    text-align: center;
    font-family: 'DM Mono', monospace;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.event-sidebar-note {
    font-size: 0.82rem;
    color: var(--coffee);
    opacity: 0.6;
    line-height: 1.5;
    text-align: center;
}
@endsection
