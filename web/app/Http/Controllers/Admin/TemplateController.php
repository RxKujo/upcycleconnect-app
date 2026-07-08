<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Admin : modèles d'événements (CRUD + activation). Le champ `modele` = JSON pré-rempli
// pour le formulaire salarié. Proxy vers l'API Go.
class TemplateController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(config('services.api.url'), '/') . '/api/v1/admin/templates';
    }

    // --- Lecture ---

    public function index()
    {
        $response  = Http::withToken(session('admin_token'))->get($this->apiUrl());
        $templates = $response->successful() ? ($response->json() ?: []) : [];

        return view('admin.templates.index', compact('templates'));
    }

    // --- Actions (CRUD) ---

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $response = Http::withToken(session('admin_token'))->asJson()->post($this->apiUrl(), $data);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Erreur lors de la création du modèle.');
        }
        return redirect()->route('admin.templates.index')->with('success', 'Modèle créé.');
    }

    public function update(Request $request, $id)
    {
        $data = $this->validatePayload($request);
        $data['actif'] = $request->boolean('actif');
        $response = Http::withToken(session('admin_token'))->asJson()->put($this->apiUrl() . "/{$id}", $data);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Erreur lors de la modification du modèle.');
        }
        return redirect()->route('admin.templates.index')->with('success', 'Modèle mis à jour.');
    }

    // Active/désactive un modèle.
    public function toggle($id)
    {
        $response = Http::withToken(session('admin_token'))->put($this->apiUrl() . "/{$id}/toggle");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors du changement de statut.');
        }
        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy($id)
    {
        $response = Http::withToken(session('admin_token'))->delete($this->apiUrl() . "/{$id}");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la suppression du modèle.');
        }
        return redirect()->route('admin.templates.index')->with('success', 'Modèle supprimé.');
    }

    // --- Validation & assemblage du payload ---

    // Valide et assemble le JSON `modele` consommé par le formulaire salarié.
    private function validatePayload(Request $request): array
    {
        $v = $request->validate([
            'nom_template'    => 'required|string|max:150',
            'description'     => 'nullable|string',
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
