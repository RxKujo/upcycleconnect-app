@extends('layouts.admin')
@section('title', 'Utilisateur #' . $utilisateur['id_utilisateur'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Utilisateur #{{ $utilisateur['id_utilisateur'] }}</h1>
    <a href="{{ route('admin.utilisateurs.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="cursor: default; transform: none;">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Nom</span>
            <p class="info-value">{{ $utilisateur['nom'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Prénom</span>
            <p class="info-value">{{ $utilisateur['prenom'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Email</span>
            <p class="info-value">{{ $utilisateur['email'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Rôle</span>
            <p class="info-value"><span class="badge badge-waiting">{{ $utilisateur['role'] }}</span></p>
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
            <span class="info-label">Inscription</span>
            <p class="info-value">{{ $utilisateur['date_creation'] ?? '—' }}</p>
        </div>
    </div>
</div>

<div class="action-cell" style="margin-top: 24px;">
    @if($utilisateur['est_banni'])
        <form action="{{ route('admin.utilisateurs.unban', $utilisateur['id_utilisateur']) }}" method="POST">
            @csrf
            <button type="submit" class="btn-success">Débannir</button>
        </form>
    @else
        <form action="{{ route('admin.utilisateurs.ban', $utilisateur['id_utilisateur']) }}" method="POST">
            @csrf
            <input type="hidden" name="date_fin_ban" value="2099-12-31">
            <button type="submit" class="btn-danger">Bannir</button>
        </form>
    @endif
</div>
@endsection
