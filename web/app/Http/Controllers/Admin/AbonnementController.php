<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class AbonnementController extends Controller
{
    public function index()
    {
        $resp = Http::withToken(session('admin_token'))
            ->get(config('services.api.url') . '/api/v1/admin/abonnements');

        $abonnements = $resp->successful() ? $resp->json() : [];

        return view('admin.abonnements.index', compact('abonnements'));
    }
}
