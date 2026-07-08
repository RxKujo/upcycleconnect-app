<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Admin : sites (points physiques), liste + CRUD. Proxy vers l'API Go.
class SiteController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(config('services.api.url'), '/') . '/api/v1/admin/sites';
    }

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl());
        $sites = $response->successful() ? ($response->json() ?: []) : [];

        return view('admin.sites.index', compact('sites'));
    }

    // --- Validation & actions (CRUD) ---

    // Règles partagées création/modification.
    private function rules(): array
    {
        return [
            'nom_site'    => 'required|string|min:2|max:200',
            'adresse'     => 'nullable|string|max:500',
            'ville'       => 'nullable|string|max:100',
            'code_postal' => 'nullable|string|max:10',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $response = Http::withToken(session('admin_token'))->asJson()->post($this->apiUrl(), $data);
        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la création du site.');
        }
        return redirect()->route('admin.sites.index')->with('success', 'Site créé.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate($this->rules());

        $response = Http::withToken(session('admin_token'))->asJson()->put($this->apiUrl() . "/{$id}", $data);
        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la modification du site.');
        }
        return redirect()->route('admin.sites.index')->with('success', 'Site mis à jour.');
    }

    // Supprime un site ; salariés et matériel rattachés sont détachés.
    public function destroy($id)
    {
        $response = Http::withToken(session('admin_token'))->delete($this->apiUrl() . "/{$id}");
        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la suppression du site.');
        }
        return redirect()->route('admin.sites.index')->with('success', 'Site supprimé. Salariés et matériel détachés.');
    }
}
