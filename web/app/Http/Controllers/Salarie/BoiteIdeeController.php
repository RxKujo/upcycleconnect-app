<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Boîte à idées collaborative : flux, archives, votes et modération de statut.
 * Proxy vers l'API Go. Un admin conserve son layout de back-office.
 */
class BoiteIdeeController extends Controller
{
    // --- Helpers API ---
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }

    // --- Lecture ---

    /** Affiche le flux d'idées actives et la liste des idées archivées. */
    public function index()
    {
        $base = $this->api() . '/api/v1/salarie/idees';

        // Flux principal (archivées exclues) — tri par défaut : popularité.
        $rFlux = Http::withToken($this->token())->timeout(5)->get($base, ['tri' => 'populaire']);
        $idees = $rFlux->successful() ? $rFlux->json() : [];

        // Archives (les siennes ; toutes pour un admin — géré côté API).
        $rArch = Http::withToken($this->token())->timeout(5)->get($base, ['archives' => 1]);
        $archivees = $rArch->successful() ? $rArch->json() : [];

        $isAdmin = session('salarie_role') === 'admin';
        // Un admin garde sa sidebar de back-office ; un salarié garde la sienne.
        $layout = $isAdmin ? 'layouts.admin' : 'layouts.salarie';

        return view('salarie.boite-idees.index', compact('idees', 'archivees', 'isAdmin', 'layout'));
    }

    // --- Modération (statut, archivage) ---

    /** Change le statut d'une idée (nouvelle, en cours, retenue…). */
    public function statut(Request $request, $id)
    {
        $data = $request->validate([
            'statut' => 'required|in:' . implode(',', array_keys(config('idees.statuts'))),
        ]);

        $r = Http::withToken($this->token())->asJson()
            ->put($this->api() . '/api/v1/salarie/idees/' . $id . '/statut', $data);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur changement de statut');
        }
        return back()->with('success', 'Statut mis à jour.');
    }

    /** Archive une idée (la retire du flux principal). */
    public function archiver($id)
    {
        $r = Http::withToken($this->token())
            ->post($this->api() . '/api/v1/salarie/idees/' . $id . '/archiver');

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur archivage');
        }
        return back()->with('success', 'Idée archivée.');
    }

    /** Restaure une idée archivée dans le flux principal. */
    public function desarchiver($id)
    {
        $r = Http::withToken($this->token())
            ->post($this->api() . '/api/v1/salarie/idees/' . $id . '/desarchiver');

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur désarchivage');
        }
        return back()->with('success', 'Idée désarchivée.');
    }

    // --- Écriture (proposition d'idées) ---

    /** Crée une nouvelle idée. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre'   => 'required|string|max:200',
            'contenu' => 'required|string',
            'tags'    => 'nullable|string|max:300',
        ]);

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/salarie/idees', $data);

        if (!$r->successful()) {
            return back()->withInput()->with('error', $r->json('erreur') ?? 'Erreur création');
        }
        return redirect()->route('salarie.idees.index')->with('success', 'Idée partagée !');
    }

    /** Met à jour une idée existante. */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'titre'   => 'required|string|max:200',
            'contenu' => 'required|string',
            'tags'    => 'nullable|string|max:300',
        ]);

        $r = Http::withToken($this->token())->asJson()
            ->put($this->api() . '/api/v1/salarie/idees/' . $id, $data);

        if (!$r->successful()) {
            return back()->withInput()->with('error', $r->json('erreur') ?? 'Erreur mise à jour');
        }
        return redirect()->route('salarie.idees.index')->with('success', 'Idée mise à jour.');
    }

    /** Enregistre un vote (+1 ou -1) et renvoie le résultat en JSON. */
    public function voter(Request $request, $id)
    {
        // Seules les valeurs +1 / -1 sont acceptées ; toute autre retombe à +1.
        $valeur = (int) $request->input('valeur', 1);
        if (!in_array($valeur, [1, -1], true)) {
            $valeur = 1;
        }

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/salarie/idees/' . $id . '/voter', ['valeur' => $valeur]);

        return response()->json($r->json() ?? [], $r->status());
    }

    /** Supprime définitivement une idée. */
    public function destroy($id)
    {
        $r = Http::withToken($this->token())
            ->delete($this->api() . '/api/v1/salarie/idees/' . $id);

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur suppression');
        }
        return redirect()->route('salarie.idees.index')->with('success', 'Idée supprimée.');
    }
}
