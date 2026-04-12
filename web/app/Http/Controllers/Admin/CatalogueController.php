<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helpers\DateHelper;

class CatalogueController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('admin_token'))
            ->get('http://localhost:8080/api/catalogue');

        $items = $response->successful() ? $response->json() : [];

        return view('admin.catalogue.index', compact('items'));
    }

    public function create()
    {
        $categories = [
            'formation' => __('admin.formation'),
            'atelier' => __('admin.atelier'),
            'evenement' => __('admin.evenement'),
            'conseil' => __('admin.conseil'),
        ];
        $formats = [
            'presentiel' => __('admin.presentiel'),
            'distanciel' => __('admin.distanciel'),
        ];

        return view('admin.catalogue.form', compact('categories', 'formats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'categorie' => 'required|string',
            'format' => 'required|string',
            'date_debut' => 'required|date_format:Y-m-d H:i:s',
            'date_fin' => 'required|date_format:Y-m-d H:i:s',
            'nb_places_total' => 'required|integer|min:1',
            'prix' => 'required|numeric|min:0',
        ]);

        if (strtotime($request->date_debut) >= strtotime($request->date_fin)) {
            return back()->withInput()->with('error', 'La date de fin doit être après la date de début.');
        }

        $payload = [
            'id_createur' => session('admin_id'),
            'titre' => $request->titre,
            'description' => $request->description,
            'categorie' => $request->categorie,
            'format' => $request->format,
            'lieu' => $request->lieu,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'nb_places_total' => $request->nb_places_total,
            'prix' => $request->prix,
        ];

        $response = Http::withToken(session('admin_token'))
            ->post('http://localhost:8080/api/catalogue', $payload);

        if ($response->failed()) {
            $error = __('admin.erreur_creation');
            $json = $response->json();
            if (isset($json['erreur'])) {
                $error = $json['erreur'];
            }
            return back()->withInput()->with('error', $error);
        }

        return redirect()->route('admin.catalogue.index')->with('success', __('admin.catalogue_create_success'));
    }

    public function edit($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://localhost:8080/api/catalogue/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.catalogue.index')->with('error', __('admin.element_introuvable'));
        }

        $item = $response->json();
        $categories = [
            'formation' => __('admin.formation'),
            'atelier' => __('admin.atelier'),
            'evenement' => __('admin.evenement'),
            'conseil' => __('admin.conseil'),
        ];
        $formats = [
            'presentiel' => __('admin.presentiel'),
            'distanciel' => __('admin.distanciel'),
        ];

        return view('admin.catalogue.form', compact('item', 'categories', 'formats'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'required|string',
            'categorie' => 'required|string',
            'format' => 'required|string',
            'date_debut' => 'required|date_format:Y-m-d H:i:s',
            'date_fin' => 'required|date_format:Y-m-d H:i:s',
            'nb_places_total' => 'required|integer|min:1',
            'prix' => 'required|numeric|min:0',
        ]);

        if (strtotime($request->date_debut) >= strtotime($request->date_fin)) {
            return back()->withInput()->with('error', 'La date de fin doit être après la date de début.');
        }

        $payload = [
            'titre' => $request->titre,
            'description' => $request->description,
            'categorie' => $request->categorie,
            'format' => $request->format,
            'lieu' => $request->lieu,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'nb_places_total' => $request->nb_places_total,
            'prix' => $request->prix,
        ];

        $response = Http::withToken(session('admin_token'))
            ->put("http://localhost:8080/api/catalogue/{$id}", $payload);

        if ($response->failed()) {
            $error = __('admin.erreur_mise_a_jour');
            $json = $response->json();
            if (isset($json['erreur'])) {
                $error = $json['erreur'];
            }
            return back()->withInput()->with('error', $error);
        }

        return redirect()->route('admin.catalogue.index')->with('success', __('admin.catalogue_update_success'));
    }

    public function destroy($id)
    {
        Http::withToken(session('admin_token'))
            ->delete("http://localhost:8080/api/catalogue/{$id}");

        return redirect()->route('admin.catalogue.index')->with('success', __('admin.catalogue_delete_success'));
    }

    public function show($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://localhost:8080/api/catalogue/{$id}");

        if ($response->failed()) {
            return redirect()->route('admin.catalogue.index')->with('error', __('admin.element_introuvable'));
        }

        $item = $response->json();
        $reservations = [];
        $reservationsResponse = Http::withToken(session('admin_token'))
            ->get("http://localhost:8080/api/catalogue/{$id}/reservations");

        if ($reservationsResponse->successful()) {
            $reservations = $reservationsResponse->json();
        }

        return view('admin.catalogue.show', compact('item', 'reservations'));
    }

    public function valider($id)
    {
        Http::withToken(session('admin_token'))
            ->post("http://localhost:8080/api/catalogue/{$id}/valider");

        return back()->with('success', __('admin.catalogue_valide_success'));
    }

    public function reservations($id)
    {
        $response = Http::withToken(session('admin_token'))
            ->get("http://localhost:8080/api/catalogue/{$id}/reservations");

        if ($response->failed()) {
            return redirect()->route('admin.catalogue.index')->with('error', __('admin.erreur_chargement_reservations'));
        }

        $reservations = $response->json();
        return view('admin.catalogue.reservations', compact('reservations', 'id'));
    }
}
