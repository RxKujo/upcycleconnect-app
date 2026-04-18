<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MarcheController extends Controller
{
    protected function apiUrl(): string
    {
        return env('API_URL', 'http://localhost:8888');
    }

    public function index()
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/annonces');
            $annonces = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            $annonces = [];
        }

        return view('public.marche.index', compact('annonces'));
    }

    public function show($id)
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/annonces/' . $id);

            if (!$response->successful()) {
                abort(404, 'Annonce non trouvée');
            }

            $annonce = $response->json();
        } catch (\Exception $e) {
            abort(404, 'Annonce non trouvée');
        }

        return view('public.marche.show', compact('annonce'));
    }
}
