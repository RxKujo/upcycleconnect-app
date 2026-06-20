<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class PublicitesController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(config('services.api.url', env('API_URL', 'http://localhost:8080')), '/');
    }

    private function token(): ?string
    {
        return Session::get('pro_token');
    }

    public function index()
    {
        $response = Http::withToken($this->token())
            ->get($this->apiUrl() . '/api/v1/pro/publicites');

        if ($response->unauthorized()) {
            return redirect()->route('pro.login');
        }

        return view('professionnel.publicites.index', [
            'publicites' => $response->json() ?? [],
        ]);
    }

    public function create()
    {
        return view('professionnel.publicites.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre'      => 'required|string|max:200',
            'visuel_url' => 'nullable|url|max:500',
            'url_cible'  => 'nullable|url|max:500',
            'date_debut' => 'nullable|date',
            'date_fin'   => 'nullable|date|after_or_equal:date_debut',
        ]);

        $response = Http::withToken($this->token())
            ->post($this->apiUrl() . '/api/v1/pro/publicites', [
                'titre'      => $request->titre,
                'visuel_url' => $request->visuel_url ?? '',
                'url_cible'  => $request->url_cible ?? '',
                'date_debut' => $request->date_debut ? $request->date_debut . 'T00:00:00' : '',
                'date_fin'   => $request->date_fin   ? $request->date_fin   . 'T23:59:59' : '',
            ]);

        if ($response->forbidden()) {
            return back()->with('error', $response->json()['erreur'] ?? 'Limite de 5 publicités atteinte.');
        }

        if (!$response->successful()) {
            return back()->with('error', 'Erreur lors de la création de la publicité.');
        }

        return redirect()->route('pro.publicites.index')
            ->with('success', 'Publicité soumise — en attente de validation par l\'équipe UpcycleConnect.');
    }

    public function destroy(int $id)
    {
        $response = Http::withToken($this->token())
            ->delete($this->apiUrl() . '/api/v1/pro/publicites/' . $id);

        if (!$response->successful()) {
            return back()->with('error', 'Impossible de supprimer cette publicité (statut actif ou introuvable).');
        }

        return redirect()->route('pro.publicites.index')
            ->with('success', 'Publicité supprimée.');
    }
}
