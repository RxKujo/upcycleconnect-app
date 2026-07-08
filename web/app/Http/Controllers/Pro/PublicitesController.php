<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

// Espace pro : publicités (liste, création avec visuel uploadé ou URL, suppression), via l'API Go.
class PublicitesController extends Controller
{
    // --- Utilitaires (URL API + token) ---

    private function apiUrl(): string
    {
        return rtrim(config('services.api.url', env('API_URL', 'http://localhost:8080')), '/');
    }

    private function token(): ?string
    {
        return Session::get('pro_token');
    }

    // --- Actions ---

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

    // Crée une publicité (visuel : upload base64 prioritaire, sinon URL ; limite de 5).
    public function store(Request $request)
    {
        $request->validate([
            'titre'         => 'required|string|max:200',
            'visuel_url'    => 'nullable|url|max:500',
            'visuel_base64' => 'nullable|string',
            'url_cible'     => 'nullable|url|max:500',
            'date_debut'    => 'nullable|date|after_or_equal:today',
            'date_fin'      => ['nullable', 'date', 'after_or_equal:today', 'after_or_equal:date_debut'],
        ], [
            'date_debut.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'date_fin.after_or_equal'   => 'La date de fin ne peut pas être dans le passé ni avant la date de début.',
        ]);

        // Le visuel peut être téléversé (fichier -> base64) OU fourni en URL.
        // L'upload a la priorité : on l'écrit sur le disque média et on garde
        // une URL complète dans visuel_url (compatible avec l'affichage existant).
        $visuelUrl = $request->visuel_url ?? '';
        if ($request->filled('visuel_base64')) {
            $saved = $this->saveBase64Visuel($request->visuel_base64);
            if ($saved === null) {
                return back()->with('error', 'Image invalide (formats acceptés : JPG, PNG, WEBP — max 5 Mo).')->withInput();
            }
            $visuelUrl = $saved;
        }

        $response = Http::withToken($this->token())
            ->post($this->apiUrl() . '/api/v1/pro/publicites', [
                'titre'      => $request->titre,
                'visuel_url' => $visuelUrl,
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

    // Décode un visuel base64, l'écrit sur le disque média et renvoie son URL publique (null si invalide).
    private function saveBase64Visuel(string $b64): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $b64, $m)) {
            return null;
        }
        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        if (!in_array($ext, ['jpg', 'png', 'webp'], true)) {
            return null;
        }
        $data = base64_decode(substr($b64, strpos($b64, ',') + 1), true);
        if ($data === false || strlen($data) > 5 * 1024 * 1024) {
            return null;
        }
        // Chemin volontairement NEUTRE (pas de « pub »/« publicite ») : sinon les
        // bloqueurs de pub (uBlock…) filtrent l'image par son URL, même côté admin.
        $key = 'visuels/vis-' . uniqid('', true) . '.' . $ext;
        Storage::disk(media_disk())->put($key, $data);

        return media_url($key);
    }

    // Supprime une publicité (échoue si active ou introuvable).
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
