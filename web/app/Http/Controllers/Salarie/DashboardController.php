<?php

namespace App\Http\Controllers\Salarie;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

/**
 * Tableau de bord de l'espace salarié et déconnexion.
 */
class DashboardController extends Controller
{
    /** Affiche le tableau de bord avec les statistiques (valeurs par défaut si l'API échoue). */
    public function index()
    {
        $token = session('salarie_token');
        $api = config('services.api.url');
        // Valeurs par défaut : garantissent un affichage cohérent si l'API est indisponible.
        $stats = ['evenements_attente' => 0, 'evenements_valides' => 0, 'articles_brouillon' => 0, 'articles_publies' => 0, 'signalements' => 0];

        try {
            $r = Http::withToken($token)->timeout(5)->get($api . '/api/v1/salarie/stats');
            if ($r->successful()) $stats = array_merge($stats, $r->json());
        } catch (\Exception $e) {}

        return view('salarie.dashboard', compact('stats'));
    }

    /** Détruit la session salarié et redirige vers la page de connexion. */
    public function logout()
    {
        session()->forget(['salarie_token', 'salarie_role', 'salarie_id']);
        return redirect('/login');
    }
}
