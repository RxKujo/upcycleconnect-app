<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConseilController extends Controller
{
    protected function apiUrl(): string
    {
        return env('API_URL', 'http://api:8888');
    }

    public function index()
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/articles');
            $articles = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            $articles = [];
        }

        return view('public.conseils.index', compact('articles'));
    }

    public function show($id)
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/v1/public/articles/' . $id);

            if (!$response->successful()) {
                abort(404, 'Article non trouvé');
            }

            $article = $response->json();
        } catch (\Exception $e) {
            abort(404, 'Article non trouvé');
        }

        return view('public.conseils.show', compact('article'));
    }
}
