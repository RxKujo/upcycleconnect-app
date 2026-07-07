<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MaterielController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }
    private function url(): string { return $this->api() . '/api/v1/salarie/materiels'; }

    public function index()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->url());
        $materiels = $r->successful() ? ($r->json() ?: []) : [];

        return view('salarie.materiels.index', compact('materiels'));
    }

    public function show($id)
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->url() . "/{$id}");
        if (!$r->successful()) {
            return redirect()->route('salarie.materiels.index')->with('error', 'Matériel introuvable.');
        }
        $materiel = $r->json();

        // Événements pour le formulaire de réservation.
        $re = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/evenements');
        $evenements = $re->successful() ? ($re->json() ?: []) : [];

        return view('salarie.materiels.show', compact('materiel', 'evenements'));
    }

    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);
        $r = Http::withToken($this->token())->asJson()->post($this->url(), $payload);
        if (!$r->successful()) {
            return back()->withInput()->with('error', 'Erreur lors de la création du matériel.');
        }
        return redirect()->route('salarie.materiels.index')->with('success', 'Matériel ajouté.');
    }

    public function update(Request $request, $id)
    {
        $payload = $this->validatePayload($request);
        $r = Http::withToken($this->token())->asJson()->put($this->url() . "/{$id}", $payload);
        if (!$r->successful()) {
            return back()->withInput()->with('error', 'Erreur lors de la mise à jour.');
        }
        return redirect()->route('salarie.materiels.show', $id)->with('success', 'Matériel mis à jour.');
    }

    public function destroy($id)
    {
        $r = Http::withToken($this->token())->delete($this->url() . "/{$id}");
        if (!$r->successful()) {
            return back()->with('error', 'Erreur lors de la suppression.');
        }
        return redirect()->route('salarie.materiels.index')->with('success', 'Matériel supprimé.');
    }

    public function deletePhoto($id, $photoId)
    {
        $r = Http::withToken($this->token())->delete($this->url() . "/{$id}/photos/{$photoId}");
        if (!$r->successful()) {
            return back()->with('error', 'Erreur lors de la suppression de la photo.');
        }
        return back()->with('success', 'Photo supprimée.');
    }

    public function reserver(Request $request, $id)
    {
        $request->validate(['id_evenement' => 'nullable|integer']);
        $r = Http::withToken($this->token())->asJson()->post($this->url() . "/{$id}/reserver", [
            'id_evenement' => $request->filled('id_evenement') ? (int) $request->id_evenement : null,
        ]);
        if (!$r->successful()) {
            $msg = $r->status() === 409 ? 'Ce matériel est déjà réservé.' : 'Erreur lors de la réservation.';
            return back()->with('error', $msg);
        }
        return back()->with('success', 'Matériel réservé.');
    }

    public function retour($id)
    {
        $r = Http::withToken($this->token())->asJson()->post($this->url() . "/{$id}/retour", []);
        if (!$r->successful()) {
            return back()->with('error', 'Erreur lors du retour.');
        }
        return back()->with('success', 'Retour enregistré.');
    }

    /**
     * Valide les champs et prépare le payload (photos converties en base64 côté
     * client, transmises telles quelles à l'API qui les pousse sur S3).
     */
    private function validatePayload(Request $request): array
    {
        $v = $request->validate([
            'nom'            => 'required|string|max:200',
            'description'    => 'nullable|string|max:2000',
            'etat'           => 'required|in:neuf,bon,use,a_reparer',
            'est_disponible' => 'nullable|boolean',
            'image_base64'   => 'nullable|array',
            'image_base64.*' => 'string',
        ]);

        return [
            'nom'            => $v['nom'],
            'description'    => $v['description'] ?? null,
            'etat'           => $v['etat'],
            'est_disponible' => $request->boolean('est_disponible'),
            'images'         => $request->input('image_base64', []),
        ];
    }
}
