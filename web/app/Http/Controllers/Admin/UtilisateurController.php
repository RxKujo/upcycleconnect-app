<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UtilisateurController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.api.url') . '/api/v1/admin/utilisateurs';
    }

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl);
        $utilisateurs = $response->successful() ? $response->json() : [];
        return view('admin.utilisateurs.index', compact('utilisateurs'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))->get("{$this->apiUrl}/{$id}");
        if ($response->failed()) {
            return redirect()->route('admin.utilisateurs.index')->with('error', 'Utilisateur introuvable.');
        }
        $utilisateur = $response->json();

        $abonnementsResp = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . '/api/v1/admin/abonnements');
        $abonnements = $abonnementsResp->successful() ? $abonnementsResp->json() : [];

        $souscriptionResp = Http::withToken(session('admin_token'))
            ->get("{$this->apiUrl}/{$id}/abonnement");
        $souscription = $souscriptionResp->successful() ? $souscriptionResp->json() : null;

        return view('admin.utilisateurs.show', compact('utilisateur', 'abonnements', 'souscription'));
    }

    public function ban(Request $request, $id)
    {
        $dateFin = $request->input('permanent') ? '2099-12-31' : $request->input('date_fin_ban');
        $response = Http::withToken(session('admin_token'))
            ->put("{$this->apiUrl}/{$id}/ban", ['date_fin_ban' => $dateFin]);
        return back()->with($response->successful() ? 'success' : 'error', $response->successful() ? 'Utilisateur banni.' : 'Erreur lors du bannissement.');
    }

    public function unban($id)
    {
        $response = Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/unban");
        return back()->with($response->successful() ? 'success' : 'error', $response->successful() ? 'Utilisateur débanni.' : 'Erreur lors du débannissement.');
    }

    public function changeRole(Request $request, $id)
    {
        $response = Http::withToken(session('admin_token'))
            ->put("{$this->apiUrl}/{$id}/role", ['role' => $request->input('role')]);
        return back()->with($response->successful() ? 'success' : 'error', $response->successful() ? 'Rôle mis à jour.' : 'Erreur lors du changement de rôle.');
    }

    public function delete($id)
    {
        $response = Http::withToken(session('admin_token'))->delete("{$this->apiUrl}/{$id}");
        return redirect()->route('admin.utilisateurs.index')
            ->with($response->successful() ? 'success' : 'error', $response->successful() ? 'Compte supprimé.' : 'Erreur lors de la suppression.');
    }

    public function assignAbonnement(Request $request, $id)
    {
        $payload = ['id_abonnement' => (int) $request->input('id_abonnement')];
        if ($request->input('date_fin')) {
            $payload['date_fin'] = $request->input('date_fin');
        }
        $response = Http::withToken(session('admin_token'))
            ->post("{$this->apiUrl}/{$id}/abonnement", $payload);
        return back()->with($response->successful() ? 'success' : 'error', $response->successful() ? 'Abonnement assigné.' : 'Erreur lors de l\'assignation.');
    }

    public function revokeAbonnement($id)
    {
        $response = Http::withToken(session('admin_token'))->delete("{$this->apiUrl}/{$id}/abonnement");
        return back()->with($response->successful() ? 'success' : 'error', $response->successful() ? 'Abonnement révoqué.' : 'Erreur lors de la révocation.');
    }
}
