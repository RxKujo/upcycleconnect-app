@extends('layouts.professionnel')

@section('title', 'Historique des récupérations')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;">Mes récupérations</h1>
        <a href="{{ route('pro.conteneurs.index') }}" class="btn-secondary btn-sm">← En attente</a>
    </div>

    <div class="card">
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;">Historique</h2>

        @forelse($recuperations as $rec)
        @php
            $expiree = ($rec['statut'] ?? '') === 'expiree';
            $date = $expiree
                ? ($rec['date_limite_recuperation'] ?? null)
                : ($rec['date_recuperee'] ?? null);
        @endphp
        <div style="border:2px solid #120309; padding:20px; margin-bottom:16px; {{ $expiree ? 'background:#fdf3f4;' : '' }}">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                <div>
                    <div class="font-bebas" style="font-size:1.2rem;">{{ $rec['titre_annonce'] }}</div>
                    <div class="font-mono" style="font-size:0.72rem; color:#666; margin-top:4px;">
                        Commande #{{ $rec['id_commande'] }} · Conteneur {{ $rec['conteneur_ref'] }}
                    </div>
                    <div class="font-mono" style="font-size:0.72rem; color:#666;">
                        {{ $rec['adresse_conteneur'] }}
                    </div>
                </div>
                <div style="text-align:right;">
                    @if($expiree)
                        <span class="font-mono" style="font-size:0.7rem; font-weight:bold; color:#fff; background:#A4243B; padding:4px 10px; letter-spacing:0.05em;">
                            NON RÉCUPÉRÉ
                        </span>
                        @if($date)
                        <div class="font-mono" style="font-size:0.72rem; color:#A4243B; margin-top:8px;">
                            Délai dépassé le {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                        </div>
                        @endif
                    @else
                        <span class="font-mono" style="font-size:0.7rem; font-weight:bold; color:#fff; background:#244F26; padding:4px 10px; letter-spacing:0.05em;">
                            RÉCUPÉRÉ
                        </span>
                        @if($date)
                        <div class="font-mono" style="font-size:0.72rem; color:#666; margin-top:8px;">
                            Le {{ \Carbon\Carbon::parse($date)->format('d/m/Y à H:i') }}
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        @empty
            <p style="color:#666; font-style:italic; text-align:center; padding:32px 0;">
                Aucune récupération pour le moment.
            </p>
        @endforelse
    </div>

</div>
@endsection
