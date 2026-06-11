<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class FormationController extends Controller
{
    protected function apiUrl(): string
    {
        return config('services.api.url');
    }

    public function index()
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/catalogue');
            $items = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            $items = [];
        }

        return view('public.formations.index', compact('items'));
    }

    public function show($id)
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/catalogue/' . $id);

            if (!$response->successful()) {
                abort(404, 'Formation non trouvée');
            }

            $item = $response->json();
        } catch (\Exception $e) {
            abort(404, 'Formation non trouvée');
        }

        return view('public.formations.show', compact('item'));
    }
}
