<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CategorieObjetController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(config('services.api.url'), '/') . '/api/v1/admin/categories-objets';
    }

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl());
        $categories = $response->successful() ? ($response->json() ?: []) : [];

        return view('admin.categories-objets.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate(['nom' => 'required|string|min:2|max:100']);

        $response = Http::withToken(session('admin_token'))->asJson()
            ->post($this->apiUrl(), ['nom' => $request->nom]);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la création (catégorie déjà existante ?).');
        }
        return redirect()->route('admin.categories-objets.index')->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom'   => 'required|string|min:2|max:100',
            'actif' => 'nullable|boolean',
        ]);

        $response = Http::withToken(session('admin_token'))->asJson()
            ->put($this->apiUrl() . "/{$id}", [
                'nom'   => $request->nom,
                'actif' => $request->boolean('actif'),
            ]);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la modification.');
        }
        return redirect()->route('admin.categories-objets.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy($id)
    {
        $response = Http::withToken(session('admin_token'))->delete($this->apiUrl() . "/{$id}");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la suppression.');
        }
        return redirect()->route('admin.categories-objets.index')->with('success', 'Catégorie supprimée.');
    }
}
