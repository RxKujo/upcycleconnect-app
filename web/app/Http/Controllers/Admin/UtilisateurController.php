<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UtilisateurController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.api.url') . '/api/v1/admin/utilisateurs';
    }

    public function index(Request $request)
    {
        $response = Http::withToken(session('admin_token'))
            ->get('http://api:8888/api/v1/admin/utilisateurs');

        $utilisateurs = $response->successful() ? $response->json() : [];

        return view('admin.utilisateurs.index', compact('utilisateurs'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://api:8888/api/v1/admin/utilisateurs/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.utilisateurs.index')->with('error', 'Utilisateur introuvable.');
        }

        $utilisateur = $response->json();

        $plansResp = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . '/api/v1/admin/abonnements');
        $abonnements = $plansResp->successful() ? $plansResp->json() : [];

        return view('admin.utilisateurs.show', compact('utilisateur', 'subscription', 'abonnements'));
    }

    public function ban(Request $request, $id)
    {
        $response = Http::withToken(session('admin_token'))
            ->put("http://api:8888/api/v1/admin/utilisateurs/{$id}/ban", [
                'date_fin_ban' => $request->input('date_fin_ban', '2099-12-31'),
            ]);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du bannissement.');
        }

        return back()->with('success', 'Utilisateur banni.');
    }

    public function unban($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->put("http://api:8888/api/v1/admin/utilisateurs/{$id}/unban");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du débannissement.');
        }

        return back()->with('success', 'Utilisateur débanni.');
    }
}
