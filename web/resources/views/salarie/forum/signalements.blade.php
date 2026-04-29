@extends('layouts.salarie')

@section('title', 'Signalements')

@section('content')
<div class="page-header">
    <h1 class="page-title">Signalements</h1>
    <span class="font-mono" style="font-size:0.85rem; opacity:0.6;">{{ count($items) }} signalement(s)</span>
</div>

@if(empty($items))
<div class="card" style="text-align:center; padding:60px 20px;">
    <h3 class="font-bebas" style="font-size:1.4rem;">Aucun signalement</h3>
    <p style="opacity:0.7;">La communauté est calme.</p>
</div>
@else
<div style="display:flex; flex-direction:column; gap:16px;">
    @foreach($items as $s)
    <div class="card" style="padding:24px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:12px;">
            <div>
                <p class="font-mono" style="font-size:0.7rem; opacity:0.6; margin:0 0 4px;">
                    Sujet :
                    <a href="/forum/{{ $s['id_sujet'] }}" target="_blank" style="color:var(--teal);">{{ $s['titre_sujet'] }}</a>
                </p>
                <p style="font-weight:600; margin:0;">Auteur du message : {{ $s['auteur_message'] }}</p>
                <p class="font-mono" style="font-size:0.7rem; opacity:0.5; margin:4px 0 0;">
                    Signalé le {{ \Carbon\Carbon::parse($s['date_signalement'])->format('d/m/Y H:i') }}
                </p>
            </div>
            <div>
                @if($s['statut'] === 'en_cours')
                    <span class="badge badge-waiting">En cours</span>
                @elseif($s['statut'] === 'traite')
                    <span class="badge badge-valid">Traité</span>
                @else
                    <span class="badge">Rejeté</span>
                @endif
                @if($s['est_masque'])
                    <span class="badge badge-refused" style="margin-left:6px;">Masqué</span>
                @endif
            </div>
        </div>

        <div style="border-left:3px solid var(--cherry); padding:12px 16px; background:#faf2f3; margin-bottom:12px; font-size:0.95rem;">
            <p style="margin:0; white-space:pre-line;">{{ $s['contenu'] }}</p>
        </div>

        @if(!empty($s['motif']))
        <p class="font-mono" style="font-size:0.72rem; opacity:0.6; margin:0 0 12px;">
            Motif déclaré : {{ $s['motif'] }}
        </p>
        @endif

        <div style="display:flex; gap:12px;">
            @if(!$s['est_masque'])
            <form action="{{ route('salarie.forum.masquer', $s['id_message']) }}" method="POST" style="display:inline;">
                @csrf @method('PUT')
                <button type="submit" class="btn-danger btn-sm">Masquer le message</button>
            </form>
            @else
            <form action="{{ route('salarie.forum.restaurer', $s['id_message']) }}" method="POST" style="display:inline;">
                @csrf @method('PUT')
                <button type="submit" class="btn-success btn-sm">Restaurer</button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
