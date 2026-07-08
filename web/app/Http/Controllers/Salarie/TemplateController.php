<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Modèles d'événements réutilisables (préremplissage du formulaire de création).
 * Proxy vers l'API Go.
 */
class TemplateController extends Controller
{
    // --- Helpers API ---
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }
    private function url(): string { return $this->api() . '/api/v1/salarie/templates'; }

    // --- Lecture ---

    /** Affiche la liste des modèles. */
    public function index()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->url());
        $templates = $r->successful() ? ($r->json() ?: []) : [];
        return view('salarie.templates.index', compact('templates'));
    }

    // --- Écriture ---

    /** Crée un nouveau modèle. */
    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);
        $r = Http::withToken($this->token())->asJson()->post($this->url(), $payload);
        if (!$r->successful()) {
            return back()->withInput()->with('error', 'Erreur lors de la création du modèle.');
        }
        return redirect()->route('salarie.templates.index')->with('success', 'Modèle créé.');
    }

    /** Met à jour un modèle existant (réactivé au passage). */
    public function update(Request $request, $id)
    {
        $payload = $this->validatePayload($request);
        $payload['actif'] = true;
        $r = Http::withToken($this->token())->asJson()->put($this->url() . "/{$id}", $payload);
        if (!$r->successful()) {
            return back()->withInput()->with('error', 'Erreur lors de la modification du modèle.');
        }
        return redirect()->route('salarie.templates.index')->with('success', 'Modèle mis à jour.');
    }

    /** Supprime un modèle. */
    public function destroy($id)
    {
        $r = Http::withToken($this->token())->delete($this->url() . "/{$id}");
        if (!$r->successful()) {
            return back()->with('error', 'Erreur lors de la suppression du modèle.');
        }
        return redirect()->route('salarie.templates.index')->with('success', 'Modèle supprimé.');
    }

    // --- Validation ---

    /**
     * Valide les champs et assemble le JSON `modele` consommé par le formulaire de création.
     */
    private function validatePayload(Request $request): array
    {
        $v = $request->validate([
            'nom_template'    => 'required|string|max:150',
            'description'     => 'nullable|string|max:255',
            'm_titre'         => 'nullable|string|max:200',
            'm_description'   => 'nullable|string',
            'type_evenement'  => 'required|in:formation,atelier,conference',
            'format'          => 'required|in:presentiel,distanciel',
            'm_lieu'          => 'nullable|string|max:300',
            'nb_places_total' => 'required|integer|min:1',
            'prix'            => 'required|numeric|min:0',
        ]);

        return [
            'nom_template' => $v['nom_template'],
            'description'  => $v['description'] ?? '',
            'modele'       => [
                'titre'           => $v['m_titre'] ?? '',
                'description'     => $v['m_description'] ?? '',
                'type_evenement'  => $v['type_evenement'],
                'format'          => $v['format'],
                'lieu'            => $v['m_lieu'] ?? '',
                'nb_places_total' => (int) $v['nb_places_total'],
                'prix'            => (float) $v['prix'],
            ],
        ];
    }
}
