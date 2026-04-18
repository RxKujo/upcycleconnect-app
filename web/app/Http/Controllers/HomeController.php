<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    protected function apiUrl(): string
    {
        return env('API_URL', 'http://localhost:8888');
    }

    public function index()
    {
        $base = $this->apiUrl();

        $annonces = Cache::remember('home.annonces', 60, function () use ($base) {
            try {
                $response = Http::timeout(5)->get($base . '/api/v1/public/annonces');
                $all = $response->successful() ? $response->json() : [];
                return array_slice($all, 0, 4);
            } catch (\Exception $e) {
                return [];
            }
        });

        $evenements = Cache::remember('home.evenements', 120, function () use ($base) {
            try {
                $response = Http::timeout(5)->get($base . '/api/v1/evenements/catalogue');
                $all = $response->successful() ? $response->json() : [];
                return array_slice($all, 0, 3);
            } catch (\Exception $e) {
                return [];
            }
        });

        $stats = Cache::remember('home.stats', 600, function () use ($base) {
            try {
                $response = Http::timeout(5)->get($base . '/api/v1/public/stats');
                return $response->successful() ? $response->json() : [
                    'objets_sauves' => 0,
                    'membres' => 0,
                    'ateliers_an' => 0,
                ];
            } catch (\Exception $e) {
                return ['objets_sauves' => 0, 'membres' => 0, 'ateliers_an' => 0];
            }
        });

        return view('public.home', compact('annonces', 'evenements', 'stats'));
    }
}
