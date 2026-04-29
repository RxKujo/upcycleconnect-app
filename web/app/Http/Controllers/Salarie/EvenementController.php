<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvenementController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }

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
        return view('salarie.evenements.form', ['evenement' => null, 'templates' => $templates]);
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
        if (!$r->successful()) abort(404);
        return view('salarie.evenements.form', ['evenement' => $r->json(), 'templates' => []]);
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
        $data = $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'type_evenement' => 'required|in:formation,atelier,conference',
            'format' => 'required|in:presentiel,distanciel',
            'lieu' => 'nullable|string|max:300',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'nb_places_total' => 'required|integer|min:1',
            'prix' => 'required|numeric|min:0',
            'id_template' => 'nullable|integer',
        ]);
        $data['date_debut'] = date('Y-m-d H:i:s', strtotime($data['date_debut']));
        $data['date_fin'] = date('Y-m-d H:i:s', strtotime($data['date_fin']));
        $data['nb_places_total'] = (int) $data['nb_places_total'];
        $data['prix'] = (float) $data['prix'];
        $data['id_template'] = isset($data['id_template']) && $data['id_template'] !== '' ? (int) $data['id_template'] : null;
        if (empty($data['lieu'])) $data['lieu'] = '';
        return $data;
    }
}
