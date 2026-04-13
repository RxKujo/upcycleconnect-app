<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CatalogueController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
            ->get('http://localhost:8888/api/catalogue');

        $items = $response->successful() ? $response->json() : [];

        return view('admin.catalogue.index', compact('items'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://localhost:8888/api/catalogue/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.catalogue.index')->with('error', 'Annonce introuvable');
        }

        $item = $response->json();
        return view('admin.catalogue.show', compact('item'));
    }

    public function destroy($id)
    {
        Http::withToken(session('admin_token'))
            ->delete("http://localhost:8888/api/catalogue/{$id}");

        return redirect()->route('admin.catalogue.index')->with('success', 'Annonce supprimée avec succès');
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/catalogue/{$id}/valider");

        return back()->with('success', 'Annonce validée');
    }

    public function refuser(Request $request, $id)
    {
        $request->validate(['motif_refus' => 'required|string']);
        
        Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/catalogue/{$id}/refuser", [
                'motif_refus' => $request->motif_refus
            ]);

        return back()->with('success', 'Annonce refusée');
    }
}
