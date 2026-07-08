<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MarcheController extends Controller
{
    protected function apiUrl(): string
    {
        return config('services.api.url');
    }

    protected function materiaux(): array
    {
        $base = $this->apiUrl();
        return Cache::remember('marche.materiaux', 300, function () use ($base) {
            try {
                $response = Http::timeout(5)->get($base . '/api/v1/public/materiaux');
                $data = $response->successful() ? $response->json() : [];
                return is_array($data) ? $data : [];
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    public function index()
    {
        $base = $this->apiUrl();

        $annonces = Cache::remember('marche.annonces', 60, function () use ($base) {
            try {
                $response = Http::timeout(5)->get($base . '/api/v1/public/annonces');
                return $response->successful() ? $response->json() : [];
            } catch (\Exception $e) {
                return [];
            }
        });

        return view('public.marche.index', [
            'annonces'  => $annonces,
            'materiaux' => $this->materiaux(),
        ]);
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

        return view('public.marche.show', [
            'annonce'   => $annonce,
            'materiaux' => $this->materiaux(),
        ]);
    }
}
