<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvenementController extends Controller
{
    private $apiUrl = 'http://api:8888/api/v1/admin/evenements';

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
            ->get('http://api:8888/api/v1/admin/utilisateurs', ['limit' => 200]);
        if (!$resp->successful()) return [];
        $data = $resp->json();
        return $data['utilisateurs'] ?? [];
    }

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl);
        $evenements = $response->successful() ? $response->json() : [];
        return view('admin.evenements.index', compact('evenements'));
    }

    public function create()
    {
        $types   = $this->types;
        $formats = $this->formats;
        $users   = $this->getUsers();
        return view('admin.evenements.form', compact('types', 'formats', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre'          => 'required|string|max:200',
            'description'    => 'required|string',
            'type_evenement' => 'required|string',
            'format'         => 'required|string',
            'date_debut'     => 'required|date_format:Y-m-d H:i:s|after_or_equal:today',
            'date_fin'       => 'required|date_format:Y-m-d H:i:s|after:date_debut',
            'nb_places_total'=> 'required|integer|min:1',
            'prix'           => 'required|numeric|min:0',
        ]);

        $payload = [
            'id_createur'    => (int) session('admin_id'),
            'titre'          => $request->titre,
            'description'    => $request->description,
            'type_evenement' => $request->type_evenement,
            'format'         => $request->format,
            'lieu'           => $request->lieu,
            'date_debut'     => $request->date_debut,
            'date_fin'       => $request->date_fin,
            'nb_places_total'=> (int) $request->nb_places_total,
            'prix'           => (float) $request->prix,
            'animateurs'     => array_map('intval', $request->input('animateurs', [])),
        ];

        $response = Http::withToken(session('admin_token'))->post($this->apiUrl, $payload);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Erreur lors de la création');
        }

        return redirect()->route('admin.evenements.index')->with('success', 'Événement créé avec succès');
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))->get("{$this->apiUrl}/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.evenements.index')->with('error', 'Événement introuvable.');
        }

        return view('admin.evenements.show', ['evenement' => $response->json()]);
    }

    public function edit($id)
    {
        $response = Http::withToken(session('admin_token'))->get("{$this->apiUrl}/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.evenements.index')->with('error', 'Événement introuvable');
        }

        $evenement = $response->json();
        $types     = $this->types;
        $formats   = $this->formats;
        $users     = $this->getUsers();

        return view('admin.evenements.form', compact('evenement', 'types', 'formats', 'users'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titre'          => 'required|string|max:200',
            'description'    => 'required|string',
            'type_evenement' => 'required|string',
            'format'         => 'required|string',
            'date_debut'     => 'required|date_format:Y-m-d H:i:s|after_or_equal:today',
            'date_fin'       => 'required|date_format:Y-m-d H:i:s|after:date_debut',
            'nb_places_total'=> 'required|integer|min:1',
            'prix'           => 'required|numeric|min:0',
        ]);

        $payload = [
            'titre'          => $request->titre,
            'description'    => $request->description,
            'type_evenement' => $request->type_evenement,
            'format'         => $request->format,
            'lieu'           => $request->lieu,
            'date_debut'     => $request->date_debut,
            'date_fin'       => $request->date_fin,
            'nb_places_total'=> (int) $request->nb_places_total,
            'prix'           => (float) $request->prix,
            'animateurs'     => array_map('intval', $request->input('animateurs', [])),
        ];

        $response = Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}", $payload);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Erreur lors de la mise à jour');
        }

        return redirect()->route('admin.evenements.index')->with('success', 'Événement mis à jour avec succès');
    }

    public function destroy($id)
    {
        Http::withToken(session('admin_token'))->delete("{$this->apiUrl}/{$id}");
        return redirect()->route('admin.evenements.index')->with('success', 'Événement supprimé avec succès');
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/valider");
        return back()->with('success', 'Événement validé.');
    }

    public function attente($id)
    {
        Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/attente");
        return back()->with('success', 'Événement mis en attente.');
    }

    public function refuser($id)
    {
        Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/refuser");
        return back()->with('success', 'Événement refusé.');
    }
}
