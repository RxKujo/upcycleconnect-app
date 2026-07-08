<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

// Admin : publicités (validation/refus, stats, aperçu rotation). Proxy vers l'API Go.
class PubliciteController extends Controller
{
    // --- Utilitaires (URL API + jeton) ---

    private function apiUrl(): string
    {
        return rtrim(config('services.api.url', env('API_URL', 'http://localhost:8080')), '/');
    }

    private function token(): ?string
    {
        return Session::get('admin_token');
    }

    // --- Lecture ---

    // Liste les publicités, filtrées par statut si fourni en query string.
    public function index(Request $request)
    {
        $statut = $request->query('statut', '');
        $url = $this->apiUrl() . '/api/v1/admin/publicites';
        if ($statut) {
            $url .= '?statut=' . urlencode($statut);
        }

        $response = Http::withToken($this->token())->get($url);

        return view('admin.publicites.index', [
            'publicites'    => $response->json() ?? [],
            'statut_filtre' => $statut,
        ]);
    }

    // --- Actions de modération ---

    // Valide une publicité et la met en ligne.
    public function valider(int $id)
    {
        Http::withToken($this->token())
            ->put($this->apiUrl() . '/api/v1/admin/publicites/' . $id . '/valider');

        return redirect()->route('admin.publicites.index')
            ->with('success', 'Publicité validée et mise en ligne.');
    }

    // Refuse une publicité (enregistre le motif).
    public function refuser(Request $request, int $id)
    {
        Http::withToken($this->token())->asJson()
            ->put($this->apiUrl() . '/api/v1/admin/publicites/' . $id . '/refuser', [
                'motif' => $request->input('motif', ''),
            ]);

        return redirect()->route('admin.publicites.index', ['statut' => 'en_attente'])
            ->with('success', 'Publicité refusée.');
    }

    // --- Statistiques & rotation ---

    // Stats d'affichage/clics des publicités.
    public function stats()
    {
        $r = Http::withToken($this->token())->timeout(5)
            ->get($this->apiUrl() . '/api/v1/admin/publicites/stats');
        $stats = $r->successful() ? ($r->json() ?? []) : [];
        return view('admin.publicites.stats', compact('stats'));
    }

    // Aperçu des publicités en rotation.
    public function rotation()
    {
        $r = Http::withToken($this->token())->timeout(5)
            ->get($this->apiUrl() . '/api/v1/admin/publicites/rotation');
        $data = $r->successful()
            ? ($r->json() ?? ['pubs_actives' => [], 'description' => ''])
            : ['pubs_actives' => [], 'description' => ''];
        return view('admin.publicites.rotation', compact('data'));
    }
}
