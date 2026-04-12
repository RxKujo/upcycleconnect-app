<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CategorieController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
            ->get('http://localhost:8080/api/v1/admin/categories');

        $categories = $response->successful() ? $response->json() : [];

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', ['categorie' => null]);
    }

    public function store(Request $request)
    {
        $request->validate(['nom' => 'required', 'description' => 'required']);

        $response = Http::withToken(session('admin_token'))
            ->post('http://localhost:8080/api/v1/admin/categories', [
                'nom' => $request->nom,
                'description' => $request->description,
            ]);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la création.');
        }

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get('http://localhost:8080/api/v1/admin/categories');

        $categories = $response->successful() ? $response->json() : [];
        $categorie = collect($categories)->firstWhere('id_categorie', (int) $id);

        if (!$categorie) {
            return redirect()->route('admin.categories.index')->with('error', 'Catégorie introuvable.');
        }

        return view('admin.categories.form', compact('categorie'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['nom' => 'required', 'description' => 'required']);

        $response = Http::withToken(session('admin_token'))
            ->put("http://localhost:8080/api/v1/admin/categories/{$id}", [
                'nom' => $request->nom,
                'description' => $request->description,
            ]);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la modification.');
        }

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie modifiée.');
    }

    public function destroy($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->delete("http://localhost:8080/api/v1/admin/categories/{$id}");

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la suppression.');
        }

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie supprimée.');
    }
}
