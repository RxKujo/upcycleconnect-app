<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class EvenementController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
            ->get('http://localhost:8888/api/v1/admin/evenements');

        $evenements = $response->successful() ? $response->json() : [];

        return view('admin.evenements.index', compact('evenements'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://localhost:8888/api/v1/admin/evenements/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.evenements.index')->with('error', 'Événement introuvable.');
        }

        return view('admin.evenements.show', ['evenement' => $response->json()]);
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}/valider");

        return back()->with('success', 'Événement validé.');
    }

    public function refuser($id)
    {
        Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}/refuser");

        return back()->with('success', 'Événement refusé.');
    }
}
