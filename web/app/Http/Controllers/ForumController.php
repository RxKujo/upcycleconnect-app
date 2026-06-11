<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ForumController extends Controller
{
    protected function apiUrl(): string
    {
        return config('services.api.url');
    }

    public function index()
    {
        $base = $this->apiUrl();

        $sujets = Cache::remember('forum.sujets', 30, function () use ($base) {
            try {
                $response = Http::timeout(5)->get($base . '/api/v1/public/forum');
                return $response->successful() ? $response->json() : [];
            } catch (\Exception $e) {
                return [];
            }
        });

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
