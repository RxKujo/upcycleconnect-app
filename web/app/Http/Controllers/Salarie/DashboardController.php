<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        $token = session('salarie_token');
        $api = config('services.api.url');
        $stats = ['evenements_attente' => 0, 'evenements_valides' => 0, 'articles_brouillon' => 0, 'articles_publies' => 0, 'signalements' => 0];

        try {
            $r = Http::withToken($token)->timeout(5)->get($api . '/api/v1/salarie/stats');
            if ($r->successful()) $stats = array_merge($stats, $r->json());
        } catch (\Exception $e) {}

        return view('salarie.dashboard', compact('stats'));
    }

    public function logout()
    {
        session()->forget(['salarie_token', 'salarie_role', 'salarie_id']);
        return redirect('/login');
    }
}
