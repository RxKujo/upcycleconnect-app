<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ConseilController extends Controller
{
    protected function apiUrl(): string
    {
        return config('services.api.url');
    }

    public function index()
    {
        $base = $this->apiUrl();

        $articles = Cache::remember('conseils.articles', 120, function () use ($base) {
            try {
                $response = Http::timeout(5)->get($base . '/api/v1/public/articles');
                return $response->successful() ? $response->json() : [];
            } catch (\Exception $e) {
                return [];
            }
        });

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
