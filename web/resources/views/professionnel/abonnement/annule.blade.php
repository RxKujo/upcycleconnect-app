@extends('layouts.professionnel')
@section('title', 'Paiement annulé')

@section('styles')
<style>
    .annule-wrapper { max-width: 520px; margin: 80px auto; text-align: center; }
    .annule-icon { font-size: 3.5rem; margin-bottom: 24px; opacity: 0.5; }
    .annule-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.8rem; letter-spacing: 0.1em; color: var(--coffee); margin-bottom: 16px; }
    .annule-text { font-size: 1.05rem; color: var(--teal); line-height: 1.7; margin-bottom: 40px; }
    .annule-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
</style>
@endsection

@section('content')
<div class="annule-wrapper">
    <div class="annule-icon">✕</div>
    <h1 class="annule-title">Paiement annulé</h1>
    <p class="annule-text">
        Votre paiement a été annulé. Aucun montant n'a été débité.
        Vous pouvez retourner aux offres et réessayer quand vous le souhaitez.
    </p>
    <div class="annule-actions">
        <a href="/professionnel/abonnement" class="btn btn-primary">Voir les offres</a>
        <a href="/professionnel/profile" class="btn btn-secondary">Mon espace pro</a>
    </div>
</div>
@endsection
