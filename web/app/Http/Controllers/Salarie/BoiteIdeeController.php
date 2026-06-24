<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BoiteIdeeController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }

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

    public function archiver($id)
    {
        $r = Http::withToken($this->token())
            ->post($this->api() . '/api/v1/salarie/idees/' . $id . '/archiver');

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur archivage');
        }
        return back()->with('success', 'Idée archivée.');
    }

    public function desarchiver($id)
    {
        $r = Http::withToken($this->token())
            ->post($this->api() . '/api/v1/salarie/idees/' . $id . '/desarchiver');

        if (!$r->successful()) {
            return back()->with('error', $r->json('erreur') ?? 'Erreur désarchivage');
        }
        return back()->with('success', 'Idée désarchivée.');
    }

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

    public function voter(Request $request, $id)
    {
        $valeur = (int) $request->input('valeur', 1);
        if (!in_array($valeur, [1, -1], true)) {
            $valeur = 1;
        }

        $r = Http::withToken($this->token())->asJson()
            ->post($this->api() . '/api/v1/salarie/idees/' . $id . '/voter', ['valeur' => $valeur]);

        return response()->json($r->json() ?? [], $r->status());
    }

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
