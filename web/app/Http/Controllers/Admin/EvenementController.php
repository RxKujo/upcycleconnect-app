<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvenementController extends Controller
{
    private string $apiUrl;

    private array $types = [
        'formation' => 'Formation',
        'atelier'   => 'Atelier',
        'conseil'   => 'Conseil',
    ];

    private array $formats = [
        'presentiel' => 'Présentiel',
        'distanciel' => 'Distanciel',
    ];

    public function __construct()
    {
        $this->apiUrl = config('services.api.url') . '/api/v1/admin/evenements';
    }

    private function getUsers(): array
    {
        $resp = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . '/api/v1/admin/utilisateurs');
        if (!$resp->successful()) return [];
        $data = $resp->json();
        return is_array($data) ? $data : [];
    }

    public function index()
    {
        $response = Http::withToken(session('admin_token'))->get($this->apiUrl);
        $evenements = $response->successful() ? $response->json() : [];
        return view('admin.evenements.index', compact('evenements'));
    }

    public function create()
    {
        $users = $this->getUsers();
        return view('admin.evenements.form', [
            'types'   => $this->types,
            'formats' => $this->formats,
            'users'   => $users,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre'          => 'required|string|max:200',
            'type_evenement' => 'required|string',
            'format'         => 'required|string',
            'date_debut'     => 'required',
            'date_fin'       => 'required',
            'nb_places_total'=> 'required|integer|min:1',
            'prix'           => 'required|numeric|min:0',
            'description'    => 'required|string',
        ]);

        $payload = [
            'id_createur'    => (int) session('admin_id'),
            'titre'          => $request->titre,
            'description'    => $request->description,
            'type_evenement' => $request->type_evenement,
            'format'         => $request->format,
            'lieu'           => $request->lieu ?: null,
            'date_debut'     => $request->date_debut,
            'date_fin'       => $request->date_fin,
            'nb_places_total'=> (int) $request->nb_places_total,
            'prix'           => (float) $request->prix,
            'animateurs'     => array_map('intval', $request->input('animateurs', [])),
        ];

        $response = Http::withToken(session('admin_token'))->post($this->apiUrl, $payload);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Impossible de créer l\'événement.');
        }

        return redirect()->route('admin.evenements.index')->with('success', 'Événement créé.');
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
            return redirect()->route('admin.evenements.index')->with('error', 'Événement introuvable.');
        }

        $users = $this->getUsers();
        return view('admin.evenements.form', [
            'evenement' => $response->json(),
            'types'     => $this->types,
            'formats'   => $this->formats,
            'users'     => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titre'          => 'required|string|max:200',
            'type_evenement' => 'required|string',
            'format'         => 'required|string',
            'date_debut'     => 'required',
            'date_fin'       => 'required',
            'nb_places_total'=> 'required|integer|min:1',
            'prix'           => 'required|numeric|min:0',
            'description'    => 'required|string',
        ]);

        $payload = [
            'titre'          => $request->titre,
            'description'    => $request->description,
            'type_evenement' => $request->type_evenement,
            'format'         => $request->format,
            'lieu'           => $request->lieu ?: null,
            'date_debut'     => $request->date_debut,
            'date_fin'       => $request->date_fin,
            'nb_places_total'=> (int) $request->nb_places_total,
            'prix'           => (float) $request->prix,
            'animateurs'     => array_map('intval', $request->input('animateurs', [])),
        ];

        $response = Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}", $payload);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Impossible de modifier l\'événement.');
        }

        return redirect()->route('admin.evenements.show', $id)->with('success', 'Événement mis à jour.');
    }

    public function destroy($id)
    {
        Http::withToken(session('admin_token'))->delete("{$this->apiUrl}/{$id}");
        return redirect()->route('admin.evenements.index')->with('success', 'Événement supprimé.');
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/valider");
        return back()->with('success', 'Événement validé.');
    }

    public function refuser($id)
    {
        Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/refuser");
        return back()->with('success', 'Événement refusé.');
    }

    public function attente($id)
    {
        Http::withToken(session('admin_token'))->put("{$this->apiUrl}/{$id}/attente");
        return back()->with('success', 'Événement remis en attente.');
    }
}
