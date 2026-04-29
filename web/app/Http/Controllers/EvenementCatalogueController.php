<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvenementCatalogueController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.api.url');
    }

    public function index()
    {
        try {
            $response = Http::timeout(5)->get("{$this->apiUrl}/api/v1/evenements/catalogue");
            $evenements = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            $evenements = [];
        }

        return view('catalogue.index', compact('evenements'));
    }

    public function show($id)
    {
        try {
            $response = Http::timeout(5)->get("{$this->apiUrl}/api/v1/evenements/{$id}");

            if (!$response->successful()) {
                abort(404, 'Événement non trouvé');
            }

            $evenement = $response->json();
        } catch (\Exception $e) {
            abort(500, 'Erreur de connexion à l\'API');
        }

        return view('catalogue.show', compact('evenement'));
    }
}
