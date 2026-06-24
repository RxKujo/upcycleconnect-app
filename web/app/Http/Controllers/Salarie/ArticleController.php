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
        $r = Http::withToken($this->token())->asJson()->post($this->api() . '/api/v1/salarie/articles', $data);
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
        $r = Http::withToken($this->token())->asJson()->put($this->api() . '/api/v1/salarie/articles/' . $id, $data);
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
        $data = $request->validate([
            'titre' => 'required|string|max:300',
            'contenu' => 'required|string',
            'categorie' => 'nullable|in:' . implode(',', array_keys(config('articles.categories'))),
            'statut' => 'required|in:brouillon,publie,archive',
        ]);

        // Le contenu est du HTML issu de l'éditeur enrichi : on neutralise
        // les éléments/attributs dangereux avant stockage (defense-in-depth).
        $data['contenu'] = $this->sanitizeHtml($data['contenu']);

        return $data;
    }

    private function sanitizeHtml(string $html): string
    {
        // Supprime les balises dangereuses (avec ou sans contenu).
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*/?>#is', '', $html) ?? $html;
        // Supprime les gestionnaires d'événements inline (onclick, onerror…).
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
        // Neutralise les URL javascript:.
        $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1=$2#$2', $html) ?? $html;

        return $html;
    }
}
