<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UtilisateurController extends Controller
{
    private $apiUrl = 'http://api:8888/api/v1/admin/utilisateurs';

    public function index(Request $request)
    {
        $params = [];
        if ($request->filled('role')) $params['role'] = $request->input('role');
        if ($request->filled('est_banni')) $params['est_banni'] = $request->input('est_banni');
        if ($request->filled('search')) $params['search'] = $request->input('search');
        if ($request->filled('page')) $params['page'] = $request->input('page');

        $response = Http::withToken(session('admin_token'))
            ->get($this->apiUrl, $params);

        $data = $response->successful() ? $response->json() : [];

        $utilisateurs = $data['utilisateurs'] ?? $data ?? [];
        $pagination = [
            'total' => $data['total'] ?? 0,
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 20,
            'total_pages' => $data['total_pages'] ?? 1,
        ];

        return view('admin.utilisateurs.index', compact('utilisateurs', 'pagination'));
    }

    public function show($id)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Session expirée. Reconnectez-vous.');
        }

        $response = Http::withToken($token)
            ->get("{$this->apiUrl}/{$id}");

        if ($response->failed()) {
            \Log::error('GetUtilisateur failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'id' => $id,
            ]);
            return redirect()->route('admin.utilisateurs.index')->with('error', 'Utilisateur introuvable.');
        }

        $data = $response->json();
        $utilisateur = $data['utilisateur'] ?? $data;
        $subscription = $data['subscription'] ?? null;

        $plansResp = Http::withToken(session('admin_token'))
            ->get('http://api:8888/api/v1/admin/abonnements');
        $abonnements = $plansResp->successful() ? $plansResp->json() : [];

        return view('admin.utilisateurs.show', compact('utilisateur', 'subscription', 'abonnements'));
    }

    public function ban(Request $request, $id)
    {
        $payload = [];
        if ($request->filled('date_fin_ban')) {
            $payload['date_fin_ban'] = $request->input('date_fin_ban');
        }

        $response = Http::withToken(session('admin_token'))
            ->put("{$this->apiUrl}/{$id}/ban", $payload);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du bannissement.');
        }

        return back()->with('success', 'Utilisateur banni.');
    }

    public function unban($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->put("{$this->apiUrl}/{$id}/unban");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du débannissement.');
        }

        return back()->with('success', 'Utilisateur débanni.');
    }

    public function delete($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->delete("{$this->apiUrl}/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.utilisateurs.index')->with('error', 'Erreur lors de la suppression.');
        }

        return redirect()->route('admin.utilisateurs.index')->with('success', 'Compte supprimé.');
    }
}
