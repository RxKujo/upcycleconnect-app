<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ScoreController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.api.url') . '/api/v1/admin';
    }

    public function index()
    {
        $resp = Http::withToken(session('admin_token'))->get($this->apiUrl . '/paliers');
        $paliers = $resp->successful() ? $resp->json() : [];

        return view('admin.scores.index', compact('paliers'));
    }

    public function updatePalier(Request $request, string $id)
    {
        $payload = array_filter([
            'nom'                  => $request->input('nom'),
            'seuil_min'            => $request->input('seuil_min') !== null ? (int) $request->input('seuil_min') : null,
            'couleur'              => $request->input('couleur'),
            'confere_certification'=> $request->boolean('confere_certification'),
            'mise_en_avant'        => $request->boolean('mise_en_avant'),
        ], fn($v) => $v !== null);

        Http::withToken(session('admin_token'))->asJson()->put($this->apiUrl . '/paliers/' . $id, $payload);

        return redirect()->route('admin.scores.index')->with('success', 'Palier mis à jour.');
    }

    public function recompute()
    {
        $resp = Http::withToken(session('admin_token'))->post($this->apiUrl . '/scores/recompute');
        $data = $resp->json();
        $n = $data['utilisateurs_recalcules'] ?? '?';

        return redirect()->route('admin.scores.index')->with('success', "Scores recalculés pour {$n} utilisateurs.");
    }
}
