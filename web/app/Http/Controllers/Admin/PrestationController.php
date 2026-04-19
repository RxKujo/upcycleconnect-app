<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PrestationController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . '/api/v1/admin/prestations');

        $prestations = $response->successful() ? $response->json() : [];

        return view('admin.prestations.index', compact('prestations'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . "/api/v1/admin/prestations/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.prestations.index')->with('error', 'Prestation introuvable.');
        }

        return view('admin.prestations.show', ['prestation' => $response->json()]);
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))
            ->put(config('services.api.url') . "/api/v1/admin/prestations/{$id}/valider");

        return back()->with('success', 'Prestation validée.');
    }

    public function refuser($id)
    {
        Http::withToken(session('admin_token'))
            ->put(config('services.api.url') . "/api/v1/admin/prestations/{$id}/refuser");

        return back()->with('success', 'Prestation refusée.');
    }
}
