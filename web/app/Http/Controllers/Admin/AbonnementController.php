<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

/**
 * Consultation des abonnements côté administration.
 * Sert de proxy vers l'API Go et transmet les données à la vue.
 */
class AbonnementController extends Controller
{
    /**
     * Liste des abonnements récupérée depuis l'API.
     */
    public function index()
    {
        $resp = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . '/api/v1/admin/abonnements');

        $abonnements = $resp->successful() ? $resp->json() : [];

        return view('admin.abonnements.index', compact('abonnements'));
    }
}
