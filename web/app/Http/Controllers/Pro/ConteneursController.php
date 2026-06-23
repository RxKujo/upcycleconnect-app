<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ConteneursController extends Controller
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
            ->get($this->apiUrl() . '/api/v1/pro/conteneurs/commandes');

        if ($response->unauthorized()) {
            return redirect()->route('pro.login');
        }

        return view('professionnel.conteneurs.index', [
            'commandes' => $response->json() ?? [],
        ]);
    }

    public function historique()
    {
        $response = Http::withToken($this->token())
            ->get($this->apiUrl() . '/api/v1/pro/conteneurs/historique');

        if ($response->unauthorized()) {
            return redirect()->route('pro.login');
        }

        return view('professionnel.conteneurs.historique', [
            'recuperations' => $response->json() ?? [],
        ]);
    }

    public function validerReception(Request $request)
    {
        $request->validate([
            'code_barre' => 'required|string',
        ]);

        $response = Http::withToken($this->token())
            ->post($this->apiUrl() . '/api/v1/pro/conteneurs/valider-reception', [
                'code_barre' => $request->code_barre,
            ]);

        if ($response->status() === 410) {
            return back()->with('error', 'Délai de récupération dépassé (7 jours). Contactez le support.');
        }

        if (!$response->successful()) {
            return back()->with('error', $response->json()['erreur'] ?? 'Code-barre invalide ou déjà utilisé.');
        }

        return redirect()->route('pro.conteneurs.index')
            ->with('success', 'Réception validée — commande marquée comme récupérée.');
    }
}
