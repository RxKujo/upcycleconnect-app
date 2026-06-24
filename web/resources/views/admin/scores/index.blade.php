@extends('layouts.admin')
@section('title', 'Upcycling Score — Paliers')

@section('content')
<div class="page-header">
    <h1 class="page-title">Upcycling Score</h1>
    <div class="action-cell">
        <form action="{{ route('admin.scores.recompute') }}" method="POST" onsubmit="return confirm('Recalculer tous les scores ? L\'opération peut prendre quelques secondes.')">
            @csrf
            <button type="submit" class="btn-secondary">⟳ Recalculer tous les scores</button>
        </form>
    </div>
</div>

<div class="card">
    <p style="font-family:'DM Mono',monospace;font-size:0.85rem;opacity:0.6;margin:0 0 28px;line-height:1.6;">
        Les paliers définissent les seuils de points, les couleurs et les certifications. Modifier un palier déclenche automatiquement un recalcul en arrière-plan.
    </p>

    @if(empty($paliers))
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;opacity:0.5;">Aucun palier configuré.</p>
    @else
    <div style="display:grid;gap:20px;">
        @foreach($paliers as $palier)
        <div style="border:3px solid {{ $palier['couleur'] ?? 'var(--coffee)' }};padding:24px;background:#fff;box-shadow:var(--shadow-sm);">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="width:18px;height:18px;border-radius:50%;background:{{ $palier['couleur'] ?? '#ccc' }};flex-shrink:0;"></div>
                <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;margin:0;color:{{ $palier['couleur'] ?? 'var(--coffee)' }};">
                    {{ $palier['nom'] }}
                </h3>
                <span style="font-family:'DM Mono',monospace;font-size:0.75rem;opacity:0.5;">≥ {{ $palier['seuil_min'] }} pts</span>
                @if($palier['confere_certification'])
                    <span class="badge badge-valid" style="margin-left:auto;">★ Certifié</span>
                @endif
                @if($palier['mise_en_avant'])
                    <span class="badge badge-waiting">Mis en avant</span>
                @endif
            </div>

            <form action="{{ route('admin.scores.palier.update', $palier['id_palier']) }}" method="POST">
                @csrf @method('PUT')
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom" value="{{ $palier['nom'] }}" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Seuil min (pts)</label>
                        <input type="number" name="seuil_min" value="{{ $palier['seuil_min'] }}" class="form-input" min="0" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Couleur (hex)</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" name="couleur" value="{{ $palier['couleur'] }}" style="width:44px;height:44px;border-radius:8px;border:2px solid var(--coffee);padding:2px;cursor:pointer;background:none;">
                            <input type="text" value="{{ $palier['couleur'] }}" class="form-input" style="flex:1;" oninput="this.previousElementSibling.value=this.value" readonly>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:24px;align-items:center;">
                    <label style="display:flex;align-items:center;gap:8px;font-family:'DM Mono',monospace;font-size:0.8rem;cursor:pointer;">
                        <input type="checkbox" name="confere_certification" value="1" {{ $palier['confere_certification'] ? 'checked' : '' }}>
                        Confère la certification
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-family:'DM Mono',monospace;font-size:0.8rem;cursor:pointer;">
                        <input type="checkbox" name="mise_en_avant" value="1" {{ $palier['mise_en_avant'] ? 'checked' : '' }}>
                        Mis en avant
                    </label>
                    <button type="submit" class="btn-success" style="margin-left:auto;">Enregistrer</button>
                </div>
            </form>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
