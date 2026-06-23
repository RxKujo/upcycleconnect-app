<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlanningController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }

    public function index()
    {
        $r = Http::withToken($this->token())->timeout(5)
            ->get($this->api() . '/api/v1/salarie/planning');
        $items = $r->successful() ? $r->json() : [];
        return view('salarie.planning.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titre_creneau' => 'required|string|max:200',
            'description'   => 'nullable|string',
            'date_debut'    => 'required|date',
            'date_fin'      => 'required|date|after_or_equal:date_debut',
            'type_creneau'  => 'required|in:evenement,formation,reunion,travail,perso',
        ]);

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/salarie/planning', $data);

        if (!$r->successful()) {
            return back()->withInput()->with('error', $r->json('erreur') ?? 'Erreur création créneau');
        }
        return redirect()->route('salarie.planning.index')->with('success', 'Créneau ajouté.');
    }

    public function destroy($id)
    {
        $r = Http::withToken($this->token())
            ->delete($this->api() . '/api/v1/salarie/planning/' . $id);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Impossible de supprimer ce créneau.');
        }
        return redirect()->route('salarie.planning.index')->with('success', 'Créneau supprimé.');
    }
}
