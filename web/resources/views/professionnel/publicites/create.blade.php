@extends('layouts.professionnel')

@section('title', 'Nouvelle publicité')

{{-- Création d'une publicité Pro (soumise à validation, 100 €/mois) --}}

@section('content')
<div class="main-content" style="max-width:680px;">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;"><span data-i18n="prod.ads.new">Nouvelle publicité</span></h1>
        <a href="{{ route('pro.publicites.index') }}" class="btn-secondary btn-sm">← Retour</a>
    </div>

    {{-- === Formulaire === --}}
    <div class="card">
        <form method="POST" action="{{ route('pro.publicites.store') }}">
            @csrf

            <div style="margin-bottom:20px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Titre <span style="color:#A4243B;">*</span></label>
                <input type="text" name="titre" value="{{ old('titre') }}" required maxlength="200"
                    style="width:100%; padding:12px; border:3px solid #120309; font-family:'Outfit',sans-serif; font-size:1rem;">
                @error('titre')<p style="color:#A4243B;font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:20px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.visual">URL du visuel (image)</span></label>
                <input type="url" name="visuel_url" value="{{ old('visuel_url') }}" maxlength="500"
                    placeholder="https://..."
                    style="width:100%; padding:12px; border:3px solid #120309; font-family:'Outfit',sans-serif; font-size:1rem;">
                @error('visuel_url')<p style="color:#A4243B;font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:20px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.url">URL de destination (clic)</span></label>
                <input type="url" name="url_cible" value="{{ old('url_cible') }}" maxlength="500"
                    placeholder="https://..."
                    style="width:100%; padding:12px; border:3px solid #120309; font-family:'Outfit',sans-serif; font-size:1rem;">
                @error('url_cible')<p style="color:#A4243B;font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            {{-- Période de diffusion --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
                <div>
                    <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.start">Date de début</span></label>
                    <input type="date" name="date_debut" value="{{ old('date_debut') }}"
                        style="width:100%; padding:12px; border:3px solid #120309; font-family:'DM Mono',monospace;">
                </div>
                <div>
                    <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.end">Date de fin</span></label>
                    <input type="date" name="date_fin" value="{{ old('date_fin') }}"
                        style="width:100%; padding:12px; border:3px solid #120309; font-family:'DM Mono',monospace;">
                </div>
            </div>

            {{-- Rappel tarif --}}
            <div style="background:#f9f5e7; border:2px solid #D8C99B; padding:14px; margin-bottom:24px; font-family:'DM Mono',monospace; font-size:0.8rem; color:#666;">
                💰 Tarif : <strong style="color:#120309;">100 €/mois</strong> — facturation mensuelle via Stripe.<br>
                ⏳ Votre publicité sera <strong>soumise à validation</strong> avant mise en ligne.
            </div>

            <button type="submit" class="btn-primary"><span data-i18n="prod.ads.submitfull">Soumettre la publicité</span></button>
        </form>
    </div>

</div>
@endsection
