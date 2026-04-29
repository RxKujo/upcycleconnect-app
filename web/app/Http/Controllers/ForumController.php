<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ForumController extends Controller
{
    protected function apiUrl(): string
    {
        return config('services.api.url');
    }

    public function index()
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/forum');
            $sujets = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            $sujets = [];
        }

        return view('public.forum.index', compact('sujets'));
    }

    public function show($id)
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/forum/' . $id);

            if (!$response->successful()) {
                abort(404, 'Sujet non trouvé');
            }

            $sujet = $response->json();
        } catch (\Exception $e) {
            abort(404, 'Sujet non trouvé');
        }

        return view('public.forum.show', compact('sujet'));
    }
}
