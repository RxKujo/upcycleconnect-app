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
        $r = Http::withToken($this->token())->timeout(5)
            ->get($this->api() . '/api/v1/salarie/idees');
        $idees = $r->successful() ? $r->json() : [];
        return view('salarie.boite-idees.index', compact('idees'));
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
