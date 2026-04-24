@extends('layouts.public')

@section('title', 'Politique de confidentialité — RGPD')

@section('content')
<div class="page-container" style="max-width:800px;">
    <h1 class="page-title">Politique de Confidentialité</h1>
    <p class="font-mono" style="font-size:0.75rem; opacity:0.5; margin-bottom:40px;">Conformément au RGPD — Dernière mise à jour : avril 2026</p>

    <div style="font-size:0.95rem; line-height:1.8;">
        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; margin:32px 0 12px;">1. Données collectées</h2>
        <p>Nous collectons les données suivantes lors de l'inscription : nom, prénom, email, téléphone (facultatif), ville, adresse (facultative), photo de profil (facultative). Pour les professionnels : numéro SIRET, adresse de l'entreprise.</p>

        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; margin:32px 0 12px;">2. Finalité du traitement</h2>
        <p>Vos données sont utilisées pour : la gestion de votre compte, la mise en relation entre utilisateurs, la gestion des transactions, l'envoi de notifications et l'amélioration du service.</p>

        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; margin:32px 0 12px;">3. Anonymisation publique</h2>
        <p>Sur les pages publiques (consultables sans compte), les informations des vendeurs sont anonymisées : seuls le prénom et l'initiale du nom sont affichés. L'email, le téléphone et l'adresse complète ne sont jamais exposés aux visiteurs non connectés.</p>

        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; margin:32px 0 12px;">4. Durée de conservation</h2>
        <p>Les données sont conservées pendant la durée de votre compte, plus 3 ans après suppression pour obligations légales.</p>

        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; margin:32px 0 12px;">5. Vos droits</h2>
        <p>Vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité de vos données. Vous pouvez exercer ces droits depuis votre profil (export PDF) ou en contactant rgpd@upcycleconnect.com.</p>

        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; margin:32px 0 12px;">6. Sous-traitants</h2>
        <p>Nous utilisons les services suivants : Stripe (paiements), OneSignal (notifications push), OVH (hébergement). Chaque sous-traitant est conforme au RGPD.</p>
    </div>
</div>
@endsection
