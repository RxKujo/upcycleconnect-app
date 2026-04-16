<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    public function create()
    {
        $types = [
            'formation' => __('Formation'),
            'atelier' => __('Atelier'),
            'conference' => __('Conférence'),
        ];
        $formats = [
            'presentiel' => __('Présentiel'),
            'distanciel' => __('Distanciel'),
            'hybride' => __('Hybride'),
        ];

        return view('admin.evenements.form', compact('types', 'formats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'type_evenement' => 'required|string',
            'format' => 'required|string',
            'date_debut' => 'required|date_format:Y-m-d H:i:s|after_or_equal:today',
            'date_fin' => 'required|date_format:Y-m-d H:i:s|after:date_debut',
            'nb_places_total' => 'required|integer|min:1',
            'prix' => 'required|numeric|min:0',
        ]);

        $payload = [
            'id_createur' => (int) session('admin_id'),
            'titre' => $request->titre,
            'description' => $request->description,
            'type_evenement' => $request->type_evenement,
            'format' => $request->format,
            'lieu' => $request->lieu,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'nb_places_total' => (int) $request->nb_places_total,
            'prix' => (float) $request->prix,
        ];

        $response = Http::withToken(session('admin_token'))
            ->post('http://localhost:8888/api/v1/admin/evenements', $payload);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Erreur lors de la création');
        }

        return redirect()->route('admin.evenements.index')->with('success', 'Événement créé avec succès');
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

    public function edit($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://localhost:8888/api/v1/admin/evenements/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.evenements.index')->with('error', 'Événement introuvable');
        }

        $evenement = $response->json();
        $types = [
            'formation' => __('Formation'),
            'atelier' => __('Atelier'),
            'conference' => __('Conférence'),
        ];
        $formats = [
            'presentiel' => __('Présentiel'),
            'distanciel' => __('Distanciel'),
            'hybride' => __('Hybride'),
        ];

        return view('admin.evenements.form', compact('evenement', 'types', 'formats'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'type_evenement' => 'required|string',
            'format' => 'required|string',
            'date_debut' => 'required|date_format:Y-m-d H:i:s|after_or_equal:today',
            'date_fin' => 'required|date_format:Y-m-d H:i:s|after:date_debut',
            'nb_places_total' => 'required|integer|min:1',
            'prix' => 'required|numeric|min:0',
        ]);

        $payload = [
            'titre' => $request->titre,
            'description' => $request->description,
            'type_evenement' => $request->type_evenement,
            'format' => $request->format,
            'lieu' => $request->lieu,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'nb_places_total' => (int) $request->nb_places_total,
            'prix' => (float) $request->prix,
        ];

        $response = Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}", $payload);

        if ($response->failed()) {
            return back()->withInput()->with('error', 'Erreur lors de la mise à jour');
        }

        return redirect()->route('admin.evenements.index')->with('success', 'Événement mis à jour avec succès');
    }

    public function destroy($id)
    {
        Http::withToken(session('admin_token'))
            ->delete("http://localhost:8888/api/v1/admin/evenements/{$id}");

        return redirect()->route('admin.evenements.index')->with('success', 'Événement supprimé avec succès');
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}/valider");

        return back()->with('success', 'Événement validé.');
    }

    public function attente($id)
    {
        Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}/attente");

        return back()->with('success', 'Événement mis en attente.');
    }

    public function refuser($id)
    {
        Http::withToken(session('admin_token'))
            ->put("http://localhost:8888/api/v1/admin/evenements/{$id}/refuser");

        return back()->with('success', 'Événement refusé.');
    }
}
