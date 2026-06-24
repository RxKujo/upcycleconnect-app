<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AlertesController extends Controller
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
            ->get($this->apiUrl() . '/api/v1/pro/alertes');

        if ($response->unauthorized()) {
            return redirect()->route('pro.login');
        }

        $planResp = Http::withToken($this->token())
            ->get($this->apiUrl() . '/api/v1/utilisateurs/me');
        $plan = $planResp->json()['plan'] ?? null;

        $matResp = Http::withToken($this->token())
            ->get($this->apiUrl() . '/api/v1/public/materiaux');
        $materiaux = $matResp->successful() ? $matResp->json() : [];
        if (!is_array($materiaux)) {
            $materiaux = [];
        }

        return view('professionnel.alertes.index', [
            'alertes'   => $response->json() ?? [],
            'plan'      => $plan,
            'materiaux' => $materiaux,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'materiau' => 'required|in:bois,metal,textile,plastique,verre,electronique,autre',
            'rayon_km' => 'nullable|integer|min:1|max:500',
        ]);

        $response = Http::withToken($this->token())
            ->post($this->apiUrl() . '/api/v1/pro/alertes', [
                'materiau' => $request->materiau,
                'rayon_km' => (int) ($request->rayon_km ?? 10),
            ]);

        $erreur = match(true) {
            $response->forbidden()      => $response->json()['erreur'] ?? 'Limite d\'alertes atteinte.',
            $response->status() === 409 => $response->json()['erreur'] ?? 'Vous avez déjà une alerte pour ce matériau.',
            !$response->successful()    => $response->json()['erreur'] ?? 'Impossible de créer l\'alerte.',
            default                     => null,
        };

        if ($erreur) {
            return back()->with('error', $erreur);
        }

        return redirect()->route('pro.alertes.index')
            ->with('success', 'Alerte créée avec succès.');
    }

    public function destroy(int $id)
    {
        Http::withToken($this->token())
            ->delete($this->apiUrl() . '/api/v1/pro/alertes/' . $id);

        return redirect()->route('pro.alertes.index')
            ->with('success', 'Alerte supprimée.');
    }
}
