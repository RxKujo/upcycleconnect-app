@extends('layouts.public')

@section('title', 'Tutoriels')

@section('styles')
.tutoriels-wrap { max-width: 900px; margin: 0 auto; padding: 60px 24px; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--coffee); margin-bottom: 8px; }
.page-sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; color: #666; text-transform: uppercase; margin-bottom: 48px; }
.etape-card { background: white; border: 3px solid var(--coffee); box-shadow: var(--shadow-sm); padding: 32px; margin-bottom: 24px; display: flex; gap: 24px; align-items: flex-start; }
.etape-numero { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--forest); line-height: 1; min-width: 48px; }
.etape-titre { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; margin: 0 0 8px; }
.etape-contenu { font-size: 1rem; color: #444; line-height: 1.6; }
.relaunch-btn { display: inline-flex; align-items: center; gap: 10px; font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; letter-spacing: 0.1em; text-transform: uppercase; background: var(--forest); color: var(--cream); border: 3px solid var(--coffee); padding: 14px 32px; cursor: pointer; box-shadow: var(--shadow-sm); transition: all 0.2s; margin-top: 40px; }
.relaunch-btn:hover { transform: translate(2px,2px); box-shadow: var(--shadow-hover); }
@endsection

@section('content')
<div class="tutoriels-wrap">
    <h1 class="page-title" data-i18n="tuto.title">Guide & Tutoriels</h1>
    <p class="page-sub" data-i18n="tuto.subtitle">Retrouvez ici les étapes du tutoriel de prise en main d'UpcycleConnect</p>

    <div id="etapes-list">
        <div style="font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.85rem;color:#999;" data-i18n="common.loading">Chargement...</div>
    </div>

    <button class="relaunch-btn" onclick="relancerTutoriel()">
        ↺ Relancer le tutoriel interactif
    </button>
</div>
@endsection

@section('scripts')
<script>
const API = '{{ config("services.api.public_url") }}';
const token = localStorage.getItem('uc_token');

async function loadEtapes() {
    const headers = token ? { 'Authorization': 'Bearer ' + token } : {};
    const r = await fetch(API + '/api/v1/tutoriel/etapes', { headers });
    if (!r.ok) return [];
    return await r.json();
}

async function init() {
    const etapes = await loadEtapes();
    const container = document.getElementById('etapes-list');
    if (!etapes.length) {
        container.innerHTML = '<p>Aucun tutoriel disponible pour le moment.</p>';
        return;
    }
    container.innerHTML = etapes.map(e => `
        <div class="etape-card">
            <div class="etape-numero">${e.ordre}</div>
            <div>
                <h3 class="etape-titre">${e.titre}</h3>
                <p class="etape-contenu">${e.contenu}</p>
            </div>
        </div>
    `).join('');
}

async function relancerTutoriel() {
    if (!token) {
        window.location.href = '/login?return=/tutoriels';
        return;
    }
    // Reset le statut tuto_vu côté client pour forcer le réaffichage
    sessionStorage.setItem('force_tutoriel', '1');
    window.location.href = '/';
}

init();
</script>
@endsection
