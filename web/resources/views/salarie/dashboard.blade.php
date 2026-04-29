@extends('layouts.salarie')

@section('title', 'Tableau de bord')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tableau de bord</h1>
    <span class="font-mono" style="font-size:0.75rem; opacity:0.5;">Salarié #{{ session('salarie_id') }}</span>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <p class="stat-label">Événements en attente</p>
        <p class="stat-value">{{ $stats['evenements_attente'] ?? 0 }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Événements validés</p>
        <p class="stat-value">{{ $stats['evenements_valides'] ?? 0 }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Articles brouillon</p>
        <p class="stat-value">{{ $stats['articles_brouillon'] ?? 0 }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Articles publiés</p>
        <p class="stat-value">{{ $stats['articles_publies'] ?? 0 }}</p>
    </div>
    <div class="stat-card" style="background:#fdf3e3; border-color:var(--cherry);">
        <p class="stat-label">Signalements à traiter</p>
        <p class="stat-value" style="color:var(--cherry);">{{ $stats['signalements'] ?? 0 }}</p>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px;">
    <a href="/salarie/evenements/create" class="card" style="text-decoration:none; display:block;">
        <h3 class="font-bebas" style="font-size:1.6rem; color:var(--forest); margin:0 0 8px;">+ Nouvel événement</h3>
        <p style="font-size:0.95rem; opacity:0.7;">Formation, atelier ou conférence. Soumis à validation admin.</p>
    </a>
    <a href="/salarie/articles/create" class="card" style="text-decoration:none; display:block;">
        <h3 class="font-bebas" style="font-size:1.6rem; color:var(--forest); margin:0 0 8px;">+ Nouvel article</h3>
        <p style="font-size:0.95rem; opacity:0.7;">Rédiger un article News & Conseils en brouillon ou publié.</p>
    </a>
    <a href="/salarie/forum/signalements" class="card" style="text-decoration:none; display:block;">
        <h3 class="font-bebas" style="font-size:1.6rem; color:var(--cherry); margin:0 0 8px;">⚑ Modération</h3>
        <p style="font-size:0.95rem; opacity:0.7;">Traiter les messages signalés par la communauté.</p>
    </a>
</div>
@endsection
