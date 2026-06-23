<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class PubliciteController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(config('services.api.url', env('API_URL', 'http://localhost:8080')), '/');
    }

    private function token(): ?string
    {
        return Session::get('admin_token');
    }

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

    public function valider(int $id)
    {
        Http::withToken($this->token())
            ->put($this->apiUrl() . '/api/v1/admin/publicites/' . $id . '/valider');

        return redirect()->route('admin.publicites.index')
            ->with('success', 'Publicité validée et mise en ligne.');
    }

    public function refuser(Request $request, int $id)
    {
        Http::withToken($this->token())->asJson()
            ->put($this->apiUrl() . '/api/v1/admin/publicites/' . $id . '/refuser', [
                'motif' => $request->input('motif', ''),
            ]);

        return redirect()->route('admin.publicites.index', ['statut' => 'en_attente'])
            ->with('success', 'Publicité refusée.');
    }

    public function stats()
    {
        $r = Http::withToken($this->token())->timeout(5)
            ->get($this->apiUrl() . '/api/v1/admin/publicites/stats');
        $stats = $r->successful() ? ($r->json() ?? []) : [];
        return view('admin.publicites.stats', compact('stats'));
    }

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
