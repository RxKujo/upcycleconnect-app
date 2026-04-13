<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CommandeController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
            ->get('http://localhost:8888/api/v1/admin/commandes');

        $commandes = $response->successful() ? $response->json() : [];

        return view('admin.commandes.index', compact('commandes'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://localhost:8888/api/v1/admin/commandes/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.commandes.index')->with('error', 'Commande introuvable');
        }

        $commande = $response->json();
        return view('admin.commandes.show', compact('commande'));
    }

    public function updateStatut(Request $request, $id)
    {
        $request->validate(['statut' => 'required|string']);
        
        $payload = ['statut' => $request->statut];
        $response = Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/v1/admin/commandes/{$id}/statut", $payload);

        if ($response->failed()) {
            return back()->with('error', 'Erreur lors de la mise à jour du statut');
        }

        return back()->with('success', 'Statut de la commande mis à jour');
    }
}
