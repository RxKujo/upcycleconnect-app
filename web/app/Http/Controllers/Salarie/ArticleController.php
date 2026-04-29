<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ArticleController extends Controller
{
    private function api(): string { return config('services.api.url'); }
    private function token(): string { return session('salarie_token'); }

    public function index()
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/articles');
        $articles = $r->successful() ? $r->json() : [];
        return view('salarie.articles.index', compact('articles'));
    }

    public function create()
    {
        return view('salarie.articles.form', ['article' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $r = Http::withToken($this->token())->post($this->api() . '/api/v1/salarie/articles', $data);
        if (!$r->successful()) return back()->withInput()->with('error', $r->json('erreur') ?? 'Erreur');
        return redirect()->route('salarie.articles.index')->with('success', 'Article créé.');
    }

    public function edit($id)
    {
        $r = Http::withToken($this->token())->timeout(5)->get($this->api() . '/api/v1/salarie/articles/' . $id);
        if (!$r->successful()) abort(404);
        return view('salarie.articles.form', ['article' => $r->json()]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validatePayload($request);
        $r = Http::withToken($this->token())->put($this->api() . '/api/v1/salarie/articles/' . $id, $data);
        if (!$r->successful()) return back()->withInput()->with('error', $r->json('erreur') ?? 'Erreur');
        return redirect()->route('salarie.articles.index')->with('success', 'Article mis à jour.');
    }

    public function destroy($id)
    {
        $r = Http::withToken($this->token())->delete($this->api() . '/api/v1/salarie/articles/' . $id);
        if (!$r->successful()) return back()->with('error', $r->json('erreur') ?? 'Erreur suppression');
        return redirect()->route('salarie.articles.index')->with('success', 'Article supprimé.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'titre' => 'required|string|max:300',
            'contenu' => 'required|string',
            'categorie' => 'nullable|string|max:100',
            'statut' => 'required|in:brouillon,publie,archive',
        ]);
    }
}
