<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class EvenementController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.api.url') . '/api/v1/admin/evenements';
    }

    private $types = [
        'formation'  => 'Formation',
        'atelier'    => 'Atelier',
        'conseil'    => 'Conseil',
    ];

    private $formats = [
        'presentiel' => 'Présentiel',
        'distanciel' => 'Distanciel',
    ];

    private function getUsers()
    {
        $resp = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . '/api/v1/admin/utilisateurs', ['limit' => 200]);
        if (!$resp->successful()) return [];
        $data = $resp->json();
        return $data['utilisateurs'] ?? [];
    }

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
