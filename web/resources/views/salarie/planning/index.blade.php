@extends('layouts.salarie')

@section('title', 'Mon planning')

@section('styles')
<style>
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; border: var(--border); box-shadow: var(--shadow); margin-bottom: 32px; }
.calendar-day-label { background: var(--coffee); color: var(--wheat); font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; text-align: center; padding: 10px 4px; letter-spacing: 0.05em; }
.calendar-cell { min-height: 90px; background: white; border: 1px solid rgba(18,3,9,0.1); padding: 6px; position: relative; }
.calendar-cell.today { background: rgba(36,79,38,0.08); border-color: var(--forest); }
.calendar-cell.other-month { background: rgba(18,3,9,0.03); }
.cal-date { font-family: 'DM Mono', monospace; font-size: 0.7rem; font-weight: 700; color: var(--coffee); opacity: 0.5; margin-bottom: 4px; }
.cal-date.today-num { color: var(--forest); opacity: 1; font-size: 0.85rem; }
.cal-event { font-size: 0.7rem; background: var(--forest); color: var(--cream); padding: 2px 5px; margin-bottom: 2px; border-radius: 0; font-family: 'DM Mono', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-event.type-evenement { background: var(--teal); }
.cal-event.type-reunion { background: var(--cherry); }
.cal-event.type-formation { background: #6c5ce7; }
.cal-event.type-travail { background: var(--coffee); }
.cal-event.type-perso { background: #b2bec3; color: var(--coffee); }
.month-nav { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.month-title { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 0.08em; flex: 1; text-align: center; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title">Mon Planning</h1>
    <button class="btn-primary" onclick="document.getElementById('modal-add').style.display='flex'">
        + Ajouter un créneau
    </button>
</div>

{{-- Stats rapides --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Créneaux ce mois</div>
        <div class="stat-value">{{ count(array_filter($items, fn($i) => substr($i['date_debut'] ?? '', 0, 7) === date('Y-m'))) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total créneaux</div>
        <div class="stat-value">{{ count($items) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Événements liés</div>
        <div class="stat-value">{{ count(array_filter($items, fn($i) => ($i['type_creneau'] ?? '') === 'evenement')) }}</div>
    </div>
</div>

{{-- Navigation mois --}}
@php
    $moisCourant = request()->query('mois', date('Y-m'));
    [$annee, $mois] = explode('-', $moisCourant);
    $ts = mktime(0, 0, 0, (int)$mois, 1, (int)$annee);
    $moisPrev = date('Y-m', strtotime('-1 month', $ts));
    $moisNext = date('Y-m', strtotime('+1 month', $ts));
    $moisLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
@endphp

<div class="month-nav">
    <a href="?mois={{ $moisPrev }}" class="btn-secondary btn-sm">&larr;</a>
    <span class="month-title">{{ $moisLabels[(int)$mois - 1] }} {{ $annee }}</span>
    <a href="?mois={{ $moisNext }}" class="btn-secondary btn-sm">&rarr;</a>
</div>

{{-- Calendrier --}}
<div class="calendar-grid">
    @foreach(['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $j)
        <div class="calendar-day-label">{{ $j }}</div>
    @endforeach

    @php
        $premierJour = (int)date('N', $ts); // 1=lun, 7=dim
        $nbJours = (int)date('t', $ts);
        $aujourdhui = date('Y-m-d');
        // Index les items par date
        $itemsParDate = [];
        foreach ($items as $item) {
            $d = substr($item['date_debut'] ?? '', 0, 10);
            $itemsParDate[$d][] = $item;
        }
    @endphp

    {{-- Cases vides avant le 1er --}}
    @for($i = 1; $i < $premierJour; $i++)
        <div class="calendar-cell other-month"></div>
    @endfor

    @for($jour = 1; $jour <= $nbJours; $jour++)
        @php
            $dateStr = sprintf('%04d-%02d-%02d', $annee, $mois, $jour);
            $isToday = $dateStr === $aujourdhui;
            $eventsJour = $itemsParDate[$dateStr] ?? [];
        @endphp
        <div class="calendar-cell {{ $isToday ? 'today' : '' }}">
            <div class="cal-date {{ $isToday ? 'today-num' : '' }}">{{ $jour }}</div>
            @foreach($eventsJour as $ev)
                <div class="cal-event type-{{ $ev['type_creneau'] ?? 'travail' }}"
                     title="{{ $ev['titre_creneau'] ?? '' }}">
                    {{ Str::limit($ev['titre_creneau'] ?? '', 18) }}
                </div>
            @endforeach
        </div>
    @endfor

    {{-- Cases vides après le dernier --}}
    @php $dernierJour = (int)date('N', mktime(0,0,0,(int)$mois,$nbJours,(int)$annee)); @endphp
    @for($i = $dernierJour; $i < 7; $i++)
        <div class="calendar-cell other-month"></div>
    @endfor
</div>

{{-- Liste des créneaux --}}
<div class="card">
    <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 24px;">Liste des créneaux</h2>
    @if(empty($items))
        <p style="color:rgba(18,3,9,0.4);font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.9rem;">
            Aucun créneau planifié.
        </p>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item['titre_creneau'] ?? '—' }}</td>
                        <td><span class="badge badge-info">{{ $item['type_creneau'] ?? '—' }}</span></td>
                        <td>{{ isset($item['date_debut']) ? date('d/m/Y H:i', strtotime($item['date_debut'])) : '—' }}</td>
                        <td>{{ isset($item['date_fin']) ? date('d/m/Y H:i', strtotime($item['date_fin'])) : '—' }}</td>
                        <td>
                            <form action="{{ route('salarie.planning.destroy', $item['id_planning'] ?? 0) }}" method="POST"
                                  data-confirm="Supprimer ce créneau ?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Modal ajout créneau --}}
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:520px;position:relative;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Nouveau créneau</h2>
        <form action="{{ route('salarie.planning.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Titre <span style="color:var(--cherry)">*</span></label>
                <input type="text" name="titre_creneau" class="form-input" required maxlength="200" placeholder="Ex: Réunion équipe">
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type_creneau" class="form-select">
                    <option value="travail">Travail</option>
                    <option value="reunion">Réunion</option>
                    <option value="evenement">Événement</option>
                    <option value="formation">Formation</option>
                    <option value="perso">Personnel</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Début <span style="color:var(--cherry)">*</span></label>
                    <input type="datetime-local" name="date_debut" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fin <span style="color:var(--cherry)">*</span></label>
                    <input type="datetime-local" name="date_fin" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" style="min-height:80px;" placeholder="Détails optionnels…"></textarea>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary">Ajouter</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-add').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection
