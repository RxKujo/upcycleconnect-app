<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Admin : notifications (journal, envoi groupé, préférences par utilisateur). Proxy vers l'API Go.
class NotificationController extends Controller
{
    // Raccourcis URL API + jeton admin.
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('admin_token'); }

    // --- Lecture ---

    // Journal filtré + liste des sites.
    public function index(Request $request)
    {
        $params = $request->only(['type', 'date_debut', 'date_fin', 'user_id']);
        $log = $this->callApi('/api/v1/admin/notifications/log', $params) ?? [];
        $sites = $this->callApi('/api/v1/admin/notifications/sites') ?? [];
        return view('admin.notifications.index', compact('log', 'sites'));
    }

    // --- Envoi ---

    // Envoi groupé (push/email) à un site ou un segment.
    public function sendGroupe(Request $request)
    {
        $data = $request->validate([
            'type_envoi' => 'required|in:push,email,groupe_push,groupe_email',
            'titre'      => 'nullable|string|max:255',
            'contenu'    => 'required|string',
            'id_site'    => 'nullable|integer',
            'segment'    => 'nullable|string|max:100',
        ]);

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/admin/notifications/groupe', $data);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur envoi groupe');
        }
        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notification groupe envoyée : ' . ($r->json('nb_destinataires') ?? '?') . ' destinataires.');
    }

    // --- Préférences utilisateur ---

    // Préférences de notification d'un utilisateur (JSON).
    public function getUserPrefs($userId)
    {
        $prefs = $this->callApi('/api/v1/admin/notifications/user/' . $userId) ?? [];
        return response()->json($prefs);
    }

    // Met à jour les préférences (push/email) d'un utilisateur.
    public function updateUserPrefs(Request $request, $userId)
    {
        $data = $request->validate([
            'notif_push_active'  => 'boolean',
            'notif_email_active' => 'boolean',
        ]);

        $r = Http::withToken($this->token())->asJson()
            ->put($this->api() . '/api/v1/admin/notifications/user/' . $userId, $data);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur mise à jour préférences');
        }
        return back()->with('success', 'Préférences mises à jour.');
    }

    // --- Utilitaires ---

    // GET générique vers l'API ; JSON ou null si échec.
    private function callApi(string $path, array $query = []): ?array
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . $path, $query);
        return $r->successful() ? $r->json() : null;
    }
}
