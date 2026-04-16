@extends('layouts.particulier')

@section('title', 'Catalogue des Événements')

@section('styles')
<style>
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 32px;
        margin-top: 24px;
    }
    .event-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform 0.2s ease;
    }
    .event-card:hover {
        transform: translate(-4px, -4px);
    }
    .event-meta {
        font-family: 'DM Mono', monospace;
        font-size: 0.85rem;
        color: var(--teal);
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .event-footer {
        margin-top: auto;
        padding-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .price-tag {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 1.5rem;
        color: var(--coffee);
    }
    .seats-left {
        font-size: 0.9rem;
        font-weight: 600;
    }
    .seats-low {
        color: var(--cherry);
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title">Catalogue des Événements</h1>
    <p class="font-mono" style="color: var(--teal);">Formations, Ateliers & Conférences</p>
</div>

@if(empty($evenements))
    <x-card>
        <div style="text-align: center; padding: 40px;">
            <p style="font-size: 1.2rem; margin-bottom: 20px;">Aucun événement disponible pour le moment.</p>
            <x-btn variant="secondary" href="/">Retour à l'accueil</x-btn>
        </div>
    </x-card>
@else
    <div class="events-grid">
        @foreach($evenements as $e)
            <div class="event-card">
                <x-card :title="$e['titre']">
                    <div class="event-meta">
                        @php
                            $variant = 'waiting';
                            if($e['type_evenement'] == 'formation') $variant = 'cherry';
                            if($e['type_evenement'] == 'atelier') $variant = 'valid';
                        @endphp
                        <x-badge :variant="$variant">{{ $e['type_evenement'] }}</x-badge>
                        <span>{{ \Carbon\Carbon::parse($e['date_debut'])->format('d/m/Y') }}</span>
                    </div>

                    <p style="margin-bottom: 20px; color: #555; line-height: 1.5; height: 4.5em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                        {{ $e['description'] }}
                    </p>

                    <div style="font-size: 0.9rem; margin-bottom: 10px;">
                        <strong>Format:</strong> {{ ucfirst($e['format']) }}<br>
                        <strong>Lieu:</strong> {{ $e['lieu'] ?? 'En ligne' }}
                    </div>

                    <div class="event-footer">
                        <div>
                            <span class="price-tag">{{ $e['prix'] > 0 ? number_format($e['prix'], 2) . '€' : 'GRATUIT' }}</span>
                            <div class="seats-left {{ $e['nb_places_dispo'] <= 3 ? 'seats-low' : '' }}">
                                {{ $e['nb_places_dispo'] }} places restantes
                            </div>
                        </div>
                        <x-btn variant="primary" size="sm" href="{{ route('evenements.show', $e['id_evenement']) }}">Détails</x-btn>
                    </div>
                </x-card>
            </div>
        @endforeach
    </div>
@endif
@endsection
