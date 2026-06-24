<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class RessourceController extends Controller
{
    private function api(): string { return config('services.api.url'); }

    public function index()
    {
        return view('public.ressources.index');
    }

    public function show($id)
    {
        $r = Http::timeout(5)->get($this->api() . '/api/v1/public/articles/' . $id);
        if (!$r->successful()) {
            abort(404);
        }
        return view('public.ressources.show', ['article' => $r->json()]);
    }
}
