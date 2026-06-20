<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(config('services.api.url', env('API_URL', 'http://localhost:8080')), '/');
    }

    private function token(): ?string
    {
        return Session::get('pro_token');
    }

    public function essential(Request $request)
    {
        $response = Http::withToken($this->token())
            ->get($this->apiUrl() . '/api/v1/pro/dashboard');

        if ($response->unauthorized()) {
            return redirect()->route('pro.login');
        }

        $data = $response->json();

        return view('professionnel.dashboard.essential', [
            'impact'          => $data['impact_ecologique'] ?? [],
            'stats_materiaux' => $data['stats_materiaux'] ?? [],
            'periode'         => $data['periode'] ?? '',
        ]);
    }

    public function expert(Request $request)
    {
        $response = Http::withToken($this->token())
            ->get($this->apiUrl() . '/api/v1/pro/dashboard/annuel');

        if ($response->forbidden()) {
            return redirect()->route('pro.dashboard.essential')
                ->with('error', 'Cette fonctionnalité nécessite un abonnement Expert Pro.');
        }

        if ($response->unauthorized()) {
            return redirect()->route('pro.login');
        }

        $data = $response->json();

        return view('professionnel.dashboard.expert', [
            'annee'           => $data['annee'] ?? date('Y'),
            'impact'          => $data['impact_ecologique'] ?? [],
            'stats_materiaux' => $data['stats_materiaux'] ?? [],
            'badges'          => $data['badges'] ?? [],
        ]);
    }

    public function exportPdf(Request $request)
    {
        $response = Http::withToken($this->token())
            ->get($this->apiUrl() . '/api/v1/pro/dashboard/export-pdf');

        if ($response->forbidden()) {
            return redirect()->route('pro.dashboard.expert')
                ->with('error', 'Export PDF disponible uniquement pour les abonnés Expert Pro.');
        }

        return response($response->body(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport-annuel-upcycleconnect.pdf"',
        ]);
    }
}
