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
        // L'appel réseau est dans le try ; le abort() est volontairement HORS du try,
        // sinon l'HttpException (404) serait rattrapée et transformée en 500.
        try {
            $response = Http::timeout(5)->get("{$this->apiUrl}/api/v1/evenements/{$id}");
        } catch (\Exception $e) {
            abort(503, 'Service indisponible, réessayez dans un instant.');
        }

        if (!$response->successful()) {
            abort(404, 'Événement non trouvé');
        }

        return view('catalogue.show', ['evenement' => $response->json()]);
    }
}
