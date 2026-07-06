<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvenementController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }

    /** Liste des membres du staff pouvant animer une séance. */
    private function animateurs(): array
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/animateurs');
        return $r->successful() && is_array($r->json()) ? $r->json() : [];
    }

    public function index()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/evenements');
        $evenements = $r->successful() ? $r->json() : [];
        return view('salarie.evenements.index', compact('evenements'));
    }

    public function create()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/templates');
        $templates = $r->successful() ? $r->json() : [];
        return view('salarie.evenements.form', [
            'evenement'  => null,
            'templates'  => $templates,
            'animateurs' => $this->animateurs(),
        ]);
    }

    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);
        $r = Http::withToken($this->token())->asJson()->post($this->api() . '/api/v1/salarie/evenements', $payload);
        if (!$r->successful()) {
            return back()->withInput()->with('error', ($r->json('erreur') ?? 'Erreur création') . ' (' . $r->status() . ')');
        }
        return redirect()->route('salarie.evenements.index')->with('success', 'Événement créé, en attente de validation.');
    }

    public function edit($id)
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/evenements/' . $id);
        if (!$r->successful()) {
            abort(404);
        }
        return view('salarie.evenements.form', [
            'evenement'  => $r->json(),
            'templates'  => [],
            'animateurs' => $this->animateurs(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $payload = $this->validatePayload($request);
        $r = Http::withToken($this->token())->asJson()->put($this->api() . '/api/v1/salarie/evenements/' . $id, $payload);
        if (!$r->successful()) {
            return back()->withInput()->with('error', ($r->json('erreur') ?? 'Erreur mise à jour') . ' (' . $r->status() . ')');
        }
        return redirect()->route('salarie.evenements.index')->with('success', 'Événement mis à jour.');
    }

    private function validatePayload(Request $request): array
    {
        $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'type_evenement' => 'required|in:formation,atelier,conference,conseil',
            'nb_places_total' => 'required|integer|min:1',
            'prix' => 'required|numeric|min:0',
            'id_template' => 'nullable|integer',
            'seances' => 'required|array|min:1',
            'seances.*.titre' => 'nullable|string|max:200',
            'seances.*.format' => 'required|in:presentiel,distanciel',
            'seances.*.lieu' => 'nullable|string|max:300',
            'seances.*.date_debut' => 'required',
            'seances.*.date_fin' => 'required',
            'seances.*.animateurs' => 'nullable|array',
            'seances.*.animateurs.*' => 'integer',
        ], [
            'seances.required' => 'Ajoutez au moins une séance.',
            'seances.min' => 'Ajoutez au moins une séance.',
        ]);

        return [
            'titre' => $request->titre,
            'description' => $request->description,
            'type_evenement' => $request->type_evenement,
            'nb_places_total' => (int) $request->nb_places_total,
            'prix' => (float) $request->prix,
            'id_template' => $request->filled('id_template') ? (int) $request->id_template : null,
            'seances' => $this->buildSeances($request),
        ];
    }

    /** Normalise les séances soumises pour l'API. */
    private function buildSeances(Request $request): array
    {
        $seances = [];
        foreach ($request->input('seances', []) as $s) {
            if (empty($s['date_debut']) || empty($s['date_fin'])) {
                continue;
            }
            $seances[] = [
                'titre' => trim($s['titre'] ?? '') ?: '',
                'format' => $s['format'] ?? 'presentiel',
                'lieu' => trim($s['lieu'] ?? '') ?: '',
                'date_debut' => $s['date_debut'],
                'date_fin' => $s['date_fin'],
                'animateurs' => array_map('intval', $s['animateurs'] ?? []),
            ];
        }
        return $seances;
    }
}
