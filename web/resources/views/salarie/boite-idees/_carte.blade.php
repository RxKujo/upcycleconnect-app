{{-- Partial : carte d'une idée (flux + archives) --}}
@php
    $statuts   = config('idees.statuts');
    $stKey     = $idee['statut'] ?? config('idees.statut_defaut');
    $st        = $statuts[$stKey] ?? $statuts[config('idees.statut_defaut')];
    $mine      = ($idee['id_auteur'] ?? 0) == session('salarie_id');
    $canManage = ($isAdmin ?? false) || $mine;
    $isArchive = $isArchive ?? false;
    $id        = $idee['id_idee'] ?? 0;
    $nbVotes   = $idee['nb_votes'] ?? 0;
    $monVote   = (int) ($idee['mon_vote'] ?? 0);
    $ts        = isset($idee['date_publication']) ? strtotime($idee['date_publication']) : 0;
@endphp

<div class="idee-row" data-votes="{{ $nbVotes }}" data-date="{{ $ts }}" style="border-left:8px solid {{ $st['bg'] }};">

    {{-- Colonne vote --}}
    <div class="idee-votebox" data-id="{{ $id }}">
        <button type="button" class="vote-arrow up {{ $monVote === 1 ? 'active' : '' }}" data-val="1"
                title="J'aime" {{ $isArchive ? 'disabled' : '' }}>▲</button>
        <span class="vote-score {{ $nbVotes > 0 ? 'pos' : ($nbVotes < 0 ? 'neg' : '') }}">{{ $nbVotes }}</span>
        <button type="button" class="vote-arrow down {{ $monVote === -1 ? 'active' : '' }}" data-val="-1"
                title="Je n'aime pas" {{ $isArchive ? 'disabled' : '' }}>▼</button>
    </div>

    {{-- Colonne contenu --}}
    <div class="idee-row-main">
        <h3 class="idee-title">{{ $idee['titre'] ?? '—' }}</h3>
        <p class="idee-contenu">{{ $idee['contenu'] ?? '' }}</p>
        <div class="idee-meta">
            <span>Par {{ $idee['auteur_prenom'] ?? '' }} {{ $idee['auteur_nom_initiale'] ?? '' }} · {{ $ts ? date('d/m/Y', $ts) : '' }}</span>
            @if(!empty($idee['tags']))
                <span class="idee-meta-tags">
                    @foreach(array_slice(array_filter(array_map('trim', explode(',', $idee['tags']))), 0, 3) as $tag)
                        <span class="idee-tag">{{ $tag }}</span>
                    @endforeach
                </span>
            @endif
        </div>
    </div>

    {{-- Colonne statut + actions --}}
    <div class="idee-row-side">
        <div class="idee-side-top">
            <span class="idee-statut" style="background:{{ $st['bg'] }};color:{{ $st['text'] }};">{{ $st['label'] }}</span>
            @if(($isAdmin ?? false) && !$mine)
                <span class="idee-owner-tag">{{ $idee['auteur_prenom'] ?? '' }} {{ $idee['auteur_nom_initiale'] ?? '' }}</span>
            @endif
        </div>

        @if($canManage)
            <form action="{{ route('salarie.idees.statut', $id) }}" method="POST" style="margin:0;">
                @csrf @method('PUT')
                <select name="statut" class="idee-statut-select" onchange="this.form.submit()" aria-label="Changer le statut">
                    @foreach($statuts as $key => $cfg)
                        <option value="{{ $key }}" @selected($stKey === $key)>{{ $cfg['label'] }}</option>
                    @endforeach
                </select>
            </form>

            <div class="idee-actions">
                <button type="button" class="idee-act"
                        onclick="openEditModal({{ $id }}, @js($idee['titre'] ?? ''), @js($idee['contenu'] ?? ''), @js($idee['tags'] ?? ''))">
                    <span>✎</span> Modifier
                </button>

                @if($isArchive)
                    <form action="{{ route('salarie.idees.desarchiver', $id) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="idee-act"><span>↩</span> Désarchiver</button>
                    </form>
                @else
                    <form action="{{ route('salarie.idees.archiver', $id) }}" method="POST" style="margin:0;"
                          data-confirm="Archiver cette idée ? Elle quittera le flux principal.">
                        @csrf
                        <button type="submit" class="idee-act"><span>🗄</span> Archiver</button>
                    </form>
                @endif

                <form action="{{ route('salarie.idees.destroy', $id) }}" method="POST" data-confirm="Supprimer cette idée ?" style="margin:0;">
                    @csrf @method('DELETE')
                    <button type="submit" class="idee-act danger"><span>✕</span> Supprimer</button>
                </form>
            </div>
        @endif
    </div>
</div>
